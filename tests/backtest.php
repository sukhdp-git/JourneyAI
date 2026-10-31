<?php
/**
 * Backtester checks: indicators, fills (next open, stop-first, gaps, spread, break-even), statistics, CSV formats, resampling.
 * Run from SSH (never via the web):  php tests/backtest.php
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../app/bootstrap.php';

use App\Trading\Backtest as BT;
use App\Trading\BacktestData as BD;

$fail = 0;
$ok = function (string $name, bool $pass, string $info = '') use (&$fail): void {
    $fail += $pass ? 0 : 1;
    printf("%s %s%s\n", $pass ? 'PASS' : 'FAIL', $name, $pass || $info === '' ? '' : '  → ' . $info);
};
$near = fn ($a, $b, $eps = 1e-6) => $a !== null && abs($a - $b) <= $eps;
$T0 = 1704067200;   // 2024-01-01 00:00 UTC (a Monday)

// ---------------------------------------------------------------- indicators
$s = BT::sma([1, 2, 3, 4, 5], 3);
$ok('SMA 3', $s[0] === null && $s[1] === null && $near($s[2], 2) && $near($s[4], 4));
$e = BT::ema([1, 2, 3, 4, 5, 6], 3);
$ok('EMA 3 seeded with SMA', $e[1] === null && $near($e[2], 2) && $near($e[3], 3) && $near($e[5], 5), json_encode($e));
$up = BT::rsi(range(1, 30), 14);
$ok('RSI = 100 when price only rises', $near($up[29], 100));
$alt = [];
for ($i = 0; $i < 60; $i++) { $alt[] = 100 + ($i % 2); }
$ok('RSI ≈ 50 when gains equal losses', abs(BT::rsi($alt, 14)[59] - 50) < 2.0, (string) BT::rsi($alt, 14)[59]);
// RSI cross-check against a plain re-implementation of Wilder's formula
$px = [44.34, 44.09, 44.15, 43.61, 44.33, 44.83, 45.10, 45.42, 45.84, 46.08, 45.89, 46.03, 45.61, 46.28, 46.28, 46.00, 46.03, 46.41, 46.22, 45.64];
$g = $l = 0;
for ($i = 1; $i <= 14; $i++) { $dd = $px[$i] - $px[$i - 1]; $g += max($dd, 0); $l += max(-$dd, 0); }
$g /= 14; $l /= 14;
for ($i = 15; $i < count($px); $i++) { $dd = $px[$i] - $px[$i - 1]; $g = ($g * 13 + max($dd, 0)) / 14; $l = ($l * 13 + max(-$dd, 0)) / 14; }
$ok('RSI matches Wilder', $near(BT::rsi($px, 14)[19], 100 - 100 / (1 + $g / $l), 1e-9));
$ok('RSI 14 of the classic example ≈ 70.5', abs(BT::rsi($px, 14)[14] - 70.5) < 0.2, (string) BT::rsi($px, 14)[14]);

$flat = [];
for ($i = 0; $i < 80; $i++) { $flat[] = [$T0 + $i * 900, 100, 101, 99, 100]; }
$ok('ATR of constant 2-point candles = 2', $near((new BT($flat))->atr(14)[40], 2));
$hhBars = [];
foreach ([10, 12, 11, 15, 13, 14] as $i => $h) { $hhBars[] = [$T0 + $i * 900, $h - 1, $h, $h - 2, $h - 0.5]; }
$hh = (new BT($hhBars))->series(['k' => 'hh', 'n' => 3]);
$ok('Highest high uses the bars BEFORE the current one', $hh[2] === null && $near($hh[3], 12) && $near($hh[4], 15) && $near($hh[5], 15), json_encode($hh));

// ---------------------------------------------------------------- simulation
/** 100 flat candles at 100, then the given [o, h, l, c] candles from index 60. */
$mk = function (array $from60) use ($T0): array {
    $b = [];
    for ($i = 0; $i < 100; $i++) { $b[$i] = [$T0 + $i * 900, 100.0, 100.5, 99.5, 100.0]; }
    foreach ($from60 as $k => $c) { $b[60 + $k] = [$T0 + (60 + $k) * 900, ...array_map('floatval', $c)]; }
    return array_values($b);
};
$rules = function (array $over = []): array {
    [$r] = BT::normalize(array_replace_recursive([
        'side' => 'long', 'long' => [['a' => ['k' => 'close'], 'op' => 'crosses_above', 'b' => ['k' => 'value', 'v' => 103]]],
        'exit' => ['sl' => ['type' => 'points', 'value' => 2], 'tp' => ['type' => 'r', 'value' => 2]],
        'risk' => ['balance' => 10000, 'risk_pct' => 1, 'spread' => 0],
    ], $over));
    return $r;
};
// signal on candle 60 close (104 > 103), entry at candle 61 open (104), target 108 hit on candle 62
$res = (new BT($mk([[100, 104.5, 99.8, 104], [104, 105, 103, 104.5], [104.5, 108.5, 104, 108]])))->run($rules());
$t = $res['trades'][0] ?? null;
$ok('Entry at the NEXT candle open (no look-ahead)', $t && $t['entry_ts'] === $T0 + 61 * 900 && $near($t['entry'], 104), json_encode($t));
$ok('Take profit = +2R', $t && $t['reason'] === 'Take profit' && $near($t['r'], 2) && $near($t['exit'], 108));
$ok('Balance compounds with 1% risk', $near($res['stats']['end'], 10200, 0.01));

