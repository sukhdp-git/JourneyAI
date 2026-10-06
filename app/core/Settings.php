<?php
declare(strict_types=1);

namespace App\Core;

/** Key/value website settings stored in the `settings` table, cached per request. */
final class Settings
{
    private static ?array $cache = null;

    public static function all(): array
    {
        if (self::$cache === null) {
            self::$cache = [];
            if (APP_CONFIGURED) {
                try {
                    foreach (Database::all('SELECT `key`, `value` FROM settings') as $r) {
                        self::$cache[$r['key']] = $r['value'];
                    }
                } catch (\Throwable $e) {
                    Logger::error('Settings load failed: ' . $e->getMessage());
                }
            }
        }
        return self::$cache;
    }

    public static function get(string $key, string $default = ''): string
    {
        return (string) (self::all()[$key] ?? $default);
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $v = self::all()[$key] ?? null;
        return $v === null ? $default : $v === '1';
    }

    /** Saves many settings in one transaction. Keys must come from code-defined forms. */
    public static function save(array $values, string $group = 'general'): void
    {
        Database::transaction(function () use ($values, $group) {
            foreach ($values as $k => $v) {
                Database::query(
                    'INSERT INTO settings (`key`, `value`, `group_name`, updated_at) VALUES (:k, :v, :g, NOW())
                     ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), updated_at = NOW()',
                    ['k' => $k, 'v' => is_bool($v) ? ($v ? '1' : '0') : (string) $v, 'g' => $group],
                );
            }
        });
        self::$cache = null;
    }

    public static function flush(): void
    {
        self::$cache = null;
    }
}
