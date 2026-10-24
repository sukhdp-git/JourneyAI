<?php
declare(strict_types=1);

namespace App\Trading;

use App\Core\Database;
use App\Core\Logger;
use App\Core\Mailer;
use App\Core\Request;

/**
 * Plan reminder emails (3 days before the end, and once after it ends) and new-sign-in alerts.
 * run() is called by cron.php (cPanel cron job, recommended hourly) and, as a fallback, at most once an
 * hour at the end of an ordinary page request, so reminders still go out when no cron job is set up.
 * Each email is sent once per plan expiry date (users.plan_reminder_for / plan_expired_notice_for).
 */
final class Reminders
{
    public const DAYS_BEFORE = 3;

    public static function run(int $limit = 300): array
    {
        $n = ['expiring' => 0, 'renewing' => 0, 'expired' => 0];
        $soon = Database::all("SELECT u.id, u.name, u.email, u.plan_id, u.plan_expires_at, p.name AS plan, p.slug, p.price, p.currency
            FROM users u JOIN plans p ON p.id = u.plan_id
            WHERE u.status = 'active' AND u.plan_expires_at > UTC_TIMESTAMP() AND u.plan_expires_at <= UTC_TIMESTAMP() + INTERVAL " . self::DAYS_BEFORE . " DAY
              AND (u.plan_reminder_for IS NULL OR u.plan_reminder_for <> u.plan_expires_at) LIMIT " . (int) $limit);
        foreach ($soon as $u) {
            Database::update('users', ['plan_reminder_for' => $u['plan_expires_at']], 'id = :id', ['id' => $u['id']]); // mark first: never send twice
            $sub = Subscriptions::current((int) $u['id']);
            $auto = Subscriptions::renewing($sub);
            $left = max(1, (int) floor((strtotime($u['plan_expires_at'] . ' UTC') - time()) / 86400));
            Mailer::sendTemplate($auto ? 'plan_renewing' : 'plan_expiring', $u['email'], self::vars($u, $auto ? $sub : null) + ['days_left' => $left . ' day' . ($left === 1 ? '' : 's')],
                ['related_type' => 'member', 'related_id' => (int) $u['id']]);
            $n[$auto ? 'renewing' : 'expiring']++;
        }
        // Ended plans: wait 12 hours so an autopay renewal that is still being collected is not reported as "ended".
        $ended = Database::all("SELECT u.id, u.name, u.email, u.plan_id, u.plan_expires_at, p.name AS plan, p.slug, p.price, p.currency
            FROM users u JOIN plans p ON p.id = u.plan_id
            WHERE u.status = 'active' AND u.plan_expires_at <= UTC_TIMESTAMP() - INTERVAL 12 HOUR AND u.plan_expires_at > UTC_TIMESTAMP() - INTERVAL 7 DAY
              AND (u.plan_expired_notice_for IS NULL OR u.plan_expired_notice_for <> u.plan_expires_at) LIMIT " . (int) $limit);
        foreach ($ended as $u) {
            Database::update('users', ['plan_expired_notice_for' => $u['plan_expires_at']], 'id = :id', ['id' => $u['id']]);
            Mailer::sendTemplate('plan_expired', $u['email'], self::vars($u, null), ['related_type' => 'member', 'related_id' => (int) $u['id']]);
            $n['expired']++;
        }
        return $n;
    }

    private static function vars(array $u, ?array $sub): array
    {
        return ['name' => $u['name'], 'email' => $u['email'], 'plan' => $u['plan'], 'expires_on' => fmt_date($u['plan_expires_at'], 'M j, Y'),
            'amount' => money($sub['amount'] ?? $u['price'], $sub['currency'] ?? $u['currency']), 'renew_url' => url('/checkout/' . $u['slug']), 'billing_url' => url('/terminal/billing')];
    }

    /** Fallback scheduler: claims the hourly slot atomically so only one request runs the job. */
    public static function maybeRun(): void
    {
        try {
            $now = time();
            $claimed = Database::query("UPDATE settings SET `value` = :now WHERE `key` = 'jobs_last_run' AND CAST(`value` AS UNSIGNED) < :due", ['now' => (string) $now, 'due' => $now - 3600])->rowCount();
            if ($claimed !== 1) {
                return;
            }
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request(); // the visitor's page is already delivered
            }
            self::run(25);
        } catch (\Throwable $e) {
            Logger::error('Reminder job failed', ['error' => mb_substr($e->getMessage(), 0, 200)]);
        }
    }

    // ------------------------------------------------------------------ sign-in alerts

    private const DEVICE_COOKIE = 'jz_dev';

    /** Emails the member when they sign in from a browser that has not signed in to this account before. */
    public static function loginAlert(array $user, string $method, Request $req): void
    {
        if (setting('login_alerts_enabled', '1') !== '1' || str_starts_with($method, 'signup')) {
            self::rememberDevice((int) $user['id']);
            return;
        }
        $known = array_filter(explode('.', (string) ($_COOKIE[self::DEVICE_COOKIE] ?? '')));
        $tag = self::tag((int) $user['id']);
        if (in_array($tag, $known, true)) {
            return;
        }
        self::rememberDevice((int) $user['id']);
        try {
            Mailer::sendTemplate('login_alert', $user['email'], [
                'name' => $user['name'], 'email' => $user['email'], 'time' => fmt_date(gmdate('Y-m-d H:i:s'), 'M j, Y g:i A T'),
                'device' => self::device((string) ($_SERVER['HTTP_USER_AGENT'] ?? '')), 'ip' => $req->ip(), 'method' => str_contains($method, 'google') ? 'Google' : 'Email and password',
                'reset_url' => url('/forgot-password'),
            ], ['related_type' => 'member', 'related_id' => (int) $user['id']]);
        } catch (\Throwable $e) {
            Logger::error('Sign-in alert failed', ['member' => (int) $user['id']]);
        }
    }

    private static function tag(int $uid): string
    {
        return substr(hash_hmac('sha256', 'device|' . $uid, (string) (defined('APP_KEY') ? APP_KEY : 'jz')), 0, 16);
    }

    private static function rememberDevice(int $uid): void
    {
        $known = array_filter(explode('.', (string) ($_COOKIE[self::DEVICE_COOKIE] ?? '')), fn ($t) => preg_match('/^[a-f0-9]{16}$/', $t));
        $known = array_slice(array_values(array_unique(array_merge([self::tag($uid)], $known))), 0, 5);
        if (!headers_sent()) {
            setcookie(self::DEVICE_COOKIE, implode('.', $known), ['expires' => time() + 400 * 86400, 'path' => '/', 'secure' => is_https(), 'httponly' => true, 'samesite' => 'Lax']);
        }
        $_COOKIE[self::DEVICE_COOKIE] = implode('.', $known);
    }

    /** "Chrome on Windows" — enough to recognise the device, nothing more. */
    public static function device(string $ua): string
    {
        $b = match (true) {
            str_contains($ua, 'Edg/') => 'Edge', str_contains($ua, 'OPR/') => 'Opera', str_contains($ua, 'SamsungBrowser') => 'Samsung Internet',
            str_contains($ua, 'Chrome/') => 'Chrome', str_contains($ua, 'Firefox/') => 'Firefox', str_contains($ua, 'Safari/') => 'Safari', default => 'A browser',
        };
        $os = match (true) {
            str_contains($ua, 'Android') => 'Android', str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') => 'iOS', str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Mac OS X') => 'macOS', str_contains($ua, 'Linux') => 'Linux', default => 'an unknown system',
        };
        return $b . ' on ' . $os;
    }
}
