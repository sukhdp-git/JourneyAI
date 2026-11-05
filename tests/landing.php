<?php
/**
 * Guards for the new landing page: it must tease, never reveal the members-only features, and its teaser images
 * must be blurred inside the file itself. Run from SSH (never via the web):  php tests/landing.php
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$root = dirname(__DIR__);
$fail = 0;
$ok = function (string $name, bool $pass, string $info = '') use (&$fail): void {
    $fail += $pass ? 0 : 1;
    printf("%s %s%s\n", $pass ? 'PASS' : 'FAIL', $name, $pass || $info === '' ? '' : '  → ' . $info);
};

$files = [
    'view' => $root . '/app/views/public/landing.php',
    'layout' => $root . '/app/views/public/layouts/landing.php',
    'css' => $root . '/public/assets/css/landing.css',
    'js' => $root . '/public/assets/js/landing.js',
];
foreach ($files as $k => $f) {
    $ok("$k file exists", is_file($f));
}
$text = '';
foreach ($files as $f) {
    $text .= strtolower((string) @file_get_contents($f)) . "\n";
}
foreach (['edge matrix', 'university', 'confessions', 'death clock', 'tilt breaker', 'roast', 'ai coach', 'boss fight', 'blow-up', 'ghost race', 'mind tab', 'certificate'] as $w) {
    $ok("page never names \"$w\"", !str_contains($text, $w));
}
foreach (['multi-broker', 'broker sync', 'sync your', 'automatically imports', 'guaranteed', 'signals'] as $w) {
    // "signals" appears once in the legal footer ("do not give ... tips or signals"); everything else must stay absent
    $n = substr_count($text, $w);
    $ok("page does not promise \"$w\"", $w === 'signals' ? $n <= 1 : $n === 0, (string) $n);
}
$ok('footer keeps the SEBI sentence', str_contains($text, 'not registered with sebi'));
$ok('footer keeps the risk sentence', str_contains($text, 'substantial risk of loss'));

$dir = $root . '/public/assets/images/landing';
$have = array_map('basename', glob($dir . '/*.webp') ?: []);
sort($have);
$ok('only generic screens are sharp, the rest are teasers', $have === ['dashboard.webp', 'logtrade.webp', 'tease-1.webp', 'tease-2.webp', 'tease-3.webp', 'tease-4.webp'], implode(',', $have));
foreach (glob($dir . '/tease-*.webp') ?: [] as $f) {
    [$w] = getimagesize($f);
    $ok(basename($f) . ' is small and pre-blurred', filesize($f) < 30000 && $w <= 1000, filesize($f) . ' bytes, ' . $w . ' px');
}
foreach ($files['view'] ? [(string) file_get_contents($files['view'])] : [] as $v) {
    preg_match_all("#images/landing/([a-z0-9\-]+)\.webp#", $v, $m);
    foreach (array_unique($m[1]) as $n) {
        $ok("image $n.webp exists", is_file("$dir/$n.webp"));
    }
}
echo $fail ? "\n$fail check(s) FAILED\n" : "\nAll landing checks passed.\n";
exit($fail ? 1 : 0);
