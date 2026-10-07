<?php
declare(strict_types=1);

namespace App\Trading;

use App\Core\Database;
use App\Core\Logger;
use App\Core\Mailer;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Session;
use App\Core\Settings;

/**
 * Website members (traders). Separate from Control Panel admins: different table, session key and cookie.
 * Every member-owned query in the terminal is scoped by the id returned from current().
 */
final class Members
{
    private static ?array $current = null;
    private static bool $loaded = false;

    public static function current(): ?array
    {
        if (self::$loaded) {
            return self::$current;
        }
        self::$loaded = true;
        if (session_status() !== PHP_SESSION_ACTIVE || empty($_SESSION['member_id'])) {
            return null;
        }
        $u = Database::one('SELECT u.*, s.theme, s.language, s.timezone, s.base_currency, s.default_risk_pct, s.max_daily_loss, s.max_weekly_loss, s.default_target_rr,
                s.tilt_loss_count, s.tilt_window_minutes, s.tilt_cooldown_minutes, s.active_account_id, s.daily_limit_type, s.weekly_limit_type, s.a_plus_risk_pct
            FROM users u LEFT JOIN user_settings s ON s.user_id = u.id WHERE u.id = :id', ['id' => (int) $_SESSION['member_id']]);
        if (!$u || $u['status'] !== 'active' || (int) ($_SESSION['member_ver'] ?? 0) !== self::version($u)) {
            unset($_SESSION['member_id'], $_SESSION['member_ver']);
            return null;
        }
        unset($u['password_hash']);
        $u['ent'] = self::entitlements($u);
        return self::$current = $u;
    }

    /** Changes when the password changes, so other sessions are signed out after a reset. */
    public static function version(array $u): int
    {
        return crc32((string) ($u['password_hash'] ?? '') . '|' . $u['id']);
    }

    public static function refresh(): void
    {
        self::$loaded = false;
        self::$current = null;
    }

    /** What the member's plan allows. Free members may only use demo accounts. */
    public static function entitlements(array $u): array
    {
        $plan = null;
        if ($u['plan_id'] && $u['plan_expires_at'] && strtotime($u['plan_expires_at'] . ' UTC') > time()) {
            $plan = Database::one('SELECT * FROM plans WHERE id = :id', ['id' => $u['plan_id']]);
        }
        return [
            'paid' => (bool) $plan,
            'plan' => $plan,
            'plan_name' => $plan['name'] ?? (Settings::get('free_plan_name') ?: 'Free (Demo)'),
            'expires_at' => $plan ? $u['plan_expires_at'] : null,
            'live' => $plan && (int) $plan['allow_live'],
            'max_live' => $plan ? (int) $plan['max_live_accounts'] : 0,
            'ai_daily' => $plan ? (int) $plan['ai_daily_limit'] : (int) (Settings::get('free_ai_daily_limit') ?: 3),
        ];
    }

    public static function login(array $user, string $method, Request $req): void
    {
        Session::regenerate();
        $_SESSION['member_id'] = (int) $user['id'];
        $_SESSION['member_ver'] = self::version($user);
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        Database::query('UPDATE users SET last_login_at = UTC_TIMESTAMP(), last_login_ip = :ip, login_count = login_count + 1 WHERE id = :id', ['ip' => $req->ip(), 'id' => $user['id']]);
        self::logLogin((int) $user['id'], $user['email'], $method, true, null, $req);
        self::refresh();
    }

    public static function logout(): void
    {
        if ($id = self::current()['id'] ?? null) {
            self::audit((int) $id, 'logout', 'Signed out');
        }
        unset($_SESSION['member_id'], $_SESSION['member_ver']);
        Session::regenerate();
        self::refresh();
    }

    public static function logLogin(?int $userId, ?string $email, string $method, bool $ok, ?string $reason, Request $req): void
    {
        try {
            Database::insert('user_logins', ['user_id' => $userId, 'email' => $email ? mb_substr($email, 0, 190) : null, 'method' => $method, 'success' => $ok ? 1 : 0, 'reason' => $reason, 'ip' => $req->ip(), 'user_agent' => $req->userAgent()]);
        } catch (\Throwable $e) {
            Logger::error('Member login log failed: ' . $e->getMessage());
        }
    }

    public static function audit(int $userId, string $action, string $details = ''): void
    {
        try {
            Database::insert('user_audit_logs', ['user_id' => $userId, 'action' => $action, 'details' => mb_substr($details, 0, 500), 'ip' => (new Request())->ip()]);
        } catch (\Throwable $e) {
            Logger::error('Member audit failed: ' . $e->getMessage());
        }
    }

    /** Creates a member and default settings. */
    public static function create(array $data, Request $req): array
    {
        return Database::transaction(function () use ($data, $req) {
            $id = Database::insert('users', [
                'email' => strtolower($data['email']), 'name' => mb_substr($data['name'], 0, 120), 'avatar_url' => $data['avatar_url'] ?? null,
                'google_sub' => $data['google_sub'] ?? null, 'password_hash' => $data['password_hash'] ?? null, 'email_verified' => (int) ($data['email_verified'] ?? 0),
                'signup_method' => $data['method'], 'signup_ip' => $req->ip(),
            ]);
            Database::insert('user_settings', ['user_id' => $id, 'timezone' => $data['timezone'] ?? 'UTC']);
            self::audit($id, 'signup', 'Account created via ' . $data['method']);
            return Database::one('SELECT * FROM users WHERE id = :id', ['id' => $id]);
        });
    }

    /** Email + password sign-in. Returns the user or an error message. */
    public static function attempt(string $email, string $password, Request $req): array|string
    {
        $email = strtolower(trim($email));
        if (!RateLimiter::hit('member-login-ip:' . $req->ip(), 30, 900) || !RateLimiter::hit('member-login:' . $email, 8, 900)) {
            self::logLogin(null, $email, 'email', false, 'rate_limited', $req);
            return 'Too many sign-in attempts. Please wait 15 minutes and try again.';
        }
        $u = Database::one('SELECT * FROM users WHERE email = :e', ['e' => $email]);
        $hash = $u['password_hash'] ?? '$2y$10$vZeD4ddpLR3u5KKX/E0EpeKNhhlYcCsLZPFAd8YtxhCH/nlJRYN9e';
        if (!password_verify($password, $hash) || !$u || !$u['password_hash']) {
            self::logLogin($u['id'] ?? null, $email, 'email', false, 'invalid', $req);
            if ($u && !$u['password_hash'] && $u['google_sub']) {
                return 'This account uses Google sign-in. Click “Continue with Google”.';
            }
            return 'Incorrect email or password.';
        }
        if ($u['status'] !== 'active') {
            self::logLogin((int) $u['id'], $email, 'email', false, 'suspended', $req);
            return 'This account has been suspended. Please contact support.';
        }
        if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
            Database::update('users', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], 'id = :id', ['id' => $u['id']]);
        }
        RateLimiter::clear('member-login:' . $email);
        return $u;
    }

    public static function validPassword(string $p): bool
    {
        return strlen($p) >= 10 && strlen($p) <= 200 && preg_match('/[A-Za-z]/', $p) && preg_match('/\d/', $p);
    }

    /** Sends a single-use, 60-minute reset link. Always behaves the same whether or not the email exists. */
    public static function sendReset(string $email, Request $req): void
    {
        $u = Database::one("SELECT id, name, email FROM users WHERE email = :e AND status = 'active'", ['e' => strtolower(trim($email))]);
        if (!$u) {
            return;
        }
        $token = bin2hex(random_bytes(32));
        Database::insert('password_resets', ['user_id' => $u['id'], 'token_hash' => hash('sha256', $token), 'expires_at' => gmdate('Y-m-d H:i:s', time() + 3600)]);
        Mailer::sendTemplate('password_reset', $u['email'], ['name' => $u['name'], 'email' => $u['email'], 'reset_url' => url('/reset-password/' . $token)], ['related_type' => 'member', 'related_id' => (int) $u['id']]);
        self::logLogin((int) $u['id'], $u['email'], 'reset', true, 'reset_requested', $req);
    }

    public static function findReset(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        return Database::one('SELECT r.id AS reset_id, u.* FROM password_resets r JOIN users u ON u.id = r.user_id WHERE r.token_hash = :h AND r.used_at IS NULL AND r.expires_at > UTC_TIMESTAMP()', ['h' => hash('sha256', $token)]);
    }
}
