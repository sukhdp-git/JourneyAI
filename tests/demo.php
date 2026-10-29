<?php
/**
 * Checks that the DEMO DATA journal still shows every feature with a meaningful example. Run from SSH (never via the web):
 *   php tests/demo.php
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../app/bootstrap.php';

use App\Trading\{DemoData, Edge, Mind, TradeMath};

$fail = 0;
$check = function (string $name, bool $ok, string $got = '') use (&$fail): void {
    $fail += $ok ? 0 : 1;
    printf("%s %-58s %s\n", $ok ? 'PASS' : 'FAIL', $name, $got);
};
$rows = [];
foreach (DemoData::trades('UTC') as $i => $t) {
    $c = TradeMath::compute($t['symbol'], $t['side'], $t['entry'], $t['exit'], $t['stop'], $t['lots'], 0, 'USD');
    $rows[] = ['id' => $i + 1, 'executed_at' => $t['executed_at'], 'closed_at' => $t['closed_at'], 'status' => 'CLOSED', 'symbol' => $t['symbol'], 'side' => $t['side'], 'pnl' => $c['pnl'],
        'rr' => $c['rr'], 'risk_amount' => $c['risk'], 'mistake_tag' => $t['mistake'], 'emotion' => $t['emotion'], 'rules_followed' => $t['rules'], 'lot_size' => $t['lots'], 'session' => null];
}
$sum = fn (array $a) => array_sum(array_column($a, 'pnl'));
$equity = DemoData::START_CAPITAL + 500 + $sum($rows);
$days = DemoData::businessDays('UTC');
$check('about 100+ trades over ~100 business days', count($rows) >= 100 && count($days) === 100, count($rows) . ' trades');
$check('last trade is on the last weekday before today', substr(end($rows)['executed_at'], 0, 10) === end($days), end($rows)['executed_at']);
$check('no trade on a weekend', !array_filter($rows, fn ($t) => (int) date('N', strtotime($t['executed_at'] . ' UTC')) > 5));
$check('all-time P&L is positive (the strategy works)', $sum($rows) > 0, number_format($sum($rows), 2));
$check('last 60 trades are negative (Death Clock ticks)', $sum(array_slice($rows, -60)) < 0, number_format($sum(array_slice($rows, -60)), 2));
$radar = Edge::ruinRadar($rows, $equity, 1.0, 0.10, 800);
$check('blow-up radar at −10% is between 15% and 60%', $radar['ok'] && $radar['current_p'] >= 0.15 && $radar['current_p'] <= 0.6, round($radar['current_p'] * 100, 1) . '%');
$mind = new Mind($rows, $equity, 'UTC', 1, 'USD');
$roast = $mind->roast('savage');
$check('Roast Mode has a roast for the last trading day', ($roast['kind'] ?? '') === 'roast', $roast['code'] ?? 'none');
$check('Death Clock is counting down', $mind->deathClock()['state'] === 'dying');
$g = $mind->ghost();
$check('the Ghost (rules followed) is ahead of the real account', ($g['gap'] ?? 0) > 0, $g['gap_fmt'] ?? '');
$codes = array_count_values(array_column($rows, 'mistake_tag'));
foreach (['REVENGE_TRADE', 'FOMO_ENTRY', 'MOVED_STOP', 'OVERSIZED', 'EARLY_EXIT'] as $k) {
    $check("has $k examples", ($codes[$k] ?? 0) > 0, (string) ($codes[$k] ?? 0));
}
$a = $mind->art(-1);
$check('Psych Art last week has trades', $a['trades'] > 0, $a['type']);
echo $fail ? "\n$fail check(s) failed\n" : "\nAll demo checks passed\n";
exit($fail ? 1 : 0);
