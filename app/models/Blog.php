<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Blog queries. A post is public when it is "published" (or "scheduled") and its publish time has passed,
 * so scheduled posts go live automatically without a cron job.
 */
final class Blog
{
    public const PUBLIC_WHERE = "p.status IN ('published','scheduled') AND p.published_at IS NOT NULL AND p.published_at <= UTC_TIMESTAMP()";
    private const SELECT = 'SELECT p.id, p.title, p.slug, p.excerpt, p.featured_image, p.published_at, p.is_featured, p.author_name,
        c.name AS category_name, c.slug AS category_slug, a.name AS admin_name
        FROM blog_posts p LEFT JOIN blog_categories c ON c.id = p.category_id LEFT JOIN admins a ON a.id = p.author_id';

    /** @return array{0: array, 1: int} [rows, total] */
    public static function list(array $f, int $limit, int $offset): array
    {
        $where = [self::PUBLIC_WHERE];
        $params = [];
        if (!empty($f['category_id'])) {
            $where[] = 'p.category_id = :cat';
            $params['cat'] = (int) $f['category_id'];
        }
        if (!empty($f['tag_id'])) {
            $where[] = 'EXISTS (SELECT 1 FROM blog_post_tags t WHERE t.post_id = p.id AND t.tag_id = :tag)';
            $params['tag'] = (int) $f['tag_id'];
        }
        if (!empty($f['q'])) {
            $where[] = '(p.title LIKE :q1 OR p.excerpt LIKE :q2 OR p.content LIKE :q3)';
            $like = '%' . addcslashes((string) $f['q'], '%_\\') . '%';
            $params += ['q1' => $like, 'q2' => $like, 'q3' => $like];
        }
        if (!empty($f['exclude'])) {
            $where[] = 'p.id <> :ex';
            $params['ex'] = (int) $f['exclude'];
        }
        $w = implode(' AND ', $where);
        $total = (int) Database::value("SELECT COUNT(*) FROM blog_posts p WHERE $w", $params);
        $rows = Database::all(self::SELECT . " WHERE $w ORDER BY p.published_at DESC, p.id DESC LIMIT :lim OFFSET :off", $params + ['lim' => $limit, 'off' => $offset]);
        return [$rows, $total];
    }

    public static function featured(int $limit = 3): array
    {
        return Database::all(self::SELECT . ' WHERE ' . self::PUBLIC_WHERE . ' AND p.is_featured = 1 ORDER BY p.published_at DESC LIMIT :lim', ['lim' => $limit]);
    }

    public static function latest(int $limit = 3): array
    {
        return Database::all(self::SELECT . ' WHERE ' . self::PUBLIC_WHERE . ' ORDER BY p.published_at DESC LIMIT :lim', ['lim' => $limit]);
    }

    public static function find(string $slug): ?array
    {
        return Database::one(
            'SELECT p.*, c.name AS category_name, c.slug AS category_slug, a.name AS admin_name
             FROM blog_posts p LEFT JOIN blog_categories c ON c.id = p.category_id LEFT JOIN admins a ON a.id = p.author_id
             WHERE p.slug = :s AND ' . self::PUBLIC_WHERE,
            ['s' => $slug],
        );
    }

    public static function related(array $post, int $limit = 3): array
    {
        $rows = [];
        if ($post['category_id']) {
            [$rows] = self::list(['category_id' => $post['category_id'], 'exclude' => $post['id']], $limit, 0);
        }
        if (count($rows) < $limit) {
            $ids = array_merge([$post['id']], array_column($rows, 'id'));
            $more = Database::all(self::SELECT . ' WHERE ' . self::PUBLIC_WHERE . ' AND p.id NOT IN (' . implode(',', array_map('intval', $ids)) . ') ORDER BY p.published_at DESC LIMIT :lim', ['lim' => $limit - count($rows)]);
            $rows = array_merge($rows, $more);
        }
        return $rows;
    }

    public static function tags(int $postId): array
    {
        return Database::all('SELECT t.name, t.slug FROM blog_tags t JOIN blog_post_tags pt ON pt.tag_id = t.id WHERE pt.post_id = :p ORDER BY t.name', ['p' => $postId]);
    }

    public static function categories(): array
    {
        return Database::all('SELECT c.name, c.slug, (SELECT COUNT(*) FROM blog_posts p WHERE p.category_id = c.id AND ' . self::PUBLIC_WHERE . ') AS posts FROM blog_categories c ORDER BY c.sort_order, c.name');
    }
}
