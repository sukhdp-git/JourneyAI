<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Seo;
use App\Models\Blog;
use App\Models\Content;

final class HomeController extends Controller
{
    public function index(Request $req): never
    {
        $sections = Content::homeSections();
        $data = ['sections' => $sections];
        foreach ($sections as $s) {
            $opt = json_list($s['options']);
            $limit = max(1, min(24, (int) ($opt['limit'] ?? 6)));
            match ($s['section_key']) {
                'services' => $data['services'] = Content::services($limit, true),
                'process' => $data['process'] = Content::process(),
                'featured' => $data['posts'] = Blog::featured($limit) ?: Blog::latest($limit),
                'testimonials' => $data['testimonials'] = Content::testimonials($limit),
                'faq' => $data['faqs'] = Content::faqs((string) ($opt['category'] ?? ''), $limit),
                default => null,
            };
        }
        $data['custom'] = Content::customSections('home');
        if (!empty($data['faqs'])) {
            Seo::faq($data['faqs']);
        }
        $this->render('home', $data, [
            'title' => setting('seo_home_title') ?: (setting('seo_default_title') ?: setting('site_name')),
            'raw_title' => true,
            'description' => setting('seo_home_description') ?: setting('seo_default_description'),
            'path' => '/',
        ]);
    }
}
