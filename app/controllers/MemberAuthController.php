<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Mailer;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Settings;
use App\Core\View;
use App\Trading\Domain;
use App\Trading\GoogleAuth;
use App\Trading\Ledger;
use App\Trading\Members;
use App\Trading\Sessions;

/** Member sign-up, sign-in (email or Google), password reset and first-run onboarding. */
final class MemberAuthController
{
    private function page(string $view, array $data = [], int $status = 200): never
    {
        $GLOBALS['__errors'] = Session::takeErrors();
        $GLOBALS['__old'] = Session::takeOld();
        View::show('public/auth/' . $view, $data + ['flash' => Session::takeFlash(), 'google' => GoogleAuth::configured()], 'public/layouts/auth', $status);
    }

    private function guestOnly(): void
    {
        if (Members::current()) {
            Response::redirect('/terminal');
        }
    }

    public function loginForm(Request $req): never
    {
        $this->guestOnly();
        if (($to = (string) $req->query('next', '')) && preg_match('#^/(terminal|checkout|pricing)[a-z0-9/\-]*$#', $to)) {
            $_SESSION['member_intended'] = $to;
        }
        $this->page('login', ['title' => 'Sign in']);
    }

    public function signupForm(Request $req): never
    {
        $this->guestOnly();
        if (($plan = (string) $req->query('plan', '')) && preg_match('/^[a-z0-9\-]{1,80}$/', $plan)) {
            $_SESSION['member_intended'] = '/checkout/' . $plan;
        }
        $this->page('signup', ['title' => 'Create your account', 'enabled' => Settings::bool('signup_enabled', true), 'emailEnabled' => Settings::bool('email_signup_enabled', true)]);
    }

    public function login(Request $req): never
    {
        Csrf::verify($req);
        $email = mb_substr($req->str('email', 190), 0, 190);
        $result = Members::attempt($email, (string) ($_POST['password'] ?? ''), $req);
        if (is_string($result)) {
            Session::withErrors([], ['email' => $email]);
            Session::flash('error', $result);
            Response::redirect('/login');
        }
        Members::login($result, 'email', $req);
        $this->afterLogin();
    }

    public function signup(Request $req): never
    {
        Csrf::verify($req);
        if (!Settings::bool('signup_enabled', true) || !Settings::bool('email_signup_enabled', true)) {
            Response::abort(403, 'Email sign-up is currently disabled.');
        }
        if (($_POST['website'] ?? '') !== '') {
            Response::redirect('/signup');
        }
        $name = trim((string) $req->post('name', ''));
        $email = strtolower(trim((string) $req->post('email', '')));
        $pass = (string) ($_POST['password'] ?? '');
        $errors = [];
        if ($name === '' || mb_strlen($name) > 120) {
            $errors['name'] = 'Enter your name.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            $errors['email'] = 'Enter a valid email address.';
        } elseif (Database::value('SELECT id FROM users WHERE email = :e', ['e' => $email])) {
            $errors['email'] = 'An account with this email already exists. Sign in instead.';
        }
        if (!Members::validPassword($pass)) {
            $errors['password'] = 'Use at least 10 characters including letters and numbers.';
        }
        if ($req->post('terms') !== '1') {
            $errors['terms'] = 'Please accept the Terms and Privacy Policy.';
        }
        if (!$errors && !RateLimiter::hit('member-signup:' . $req->ip(), 10, 3600)) {
            $errors['email'] = 'Too many sign-ups from your connection. Please try again later.';
        }
        if ($errors) {
            Session::withErrors($errors, $_POST);
            Response::redirect('/signup');
        }
        $tz = (string) $req->post('timezone', 'UTC');
        $user = Members::create(['email' => $email, 'name' => $name, 'password_hash' => password_hash($pass, PASSWORD_DEFAULT), 'method' => 'email', 'timezone' => Sessions::isValidTz($tz) ? $tz : 'UTC'], $req);
        Members::login($user, 'signup_email', $req);
        Mailer::sendTemplate('welcome', $email, ['name' => $name, 'email' => $email], ['related_type' => 'member', 'related_id' => (int) $user['id']]);
        Response::redirect('/onboarding');
    }

    public function logout(Request $req): never
    {
        Csrf::verify($req);
        Members::logout();
        Session::flash('success', 'You have been signed out.');
        Response::redirect('/login');
    }

