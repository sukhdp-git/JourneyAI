<?php
declare(strict_types=1);

namespace App\Core;

/** libsodium secretbox encryption for secrets at rest (e.g. the SMTP password), keyed by APP_KEY. */
final class Crypto
{
    private static function key(): string
    {
        if (!defined('APP_KEY') || strlen(APP_KEY) < 32) {
            throw new \RuntimeException('APP_KEY must be at least 32 characters');
        }
        return sodium_crypto_generichash(APP_KEY, '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
    }

    public static function encrypt(string $plain): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        return 'v1:' . base64_encode($nonce . sodium_crypto_secretbox($plain, $nonce, self::key()));
    }

    public static function decrypt(string $cipher): ?string
    {
        if (!str_starts_with($cipher, 'v1:')) {
            return null;
        }
        $raw = base64_decode(substr($cipher, 3), true);
        if ($raw === false || strlen($raw) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            return null;
        }
        $plain = sodium_crypto_secretbox_open(substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), self::key());
        return $plain === false ? null : $plain;
    }
}
