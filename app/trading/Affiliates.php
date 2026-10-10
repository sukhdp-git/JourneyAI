<?php
declare(strict_types=1);

namespace App\Trading;

use App\Core\Database;

/**
 * Affiliate programme. Influencers apply from /affiliates with their member account; an admin approves them and
 * they get a personal code and link (/r/CODE). A customer is attributed to an affiliate when they enter that code
 * at checkout (the link pre-fills it). From then on the affiliate earns a commission — 25% by default — on every
 * paid plan payment that customer makes. Commissions start as "pending"; the admin approves and marks them paid.
 */
final class Affiliates
{
    public const DEFAULT_PCT = 25.0;
    public const COOKIE = 'jz_ref';

    public static function defaultPct(): float
    {
        $v = (float) setting('affiliate_commission_pct', (string) self::DEFAULT_PCT);
        return $v > 0 && $v <= 90 ? $v : self::DEFAULT_PCT;
    }

    public static function forUser(int $uid): ?array
    {
        return Database::one('SELECT * FROM affiliates WHERE user_id = :u', ['u' => $uid]);
    }

    public static function normalizeCode(string $code): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code) ?? '');
    }

    /** Approved affiliate for a code (case-insensitive), or null. */
    public static function byCode(string $code): ?array
    {
        $c = self::normalizeCode($code);
        if ($c === '' || strlen($c) > 32) {
            return null;
        }
        return Database::one("SELECT * FROM affiliates WHERE code = :c AND status = 'approved'", ['c' => $c]);
    }

    /** A readable unique code from the affiliate's name, e.g. "ARJUN25" / "ARJUN7". */
    public static function generateCode(string $name): string
    {
        $base = substr(self::normalizeCode(preg_replace('/\s+.*/', '', trim($name)) ?? ''), 0, 10) ?: 'JZ';
        foreach (array_merge(['25'], array_map('strval', range(1, 99))) as $suffix) {
            $c = $base . $suffix;
            if (!Database::value('SELECT id FROM affiliates WHERE code = :c', ['c' => $c])) {
                return $c;
            }
        }
        return $base . strtoupper(bin2hex(random_bytes(3)));
    }

    /**
     * Links the customer to the affiliate behind $code, once. Returns null on success or an error message.
     * The first valid code a customer uses stays attached to them for all later payments.
     */
    public static function attach(int $uid, string $code): ?string
    {
        if (trim($code) === '') {
            return null;
        }
        $a = self::byCode($code);
        if (!$a) {
            return 'That coupon / affiliate code is not valid.';
        }
        if ((int) $a['user_id'] === $uid) {
            return 'You cannot use your own affiliate code.';
        }
        Database::query('UPDATE users SET referred_by_affiliate_id = :a, referred_at = UTC_TIMESTAMP() WHERE id = :u AND referred_by_affiliate_id IS NULL', ['a' => $a['id'], 'u' => $uid]);
        return null;
    }

    /** Creates the commission for a paid payment (idempotent: one commission per payment). */
    public static function onPayment(int $paymentId): void
    {
        $p = Database::one("SELECT p.id, p.user_id, p.amount, p.currency, p.status, u.referred_by_affiliate_id AS aff FROM payments p JOIN users u ON u.id = p.user_id WHERE p.id = :id", ['id' => $paymentId]);
        if (!$p || $p['status'] !== 'paid' || !$p['aff'] || (float) $p['amount'] <= 0) {
            return;
        }
        $a = Database::one("SELECT id, user_id, commission_pct FROM affiliates WHERE id = :id AND status = 'approved'", ['id' => $p['aff']]);
        if (!$a || (int) $a['user_id'] === (int) $p['user_id']) {
            return;
        }
        $rate = (float) ($a['commission_pct'] ?: self::defaultPct());
        Database::query('INSERT IGNORE INTO affiliate_commissions (affiliate_id, user_id, payment_id, payment_amount, rate, commission, currency, status)
            VALUES (:a, :u, :p, :amt, :r, :c, :cur, \'pending\')', [
            'a' => $a['id'], 'u' => $p['user_id'], 'p' => $p['id'], 'amt' => $p['amount'], 'r' => $rate,
            'c' => round((float) $p['amount'] * $rate / 100, 2), 'cur' => $p['currency'],
        ]);
    }

    /** A refunded payment cancels its commission unless it was already paid out (the admin settles that by hand). */
    public static function voidForPayment(int $paymentId): void
    {
        Database::query("UPDATE affiliate_commissions SET status = 'void' WHERE payment_id = :p AND status IN ('pending','approved')", ['p' => $paymentId]);
    }

    public static function trackClick(string $code): ?array
    {
        $a = self::byCode($code);
        if ($a) {
            Database::query('UPDATE affiliates SET clicks = clicks + 1 WHERE id = :id', ['id' => $a['id']]);
        }
        return $a;
    }

    /** Dashboard figures for one affiliate. */
    public static function stats(int $affiliateId): array
    {
        $referred = (int) Database::value('SELECT COUNT(*) FROM users WHERE referred_by_affiliate_id = :a', ['a' => $affiliateId]);
        $paying = (int) Database::value('SELECT COUNT(DISTINCT user_id) FROM affiliate_commissions WHERE affiliate_id = :a AND status <> \'void\'', ['a' => $affiliateId]);
        $totals = [];
        foreach (Database::all('SELECT currency, status, SUM(commission) s, SUM(payment_amount) v, COUNT(*) n FROM affiliate_commissions WHERE affiliate_id = :a GROUP BY currency, status', ['a' => $affiliateId]) as $r) {
            $totals[$r['currency']][$r['status']] = ['sum' => (float) $r['s'], 'sales' => (float) $r['v'], 'n' => (int) $r['n']];
        }
        $rows = Database::all('SELECT c.*, u.email, pl.name AS plan_name, p.paid_at FROM affiliate_commissions c LEFT JOIN users u ON u.id = c.user_id
            LEFT JOIN payments p ON p.id = c.payment_id LEFT JOIN plans pl ON pl.id = p.plan_id WHERE c.affiliate_id = :a ORDER BY c.id DESC LIMIT 200', ['a' => $affiliateId]);
        return ['referred' => $referred, 'paying' => $paying, 'totals' => $totals, 'rows' => $rows];
    }

    /** "ar***@gmail.com" — affiliates never see full customer emails. */
    public static function maskEmail(?string $email): string
    {
        if (!$email || !str_contains($email, '@')) {
            return 'customer';
        }
        [$u, $d] = explode('@', $email, 2);
        return mb_substr($u, 0, 2) . str_repeat('*', max(3, mb_strlen($u) - 2)) . '@' . $d;
    }
}