    public function google(Request $req): never
    {
        if (!GoogleAuth::configured()) {
            Session::flash('error', 'Google sign-in is not configured yet. The site owner must add the Google OAuth client ID and secret in the Control Panel (Integrations).');
            Response::redirect('/login');
        }
        Response::redirect(GoogleAuth::authUrl());
    }

    public function googleCallback(Request $req): never
    {
        try {
            $g = GoogleAuth::handleCallback($_GET);
        } catch (\RuntimeException $e) {
            Members::logLogin(null, null, 'google', false, 'oauth_error', $req);
            Session::flash('error', $e->getMessage());
            Response::redirect('/login');
        }
        $user = Database::one('SELECT * FROM users WHERE google_sub = :s', ['s' => $g['sub']]);
        $method = 'google';
        if (!$user) {
            $user = Database::one('SELECT * FROM users WHERE email = :e', ['e' => $g['email']]);
            if ($user) {
                // Link Google to the existing email account. Google has verified the address; an unverified
                // password set by someone else could have pre-registered it, so that password is removed.
                $upd = ['google_sub' => $g['sub'], 'email_verified' => 1, 'avatar_url' => $g['picture'] ?: $user['avatar_url']];
                if (!(int) $user['email_verified']) {
                    $upd['password_hash'] = null;
                }
                Database::update('users', $upd, 'id = :id', ['id' => $user['id']]);
                Members::audit((int) $user['id'], 'google_linked', 'Google account linked');
                $user = Database::one('SELECT * FROM users WHERE id = :id', ['id' => $user['id']]);
            } else {
                if (!Settings::bool('signup_enabled', true)) {
                    Session::flash('error', 'New sign-ups are currently closed.');
                    Response::redirect('/login');
                }
                $user = Members::create(['email' => $g['email'], 'name' => $g['name'] ?: 'Trader', 'avatar_url' => $g['picture'] ?: null, 'google_sub' => $g['sub'], 'email_verified' => 1, 'method' => 'google'], $req);
                $method = 'signup_google';
                Mailer::sendTemplate('welcome', $g['email'], ['name' => $user['name'], 'email' => $g['email']], ['related_type' => 'member', 'related_id' => (int) $user['id']]);
            }
        } elseif ($g['picture'] && $g['picture'] !== $user['avatar_url']) {
            Database::update('users', ['avatar_url' => $g['picture']], 'id = :id', ['id' => $user['id']]);
        }
        if ($user['status'] !== 'active') {
            Members::logLogin((int) $user['id'], $user['email'], 'google', false, 'suspended', $req);
            Session::flash('error', 'This account has been suspended. Please contact support.');
            Response::redirect('/login');
        }
        Members::login($user, $method, $req);
        $this->afterLogin();
    }

    private function afterLogin(): never
    {
        $m = Members::current();
        if (!(int) $m['onboarded']) {
            Response::redirect('/onboarding');
        }
        $to = (string) ($_SESSION['member_intended'] ?? '/terminal');
        unset($_SESSION['member_intended']);
        Response::redirect(preg_match('#^/(terminal|checkout|pricing)[a-z0-9/\-]*$#', $to) ? $to : '/terminal');
    }

    public function forgotForm(Request $req): never
    {
        $this->page('forgot', ['title' => 'Reset your password']);
    }

    public function forgot(Request $req): never
    {
        Csrf::verify($req);
        $email = trim((string) $req->post('email', ''));
        if (filter_var($email, FILTER_VALIDATE_EMAIL) && RateLimiter::hit('member-reset:' . $req->ip(), 5, 3600)) {
            Members::sendReset($email, $req);
        }
        Session::flash('success', 'If an account exists for that email, a reset link is on its way. It expires in 60 minutes.');
        Response::redirect('/forgot-password');
    }

    public function resetForm(Request $req): never
    {
        $u = Members::findReset($req->params['token'] ?? '');
        if (!$u) {
            Session::flash('error', 'This reset link is invalid or has expired. Request a new one.');
            Response::redirect('/forgot-password');
        }
        $this->page('reset', ['title' => 'Choose a new password', 'token' => $req->params['token']]);
    }

