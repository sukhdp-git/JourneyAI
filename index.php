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
header('Permissions-Policy: camera=(), microphone=(self), geolocation=(), payment=()');
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
if ($installed) {
    // One-time, non-destructive schema update after deploying a new version.
    App\Core\Migrator::ensure();
}

if ($request->isAdmin()) {
    Session::start(true);
    header('Cache-Control: no-store, private');
    header('X-Robots-Tag: noindex, nofollow');
} elseif (str_starts_with($request->path, '/webhooks/') || str_starts_with($request->path, '/api/webhooks/')) {
    // Server-to-server webhooks: no session, authenticated by signatures instead.
    header('Cache-Control: no-store');
} elseif ($request->method === 'POST' || isset($_COOKIE['JZSESS']) || preg_match('#^/(contact|setup|login|signup|auth|onboarding|forgot-password|reset-password|terminal|checkout|pricing)(/|$)#', $request->path)) {
    Session::start(false);
    if (preg_match('#^/(terminal|checkout|onboarding|login|signup)(/|$)#', $request->path)) {
        header('Cache-Control: no-store, private');
        header('X-Robots-Tag: noindex');
    }
}

$router = new Router();
require ROOT_PATH . '/routes/admin.php';
require ROOT_PATH . '/routes/app.php';
require ROOT_PATH . '/routes/web.php';
$router->dispatch($request);
