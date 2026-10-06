<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Read-side queries for published website content. */
final class Content
{
    public static function services(int $limit = 100, bool $featuredOnly = false): array
    {
        return Database::all(
            'SELECT id, title, slug, icon, thumbnail, short_description FROM services WHERE status = \'published\'' . ($featuredOnly ? ' AND is_featured = 1' : '') . ' ORDER BY sort_order, id LIMIT :lim',
            ['lim' => $limit],
        );
    }

    public static function service(string $slug): ?array
    {
        return Database::one('SELECT * FROM services WHERE slug = :s AND status = \'published\'', ['s' => $slug]);
    }

    public static function page(string $slug): ?array
    {
        return Database::one('SELECT * FROM pages WHERE slug = :s AND status = \'published\'', ['s' => $slug]);
    }

    public static function homeSections(): array
    {
        return Database::all('SELECT * FROM homepage_sections WHERE is_enabled = 1 ORDER BY sort_order, id');
    }

    public static function testimonials(int $limit = 12): array
    {
        return Database::all('SELECT * FROM testimonials WHERE status = \'published\' ORDER BY sort_order, id LIMIT :lim', ['lim' => $limit]);
    }

    public static function faqs(string $category = '', int $limit = 50): array
    {
        if ($category !== '') {
            return Database::all('SELECT question, answer FROM faqs WHERE status = \'published\' AND category = :c ORDER BY sort_order, id LIMIT :lim', ['c' => $category, 'lim' => $limit]);
        }
        return Database::all('SELECT question, answer FROM faqs WHERE status = \'published\' ORDER BY sort_order, id LIMIT :lim', ['lim' => $limit]);
    }

    public static function process(): array
    {
        return Database::all('SELECT * FROM process_steps WHERE status = \'published\' ORDER BY sort_order, id');
    }

    public static function customSections(string $placement): array
    {
        return Database::all('SELECT * FROM custom_sections WHERE status = \'published\' AND placement = :p ORDER BY sort_order, id', ['p' => $placement]);
    }
}