$res = (new BT($mk([[100, 104.5, 99.8, 104], [104, 105, 103, 104.5], [104.5, 109, 101, 105]])))->run($rules());
$ok('Stop counts first when one candle hits both', ($res['trades'][0]['r'] ?? 0) === -1.0 && $res['trades'][0]['reason'] === 'Stop loss', json_encode($res['trades'][0] ?? null));

$res = (new BT($mk([[100, 104.5, 99.8, 104], [104, 105, 103, 104.5], [99, 99.5, 98, 99]])))->run($rules());
$ok('A gap through the stop fills at the open (worse than −1R)', $near($res['trades'][0]['exit'] ?? 0, 99) && $near($res['trades'][0]['r'], -2.5), json_encode($res['trades'][0] ?? null));

$res = (new BT($mk([[100, 104.5, 99.8, 104], [104, 105, 103, 104.5], [104.5, 108.5, 104, 108]])))->run($rules(['risk' => ['spread' => 0.5]]));
$ok('Long pays the spread on entry', $near($res['trades'][0]['entry'] ?? 0, 104.5) && $near($res['trades'][0]['r'], 2), json_encode($res['trades'][0] ?? null));

$res = (new BT($mk([[100, 104.5, 99.8, 104], [104, 106.2, 103.5, 106], [106, 106.1, 103.9, 104]])))->run($rules(['exit' => ['be_at_r' => 1]]));
$ok('Break-even: after +1R the stop moves to entry', ($res['trades'][0]['reason'] ?? '') === 'Break-even' && $near($res['trades'][0]['r'], 0), json_encode($res['trades'][0] ?? null));

[$sr] = BT::normalize(['side' => 'short', 'short' => [['a' => ['k' => 'close'], 'op' => 'crosses_below', 'b' => ['k' => 'value', 'v' => 97]]],
    'exit' => ['sl' => ['type' => 'points', 'value' => 2], 'tp' => ['type' => 'r', 'value' => 1]], 'risk' => ['spread' => 0.2]]);
$res = (new BT($mk([[100, 100.2, 95.5, 96], [96, 96.5, 95, 95.5], [95.5, 95.6, 93.5, 94]])))->run($sr);
$ok('Short: target needs ask (low + spread) to reach it', ($res['trades'][0]['reason'] ?? '') === 'Take profit' && $near($res['trades'][0]['r'], 1), json_encode($res['trades'][0] ?? null));

$res = (new BT($mk([[100, 104.5, 99.8, 104], [104, 105, 103, 104.5], [104.5, 104.8, 104, 104.6], [104.6, 104.9, 104.2, 104.7]])))->run($rules(['exit' => ['max_bars' => 2]]));
$ok('Time exit after N candles at the close', ($res['trades'][0]['reason'] ?? '') === 'Time exit' && $res['trades'][0]['bars'] === 2 && $near($res['trades'][0]['exit'], 104.6), json_encode($res['trades'][0] ?? null));

