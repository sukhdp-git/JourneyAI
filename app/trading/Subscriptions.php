<?php
declare(strict_types=1);

namespace App\Trading;

use App\Core\Database;
use App\Core\Logger;
use App\Core\Mailer;

/**
 * Autopay: recurring plan payments through Razorpay Subscriptions or Stripe Billing.
 * Every successful charge becomes a normal row in `payments` and goes through Payments::markPaid(), so it
 * extends access, pays affiliates and sends the receipt exactly like a one-time payment. Charges are only
 * recorded after a signature check (checkout handler, webhooks) or a server-to-server lookup.
 * A charge is keyed by the gateway's payment / invoice id, so webhooks that arrive twice are harmless.
 */
final class Subscriptions
{
    public const LIVE = ['active', 'authenticated', 'pending'];

    public static function available(): bool
    {
        return setting('autopay_enabled', '1') === '1' && Payments::gateway() !== 'none';
    }

    /** Razorpay needs an interval of at least 7 days for daily plans. */
    public static function supports(array $plan): bool
    {
        return (int) $plan['interval_days'] >= 7;
    }

    /** The member's current autopay, if any (newest first). */
    public static function current(int $uid): ?array
    {
        return Database::one("SELECT s.*, p.name AS plan_name FROM subscriptions s LEFT JOIN plans p ON p.id = s.plan_id
            WHERE s.user_id = :u AND s.status IN ('active','authenticated','pending','halted') ORDER BY s.id DESC LIMIT 1", ['u' => $uid]);
    }

    public static function renewing(?array $s): bool
    {
        return $s && in_array($s['status'], self::LIVE, true) && !(int) $s['cancel_at_period_end'];
    }

    /** [unit, count] for the gateway's billing interval. */
    private static function interval(int $days): array
    {
        return match (true) {
            $days % 365 === 0 => ['year', intdiv($days, 365)],
            $days % 30 === 0 => ['month', intdiv($days, 30)],
            $days % 7 === 0 => ['week', intdiv($days, 7)],
            default => ['day', $days],
        };
    }

    // ------------------------------------------------------------------ start

    public static function start(array $m, array $plan): array
    {
        $gateway = Payments::gateway();
        if (!self::available() || !self::supports($plan)) {
            return ['ok' => false, 'error' => 'Autopay is not available for this plan. Choose "Pay once" instead.'];
        }
        $sid = Database::insert('subscriptions', ['user_id' => $m['id'], 'plan_id' => $plan['id'], 'gateway' => $gateway, 'status' => 'created',
            'amount' => $plan['price'], 'currency' => $plan['currency'], 'period_days' => $plan['interval_days']]);
        return $gateway === 'razorpay' ? self::startRazorpay($sid, $m, $plan) : self::startStripe($sid, $m, $plan);
    }

    private static function failed(int $sid, string $what, string $detail): array
    {
        Database::update('subscriptions', ['status' => 'failed'], 'id = :id', ['id' => $sid]);
        Logger::error('Autopay start failed', ['what' => $what, 'detail' => mb_substr($detail, 0, 200)]);
        return ['ok' => false, 'error' => 'The payment provider could not start autopay. Please try again, or choose "Pay once".'];
    }

    /** Razorpay plans are created once per price / interval and remembered on the plan row. */
    private static function razorpayPlan(array $plan): ?string
    {
        [$unit, $count] = self::interval((int) $plan['interval_days']);
        [$period, $interval] = match ($unit) { 'year' => ['yearly', $count], 'month' => ['monthly', $count], 'week' => ['weekly', $count], default => ['daily', $count] };
        $sig = implode(':', [$period, $interval, Payments::minor((float) $plan['price'], $plan['currency']), $plan['currency'], substr(setting('razorpay_key_id'), 0, 9)]);
        $ref = (string) ($plan['razorpay_plan_ref'] ?? '');
        if ($ref !== '' && str_starts_with($ref, $sig . '|')) {
            return substr($ref, strlen($sig) + 1);
        }
        [$st, $j, $err] = Payments::http('POST', 'https://api.razorpay.com/v1/plans', [
            'period' => $period, 'interval' => $interval,
            'item' => ['name' => setting('site_name', 'journzey.ai') . ' — ' . $plan['name'], 'amount' => Payments::minor((float) $plan['price'], $plan['currency']), 'currency' => $plan['currency']],
            'notes' => ['plan' => $plan['slug']],
        ], 'razorpay');
        if ($err || $st !== 200 || empty($j['id'])) {
            Logger::error('Razorpay plan create failed', ['detail' => $err ?? ('HTTP ' . $st . ' ' . ($j['error']['code'] ?? ''))]);
            return null;
        }
        Database::update('plans', ['razorpay_plan_ref' => $sig . '|' . $j['id']], 'id = :id', ['id' => $plan['id']]);
        return (string) $j['id'];
    }

    private static function startRazorpay(int $sid, array $m, array $plan): array
    {
        $rp = self::razorpayPlan($plan) ?? null;
        if (!$rp) {
            return self::failed($sid, 'razorpay plan', 'plan');
        }
        [$unit, $count] = self::interval((int) $plan['interval_days']);
        $cycles = max(1, (int) floor(match ($unit) { 'year' => 10, 'month' => 120, 'week' => 520, default => 3650 } / $count));
        [$st, $j, $err] = Payments::http('POST', 'https://api.razorpay.com/v1/subscriptions', [
            'plan_id' => $rp, 'total_count' => min($cycles, 120), 'customer_notify' => 1, 'notes' => ['jz_sub' => (string) $sid, 'plan' => $plan['slug']],
        ], 'razorpay');
        if ($err || $st !== 200 || empty($j['id'])) {
            return self::failed($sid, 'razorpay subscription', $err ?? ('HTTP ' . $st . ' ' . ($j['error']['code'] ?? '')));
        }
        Database::update('subscriptions', ['gateway_sub_id' => $j['id']], 'id = :id', ['id' => $sid]);
        return ['ok' => true, 'gateway' => 'razorpay', 'subscription_id' => $j['id'], 'key_id' => setting('razorpay_key_id')];
    }

    private static function startStripe(int $sid, array $m, array $plan): array
    {
        [$unit, $count] = self::interval((int) $plan['interval_days']);
        $fields = [
            'mode' => 'subscription',
            'success_url' => url('/checkout/stripe/success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => url('/checkout/' . $plan['slug']) . '?cancelled=1',
            'client_reference_id' => 'sub_' . $sid,
            'customer_email' => $m['email'],
            'metadata[jz_sub]' => (string) $sid,
            'subscription_data[metadata][jz_sub]' => (string) $sid,
            'line_items[0][quantity]' => '1',
            'line_items[0][price_data][currency]' => strtolower($plan['currency']),
            'line_items[0][price_data][unit_amount]' => (string) Payments::minor((float) $plan['price'], $plan['currency']),
            'line_items[0][price_data][recurring][interval]' => $unit,
            'line_items[0][price_data][recurring][interval_count]' => (string) $count,
            'line_items[0][price_data][product_data][name]' => setting('site_name', 'journzey.ai') . ' — ' . $plan['name'] . ' (autopay)',
        ];
        [$st, $j, $err] = Payments::http('POST', 'https://api.stripe.com/v1/checkout/sessions', $fields, 'stripe');
        if ($err || $st !== 200 || empty($j['id']) || empty($j['url'])) {
            return self::failed($sid, 'stripe session', $err ?? ('HTTP ' . $st . ' ' . ($j['error']['code'] ?? $j['error']['type'] ?? '')));
        }
        Database::update('subscriptions', ['checkout_ref' => $j['id']], 'id = :id', ['id' => $sid]);
        return ['ok' => true, 'gateway' => 'stripe', 'redirect' => $j['url']];
    }

    // ------------------------------------------------------------------ charges

    /** Records one successful autopay charge (idempotent per gateway charge id) and extends access. */
    public static function recordCharge(array $s, string $chargeId, float $amount, string $currency): void
    {
        if ($chargeId === '') {
            return;
        }
        $email = (string) Database::value('SELECT email FROM users WHERE id = :u', ['u' => $s['user_id']]);
        try {
            $pid = Database::insert('payments', [
                'user_id' => $s['user_id'], 'plan_id' => $s['plan_id'], 'subscription_id' => $s['id'], 'gateway' => $s['gateway'],
                'gateway_order_id' => mb_substr($chargeId, 0, 120), 'amount' => $amount, 'currency' => strtoupper($currency), 'status' => 'created',
                'period_days' => $s['period_days'], 'customer_email' => $email, 'note' => 'Autopay',
            ]);
        } catch (\PDOException $e) {
            if (($e->errorInfo[1] ?? 0) === 1062) {
                return; // this charge was already recorded
            }
            throw $e;
        }
        Payments::markPaid(Database::one('SELECT * FROM payments WHERE id = :id', ['id' => $pid]), $chargeId);
        Database::update('subscriptions', ['status' => 'active', 'last_charge_at' => gmdate('Y-m-d H:i:s'), 'failed_notice_at' => null], 'id = :id', ['id' => $s['id']]);
    }

    private static function setStatus(array $s, string $status, ?bool $cancelAtEnd = null): void
    {
        $f = ['status' => mb_substr($status, 0, 20)];
        if ($cancelAtEnd !== null) {
            $f['cancel_at_period_end'] = $cancelAtEnd ? 1 : 0;
        }
        Database::update('subscriptions', $f, 'id = :id', ['id' => $s['id']]);
    }

    private static function notifyFailed(array $s): void
    {
        if ($s['failed_notice_at'] && strtotime($s['failed_notice_at'] . ' UTC') > time() - 86400) {
            return; // at most one email a day
        }
        Database::update('subscriptions', ['failed_notice_at' => gmdate('Y-m-d H:i:s')], 'id = :id', ['id' => $s['id']]);
        $u = Database::one('SELECT u.name, u.email, p.name AS plan FROM users u LEFT JOIN plans p ON p.id = :p WHERE u.id = :u', ['u' => $s['user_id'], 'p' => $s['plan_id']]);
        if ($u) {
            Mailer::sendTemplate('autopay_failed', $u['email'], ['name' => $u['name'], 'plan' => $u['plan'] ?? 'Plan', 'amount' => money($s['amount'], $s['currency']), 'billing_url' => url('/terminal/billing')],
                ['related_type' => 'subscription', 'related_id' => (int) $s['id']]);
        }
    }

    // ------------------------------------------------------------------ Razorpay

    /** Checkout handler signature for subscriptions: HMAC-SHA256(payment_id|subscription_id, key_secret). */
    public static function razorpayVerify(int $uid, string $subId, string $paymentId, string $signature): bool
    {
        $s = Database::one("SELECT * FROM subscriptions WHERE gateway = 'razorpay' AND gateway_sub_id = :g AND user_id = :u", ['g' => $subId, 'u' => $uid]);
        if (!$s || $paymentId === '' || !hash_equals(hash_hmac('sha256', $paymentId . '|' . $subId, secret_setting('razorpay_key_secret')), $signature)) {
            return false;
        }
        self::recordCharge($s, $paymentId, (float) $s['amount'], $s['currency']);
        return true;
    }

    /** subscription.* webhook events (the signature was already checked by Payments::razorpayWebhook). */
    public static function razorpayEvent(string $event, array $j): void
    {
        $ent = $j['payload']['subscription']['entity'] ?? [];
        $s = Database::one("SELECT * FROM subscriptions WHERE gateway = 'razorpay' AND gateway_sub_id = :g", ['g' => (string) ($ent['id'] ?? '')]);
        if (!$s) {
            return;
        }
        $pay = $j['payload']['payment']['entity'] ?? null;
        switch ($event) {
            case 'subscription.charged':
                if ($pay && ($pay['status'] ?? '') === 'captured') {
                    self::recordCharge($s, (string) $pay['id'], Payments::major((int) $pay['amount'], (string) $pay['currency']), (string) $pay['currency']);
                }
                break;
            case 'subscription.authenticated':
            case 'subscription.activated':
            case 'subscription.resumed':
                self::setStatus($s, $event === 'subscription.authenticated' ? 'authenticated' : 'active');
                break;
            case 'subscription.pending':
            case 'subscription.halted':
                self::setStatus($s, substr($event, 13));
                self::notifyFailed($s);
                break;
            case 'subscription.cancelled':
            case 'subscription.completed':
            case 'subscription.paused':
                self::setStatus($s, substr($event, 13));
                break;
        }
    }

    // ------------------------------------------------------------------ Stripe

    /** Local subscription for a Stripe object: by subscription id, then by our metadata. */
    private static function stripeSub(string $subId, array $meta): ?array
    {
        $s = $subId !== '' ? Database::one("SELECT * FROM subscriptions WHERE gateway = 'stripe' AND gateway_sub_id = :g", ['g' => $subId]) : null;
        if (!$s && ctype_digit((string) ($meta['jz_sub'] ?? ''))) {
            $s = Database::one("SELECT * FROM subscriptions WHERE gateway = 'stripe' AND id = :id", ['id' => (int) $meta['jz_sub']]);
            if ($s && $subId !== '' && !$s['gateway_sub_id']) {
                Database::update('subscriptions', ['gateway_sub_id' => $subId], 'id = :id', ['id' => $s['id']]);
                $s['gateway_sub_id'] = $subId;
            }
        }
        return $s;
    }

    /** Stripe moved the subscription link of an invoice to parent.subscription_details in newer API versions. */
    private static function invoiceSub(array $inv): array
    {
        $d = $inv['parent']['subscription_details'] ?? $inv['subscription_details'] ?? [];
        $sub = $inv['subscription'] ?? ($d['subscription'] ?? '');
        return [is_array($sub) ? (string) ($sub['id'] ?? '') : (string) $sub, (array) ($d['metadata'] ?? [])];
    }

    public static function stripeInvoicePaid(array $inv): void
    {
        [$subId, $meta] = self::invoiceSub($inv);
        $s = self::stripeSub($subId, $meta);
        if ($s && ($inv['status'] ?? '') === 'paid' && (int) ($inv['amount_paid'] ?? 0) > 0) {
            self::recordCharge($s, (string) $inv['id'], Payments::major((int) $inv['amount_paid'], (string) $inv['currency']), (string) $inv['currency']);
        }
    }

    public static function stripeEvent(string $type, array $obj): bool
    {
        switch ($type) {
            case 'checkout.session.completed':
                if (($obj['mode'] ?? '') !== 'subscription') {
                    return false;
                }
                self::stripeSub((string) ($obj['subscription'] ?? ''), (array) ($obj['metadata'] ?? []));
                return true;
            case 'invoice.paid':
            case 'invoice.payment_succeeded':
                self::stripeInvoicePaid($obj);
                return true;
            case 'invoice.payment_failed':
                [$subId, $meta] = self::invoiceSub($obj);
                if ($s = self::stripeSub($subId, $meta)) {
                    self::setStatus($s, 'pending');
                    self::notifyFailed($s);
                }
                return true;
            case 'customer.subscription.updated':
            case 'customer.subscription.deleted':
                if ($s = self::stripeSub((string) ($obj['id'] ?? ''), (array) ($obj['metadata'] ?? []))) {
                    $st = (string) ($obj['status'] ?? '');
                    $map = ['active' => 'active', 'trialing' => 'active', 'past_due' => 'pending', 'unpaid' => 'halted', 'canceled' => 'cancelled', 'incomplete_expired' => 'cancelled', 'incomplete' => 'created', 'paused' => 'paused'];
                    self::setStatus($s, $type === 'customer.subscription.deleted' ? 'cancelled' : ($map[$st] ?? $st), (bool) ($obj['cancel_at_period_end'] ?? false));
                }
                return true;
        }
        return false;
    }

    /** Success page: confirm the first charge server-side so access starts before the webhook arrives. */
    public static function stripeConfirm(string $sessionId): ?bool
    {
        $s = Database::one("SELECT * FROM subscriptions WHERE gateway = 'stripe' AND checkout_ref = :c", ['c' => $sessionId]);
        if (!$s) {
            return null;
        }
        [$st, $j] = Payments::http('GET', 'https://api.stripe.com/v1/checkout/sessions/' . rawurlencode($sessionId) . '?expand[]=invoice', [], 'stripe');
        if ($st !== 200 || (string) ($j['client_reference_id'] ?? '') !== 'sub_' . $s['id']) {
            return false;
        }
        $s = self::stripeSub((string) ($j['subscription'] ?? ''), ['jz_sub' => (string) $s['id']]) ?? $s;
        $inv = $j['invoice'] ?? null;
        if (is_string($inv) && $inv !== '') {
            [, $inv] = Payments::http('GET', 'https://api.stripe.com/v1/invoices/' . rawurlencode($inv), [], 'stripe');
        }
        if (is_array($inv) && ($inv['status'] ?? '') === 'paid') {
            self::recordCharge($s, (string) $inv['id'], Payments::major((int) $inv['amount_paid'], (string) $inv['currency']), (string) $inv['currency']);
            return true;
        }
        return ($j['payment_status'] ?? '') === 'paid';
    }

    // ------------------------------------------------------------------ cancel

    /** Turns autopay off at the end of the paid period. Access already paid for is kept. */
    public static function cancel(int $uid): array
    {
        $s = self::current($uid);
        if (!$s || !self::renewing($s)) {
            return ['ok' => false, 'error' => 'Autopay is already off.'];
        }
        if ($s['gateway_sub_id']) {
            if ($s['gateway'] === 'razorpay') {
                [$st, $j, $err] = Payments::http('POST', 'https://api.razorpay.com/v1/subscriptions/' . rawurlencode($s['gateway_sub_id']) . '/cancel', ['cancel_at_cycle_end' => in_array($s['status'], ['active'], true) ? 1 : 0], 'razorpay');
            } else {
                [$st, $j, $err] = Payments::http('POST', 'https://api.stripe.com/v1/subscriptions/' . rawurlencode($s['gateway_sub_id']), ['cancel_at_period_end' => 'true'], 'stripe');
            }
            if ($err || $st !== 200) {
                Logger::error('Autopay cancel failed', ['sub' => $s['id'], 'detail' => $err ?? ('HTTP ' . $st)]);
                return ['ok' => false, 'error' => 'The payment provider did not confirm the change. Please try again in a moment.'];
            }
        }
        self::setStatus($s, $s['gateway'] === 'razorpay' && $s['status'] !== 'active' ? 'cancelled' : $s['status'], true);
        Members::audit($uid, 'autopay_off', 'Subscription #' . $s['id']);
        return ['ok' => true];
    }
}
