<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Read-side queries for the public Learning playbook (table `learn_strategies`). */
final class Learn
{
    public static function all(): array
    {
        try {
            return Database::all("SELECT * FROM learn_strategies WHERE status = 'published' ORDER BY sort_order, id");
        } catch (\PDOException) {
            return [];
        }
    }

    public static function find(string $slug): ?array
    {
        try {
            return Database::one("SELECT * FROM learn_strategies WHERE slug = :s AND status = 'published'", ['s' => $slug]);
        } catch (\PDOException) {
            return null;
        }
    }

    /** Splits a one-item-per-line text column into a clean list. */
    public static function lines(?string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $text)), 'strlen'));
    }
}
