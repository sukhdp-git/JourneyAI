<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Seo;
use App\Models\Learn;

/** Public Learning section: the institutional intraday strategy playbook. */
final class LearnController extends Controller
{
    public function index(Request $req): never
    {
        Seo::breadcrumbs([['Home', '/'], ['Learn', '/learn']]);
        $all = Learn::all();
        $this->render('learn/index', [
            'free' => array_values(array_filter($all, fn ($s) => (int) $s['is_free'] === 1)),
            'locked' => array_values(array_filter($all, fn ($s) => (int) $s['is_free'] !== 1)),
            'total' => count($all),
            'member' => member(),
        ], [
            'title' => setting('seo_learn_title') ?: 'Learn: intraday trading strategies',
            'description' => setting('seo_learn_description'),
            'path' => '/learn',
        ]);
    }

    public function show(Request $req): never
    {
        $s = Learn::find($req->params['slug'] ?? '');
        if (!$s) {
            $this->notFound();
        }
        $member = member();
        $all = Learn::all();
        if ((int) $s['is_free'] !== 1) {
            if ($member) {
                Response::redirect('/terminal/university/' . $s['slug']);
            }
            Seo::breadcrumbs([['Home', '/'], ['Learn', '/learn'], [$s['short_title'] ?: $s['title'], '/learn/' . $s['slug']]]);
            $this->render('learn/locked', ['strategy' => $s, 'total' => count($all)], [
                'title' => $s['meta_title'] ?: $s['title'], 'description' => $s['meta_description'] ?: $s['summary'], 'path' => '/learn/' . $s['slug'], 'noindex' => true,
            ]);
        }
        $free = array_values(array_filter($all, fn ($o) => (int) $o['is_free'] === 1));
        $pos = 0;
        foreach ($free as $i => $o) {
            if ((int) $o['id'] === (int) $s['id']) {
                $pos = $i;
            }
        }
        $num = 1;
        foreach ($all as $i => $o) {
            if ((int) $o['id'] === (int) $s['id']) {
                $num = $i + 1;
            }
        }
        Seo::breadcrumbs([['Home', '/'], ['Learn', '/learn'], [$s['short_title'] ?: $s['title'], '/learn/' . $s['slug']]]);
        $this->render('learn/show', [
            'strategy' => $s, 'number' => $num, 'total' => count($all),
            'prev' => $pos > 0 ? $free[$pos - 1] : null,
            'next' => $pos < count($free) - 1 ? $free[$pos + 1] : null,
            'related' => [],
            'member' => $member,
        ], [
            'title' => $s['meta_title'] ?: $s['title'],
            'description' => $s['meta_description'] ?: $s['summary'],
            'image' => $s['og_image'] ?: null,
            'path' => '/learn/' . $s['slug'],
        ]);
    }
}
