<?php
declare(strict_types=1);

namespace App\Core;

/** Fixed-window limiter stored in MySQL (works on shared hosting without Redis). */
final class RateLimiter
{
    /** Returns true when the action is allowed and records the hit. */
    public static function hit(string $key, int $max, int $windowSeconds): bool
    {
        $k = hash('sha256', $key);
        $row = Database::one('SELECT attempts, reset_at FROM rate_limits WHERE `key` = :k', ['k' => $k]);
        $now = time();
        if (!$row || strtotime($row['reset_at']) <= $now) {
            Database::query(
                'INSERT INTO rate_limits (`key`, attempts, reset_at) VALUES (:k, 1, FROM_UNIXTIME(:r))
                 ON DUPLICATE KEY UPDATE attempts = 1, reset_at = FROM_UNIXTIME(:r2)',
                ['k' => $k, 'r' => $now + $windowSeconds, 'r2' => $now + $windowSeconds],
            );
            return true;
        }
        if ((int) $row['attempts'] >= $max) {
            return false;
        }
        Database::query('UPDATE rate_limits SET attempts = attempts + 1 WHERE `key` = :k', ['k' => $k]);
        return true;
    }

    public static function tooMany(string $key, int $max): bool
    {
        $row = Database::one('SELECT attempts, reset_at FROM rate_limits WHERE `key` = :k', ['k' => hash('sha256', $key)]);
        return $row && strtotime($row['reset_at']) > time() && (int) $row['attempts'] >= $max;
    }

    public static function clear(string $key): void
    {
        Database::query('DELETE FROM rate_limits WHERE `key` = :k', ['k' => hash('sha256', $key)]);
    }
}