    public function reset(Request $req): never
    {
        Csrf::verify($req);
        $token = $req->params['token'] ?? '';
        $u = Members::findReset($token);
        if (!$u) {
            Session::flash('error', 'This reset link is invalid or has expired.');
            Response::redirect('/forgot-password');
        }
        $pass = (string) ($_POST['password'] ?? '');
        if (!Members::validPassword($pass) || $pass !== (string) ($_POST['password_confirmation'] ?? '')) {
            Session::withErrors(['password' => 'Use at least 10 characters including letters and numbers, and type it twice.']);
            Response::redirect('/reset-password/' . $token);
        }
        Database::transaction(function () use ($u, $pass) {
            Database::update('users', ['password_hash' => password_hash($pass, PASSWORD_DEFAULT), 'email_verified' => 1], 'id = :id', ['id' => $u['id']]);
            Database::update('password_resets', ['used_at' => gmdate('Y-m-d H:i:s')], 'id = :id', ['id' => $u['reset_id']]);
        });
        Members::audit((int) $u['id'], 'password_reset', 'Password reset by email link');
        Session::flash('success', 'Your password has been changed. Sign in with your new password.');
        Response::redirect('/login');
    }

    // --------------------------------------------------------------- onboarding
    public function onboarding(Request $req): never
    {
        $m = Members::current() ?? Response::redirect('/login');
        if ((int) $m['onboarded']) {
            Response::redirect('/terminal');
        }
        $this->page('onboarding', ['title' => 'Set up your terminal', 'm' => $m]);
    }

    public function completeOnboarding(Request $req): never
    {
        Csrf::verify($req);
        $m = Members::current() ?? Response::redirect('/login');
        $markets = array_values(array_intersect((array) ($_POST['markets'] ?? []), array_keys(Domain::ASSET_CLASSES)));
        $currency = in_array($req->post('currency'), Domain::CURRENCIES, true) ? $req->post('currency') : 'USD';
        $tz = Sessions::isValidTz((string) $req->post('timezone')) ? (string) $req->post('timezone') : 'UTC';
        $capital = (float) $req->post('starting_capital', '10000');
        $risk = (float) $req->post('risk_pct', '1');
        $errors = [];
        if ($capital <= 0 || $capital > 1e12) {
            $errors['starting_capital'] = 'Enter your starting capital.';
        }
        if ($risk < 0.05 || $risk > 10) {
            $errors['risk_pct'] = 'Risk per trade must be between 0.05% and 10%.';
        }
        $name = 'Demo account';
        $withDemo = $req->post('load_demo') === '1';
        if ($errors) {
            Session::withErrors($errors, $_POST);
            Response::redirect('/onboarding');
        }
        $dec = fn ($k) => is_numeric($req->post($k)) && (float) $req->post($k) > 0 ? round((float) $req->post($k), 2) : null;
        // One account to start: the demo journal (sample data) or, without it, an empty demo account. Broker and
        // prop-firm accounts are added later from the account menu ("＋ Add account").
        Database::transaction(function () use ($m, $markets, $currency, $tz, $capital, $risk, $name, $dec, $withDemo) {
            $uid = (int) $m['id'];
            Ledger::ensureTemplates($uid);
            $accId = $withDemo ? null : Database::insert('trading_accounts', ['user_id' => $uid, 'name' => $name, 'currency' => $currency, 'starting_capital' => round($capital, 2), 'is_demo' => 1, 'max_daily_loss' => $dec('max_daily_loss'), 'max_weekly_loss' => $dec('max_weekly_loss')]);
            Database::update('user_settings', [
                'timezone' => $tz, 'base_currency' => $currency, 'default_risk_pct' => round($risk, 2), 'default_target_rr' => $dec('target_rr') ?? 2, 'active_account_id' => $accId,
            ], 'user_id = :u', ['u' => $uid]);
            Database::update('users', ['onboarded' => 1, 'primary_markets' => implode(',', $markets)], 'id = :id', ['id' => $uid]);
        });
        Members::refresh();
        if ($withDemo) {
            Ledger::loadDemo(Members::current());
            if ($dec('max_daily_loss') || $dec('max_weekly_loss')) {
                Database::query("UPDATE trading_accounts SET max_daily_loss = :d, max_weekly_loss = :w WHERE user_id = :u AND has_demo_data = 1", ['d' => $dec('max_daily_loss'), 'w' => $dec('max_weekly_loss'), 'u' => $m['id']]);
            }
        }
        Members::audit((int) $m['id'], 'onboarded', 'Completed onboarding');
        $to = (string) ($_SESSION['member_intended'] ?? '/terminal');
        unset($_SESSION['member_intended']);
        Session::flash('success', 'Your terminal is ready.' . ($req->post('load_demo') === '1' ? ' The demo journal is loaded — it is clearly marked DEMO DATA.' : ''));
        Response::redirect(preg_match('#^/(terminal|checkout)[a-z0-9/\-]*$#', $to) ? $to : '/terminal');
    }
}
