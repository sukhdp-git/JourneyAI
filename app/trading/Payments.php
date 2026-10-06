<?php
declare(strict_types=1);

namespace App\Trading;

use App\Core\Database;
use App\Core\Logger;
use App\Core\Mailer;

/**
 * Plan purchases through Razorpay (Orders API + Checkout) or Stripe (Checkout Sessions).
 * Each paid purchase extends plan access by the plan's period. Payments are only marked paid after a
 * server-side signature check or a server-to-server lookup — never because the browser says so.
 * Keys are read from the encrypted Control Panel settings and never sent to the browser (except the
 * Razorpay key id, which is public by design).
 */
final class Payments
{
    private const ZERO_DECIMAL = ['JPY', 'KRW', 'VND', 'CLP'];

    public static function gateway(): string
    {
        $g = setting('payment_gateway', 'none');
        return match ($g) {
            'razorpay' => setting('razorpay_key_id') !== '' && secret_setting('razorpay_key_secret') !== '' ? 'razorpay' : 'none',
            'stripe' => secret_setting('stripe_secret_key') !== '' ? 'stripe' : 'none',
            default => 'none',
        };
    }

    public static function minor(float $amount, string $currency): int
    {
        return in_array($currency, self::ZERO_DECIMAL, true) ? (int) round($amount) : (int) round($amount * 100);
    }

    public static function plans(): array
    {
        return Database::all('SELECT * FROM plans WHERE is_active = 1 ORDER BY sort_order, price');
    }

    public static function plan(string $slug): ?array
    {
        return Database::one('SELECT * FROM plans WHERE slug = :s AND is_active = 1', ['s' => $slug]);
    }

