<?php
/**
 * Deterministic checks for the trading maths and voice parser. Run from a terminal/SSH (never via the web):
 *   php tests/calculations.php
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../app/bootstrap.php';

use App\Trading\QuickTrade;
use App\Trading\TradeMath as T;

$fail = 0;
$check = function (string $name, mixed $got, mixed $want) use (&$fail): void {
    $ok = $got == $want;
    $fail += $ok ? 0 : 1;
    printf("%s %-44s %s\n", $ok ? 'PASS' : 'FAIL', $name, $ok ? '' : 'got ' . json_encode($got) . ' want ' . json_encode($want));
};
$r = T::pnlCalc('XAUUSD', 'LONG', 2645.5, 2639, 2660, 0.5, 'USD');
$check('Gold BUY P&L / risk / R:R', [$r['pnl'], $r['risk'], $r['rr']], [725.0, 325.0, 2.23]);
$r = T::pnlCalc('EURUSD', 'SHORT', 1.0925, 1.0950, 1.0875, 1, 'USD', null, 'Exit');
$check('EURUSD SELL exit P&L and R', [$r['pnl'], $r['rr'], $r['r_multiple']], [500.0, 2.0, 2.0]);
$check('BUY stop above entry rejected', T::pnlCalc('EURUSD', 'LONG', 1.0925, 1.0950, 1.0975, 1, 'USD')['ok'], false);
$check('SELL take profit above entry rejected', T::pnlCalc('XAUUSD', 'SHORT', 2650, 2655, 2660, 1, 'USD')['ok'], false);
$r = T::positionSize('XAUUSD', 'LONG', 2645.5, 2639, 2660, 200, 20000, 'USD');
$check('1% of $20k on gold → lots, risk, R:R', [$r['lots'], $r['risk_actual'], $r['rr']], [0.3, 195.0, 2.23]);
$check('Prop $100k 1% on gold → 1.53 lots', T::positionSize('XAUUSD', 'LONG', 2645.5, 2639, null, 1000, 100000, 'USD')['lots'], 1.53);
$check('USDJPY per-lot risk uses entry price', T::positionSize('USDJPY', 'LONG', 150, 149.5, null, 333.34, 10000, 'USD')['per_lot_risk'], 333.33);
$check('NAS100 20-point stop, $100 risk → 5 lots', T::positionSize('NAS100', 'SHORT', 20000, 20020, 19950, 100, 10000, 'USD')['lots'], 5.0);
$check('GER40 in USD needs a conversion rate', !empty(T::positionSize('GER40', 'LONG', 24000, 23950, null, 100, 10000, 'USD')['needs_rate']), true);
$check('GER40 with EURUSD 1.08', T::positionSize('GER40', 'LONG', 24000, 23950, null, 100, 10000, 'USD', 1.08)['lots'], 1.85);
$check('BTC $500 risk, 2R target, +4% gain', array_values(array_intersect_key(T::positionSize('BTCUSDT', 'LONG', 60000, 59000, 62000, 500, 25000, 'USD'), array_flip(['lots', 'rr', 'gain_pct']))), [0.5, 2.0, 4.0]);
$p = QuickTrade::parse('Bought gold at 2645.50, stop loss 2639, take profit 2660, 0.5 lots.', true);
$check('Voice: gold sentence', [$p['symbol'], $p['side'], $p['entry'], $p['stop'], $p['tp'], $p['lots']], ['XAUUSD', 'LONG', 2645.5, 2639, 2660, 0.5]);
$p = QuickTrade::parse('Sold EURUSD at 1.0925, stop 1.0950, exit 1.0875, one lot.', true);
$check('Voice: EURUSD sentence', [$p['symbol'], $p['side'], $p['entry'], $p['stop'], $p['exit_spoken'], $p['lots']], ['EURUSD', 'SHORT', 1.0925, 1.095, 1.0875, 1]);
$p = QuickTrade::parse('Sell euro dollar at 1.0925 stop loss 1.0950 target 1.0880 half a lot', true);
$check('Voice: spoken pair name + half a lot', [$p['symbol'], $p['tp'], $p['lots']], ['EURUSD', 1.088, 0.5]);
echo $fail ? "\n$fail check(s) failed\n" : "\nAll checks passed\n";
exit($fail ? 1 : 0);
