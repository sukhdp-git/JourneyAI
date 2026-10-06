<?php
declare(strict_types=1);

namespace App\Core;

/** Writes to storage/logs (blocked from the web). Context values are scrubbed of secrets. */
final class Logger
{
    private const SECRET_KEYS = ['password', 'pass', 'smtp_password', 'token', 'csrf', 'secret', 'app_key'];

    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    private static function write(string $level, string $message, array $context): void
    {
        foreach ($context as $k => $v) {
            if (in_array(strtolower((string) $k), self::SECRET_KEYS, true)) {
                $context[$k] = '[redacted]';
            }
        }
        $line = sprintf("[%s] %s %s %s\n", gmdate('c'), $level, $message, $context ? json_encode($context, JSON_UNESCAPED_SLASHES) : '');
        $dir = STORAGE_PATH . '/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        @file_put_contents($dir . '/app-' . gmdate('Y-m') . '.log', $line, FILE_APPEND | LOCK_EX);
    }
}
