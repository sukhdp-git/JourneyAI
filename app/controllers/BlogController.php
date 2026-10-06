<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Seo;
use App\Models\Blog;

final class BlogController extends Controller
{
    private const PER_PAGE = 9;

    public function index(Request $req): never
    {
        $this->listing($req, [], '/blog', setting('blog_heading') ?: 'Blog', setting('seo_blog_title') ?: 'Blog', setting('seo_blog_description'));
    }

    public function category(Request $req): never
    {
        $cat = Database::one('SELECT * FROM blog_categories WHERE slug = :s', ['s' => $req->params['slug'] ?? '']);
        if (!$cat) {
            $this->notFound();
        }
        $this->listing($req, ['category_id' => $cat['id']], '/blog/category/' . $cat['slug'], $cat['name'], $cat['name'] . ' — Blog', $cat['description'] ?: '', $cat);
    }

    public function tag(Request $req): never
    {
        $tag = Database::one('SELECT * FROM blog_tags WHERE slug = :s', ['s' => $req->params['slug'] ?? '']);
        if (!$tag) {
            $this->notFound();
        }
        $this->listing($req, ['tag_id' => $tag['id']], '/blog/tag/' . $tag['slug'], '#' . $tag['name'], $tag['name'] . ' — Blog', '', null, $tag);
    }

    private function listing(Request $req, array $filters, string $base, string $heading, string $title, string $desc, ?array $category = null, ?array $tag = null): never
    {
        $page = max(1, (int) ($req->params['n'] ?? 1));
        if (isset($req->params['n']) && $page === 1) {
            \App\Core\Response::redirect($base, 301);
        }
        $q = mb_substr(trim((string) $req->query('q', '')), 0, 100);
        $filters['q'] = $q;
        [, $total] = Blog::list($filters, 1, 0);
        $pg = new Paginator($total, self::PER_PAGE, $page, $base . '/page/{n}', $base);
        if ($page > $pg->pages && $total > 0) {
            $this->notFound();
        }
        [$posts] = Blog::list($filters, self::PER_PAGE, $pg->offset);
        $featured = ($page === 1 && $q === '' && !$category && !$tag) ? Blog::featured(1) : [];
        if ($featured) {
            $posts = array_values(array_filter($posts, fn ($p) => (int) $p['id'] !== (int) $featured[0]['id']));
        }
        Seo::breadcrumbs(array_filter([['Home', '/'], ['Blog', '/blog'], $category ? [$category['name'], $base] : null, $tag ? ['#' . $tag['name'], $base] : null]));
        $this->render('blog/index', [
            'posts' => $posts, 'featured' => $featured[0] ?? null, 'pager' => $pg, 'q' => $q, 'heading' => $heading,
            'category' => $category, 'tag' => $tag, 'categories' => Blog::categories(), 'base' => $base,
        ], ['title' => $title . ($page > 1 ? " — Page $page" : ''), 'description' => $desc, 'path' => $page > 1 ? "$base/page/$page" : $base, 'noindex' => $q !== '']);
    }

    public function show(Request $req): never
    {
        $post = Blog::find($req->params['slug'] ?? '');
        if (!$post) {
            $this->notFound();
        }
        Database::query('UPDATE blog_posts SET views = views + 1 WHERE id = :id', ['id' => $post['id']]);
        $author = $post['author_name'] ?: ($post['admin_name'] ?: setting('site_name'));
        $image = $post['og_image'] ?: $post['featured_image'];
        Seo::breadcrumbs(array_filter([['Home', '/'], ['Blog', '/blog'], $post['category_slug'] ? [$post['category_name'], '/blog/category/' . $post['category_slug']] : null, [$post['title'], '/blog/' . $post['slug']]]));
        $article = [
            '@type' => 'Article', 'headline' => mb_substr($post['title'], 0, 110), 'datePublished' => gmdate('c', strtotime($post['published_at'])),
            'dateModified' => gmdate('c', strtotime($post['updated_at'])), 'author' => ['@type' => 'Organization', 'name' => $author],
            'publisher' => ['@type' => 'Organization', 'name' => setting('site_name')], 'mainEntityOfPage' => url('/blog/' . $post['slug']),
        ];
        if ($image) {
            $article['image'] = media_url($image);
        }
        Seo::add($article);
        $this->render('blog/show', [
            'post' => $post, 'author' => $author, 'tags' => Blog::tags((int) $post['id']), 'related' => Blog::related($post, 3),
        ], [
            'title' => $post['meta_title'] ?: $post['title'], 'description' => $post['meta_description'] ?: ($post['excerpt'] ?: excerpt($post['content'])),
            'image' => $image, 'type' => 'article', 'path' => '/blog/' . $post['slug'], 'published' => $post['published_at'], 'modified' => $post['updated_at'],
        ]);
    }
}
