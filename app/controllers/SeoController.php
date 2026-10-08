<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Settings;
use App\Models\Blog;

final class SeoController
{
    public function sitemap(Request $req): never
    {
        $base = rtrim(Settings::get('seo_canonical_base') ?: BASE_URL, '/');
        $urls = [['/', null, '1.0'], ['/services', null, '0.8'], ['/learn', null, '0.8'], ['/blog', null, '0.8'], ['/contact', null, '0.6'], ['/pricing', null, '0.8'], ['/login', null, '0.3'], ['/signup', null, '0.5']];
        foreach (Database::all("SELECT slug, updated_at FROM services WHERE status = 'published' ORDER BY sort_order") as $r) {
            $urls[] = ['/services/' . $r['slug'], $r['updated_at'], '0.8'];
        }
        try {
            foreach (Database::all("SELECT slug, updated_at FROM learn_strategies WHERE status = 'published' ORDER BY sort_order") as $r) {
                $urls[] = ['/learn/' . $r['slug'], $r['updated_at'], '0.7'];
            }
        } catch (\PDOException) {
        }
        foreach (Database::all("SELECT slug, updated_at FROM pages WHERE status = 'published' AND in_sitemap = 1 AND noindex = 0 ORDER BY sort_order") as $r) {
            $urls[] = ['/' . $r['slug'], $r['updated_at'], '0.5'];
        }
        foreach (Database::all('SELECT p.slug, p.updated_at FROM blog_posts p WHERE ' . Blog::PUBLIC_WHERE . ' ORDER BY p.published_at DESC LIMIT 5000') as $r) {
            $urls[] = ['/blog/' . $r['slug'], $r['updated_at'], '0.6'];
        }
        foreach (Database::all('SELECT c.slug FROM blog_categories c WHERE EXISTS (SELECT 1 FROM blog_posts p WHERE p.category_id = c.id AND ' . Blog::PUBLIC_WHERE . ')') as $r) {
            $urls[] = ['/blog/category/' . $r['slug'], null, '0.4'];
        }
        header('Content-Type: application/xml; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as [$path, $mod, $prio]) {
            echo '  <url><loc>' . e($base . ($path === '/' ? '/' : $path)) . '</loc>' . ($mod ? '<lastmod>' . gmdate('Y-m-d', strtotime($mod)) . '</lastmod>' : '') . '<priority>' . $prio . "</priority></url>\n";
        }
        echo '</urlset>';
        exit;
    }

    public function robots(Request $req): never
    {
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        $base = rtrim(Settings::get('seo_canonical_base') ?: BASE_URL, '/');
        if (!Settings::bool('seo_allow_indexing', true)) {
            echo "User-agent: *\nDisallow: /\n";
            exit;
        }
        echo "User-agent: *\nDisallow: /" . ADMIN_PREFIX . "/\nDisallow: /setup\nDisallow: /terminal\nDisallow: /checkout\nDisallow: /onboarding\nDisallow: /api/\nDisallow: /webhooks/\nDisallow: /blog?q=\n";
        $extra = trim(Settings::get('seo_robots_extra'));
        if ($extra !== '') {
            echo preg_replace('/[^\x20-\x7E\n]/', '', $extra) . "\n";
        }
        echo "\nSitemap: $base/sitemap.xml\n";
        exit;
    }
}
