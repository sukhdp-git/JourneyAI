<?php
/**
 * journzey.ai — single front controller. Apache rewrites every clean URL here (see .htaccess);
 * visitors never see .php in the address bar.
 */
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
header_remove('X-Powered-By');
if (is_https()) {
    header('Strict-Transport-Security: max-age=31536000');
}

$request = new Request();

// Until the installer has finished, every request goes to /setup.
$installed = APP_CONFIGURED && is_file(INSTALL_LOCK);
if (!$installed && !str_starts_with($request->path, '/setup')) {
    Response::redirect('/setup');
}

if ($request->isAdmin()) {
    Session::start(true);
    header('Cache-Control: no-store, private');
    header('X-Robots-Tag: noindex, nofollow');
} elseif ($request->method === 'POST' || isset($_COOKIE['JZSESS']) || in_array($request->path, ['/contact', '/book-consultation', '/setup'], true)) {
    Session::start(false);
}

$router = new Router();
require ROOT_PATH . '/routes/admin.php';
require ROOT_PATH . '/routes/web.php';
$router->dispatch($request);
