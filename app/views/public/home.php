<?php
/** @var array $sections */
use App\Core\View;

$known = ['hero', 'intro', 'about', 'services', 'benefits', 'stats', 'process', 'featured', 'testimonials', 'faq', 'cta', 'contact'];
$customDone = false;
foreach ($sections as $s) {
    if ($s['section_key'] === 'cta' && !$customDone) {
        foreach ($custom as $c) {
            echo View::partial('public/sections/custom', ['c' => $c]);
        }
        $customDone = true;
    }
    $key = in_array($s['section_key'], $known, true) ? $s['section_key'] : 'generic';
    echo View::partial('public/sections/' . $key, get_defined_vars() + ['s' => $s, 'items' => json_list($s['items']), 'opt' => json_list($s['options'])]);
}
if (!$customDone) {
    foreach ($custom as $c) {
        echo View::partial('public/sections/custom', ['c' => $c]);
    }
}
