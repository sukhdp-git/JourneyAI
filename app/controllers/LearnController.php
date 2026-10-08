<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Seo;
use App\Models\Learn;
use App\Trading\Domain;

/** Public Learning section: the institutional intraday strategy playbook. */
final class LearnController extends Controller
{
    public function index(Request $req): never
    {
        Seo::breadcrumbs([['Home', '/'], ['Learn', '/learn']]);
        $all = Learn::all();
        $styles = [];
        foreach ($all as $s) {
            if ($s['style'] && isset(Domain::STRATEGY_STYLES[$s['style']])) {
                $styles[$s['style']] = ($styles[$s['style']] ?? 0) + 1;
            }
        }
        $style = isset($styles[(string) $req->query('style')]) ? (string) $req->query('style') : '';
        $this->render('learn/index', [
            'all' => $all,
            'strategies' => $style ? array_values(array_filter($all, fn ($s) => $s['style'] === $style)) : $all,
            'styles' => $styles,
            'style' => $style,
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
        $all = Learn::all();
        $pos = 0;
        foreach ($all as $i => $o) {
            if ((int) $o['id'] === (int) $s['id']) {
                $pos = $i;
            }
        }
        $count = count($all);
        Seo::breadcrumbs([['Home', '/'], ['Learn', '/learn'], [$s['short_title'] ?: $s['title'], '/learn/' . $s['slug']]]);
        $this->render('learn/show', [
            'strategy' => $s,
            'number' => $pos + 1,
            'prev' => $count > 1 && $pos > 0 ? $all[$pos - 1] : null,
            'next' => $count > 1 && $pos < $count - 1 ? $all[$pos + 1] : null,
            'related' => array_slice(array_values(array_filter($all, fn ($o) => (int) $o['id'] !== (int) $s['id'] && $o['style'] === $s['style'])), 0, 3),
            'member' => member(),
        ], [
            'title' => $s['meta_title'] ?: $s['title'],
            'description' => $s['meta_description'] ?: $s['summary'],
            'image' => $s['og_image'] ?: null,
            'path' => '/learn/' . $s['slug'],
        ]);
    }
}
