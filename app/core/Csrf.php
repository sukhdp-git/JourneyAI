<?php
declare(strict_types=1);

namespace App\Core;

/** Per-session synchronizer token. Checked on every POST (forms send _csrf, AJAX sends X-CSRF-Token). */
final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function verify(Request $req): void
    {
        if ($req->method !== 'POST') {
            return;
        }
        $sent = (string) ($_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        $expected = (string) ($_SESSION['_csrf'] ?? '');
        if ($expected === '' || $sent === '' || !hash_equals($expected, $sent)) {
            Response::abort(403, 'Your session expired or the form token was invalid. Please go back, reload the page and try again.');
        }
    }
}
