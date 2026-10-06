<?php
declare(strict_types=1);

namespace App\Trading;

/**
 * "Continue with Google" — OAuth 2.0 authorization-code flow with OpenID Connect, state, nonce and PKCE.
 * The client secret stays on the server (encrypted in settings or set in config/config.php).
 * Redirect URI to register in Google Cloud: {BASE_URL}/auth/google/callback
 */
final class GoogleAuth
{
    public static function clientId(): string
    {
        return defined('GOOGLE_CLIENT_ID') && GOOGLE_CLIENT_ID !== '' ? GOOGLE_CLIENT_ID : \App\Core\Settings::get('google_client_id');
    }

    public static function configured(): bool
    {
        return self::clientId() !== '' && secret_setting('google_client_secret') !== '';
    }

    public static function redirectUri(): string
    {
        return url('/auth/google/callback');
    }

    public static function authUrl(): string
    {
        $state = bin2hex(random_bytes(16));
        $nonce = bin2hex(random_bytes(16));
        $verifier = rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
        $_SESSION['g_oauth'] = ['state' => $state, 'nonce' => $nonce, 'verifier' => $verifier, 'at' => time()];
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id' => self::clientId(), 'redirect_uri' => self::redirectUri(), 'response_type' => 'code', 'scope' => 'openid email profile',
            'state' => $state, 'nonce' => $nonce, 'prompt' => 'select_account',
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='), 'code_challenge_method' => 'S256',
        ]);
    }

    /**
     * Validates the callback and returns verified identity [sub, email, name, picture].
     * @throws \RuntimeException with a safe message
     */
    public static function handleCallback(array $query): array
    {
        $saved = $_SESSION['g_oauth'] ?? null;
        unset($_SESSION['g_oauth']);
        if (isset($query['error'])) {
            throw new \RuntimeException($query['error'] === 'access_denied' ? 'Google sign-in was cancelled.' : 'Google returned an error. Please try again.');
        }
        if (!$saved || time() - $saved['at'] > 600 || !hash_equals($saved['state'], (string) ($query['state'] ?? ''))) {
            throw new \RuntimeException('Your sign-in session expired. Please try again.');
        }
        $code = (string) ($query['code'] ?? '');
        if ($code === '') {
            throw new \RuntimeException('Google did not return an authorization code.');
        }
        $token = self::post('https://oauth2.googleapis.com/token', [
            'code' => $code, 'client_id' => self::clientId(), 'client_secret' => secret_setting('google_client_secret'),
            'redirect_uri' => self::redirectUri(), 'grant_type' => 'authorization_code', 'code_verifier' => $saved['verifier'],
        ]);
        if (empty($token['access_token']) || empty($token['id_token'])) {
            throw new \RuntimeException('Google sign-in failed: the authorization code was rejected. Check the OAuth client ID, secret and redirect URI.');
        }
        // The ID token came directly from Google's token endpoint over TLS, so its claims can be read without
        // re-verifying the signature (OIDC Core 3.1.3.7); audience, issuer, expiry and nonce are still checked.
        $parts = explode('.', $token['id_token']);
        $claims = json_decode((string) base64_decode(strtr($parts[1] ?? '', '-_', '+/')), true) ?: [];
        if (($claims['aud'] ?? '') !== self::clientId() || !in_array($claims['iss'] ?? '', ['https://accounts.google.com', 'accounts.google.com'], true)
            || ($claims['exp'] ?? 0) < time() || !hash_equals($saved['nonce'], (string) ($claims['nonce'] ?? ''))) {
            throw new \RuntimeException('Google sign-in could not be verified. Please try again.');
        }
        $info = self::get('https://openidconnect.googleapis.com/v1/userinfo', $token['access_token']);
        if (($info['sub'] ?? '') !== ($claims['sub'] ?? null)) {
            throw new \RuntimeException('Google sign-in could not be verified.');
        }
        if (empty($info['email']) || empty($info['email_verified'])) {
            throw new \RuntimeException('Your Google account email is not verified.');
        }
        return ['sub' => (string) $info['sub'], 'email' => strtolower((string) $info['email']), 'name' => (string) ($info['name'] ?? strstr($info['email'], '@', true)), 'picture' => (string) ($info['picture'] ?? '')];
    }

    private static function post(string $url, array $fields): array
    {
        return self::curl($url, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($fields), CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json']]);
    }

    private static function get(string $url, string $bearer): array
    {
        return self::curl($url, [CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $bearer, 'Accept: application/json']]);
    }

    private static function curl(string $url, array $opts): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, $opts + [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_CONNECTTIMEOUT => 8]);
        $body = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($body === false) {
            \App\Core\Logger::error('Google OAuth request failed: ' . $err);
            throw new \RuntimeException('Could not reach Google. Please try again.');
        }
        return json_decode((string) $body, true) ?: [];
    }
}
