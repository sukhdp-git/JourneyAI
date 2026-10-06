<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Core\Seo;
use App\Core\Session;
use App\Core\Settings;
use App\Core\View;

/** Base for public controllers: renders inside the public layout with SEO metadata. */
abstract class Controller
{
    protected function render(string $view, array $data = [], array $seo = [], int $status = 200): never
    {
        if (Settings::bool('maintenance_mode')) {
            View::show('public/errors/maintenance', ['title' => 'Maintenance'], 'public/layouts/minimal', 503);
        }
        $GLOBALS['__errors'] = session_status() === PHP_SESSION_ACTIVE ? Session::takeErrors() : [];
        $GLOBALS['__old'] = session_status() === PHP_SESSION_ACTIVE ? Session::takeOld() : [];
        $data['flash'] = session_status() === PHP_SESSION_ACTIVE ? Session::takeFlash() : [];
        $data['seo'] = Seo::meta($seo);
        View::show('public/' . $view, $data, 'public/layouts/main', $status);
    }

    protected function notFound(): never
    {
        Response::abort(404);
    }
}
