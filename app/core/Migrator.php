<?php
declare(strict_types=1);

namespace App\Core;

use App\Controllers\SetupController;

/**
 * Applies database-updated.sql automatically after an update. The migration only adds columns, tables and
 * rows (never drops or overwrites data) and is safe to run repeatedly; `schema_version` records completion.
 */
final class Migrator
{
    public const VERSION = 3;

    public static function ensure(): void
    {
        try {
            if ((int) Settings::get('schema_version', '0') >= self::VERSION) {
                return;
            }
            $file = ROOT_PATH . '/database-updated.sql';
            if (!is_file($file)) {
                return;
            }
            SetupController::importSql(Database::pdo(), (string) file_get_contents($file));
            Settings::flush();
            Logger::info('Database migrated', ['version' => self::VERSION]);
        } catch (\Throwable $e) {
            Logger::error('Automatic database update failed — import database-updated.sql with phpMyAdmin', ['error' => mb_substr($e->getMessage(), 0, 300)]);
        }
    }
}
