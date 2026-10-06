<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Activity;
use App\Core\Auth;
use App\Core\Crypto;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Core\Settings;
use App\Trading\AiCoach;
use App\Trading\GoogleAuth;
use App\Trading\MarketData;
use App\Trading\Payments;

/**
 * Google sign-in, payments, AI and market-data keys, plus member sign-up options.
 * Secret values are encrypted at rest (libsodium, APP_KEY) and never rendered back into the page.
 */
final class IntegrationsController extends AdminController
{
    public const SECRETS = ['google_client_secret', 'razorpay_key_secret', 'razorpay_webhook_secret', 'stripe_secret_key', 'stripe_webhook_secret', 'ai_api_key', 'market_data_api_key'];
    private const PLAIN = ['google_client_id' => 200, 'razorpay_key_id' => 100, 'ai_model' => 80, 'payment_currency_note' => 300, 'free_plan_name' => 60, 'free_plan_features' => 2000];

    public function show(Request $req): never
    {
        Auth::authorize('integrations');
        $saved = [];
        foreach (self::SECRETS as $k) {
            $saved[$k] = Settings::get($k) !== '';
        }
        $locked = ['google_client_id' => defined('GOOGLE_CLIENT_ID') && GOOGLE_CLIENT_ID !== '', 'google_client_secret' => defined('GOOGLE_CLIENT_SECRET') && GOOGLE_CLIENT_SECRET !== ''];
        $this->render('integrations', [
            'saved' => $saved, 'locked' => $locked,
            'status' => ['google' => GoogleAuth::configured(), 'gateway' => Payments::gateway(), 'ai' => AiCoach::configured(), 'market' => MarketData::configured()],
            'redirectUri' => GoogleAuth::redirectUri(),
        ], 'Integrations', [['Integrations', null]]);
    }

    public function save(Request $req): never
    {
        Auth::authorize('integrations');
        $errors = [];
        $values = [];
        foreach (self::PLAIN as $k => $max) {
            $v = trim((string) ($_POST[$k] ?? ''));
            if (mb_strlen($v) > $max) {
                $errors[$k] = 'Too long.';
            }
            $values[$k] = str_replace("\r\n", "\n", $v);
        }
        if ($values['google_client_id'] !== '' && !preg_match('/^[0-9]+-[a-z0-9]+\.apps\.googleusercontent\.com$/', $values['google_client_id'])) {
            $errors['google_client_id'] = 'This does not look like a Google OAuth client ID (…apps.googleusercontent.com).';
        }
        if ($values['razorpay_key_id'] !== '' && !preg_match('/^rzp_(test|live)_[A-Za-z0-9]+$/', $values['razorpay_key_id'])) {
            $errors['razorpay_key_id'] = 'Razorpay key IDs start with rzp_test_ or rzp_live_.';
        }
        if ($values['ai_model'] !== '' && !preg_match('/^[A-Za-z0-9._:-]{2,80}$/', $values['ai_model'])) {
            $errors['ai_model'] = 'Use the model ID only, e.g. claude-opus-5-5.';
        }
        $values['payment_gateway'] = in_array($_POST['payment_gateway'] ?? '', ['none', 'razorpay', 'stripe'], true) ? $_POST['payment_gateway'] : 'none';
        $values['ai_provider'] = in_array($_POST['ai_provider'] ?? '', ['none', 'anthropic', 'gemini'], true) ? $_POST['ai_provider'] : 'none';
        $values['signup_enabled'] = ($_POST['signup_enabled'] ?? '') === '1' ? '1' : '0';
        $values['email_signup_enabled'] = ($_POST['email_signup_enabled'] ?? '') === '1' ? '1' : '0';
        $limit = $_POST['free_ai_daily_limit'] ?? '';
        if (!ctype_digit((string) $limit) || (int) $limit > 1000) {
            $errors['free_ai_daily_limit'] = 'Enter 0–1000.';
        }
        $values['free_ai_daily_limit'] = (string) (int) $limit;
        $changed = [];
        foreach (self::SECRETS as $k) {
            $v = trim((string) ($_POST[$k] ?? ''));
            if (($_POST['clear_' . $k] ?? '') === '1') {
                $values[$k] = '';
                $changed[] = $k . ' (removed)';
            } elseif ($v !== '') {
                if (mb_strlen($v) > 500 || preg_match('/\s/', $v)) {
                    $errors[$k] = 'Paste the key without spaces.';
                    continue;
                }
                if ($k === 'stripe_secret_key' && !preg_match('/^(sk|rk)_(test|live)_/', $v)) {
                    $errors[$k] = 'Stripe secret keys start with sk_test_, sk_live_ or rk_.';
                    continue;
                }
                if ($k === 'stripe_webhook_secret' && !str_starts_with($v, 'whsec_')) {
                    $errors[$k] = 'Stripe webhook signing secrets start with whsec_.';
                    continue;
                }
                $values[$k] = Crypto::encrypt($v);
                $changed[] = $k;
            }
        }
        if ($errors) {
            $this->fail($errors, '/' . ADMIN_PREFIX . '/integrations');
        }
        Settings::save($values, 'integrations');
        Activity::log('update', 'integrations', null, $changed ? 'Updated keys: ' . implode(', ', $changed) : 'Updated integration options');
        $this->back(admin_url('integrations'), 'success', 'Integrations saved.' . ($changed ? ' Secret keys are stored encrypted.' : ''));
    }

    /** Live "Test connection" using the saved (decrypted server-side) keys. Returns JSON. */
    public function test(Request $req): never
    {
        Auth::authorize('integrations');
        if (!RateLimiter::hit('integration-test:' . Auth::id(), 20, 600)) {
            Response::json(['ok' => false, 'message' => 'Too many tests. Wait a few minutes.'], 429);
        }
        $which = (string) $req->post('which');
        $r = match ($which) {
            'ai' => setting('ai_provider', 'none') === 'none' || secret_setting('ai_api_key') === ''
                ? ['ok' => false, 'message' => 'Choose a provider and save an API key first.']
                : AiCoach::test(setting('ai_provider'), secret_setting('ai_api_key'), trim(setting('ai_model'))),
            'market' => secret_setting('market_data_api_key') === '' ? ['ok' => false, 'message' => 'Save a Twelve Data API key first.'] : MarketData::test(secret_setting('market_data_api_key')),
            'razorpay' => setting('razorpay_key_id') === '' || secret_setting('razorpay_key_secret') === '' ? ['ok' => false, 'message' => 'Save the Razorpay key ID and key secret first.'] : Payments::test('razorpay', setting('razorpay_key_id'), secret_setting('razorpay_key_secret')),
            'stripe' => secret_setting('stripe_secret_key') === '' ? ['ok' => false, 'message' => 'Save the Stripe secret key first.'] : Payments::test('stripe', secret_setting('stripe_secret_key')),
            'google' => GoogleAuth::configured() ? ['ok' => true, 'message' => 'Client ID and secret are saved. Use “Continue with Google” on /login to complete a real sign-in test.'] : ['ok' => false, 'message' => 'Save the Google client ID and client secret first.'],
            default => ['ok' => false, 'message' => 'Unknown integration.'],
        };
        Activity::log('test', 'integrations', null, $which . ': ' . ($r['ok'] ? 'ok' : 'failed'));
        Response::json(['ok' => $r['ok'], 'message' => $r['message']]);
    }
}
