<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Hardened PHP sessions. The public site and the control panel use different cookie names and
 * paths, so a visitor session can never be used as an admin session.
 */
final class Session
{
    public static function start(bool $admin = false): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $secure = is_https();
        $basePath = rtrim((string) parse_url(BASE_URL, PHP_URL_PATH), '/');
        session_name($admin ? 'JZADMIN' : 'JZSESS');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.gc_maxlifetime', (string) (60 * 60 * 12));
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => $basePath . ($admin ? '/' . ADMIN_PREFIX : '/'),
            'secure' => $secure,
            'httponly' => true,
            'samesite' => $admin ? 'Strict' : 'Lax',
        ]);
        $dir = STORAGE_PATH . '/sessions';
        if (is_dir($dir) || @mkdir($dir, 0700, true)) {
            if (is_writable($dir)) {
                session_save_path($dir);
            }
        }
        session_start();
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function get(string $k, mixed $d = null): mixed
    {
        return $_SESSION[$k] ?? $d;
    }

    public static function set(string $k, mixed $v): void
    {
        $_SESSION[$k] = $v;
    }

    public static function forget(string $k): void
    {
        unset($_SESSION[$k]);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', ['expires' => time() - 42000, 'path' => $p['path'], 'secure' => $p['secure'], 'httponly' => true, 'samesite' => $p['samesite']]);
        }
        session_destroy();
    }

    /** One-request flash messages (rendered as toasts / alerts). */
    public static function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    public static function takeFlash(): array
    {
        $f = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $f;
    }

    /** Old input + field errors for redisplaying forms after validation failure. */
    public static function withErrors(array $errors, array $old = []): void
    {
        unset($old['_csrf'], $old['password'], $old['password_confirmation'], $old['smtp_password']);
        $_SESSION['_errors'] = $errors;
        $_SESSION['_old'] = $old;
    }

    public static function takeErrors(): array
    {
        $e = $_SESSION['_errors'] ?? [];
        unset($_SESSION['_errors']);
        return $e;
    }

    public static function takeOld(): array
    {
        $o = $_SESSION['_old'] ?? [];
        unset($_SESSION['_old']);
        return $o;
    }
}
