<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Trading\Members;
use App\Trading\Payments;
use App\Trading\Subscriptions;

/** Public pricing, checkout (Razorpay / Stripe) and the member's Plan & Billing page. */
final class BillingController extends Controller
{
    public function pricing(Request $req): never
    {
        $this->render('billing/pricing', [], [
            'title' => setting('seo_pricing_title') ?: 'Pricing', 'description' => setting('seo_pricing_description'), 'path' => '/pricing',
        ]);
    }

    private function member(Request $req): array
    {
        $m = Members::current();
        if (!$m) {
            Response::redirect('/signup?plan=' . rawurlencode((string) ($req->params['plan'] ?? '')));
        }
        return $m;
    }

    public function checkout(Request $req): never
    {
        $m = $this->member($req);
        $plan = Payments::plan((string) $req->params['plan']) ?? Response::redirect('/pricing');
        $this->render('billing/checkout', [
            'coupon' => (string) ($_COOKIE[\App\Trading\Affiliates::COOKIE] ?? ''), 'referred' => (bool) \App\Core\Database::value('SELECT referred_by_affiliate_id FROM users WHERE id = :u', ['u' => $m['id']]),
            'plan' => $plan, 'm' => $m, 'gateway' => Payments::gateway(), 'cancelled' => $req->query('cancelled') === '1',
            'extends' => $m['ent']['paid'] && (int) $m['ent']['plan']['id'] === (int) $plan['id'],
            'autopay' => Subscriptions::available() && Subscriptions::supports($plan), 'autopayOn' => Subscriptions::renewing(Subscriptions::current((int) $m['id'])),
        ], ['title' => 'Checkout — ' . $plan['name'], 'path' => '/checkout/' . $plan['slug'], 'noindex' => true]);
    }

    /** Links the member to an affiliate code without starting a payment (used when payments are activated manually). */
    public function applyCoupon(Request $req): never
    {
        Csrf::verify($req);
        $m = $this->member($req);
        $plan = Payments::plan((string) $req->params['plan']) ?? Response::redirect('/pricing');
        if (!RateLimiter::hit('coupon:' . $m['id'], 10, 3600)) {
            Session::flash('error', 'Too many attempts. Please wait a while and try again.');
        } elseif (trim((string) $req->post('coupon', '')) === '') {
            Session::flash('error', 'Enter a coupon code.');
        } elseif (($err = \App\Trading\Affiliates::attach((int) $m['id'], (string) $req->post('coupon', ''))) !== null) {
            Session::flash('error', $err);
        } else {
            Session::flash('success', 'Code applied.');
        }
        Response::redirect('/checkout/' . $plan['slug']);
    }

    public function startPayment(Request $req): never
    {
        Csrf::verify($req);
        $m = $this->member($req);
        $plan = Payments::plan((string) $req->params['plan']) ?? Response::redirect('/pricing');
        $fail = function (string $msg) use ($req, $plan): never {
            if ($req->isAjax()) {
                Response::json(['ok' => false, 'error' => $msg], 422);
            }
            Session::flash('error', $msg);
            Response::redirect('/checkout/' . $plan['slug']);
        };
        if ($req->post('terms') !== '1') {
            $fail('Please accept the terms to continue.');
        }
        if (!RateLimiter::hit('checkout:' . $m['id'], 10, 3600)) {
            $fail('Too many checkout attempts. Please wait a while and try again.');
        }
        if (($couponErr = \App\Trading\Affiliates::attach((int) $m['id'], (string) $req->post('coupon', ''))) !== null) {
            $fail($couponErr);
        }
        $auto = $req->post('autopay') === '1';
        if ($auto && Subscriptions::renewing(Subscriptions::current((int) $m['id']))) {
            $fail('Autopay is already on for your account. Turn it off under Plan & Billing before starting a new one.');
        }
        $r = $auto ? Subscriptions::start($m, $plan) : Payments::start($m, $plan);
        if (!$r['ok']) {
            $fail($r['error']);
        }
        Members::audit((int) $m['id'], 'checkout_started', $plan['slug'] . ' via ' . $r['gateway'] . ($auto ? ' (autopay)' : ''));
        if ($r['gateway'] === 'stripe') {
            if ($req->isAjax()) {
                Response::json(['ok' => true, 'redirect' => $r['redirect']]);
            }
            header('Location: ' . $r['redirect'], true, 303);
            exit;
        }
        Response::json(['ok' => true] + array_intersect_key($r, array_flip(['order_id', 'subscription_id', 'amount', 'currency', 'key_id'])));
    }

    public function razorpayVerify(Request $req): never
    {
        Csrf::verify($req);
        $m = Members::current() ?? Response::redirect('/login');
        $ok = Payments::razorpayVerify((int) $m['id'], (string) $req->post('razorpay_order_id'), (string) $req->post('razorpay_payment_id'), (string) $req->post('razorpay_signature'));
        if (!$ok) {
            Session::flash('error', 'We could not verify this payment. If you were charged, it will be confirmed automatically by the payment provider shortly — or contact us with your payment reference.');
            Response::redirect('/terminal/billing');
        }
        Members::refresh();
        Session::flash('success', 'Payment received — your plan is active. Live accounts are now unlocked.');
        Response::redirect('/terminal/billing');
    }

    /** Razorpay Checkout handler for autopay (subscription) payments. */
    public function razorpaySubscriptionVerify(Request $req): never
    {
        Csrf::verify($req);
        $m = Members::current() ?? Response::redirect('/login');
        $ok = Subscriptions::razorpayVerify((int) $m['id'], (string) $req->post('razorpay_subscription_id'), (string) $req->post('razorpay_payment_id'), (string) $req->post('razorpay_signature'));
        if (!$ok) {
            Session::flash('error', 'We could not verify this payment. If you were charged, it will be confirmed automatically by the payment provider shortly — or contact us with your payment reference.');
            Response::redirect('/terminal/billing');
        }
        Members::refresh();
        Session::flash('success', 'Payment received — autopay is on and your plan renews automatically. You can turn autopay off any time on this page.');
        Response::redirect('/terminal/billing');
    }

    public function cancelAutopay(Request $req): never
    {
        Csrf::verify($req);
        $m = Members::current() ?? Response::redirect('/login');
        $r = Subscriptions::cancel((int) $m['id']);
        Session::flash($r['ok'] ? 'success' : 'error', $r['ok'] ? 'Autopay is off. You will not be charged again; your access stays active until the end of the period you paid for.' : $r['error']);
        Response::redirect('/terminal/billing');
    }

    public function stripeSuccess(Request $req): never
    {
        $sid = (string) $req->query('session_id', '');
        if (preg_match('/^cs_[A-Za-z0-9_]{10,200}$/', $sid) && ($sub = Subscriptions::stripeConfirm($sid)) !== null) {
            Session::flash($sub ? 'success' : 'info', $sub ? 'Payment received — autopay is on and your plan renews automatically.' : 'Your payment is being confirmed by Stripe. This page will show your plan as soon as it is confirmed.');
            Response::redirect('/terminal/billing');
        }
        $p = Payments::stripeConfirm($sid);
        if ($p && $p['status'] === 'paid') {
            Session::flash('success', 'Payment received — your plan is active. Live accounts are now unlocked.');
        } else {
            Session::flash('info', 'Your payment is being confirmed by Stripe. This page will show your plan as soon as it is confirmed.');
        }
        Response::redirect('/terminal/billing');
    }

    /** /app/billing — rendered inside the terminal shell. */
    public function billing(Request $req): never
    {
        (new Terminal\BillingPage())->show();
    }
}
