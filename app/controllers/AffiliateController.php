<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Trading\Affiliates;
use App\Trading\Members;

/** Public affiliate programme page, applications and the /r/CODE referral link. */
final class AffiliateController extends Controller
{
    public const PLATFORMS = ['YouTube', 'Instagram', 'X / Twitter', 'Telegram', 'TikTok', 'Discord', 'Blog / website', 'Other'];
    public const AUDIENCES = ['Under 1,000', '1,000 – 10,000', '10,000 – 100,000', '100,000+'];

    public function page(Request $req): never
    {
        $m = member();
        $plan = Database::one('SELECT name, price, currency, interval_days FROM plans WHERE is_active = 1 AND price > 0 ORDER BY sort_order, price LIMIT 1');
        $this->render('affiliates', [
            'member' => $m, 'mine' => $m ? Affiliates::forUser((int) $m['id']) : null, 'pct' => Affiliates::defaultPct(), 'plan' => $plan,
            'enabled' => setting('affiliates_enabled', '1') === '1',
        ], ['title' => 'Affiliate programme — earn ' . rtrim(rtrim(number_format(Affiliates::defaultPct(), 2), '0'), '.') . '% recurring', 'description' => 'Partner with journzey.ai: share your personal code and earn a recurring commission on every plan payment from the traders you refer.', 'path' => '/affiliates']);
    }

    public function apply(Request $req): never
    {
        Csrf::verify($req);
        $m = member();
        if (!$m) {
            $_SESSION['member_intended'] = '/affiliates#apply';
            Response::redirect('/signup');
        }
        if (setting('affiliates_enabled', '1') !== '1') {
            $this->back('Applications are closed at the moment.');
        }
        $existing = Affiliates::forUser((int) $m['id']);
        if ($existing && $existing['status'] !== 'rejected') {
            $this->back('You have already applied — your status is shown below.');
        }
        if (!RateLimiter::hit('affiliate-apply:' . $m['id'], 5, 86400)) {
            $this->back('Too many attempts — please try again tomorrow.');
        }
        $name = mb_substr(trim((string) $req->post('full_name', '')), 0, 120);
        $platform = in_array($req->post('platform'), self::PLATFORMS, true) ? (string) $req->post('platform') : '';
        $url = trim((string) $req->post('channel_url', ''));
        $audience = in_array($req->post('audience'), self::AUDIENCES, true) ? (string) $req->post('audience') : null;
        $errors = [];
        if ($name === '') {
            $errors['full_name'] = 'Enter your name or brand.';
        }
        if ($platform === '') {
            $errors['platform'] = 'Choose your main platform.';
        }
        if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $url) || strlen($url) > 255) {
            $errors['channel_url'] = 'Enter the full link to your channel or profile.';
        }
        if ($req->post('terms') !== '1') {
            $errors['terms'] = 'Please accept the affiliate terms.';
        }
        if ($errors) {
            Session::withErrors($errors, $_POST);
            Response::redirect('/affiliates#apply');
        }
        $row = ['full_name' => $name, 'platform' => $platform, 'channel_url' => $url, 'audience' => $audience,
            'message' => mb_substr(trim((string) $req->post('message', '')), 0, 2000) ?: null, 'status' => 'pending', 'commission_pct' => Affiliates::defaultPct()];
        if ($existing) {
            Database::update('affiliates', $row, 'id = :id', ['id' => $existing['id']]);
        } else {
            Database::insert('affiliates', $row + ['user_id' => $m['id']]);
        }
        Members::audit((int) $m['id'], 'affiliate_applied', $platform . ' ' . $url);
        Session::flash('success', 'Thanks! Your application was received. You will see your personal code and link in the terminal (Affiliate) once it is approved.');
        Response::redirect('/affiliates#apply');
    }

    /** /r/CODE — remembers the code for 60 days (it pre-fills checkout) and opens the pricing page. */
    public function track(Request $req): never
    {
        $code = Affiliates::normalizeCode((string) ($req->params['code'] ?? ''));
        $a = Affiliates::byCode($code);
        if ($a) {
            if (($_COOKIE[Affiliates::COOKIE] ?? '') !== $code) {
                Affiliates::trackClick($code);
            }
            setcookie(Affiliates::COOKIE, $code, ['expires' => time() + 60 * 86400, 'path' => '/', 'secure' => is_https(), 'httponly' => true, 'samesite' => 'Lax']);
        }
        Response::redirect('/pricing');
    }

    private function back(string $msg): never
    {
        Session::flash('error', $msg);
        Response::redirect('/affiliates#apply');
    }
}
