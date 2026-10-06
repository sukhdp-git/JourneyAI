<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Control-panel authentication and role-based authorization.
 * Sessions are bound to the admin id, regenerated on login, idle-timed-out and re-validated
 * against the database on every request (a disabled admin is signed out immediately).
 */
final class Auth
{
    public const IDLE_TIMEOUT = 1800;        // 30 minutes without activity
    public const ABSOLUTE_TIMEOUT = 43200;   // 12 hours per sign-in
    private const MAX_PER_EMAIL = 5;
    private const MAX_PER_IP = 20;
    private const WINDOW = 900;              // 15 minutes

    /** Every permission key the panel understands, grouped for the Roles screen. */
    public const PERMISSIONS = [
        'Content' => [
            'pages' => 'Pages',
            'services' => 'Services',
            'blog' => 'Blog posts, categories & tags',
            'testimonials' => 'Testimonials',
            'faqs' => 'FAQs',
            'process' => 'Process steps',
            'sections' => 'Custom sections',
            'homepage' => 'Homepage sections',
            'navigation' => 'Navigation menus',
            'media' => 'Media library',
        ],
        'Leads' => [
            'leads' => 'Consultation leads',
            'messages' => 'Contact messages',
        ],
        'Website settings' => [
            'settings.general' => 'General, header, footer, contact & social',
            'settings.seo' => 'SEO',
            'settings.analytics' => 'Analytics & tracking codes',
            'settings.whatsapp' => 'WhatsApp button',
            'appearance' => 'Appearance (colours, typography, buttons, layout)',
            'custom_css' => 'Custom CSS',
        ],
        'Email' => [
            'email.templates' => 'Email templates & delivery log',
            'email.smtp' => 'SMTP credentials & test email',
        ],
        'System' => [
            'users' => 'Admin users',
            'roles' => 'Roles & permissions',
            'logs' => 'Activity logs',
            'system' => 'System information',
        ],
    ];

    private static ?array $user = null;
    private static bool $loaded = false;

    public static function attempt(string $email, string $password, Request $req): string|true
    {
        $email = strtolower(trim($email));
        $ipKey = 'login-ip:' . $req->ip();
        $emailKey = 'login-email:' . $email;
        if (RateLimiter::tooMany($ipKey, self::MAX_PER_IP) || RateLimiter::tooMany($emailKey, self::MAX_PER_EMAIL)) {
            self::record($email, false, $req, 'rate_limited');
            return 'Too many sign-in attempts. Please wait 15 minutes and try again.';
        }
        RateLimiter::hit($ipKey, self::MAX_PER_IP, self::WINDOW);
        RateLimiter::hit($emailKey, self::MAX_PER_EMAIL, self::WINDOW);

        $admin = Database::one('SELECT id, password_hash, status FROM admins WHERE email = :e', ['e' => $email]);
        // Verify against a dummy hash when the account does not exist so timing does not reveal it.
        $hash = $admin['password_hash'] ?? '$2y$10$vZeD4ddpLR3u5KKX/E0EpeKNhhlYcCsLZPFAd8YtxhCH/nlJRYN9e';
        $ok = password_verify($password, $hash) && $admin !== null;
        if (!$ok || $admin['status'] !== 'active') {
            self::record($email, false, $req, $admin && $ok ? 'disabled' : 'invalid');
            return $admin && $ok ? 'This account is disabled. Contact a Super Admin.' : 'Incorrect email or password.';
        }
        if (password_needs_rehash($admin['password_hash'], PASSWORD_DEFAULT)) {
            Database::update('admins', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], 'id = :id', ['id' => $admin['id']]);
        }
        RateLimiter::clear($emailKey);
        Session::regenerate();
        $_SESSION['admin_id'] = (int) $admin['id'];
        $_SESSION['login_at'] = time();
        $_SESSION['last_seen'] = time();
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        Database::update('admins', ['last_login_at' => gmdate('Y-m-d H:i:s'), 'last_login_ip' => $req->ip()], 'id = :id', ['id' => $admin['id']]);
        self::$loaded = false;
        self::record($email, true, $req, 'ok');
        Activity::log('login', 'auth', (int) $admin['id'], 'Signed in');
        return true;
    }

    private static function record(string $email, bool $success, Request $req, string $reason): void
    {
        try {
            Database::insert('login_attempts', [
                'email' => mb_substr($email, 0, 190),
                'ip' => $req->ip(),
                'user_agent' => $req->userAgent(),
                'success' => $success ? 1 : 0,
                'reason' => $reason,
            ]);
        } catch (\Throwable $e) {
            Logger::error('Login attempt log failed: ' . $e->getMessage());
        }
    }

    public static function user(): ?array
    {
        if (self::$loaded) {
            return self::$user;
        }
        self::$loaded = true;
        self::$user = null;
        $id = (int) ($_SESSION['admin_id'] ?? 0);
        if ($id <= 0) {
            return null;
        }
        $now = time();
        if ($now - (int) ($_SESSION['last_seen'] ?? 0) > self::IDLE_TIMEOUT || $now - (int) ($_SESSION['login_at'] ?? 0) > self::ABSOLUTE_TIMEOUT) {
            unset($_SESSION['admin_id']);
            $_SESSION['_timeout'] = true;
            return null;
        }
        $row = Database::one(
            'SELECT a.id, a.name, a.email, a.status, a.role_id, r.name AS role_name, r.slug AS role_slug, r.permissions
             FROM admins a JOIN roles r ON r.id = a.role_id WHERE a.id = :id',
            ['id' => $id],
        );
        if (!$row || $row['status'] !== 'active') {
            unset($_SESSION['admin_id']);
            return null;
        }
        $_SESSION['last_seen'] = $now;
        $row['perms'] = json_list($row['permissions']);
        unset($row['permissions']);
        self::$user = $row;
        return $row;
    }

    public static function id(): ?int
    {
        return isset(self::user()['id']) ? (int) self::user()['id'] : null;
    }

    public static function isSuper(): bool
    {
        return in_array('*', self::user()['perms'] ?? [], true);
    }

    public static function can(string $permission): bool
    {
        $u = self::user();
        if (!$u) {
            return false;
        }
        return in_array('*', $u['perms'], true) || in_array($permission, $u['perms'], true);
    }

    /** Server-side authorization gate. */
    public static function authorize(string $permission): void
    {
        if (!self::can($permission)) {
            Activity::log('denied', 'auth', null, 'Permission denied: ' . $permission);
            Response::abort(403, 'Your role does not have access to this area.');
        }
    }

    public static function logout(): void
    {
        if (self::id()) {
            Activity::log('logout', 'auth', self::id(), 'Signed out');
        }
        Session::destroy();
        self::$user = null;
        self::$loaded = true;
    }

    public static function allPermissionKeys(): array
    {
        $keys = [];
        foreach (self::PERMISSIONS as $group) {
            $keys = array_merge($keys, array_keys($group));
        }
        return $keys;
    }
}
