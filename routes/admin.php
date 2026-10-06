<?php
/**
 * Control Panel routes. Every URL is clean and lives under /control-panel/.
 * Middleware: $auth = signed-in admin required; $csrf = token check on every POST.
 * Permissions are enforced inside each controller (server-side, per module).
 *
 * @var App\Core\Router $router
 */

use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\EmailController;
use App\Controllers\Admin\LeadController;
use App\Controllers\Admin\MediaController;
use App\Controllers\Admin\MessageController;
use App\Controllers\Admin\ResourceController;
use App\Controllers\Admin\SettingsController;
use App\Controllers\Admin\SystemController;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;

$P = '/' . ADMIN_PREFIX;
$auth = static function (Request $req): void {
    if (!Auth::user()) {
        if ($req->isAjax()) {
            Response::json(['ok' => false, 'error' => 'Your session has expired. Please sign in again.'], 401);
        }
        if ($req->method === 'GET') {
            $_SESSION['_intended'] = $req->path;
        }
        Response::redirect('/' . ADMIN_PREFIX . '/login');
    }
};
$csrf = static fn (Request $req) => Csrf::verify($req);
$mw = [$auth, $csrf];

$router->get($P, [AuthController::class, 'root']);
$router->get("$P/login", [AuthController::class, 'form']);
$router->post("$P/login", [AuthController::class, 'login']);
$router->post("$P/logout", [AuthController::class, 'logout'], [$auth]);

$router->get("$P/dashboard", [DashboardController::class, 'index'], $mw);

// Website settings screens
foreach (array_keys(SettingsController::screens()) as $slug) {
    $u = SettingsController::url($slug);
    $router->get("$P/$u", fn (Request $r) => (new SettingsController())->show($r, $slug), $mw);
    $router->post("$P/$u", fn (Request $r) => (new SettingsController())->save($r, $slug), $mw);
}
$router->get("$P/appearance", fn () => Response::redirect("/" . ADMIN_PREFIX . "/appearance/colors"), $mw);

// Leads & messages
$router->get("$P/leads", [LeadController::class, 'index'], $mw);
$router->get("$P/leads/export", [LeadController::class, 'export'], $mw);
$router->get("$P/leads/{id}", [LeadController::class, 'show'], $mw);
$router->post("$P/leads/{id}", [LeadController::class, 'update'], $mw);
$router->post("$P/leads/{id}/status", [LeadController::class, 'status'], $mw);
$router->post("$P/leads/{id}/assign", [LeadController::class, 'assign'], $mw);
$router->post("$P/leads/{id}/notes", [LeadController::class, 'addNote'], $mw);
$router->post("$P/leads/{id}/delete", [LeadController::class, 'delete'], $mw);
$router->get("$P/messages", [MessageController::class, 'index'], $mw);
$router->get("$P/messages/{id}", [MessageController::class, 'show'], $mw);
$router->post("$P/messages/{id}/status", [MessageController::class, 'status'], $mw);
$router->post("$P/messages/{id}/delete", [MessageController::class, 'delete'], $mw);

// Media
$router->get("$P/media", [MediaController::class, 'index'], $mw);
$router->get("$P/media/browse", [MediaController::class, 'browse'], $mw);
$router->post("$P/media/upload", [MediaController::class, 'upload'], $mw);
$router->post("$P/media/{id}", [MediaController::class, 'update'], $mw);
$router->post("$P/media/{id}/delete", [MediaController::class, 'delete'], $mw);

// Email
$router->get("$P/email", fn () => Response::redirect("/" . ADMIN_PREFIX . "/email/smtp"), $mw);
$router->get("$P/email/smtp", [EmailController::class, 'smtp'], $mw);
$router->post("$P/email/smtp", [EmailController::class, 'saveSmtp'], $mw);
$router->get("$P/email/test", [EmailController::class, 'testForm'], $mw);
$router->post("$P/email/test", [EmailController::class, 'test'], $mw);
$router->get("$P/email/logs", [EmailController::class, 'logs'], $mw);

// System
$router->get("$P/activity-logs", [SystemController::class, 'logs'], $mw);
$router->get("$P/system", [SystemController::class, 'info'], $mw);
$router->get("$P/profile", [SystemController::class, 'profile'], $mw);
$router->post("$P/profile", [SystemController::class, 'saveProfile'], $mw);

// Generic CRUD modules (pages, services, blog, testimonials, FAQs, steps, sections, navigation, homepage, templates, users, roles)
foreach (ResourceController::keys() as $key) {
    $c = fn () => new ResourceController($key);
    $router->get("$P/$key", fn (Request $r) => $c()->index($r), $mw);
    $router->get("$P/$key/new", fn (Request $r) => $c()->create($r), $mw);
    $router->post("$P/$key/reorder", fn (Request $r) => $c()->reorder($r), $mw);
    $router->post("$P/$key", fn (Request $r) => $c()->store($r), $mw);
    $router->get("$P/$key/{id}/edit", fn (Request $r) => $c()->edit($r), $mw);
    $router->post("$P/$key/{id}", fn (Request $r) => $c()->update($r), $mw);
    $router->post("$P/$key/{id}/delete", fn (Request $r) => $c()->delete($r), $mw);
    $router->post("$P/$key/{id}/toggle", fn (Request $r) => $c()->toggle($r), $mw);
}
