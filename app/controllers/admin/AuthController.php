<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;

final class AuthController
{
    public function root(Request $req): never
    {
        Response::redirect('/' . ADMIN_PREFIX . (Auth::user() ? '/dashboard' : '/login'));
    }

    public function form(Request $req): never
    {
        if (Auth::user()) {
            Response::redirect('/' . ADMIN_PREFIX . '/dashboard');
        }
        $timeout = !empty($_SESSION['_timeout']);
        unset($_SESSION['_timeout']);
        View::show('admin/auth/login', ['flash' => Session::takeFlash(), 'timeout' => $timeout, 'email' => Session::takeOld()['email'] ?? '']);
    }

    public function login(Request $req): never
    {
        Csrf::verify($req);
        $email = mb_substr($req->str('email', 190), 0, 190);
        $password = (string) ($_POST['password'] ?? '');
        if ($email === '' || $password === '' || strlen($password) > 1024) {
            Session::flash('error', 'Enter your email and password.');
            Session::withErrors([], ['email' => $email]);
            Response::redirect('/' . ADMIN_PREFIX . '/login');
        }
        $result = Auth::attempt($email, $password, $req);
        if ($result !== true) {
            Session::flash('error', $result);
            Session::withErrors([], ['email' => $email]);
            Response::redirect('/' . ADMIN_PREFIX . '/login');
        }
        $to = (string) ($_SESSION['_intended'] ?? '');
        unset($_SESSION['_intended']);
        Response::redirect(preg_match('#^/' . ADMIN_PREFIX . '/[a-z0-9/\-]*$#', $to) ? $to : '/' . ADMIN_PREFIX . '/dashboard');
    }

    public function logout(Request $req): never
    {
        Csrf::verify($req);
        Auth::logout();
        Session::start(true);
        Session::flash('success', 'You have been signed out.');
        Response::redirect('/' . ADMIN_PREFIX . '/login');
    }
}
