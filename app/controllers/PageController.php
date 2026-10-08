<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Seo;
use App\Models\Content;

final class PageController extends Controller
{
    /** Slugs that can never be used for CMS pages because a system route owns them. */
    public const RESERVED = ['services', 'learn', 'blog', 'contact', 'pricing', 'signup', 'logout', 'onboarding', 'forgot-password', 'reset-password', 'checkout', 'webhooks', 'auth', 'terminal', 'control-panel', 'setup', 'sitemap.xml', 'robots.txt', 'uploads', 'public', 'app', 'config', 'storage', 'vendor', 'routes', 'index', 'index.php', 'admin', 'api', 'pages', 'search', 'login', 'assets'];

    public function show(Request $req): never
    {
        $slug = $req->params['slug'] ?? '';
        $page = in_array($slug, self::RESERVED, true) ? null : Content::page($slug);
        if (!$page) {
            $this->notFound();
        }
        $blocks = array_values(array_filter(json_list($page['blocks']), fn ($b) => empty($b['hidden'])));
        $needs = array_column($blocks, 'type');
        Seo::breadcrumbs([['Home', '/'], [$page['title'], '/' . $page['slug']]]);
        $data = ['page' => $page, 'blocks' => $blocks, 'custom' => $page['slug'] === 'about' ? Content::customSections('about') : []];
        if (in_array('process', $needs, true)) {
            $data['process'] = Content::process();
        }
        if (in_array('faq', $needs, true)) {
            $data['faqs'] = Content::faqs();
            Seo::faq($data['faqs']);
        }
        if (in_array('services', $needs, true)) {
            $data['services'] = Content::services();
        }
        if (in_array('testimonials', $needs, true)) {
            $data['testimonials'] = Content::testimonials();
        }
        $this->render('page', $data, [
            'title' => $page['meta_title'] ?: $page['title'],
            'description' => $page['meta_description'] ?: ($page['hero_subtitle'] ?: excerpt($page['content'])),
            'image' => $page['og_image'] ?: $page['featured_image'],
            'canonical' => $page['canonical_url'] ?: null,
            'noindex' => (bool) $page['noindex'],
            'path' => '/' . $page['slug'],
        ]);
    }
}
