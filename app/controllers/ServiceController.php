<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Seo;
use App\Models\Content;

final class ServiceController extends Controller
{
    public function index(Request $req): never
    {
        Seo::breadcrumbs([['Home', '/'], ['Services', '/services']]);
        $this->render('services/index', [
            'services' => Content::services(),
            'process' => Content::process(),
            'custom' => Content::customSections('services'),
        ], ['title' => setting('seo_services_title') ?: 'Services', 'description' => setting('seo_services_description'), 'path' => '/services']);
    }

    public function show(Request $req): never
    {
        $s = Content::service($req->params['slug'] ?? '');
        if (!$s) {
            $this->notFound();
        }
        $faqs = array_values(array_filter(json_list($s['faqs']), fn ($f) => trim($f['question'] ?? '') !== ''));
        Seo::breadcrumbs([['Home', '/'], ['Services', '/services'], [$s['title'], '/services/' . $s['slug']]]);
        Seo::faq($faqs);
        $others = array_values(array_filter(Content::services(4), fn ($o) => (int) $o['id'] !== (int) $s['id']));
        $this->render('services/show', ['service' => $s, 'faqs' => $faqs, 'others' => array_slice($others, 0, 3)], [
            'title' => $s['meta_title'] ?: $s['title'],
            'description' => $s['meta_description'] ?: $s['short_description'],
            'image' => $s['og_image'] ?: ($s['hero_image'] ?: $s['thumbnail']),
            'path' => '/services/' . $s['slug'],
        ]);
    }
}