    /** Creates the local payment row and the gateway order/session. */
    public static function start(array $m, array $plan): array
    {
        $gateway = self::gateway();
        if ($gateway === 'none') {
            return ['ok' => false, 'error' => 'Online payments are not configured yet. Please contact us to upgrade.'];
        }
        $pid = Database::insert('payments', [
            'user_id' => $m['id'], 'plan_id' => $plan['id'], 'gateway' => $gateway, 'amount' => $plan['price'], 'currency' => $plan['currency'],
            'status' => 'created', 'period_days' => $plan['interval_days'], 'customer_email' => $m['email'],
        ]);
        if ($gateway === 'razorpay') {
            [$st, $j, $err] = self::http('POST', 'https://api.razorpay.com/v1/orders', [
                'amount' => self::minor((float) $plan['price'], $plan['currency']), 'currency' => $plan['currency'], 'receipt' => 'jz_' . $pid,
                'notes' => ['payment_id' => (string) $pid, 'plan' => $plan['slug']],
            ], 'razorpay');
            if ($err || $st !== 200 || empty($j['id'])) {
                return self::startFailed($pid, 'razorpay order', $err ?? ('HTTP ' . $st . ' ' . ($j['error']['code'] ?? '')));
            }
            Database::update('payments', ['gateway_order_id' => $j['id']], 'id = :id', ['id' => $pid]);
            return ['ok' => true, 'gateway' => 'razorpay', 'order_id' => $j['id'], 'amount' => $j['amount'], 'currency' => $j['currency'], 'key_id' => setting('razorpay_key_id'), 'payment_id' => $pid];
        }
        $fields = [
            'mode' => 'payment',
            'success_url' => url('/checkout/stripe/success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => url('/checkout/' . $plan['slug']) . '?cancelled=1',
            'client_reference_id' => (string) $pid,
            'customer_email' => $m['email'],
            'metadata[payment_id]' => (string) $pid,
            'line_items[0][quantity]' => '1',
            'line_items[0][price_data][currency]' => strtolower($plan['currency']),
            'line_items[0][price_data][unit_amount]' => (string) self::minor((float) $plan['price'], $plan['currency']),
            'line_items[0][price_data][product_data][name]' => setting('site_name', 'journzey.ai') . ' — ' . $plan['name'] . ' (' . $plan['interval_days'] . ' days)',
        ];
        [$st, $j, $err] = self::http('POST', 'https://api.stripe.com/v1/checkout/sessions', $fields, 'stripe');
        if ($err || $st !== 200 || empty($j['id']) || empty($j['url'])) {
            return self::startFailed($pid, 'stripe session', $err ?? ('HTTP ' . $st . ' ' . ($j['error']['code'] ?? $j['error']['type'] ?? '')));
        }
        Database::update('payments', ['gateway_order_id' => $j['id']], 'id = :id', ['id' => $pid]);
        return ['ok' => true, 'gateway' => 'stripe', 'redirect' => $j['url'], 'payment_id' => $pid];
    }

    private static function startFailed(int $pid, string $what, string $detail): array
    {
        Database::update('payments', ['status' => 'failed', 'note' => 'Could not create ' . $what], 'id = :id', ['id' => $pid]);
        Logger::error('Payment start failed', ['what' => $what, 'detail' => mb_substr($detail, 0, 200)]);
        return ['ok' => false, 'error' => 'The payment provider could not start checkout. Please try again in a moment.'];
    }

    /** Razorpay Checkout handler signature: HMAC-SHA256(order_id|payment_id, key_secret). */
    public static function razorpayVerify(int $uid, string $orderId, string $paymentId, string $signature): bool
    {
        $p = Database::one("SELECT * FROM payments WHERE gateway = 'razorpay' AND gateway_order_id = :o AND user_id = :u", ['o' => $orderId, 'u' => $uid]);
        if (!$p) {
            return false;
        }
        $expected = hash_hmac('sha256', $orderId . '|' . $paymentId, secret_setting('razorpay_key_secret'));
        if (!hash_equals($expected, $signature)) {
            return false;
        }
        self::markPaid($p, $paymentId);
        return true;
    }

    /** Looks the Checkout Session up server-side and activates the plan if Stripe reports it paid. */
    public static function stripeConfirm(string $sessionId): ?array
    {
        if (!preg_match('/^cs_[A-Za-z0-9_]{10,200}$/', $sessionId)) {
            return null;
        }
        $p = Database::one("SELECT * FROM payments WHERE gateway = 'stripe' AND gateway_order_id = :o", ['o' => $sessionId]);
        if (!$p) {
            return null;
        }
        if ($p['status'] === 'paid') {
            return $p;
        }
        [$st, $j] = self::http('GET', 'https://api.stripe.com/v1/checkout/sessions/' . rawurlencode($sessionId), [], 'stripe');
        if ($st === 200 && ($j['payment_status'] ?? '') === 'paid' && (string) ($j['client_reference_id'] ?? '') === (string) $p['id']) {
            self::markPaid($p, (string) ($j['payment_intent'] ?? $sessionId));
            return Database::one('SELECT * FROM payments WHERE id = :id', ['id' => $p['id']]);
        }
        return $p;
    }

    /** Idempotent: only the first transition created → paid extends access. */
    public static function markPaid(array $p, string $gatewayPaymentId): void
    {
        $done = Database::transaction(function () use ($p, $gatewayPaymentId) {
            $n = Database::query("UPDATE payments SET status = 'paid', paid_at = UTC_TIMESTAMP(), gateway_payment_id = :g WHERE id = :id AND status IN ('created', 'failed')",
                ['g' => mb_substr($gatewayPaymentId, 0, 120), 'id' => $p['id']])->rowCount();
            if ($n !== 1 || !$p['user_id']) {
                return null;
            }
            return self::extend((int) $p['user_id'], (int) $p['plan_id'], (int) $p['period_days'], (int) $p['id']);
        });
        if ($done) {
            self::notify((int) $p['id']);
        }
    }

    /** Extends (or starts) the member's plan; same plan stacks on remaining time. Returns the new expiry. */
    public static function extend(int $uid, int $planId, int $days, ?int $paymentId = null): ?string
    {
        $u = Database::one('SELECT plan_id, plan_expires_at FROM users WHERE id = :id FOR UPDATE', ['id' => $uid]);
        if (!$u) {
            return null;
        }
        $base = time();
        if ((int) $u['plan_id'] === $planId && $u['plan_expires_at'] && strtotime($u['plan_expires_at'] . ' UTC') > $base) {
            $base = strtotime($u['plan_expires_at'] . ' UTC');
        }
        $until = gmdate('Y-m-d H:i:s', $base + $days * 86400);
        Database::update('users', ['plan_id' => $planId, 'plan_expires_at' => $until], 'id = :id', ['id' => $uid]);
        if ($paymentId) {
            Database::update('payments', ['access_until' => $until], 'id = :id', ['id' => $paymentId]);
        }
        Members::audit($uid, 'plan_extended', 'Plan #' . $planId . ' until ' . $until);
        return $until;
    }

    private static function notify(int $paymentId): void
    {
        $p = Database::one('SELECT p.*, u.name, u.email, pl.name AS plan_name FROM payments p LEFT JOIN users u ON u.id = p.user_id LEFT JOIN plans pl ON pl.id = p.plan_id WHERE p.id = :id', ['id' => $paymentId]);
        if (!$p || !$p['email']) {
            return;
        }
        $vars = ['name' => $p['name'], 'email' => $p['email'], 'plan' => $p['plan_name'] ?? 'Plan', 'amount' => money($p['amount'], $p['currency']),
            'access_until' => fmt_date($p['access_until'], 'M j, Y'), 'reference' => 'JZ-' . $p['id'] . ' / ' . ($p['gateway_payment_id'] ?? '')];
        try {
            Mailer::sendTemplate('payment_receipt', $p['email'], $vars, ['related_type' => 'payment', 'related_id' => $paymentId]);
            $admin = setting('notify_email') ?: setting('contact_email');
            if ($admin !== '') {
                Mailer::sendTemplate('payment_admin', $admin, $vars, ['related_type' => 'payment', 'related_id' => $paymentId]);
            }
        } catch (\Throwable $e) {
            Logger::error('Payment email failed', ['payment' => $paymentId]);
        }
    }

    // ------------------------------------------------------------------ webhooks

    public static function razorpayWebhook(string $raw, string $signature): array
    {
        $secret = secret_setting('razorpay_webhook_secret');
        if ($secret === '' || $signature === '' || !hash_equals(hash_hmac('sha256', $raw, $secret), $signature)) {
            return [400, 'invalid signature'];
        }
        $j = json_decode($raw, true) ?: [];
        $event = (string) ($j['event'] ?? '');
        $pay = $j['payload']['payment']['entity'] ?? null;
        $orderId = (string) ($pay['order_id'] ?? ($j['payload']['order']['entity']['id'] ?? ''));
        $p = $orderId !== '' ? Database::one("SELECT * FROM payments WHERE gateway = 'razorpay' AND gateway_order_id = :o", ['o' => $orderId]) : null;
        if (!$p) {
            return [200, 'ignored'];
        }
        if (in_array($event, ['payment.captured', 'order.paid'], true)) {
            self::markPaid($p, (string) ($pay['id'] ?? $orderId));
        } elseif ($event === 'payment.failed' && $p['status'] === 'created') {
            Database::update('payments', ['status' => 'failed', 'note' => mb_substr((string) ($pay['error_description'] ?? 'Payment failed'), 0, 255)], 'id = :id', ['id' => $p['id']]);
        } elseif ($event === 'refund.processed' || $event === 'payment.refunded') {
            Database::update('payments', ['status' => 'refunded'], 'id = :id', ['id' => $p['id']]);
        }
        return [200, 'ok'];
    }

    public static function stripeWebhook(string $raw, string $header): array
    {
        $secret = secret_setting('stripe_webhook_secret');
        $parts = [];
        foreach (explode(',', $header) as $kv) {
            [$k, $v] = array_pad(explode('=', trim($kv), 2), 2, '');
            $parts[$k][] = $v;
        }
        $t = (int) ($parts['t'][0] ?? 0);
        $ok = false;
        if ($secret !== '' && $t > 0 && abs(time() - $t) <= 300) {
            $expected = hash_hmac('sha256', $t . '.' . $raw, $secret);
            foreach ($parts['v1'] ?? [] as $sig) {
                $ok = $ok || hash_equals($expected, $sig);
            }
        }
        if (!$ok) {
            return [400, 'invalid signature'];
        }
        $j = json_decode($raw, true) ?: [];
        $obj = $j['data']['object'] ?? [];
        $type = (string) ($j['type'] ?? '');
        if (in_array($type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true) && ($obj['payment_status'] ?? '') === 'paid') {
            $p = Database::one("SELECT * FROM payments WHERE gateway = 'stripe' AND gateway_order_id = :o", ['o' => (string) ($obj['id'] ?? '')]);
            if ($p) {
                self::markPaid($p, (string) ($obj['payment_intent'] ?? $obj['id']));
            }
        } elseif ($type === 'checkout.session.async_payment_failed' || $type === 'checkout.session.expired') {
            Database::query("UPDATE payments SET status = 'failed' WHERE gateway = 'stripe' AND gateway_order_id = :o AND status = 'created'", ['o' => (string) ($obj['id'] ?? '')]);
        } elseif ($type === 'charge.refunded') {
            Database::query("UPDATE payments SET status = 'refunded' WHERE gateway = 'stripe' AND gateway_payment_id = :g", ['g' => (string) ($obj['payment_intent'] ?? '')]);
        }
        return [200, 'ok'];
    }

    // ------------------------------------------------------------------ HTTP

    /** @return array{0: int, 1: array, 2: ?string} */
    private static function http(string $method, string $url, array $data, string $provider, ?array $creds = null): array
    {
        if (!function_exists('curl_init')) {
            return [0, [], 'cURL extension is not enabled'];
        }
        $ch = curl_init($url);
        $opts = [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 30, CURLOPT_CUSTOMREQUEST => $method];
        if ($provider === 'razorpay') {
            $opts[CURLOPT_USERPWD] = ($creds[0] ?? setting('razorpay_key_id')) . ':' . ($creds[1] ?? secret_setting('razorpay_key_secret'));
            $opts[CURLOPT_HTTPHEADER] = ['Content-Type: application/json'];
            if ($method === 'POST') {
                $opts[CURLOPT_POSTFIELDS] = json_encode($data);
            }
        } else {
            $opts[CURLOPT_HTTPHEADER] = ['Authorization: Bearer ' . ($creds[0] ?? secret_setting('stripe_secret_key'))];
            if ($method === 'POST') {
                $opts[CURLOPT_POSTFIELDS] = http_build_query($data);
            }
        }
        curl_setopt_array($ch, $opts);
        $raw = curl_exec($ch);
        $st = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = $raw === false ? curl_error($ch) : null;
        curl_close($ch);
        return [$st, is_string($raw) ? (json_decode($raw, true) ?: []) : [], $err];
    }

    /** Control Panel "Test connection": a harmless authenticated read. */
    public static function test(string $gateway, string $keyOrId, string $secret = ''): array
    {
        if ($gateway === 'razorpay') {
            [$st, , $err] = self::http('GET', 'https://api.razorpay.com/v1/orders?count=1', [], 'razorpay', [$keyOrId, $secret]);
        } else {
            [$st, , $err] = self::http('GET', 'https://api.stripe.com/v1/balance', [], 'stripe', [$keyOrId]);
        }
        if ($err) {
            return ['ok' => false, 'message' => 'Could not reach ' . ucfirst($gateway) . '.'];
        }
        return $st === 200 ? ['ok' => true, 'message' => ucfirst($gateway) . ' accepted the keys.'] : ['ok' => false, 'message' => ucfirst($gateway) . ' rejected the keys (HTTP ' . $st . ').'];
    }
}
