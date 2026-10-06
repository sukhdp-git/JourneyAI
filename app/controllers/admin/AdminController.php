<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;

/** Base for every Control Panel controller. Authentication and CSRF are enforced by route middleware. */
abstract class AdminController
{
    /** @param array<array{0:string,1:?string}> $crumbs */
    protected function render(string $view, array $data = [], string $title = '', array $crumbs = [], int $status = 200): never
    {
        $GLOBALS['__errors'] = Session::takeErrors();
        $GLOBALS['__old'] = Session::takeOld();
        View::show('admin/' . $view, $data + [
            'pageTitle' => $title,
            'crumbs' => $crumbs,
            'flash' => Session::takeFlash(),
            'me' => Auth::user(),
        ], 'admin/layouts/app', $status);
    }

    protected function back(string $fallback, string $type = '', string $message = ''): never
    {
        if ($message !== '') {
            Session::flash($type ?: 'success', $message);
        }
        Response::redirect($fallback);
    }

    protected function fail(array $errors, string $to, string $message = 'Please correct the highlighted fields.'): never
    {
        Session::withErrors($errors, $_POST);
        Session::flash('error', $message);
        Response::redirect($to);
    }
}