// statistics: a win then a loss
$two = $mk([[100, 104.5, 99.8, 104], [104, 105, 103, 104.5], [104.5, 108.5, 104, 108], [102, 102, 100, 100], [100, 104.5, 99.9, 104], [104, 104.2, 101.5, 102]]);
$res = (new BT($two))->run($rules());
$st = $res['stats'];
$ok('Two trades: +2R then −1R', $st['trades'] === 2 && $near($st['net_r'], 1) && $near($st['win_rate'], 50) && $near($st['profit_factor'], 2), json_encode($st));
$ok('Account after +2% then −1% = 10,098', $near($st['end'], 10098, 0.01) && $near($st['max_dd_pct'], 1, 0.001));

[$bad, $err] = BT::normalize(['side' => 'long', 'long' => [['a' => ['k' => 'close'], 'op' => 'crosses_above']]]);
$ok('Incomplete condition is rejected', $bad === null && $err !== null);
[$bad, $err] = BT::normalize(['side' => 'both', 'long' => [['pattern' => 'green']], 'short' => []]);
$ok('Both directions need sell rules too', $bad === null && str_contains((string) $err, 'sell'));

// ---------------------------------------------------------------- data
$mt5 = "<DATE>\t<TIME>\t<OPEN>\t<HIGH>\t<LOW>\t<CLOSE>\t<TICKVOL>\t<VOL>\t<SPREAD>\n2024.01.02\t00:00:00\t2062.10\t2064.00\t2061.50\t2063.20\t500\t0\t20\n2024.01.02\t00:15:00\t2063.20\t2065.00\t2062.00\t2064.80\t480\t0\t20\n2024.01.02\t00:30:00\t2064.80\t2066.00\t2064.00\t2065.10\t300\t0\t20\n";
$p = BD::parseCsv($mt5, 2);
$ok('MT5 export (tabs, <DATE> <TIME>, UTC+2 → UTC)', count($p['bars']) === 3 && $p['bars'][0][0] === strtotime('2024-01-01 22:00:00 UTC') && $near($p['bars'][1][4], 2064.80), json_encode($p['bars'][0] ?? $p['error']));
$mt4 = "2024.01.02,00:00,1.10450,1.10500,1.10400,1.10480,120\n2024.01.02,00:15,1.10480,1.10520,1.10460,1.10510,90\n2024.01.02,00:30,1.10510,1.10530,1.10490,1.10500,80\n";
$p = BD::parseCsv($mt4);
$ok('MT4 history (no header, date + time columns)', count($p['bars']) === 3 && $p['bars'][2][0] === strtotime('2024-01-02 00:30:00 UTC'));
$tv = "time,open,high,low,close,Volume\n1704153600,100,101,99,100.5,10\n1704154500,100.5,102,100,101.5,12\n1704155400,101.5,101.8,100.9,101,9\n";
$p = BD::parseCsv($tv);
$ok('TradingView export (Unix seconds)', count($p['bars']) === 3 && $p['bars'][0][0] === 1704153600);
$eu = "Date;Open;High;Low;Close\n02/01/2024 00:00;1,10450;1,10500;1,10400;1,10480\n02/01/2024 00:15;1,10480;1,10520;1,10460;1,10510\n02/01/2024 00:30;1,10510;1,10530;1,10490;1,10500\n";
$p = BD::parseCsv($eu);
$ok('European file (semicolons, decimal comma, day/month/year)', count($p['bars']) === 3 && $p['bars'][0][0] === strtotime('2024-01-02 00:00:00 UTC') && $near($p['bars'][0][1], 1.1045));
$badRow = "time,open,high,low,close\n2024-01-02 00:00,100,99,98,100\n2024-01-02 00:15,100,101,99,100\n2024-01-02 00:30,100,101,99,100\n";
$p = BD::parseCsv($badRow);
$ok('Impossible candle (high below open) is skipped', count($p['bars']) === 2 && $p['skipped'] === 1);

$q = [];
for ($i = 0; $i < 8; $i++) { $q[] = [$T0 + $i * 900, 10 + $i, 10.5 + $i, 9.5 + $i, 10.2 + $i]; }
$h1 = BD::resample($q, 3600);
$ok('15m → 1h resample (open of first, max high, min low, close of last)', count($h1) === 2 && $h1[0][0] === $T0 && $near($h1[0][1], 10) && $near($h1[0][2], 13.5) && $near($h1[0][3], 9.5) && $near($h1[0][4], 13.2) && $near($h1[1][2], 17.5), json_encode($h1));
$ok('Timeframe detection', BD::detectTf($q) === 900);

echo $fail ? "\n$fail check(s) FAILED\n" : "\nAll backtester checks passed.\n";
exit($fail ? 1 : 0);
