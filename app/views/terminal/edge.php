<?php
use App\Core\View;
use App\Trading\Domain;
$cur = $acc['currency'];
$table = function (array $groups, string $label) use ($cur) {
    if (!$groups) return '<p class="muted small">No data.</p>';
    $h = '<div class="table-wrap"><table class="tbl"><thead><tr><th>' . e($label) . '</th><th class="r">Trades</th><th class="r">Win</th><th class="r">P&amp;L</th><th class="r">Expectancy</th></tr></thead><tbody>';
    foreach ($groups as $g) { $s = $g['s']; $h .= '<tr><td>' . e($g['key']) . '</td><td class="r num">' . $s['trades'] . '</td><td class="r num">' . e(pct($s['win_rate'], 0)) . '</td><td class="r num ' . ($s['net'] >= 0 ? 'up' : 'down') . '">' . e(money($s['net'], $cur, true)) . '</td><td class="r num">' . e(money($s['expectancy'], $cur, true)) . '</td></tr>'; }
    return $h . '</tbody></table></div>';
};
$bestCard = fn ($title, $b) => '<div class="kpi"><small>' . e($title) . '</small><strong>' . e($b['key'] ?? '—') . '</strong><em>' . ($b ? e(money($b['s']['expectancy'], $cur, true)) . ' / trade · ' . $b['s']['trades'] . ' trades' : 'Needs ≥ 3 trades per group') . '</em></div>';
$leakRows = array_map(fn ($r) => (Domain::MISTAKES[$r['mistake']] ?? $r['mistake']) . ': ' . $r['trades'] . ' trades, leak ' . money($r['leak'], $cur, true), $leak['by_mistake']);
?>
<?php if (!$sum['trades']): ?><div class="panel empty"><?= icon('grid', 'icon') ?><p>The Edge Matrix needs closed trades. <a href="<?= e(url('/terminal/trades/new')) ?>">Log trades</a> or load the demo journal from the Home Hub.</p></div><?php else: ?>
<p class="muted small">Historical analysis of <strong><?= e($acc['name']) ?></strong><?= (int) $acc['has_demo_data'] ? ' (DEMO DATA)' : '' ?>. Past results describe what happened — they are not a forecast or a guarantee.</p>
<section class="panel" style="margin-bottom:12px">
  <div class="panel-head"><h2><?= icon('target', 'icon icon-sm') ?> Best trading window</h2><span class="muted small">Highest historical expectancy (min. 3 trades per group)</span></div>
  <div class="kpis" style="margin:0"><?= $bestCard('Best hour', $best['hour']) ?><?= $bestCard('Best session', $best['session']) ?><?= $bestCard('Best instrument', $best['instrument']) ?><?= $bestCard('Best setup', $best['setup']) ?><?= $bestCard('Best weekday', $best['weekday']) ?></div>
</section>
<div class="grid g-2">
  <section class="panel" id="leak" data-leak="<?= e(json_encode(['period' => 'All time · ' . $acc['name'], 'leak' => money($leak['leak'], $cur), 'actual' => money($leak['actual'], $cur, true), 'flawless' => money($leak['flawless'], $cur, true), 'violations' => $leak['violations'], 'rows' => $leakRows])) ?>">
    <div class="panel-head"><h2><?= icon('eye', 'icon icon-sm') ?> Discipline leak mirror</h2><button type="button" class="tm-btn tm-btn-sm" data-png-export="#leak"><?= icon('download', 'icon icon-sm') ?> PNG</button></div>
    <p class="muted small">Your execution mistakes cost approximately</p>
    <p class="leak-big num"><?= e(money(max(0, $leak['leak']), $cur)) ?></p>
    <div class="stat-pair small"><div>Actual P&amp;L <strong class="num"><?= e(money($leak['actual'], $cur, true)) ?></strong></div><div>Rule-compliant P&amp;L <strong class="num"><?= e(money($leak['flawless'], $cur, true)) ?></strong></div></div>
    <?php if ($leak['by_mistake']): ?><div class="table-wrap" style="margin-top:10px"><table class="tbl"><thead><tr><th>Mistake</th><th class="r">Trades</th><th class="r">Actual</th><th class="r">Leak</th></tr></thead><tbody><?php foreach ($leak['by_mistake'] as $r): ?><tr><td><?= e(Domain::MISTAKES[$r['mistake']] ?? $r['mistake']) ?></td><td class="r num"><?= $r['trades'] ?></td><td class="r num"><?= e(money($r['actual'], $cur, true)) ?></td><td class="r num"><?= e(money($r['leak'], $cur, true)) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
    <p class="panel-note">Conservative hypothetical: impulse entries (FOMO, revenge, chasing, overtrading, news gambles, ignored plan) are treated as not taken; moved/no stop and oversizing have losses capped at 1R. No hypothetical profit is ever added, and none of this was guaranteed.</p>
  </section>
  <section class="panel" data-mc>
    <div class="panel-head"><h2><?= icon('activity', 'icon icon-sm') ?> Risk of ruin — Monte Carlo</h2></div>
    <?php if (!$mc): ?><p class="muted">At least 10 closed trades with a stop loss are needed for a simulation.</p><?php else: ?>
    <label class="small" for="mc-risk">Risk per trade: <strong class="num" data-mc-risk><?= e(number_format(max(0.25, min(5, $risk)), 2)) ?>%</strong></label>
    <input id="mc-risk" type="range" min="0.25" max="5" step="0.25" value="<?= e((string) max(0.25, min(5, $risk))) ?>">
    <div class="kpis" style="margin:10px 0">
      <?php foreach ($mc['prob'] as $p): ?><div class="kpi"><small>P(≥<?= $p['dd'] ?>% drawdown)</small><strong data-p="<?= $p['dd'] ?>"><?= e(number_format($p['p'] * 100, 1)) ?>%</strong></div><?php endforeach; ?>
    </div>
    <p class="small">Median return <strong data-mc-median><?= e(number_format($mc['median_return'] * 100, 1)) ?>%</strong> · 5th–95th percentile <strong data-mc-range><?= e(number_format($mc['p5_return'] * 100, 1)) ?>% to <?= e(number_format($mc['p95_return'] * 100, 1)) ?>%</strong> · median max drawdown <strong data-mc-dd><?= e(number_format($mc['median_max_dd'] * 100, 1)) ?>%</strong></p>
    <div class="chart" data-chart="band" data-json="<?= e(json_encode($mc['bands'])) ?>" data-height="200"></div>
    <p class="panel-note"><strong>Statistical estimate based on historical assumptions — not a prediction.</strong> <?= (int) $mc['paths'] ?> paths × <?= (int) $mc['trades'] ?> trades (≈6 months at <?= e((string) $mc['per_month']) ?> trades/month), win rate <?= e(pct($mc['win_rate'])) ?>, avg win <?= e((string) $mc['avg_win_r']) ?>R, avg loss <?= e((string) $mc['avg_loss_r']) ?>R. Band = 5th–95th percentile; line = median.</p>
    <?php endif; ?>
  </section>
  <section class="panel">
    <div class="panel-head"><h2><?= icon('brain', 'icon icon-sm') ?> Disciplined vs emotional trades</h2></div>
    <div class="table-wrap"><table class="tbl"><thead><tr><th></th><th class="r">Trades</th><th class="r">Win rate</th><th class="r">P&amp;L</th><th class="r">Expectancy</th></tr></thead><tbody>
      <?php foreach (['disciplined' => 'Disciplined (rules followed, calm)', 'emotional' => 'Emotional or rule-breaking'] as $k => $l): $s = $evd[$k]; ?><tr><td><?= e($l) ?></td><td class="r num"><?= $s['trades'] ?></td><td class="r num"><?= e(pct($s['win_rate'])) ?></td><td class="r num <?= $s['net'] >= 0 ? 'up' : 'down' ?>"><?= e(money($s['net'], $cur, true)) ?></td><td class="r num"><?= e(money($s['expectancy'], $cur, true)) ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
  </section>
  <section class="panel">
    <div class="panel-head"><h2><?= icon('alert', 'icon icon-sm') ?> Tilt circuit breaker</h2><a class="small" href="<?= e(url('/terminal/settings#risk')) ?>">Change rule</a></div>
    <p>Rule: <strong><?= $tiltRule[0] ?> losses within <?= $tiltRule[1] ?> minutes</strong> triggers a <?= $tiltRule[2] ?>-minute cooldown.</p>
    <p>Historical tilt episodes in this account: <strong class="num"><?= (int) $tiltHistory ?></strong></p>
    <p class="lock-note"><strong>JOURNZEY TERMINAL LOCK</strong> pauses quick logging in this terminal during a cooldown. <strong>BROKER EXECUTION LOCK</strong> is not available — journzey.ai cannot block orders at your broker.</p>
  </section>
  <section class="panel">
    <div class="panel-head"><h2><?= icon('trend-up', 'icon icon-sm') ?> Post-trade runner auditor</h2></div>
    <?php if (!$marketData): ?><p class="muted">Requires a market-data API (configured by the site owner) to fetch real price history after each exit. No prices are ever invented, so this tool is disabled until then.</p>
    <?php else: ?><p class="muted small">Hypothetical: keeping 20% of the position after exit with a breakeven stop. Uses real 1-minute history from the market-data provider. Results are hypothetical.</p><p class="small">Open any closed trade with a stop in the <a href="<?= e(url('/terminal/trades')) ?>">trade log</a> and click “Runner audit”.</p><?php endif; ?>
  </section>
</div>
<section class="panel" style="margin-top:12px">
  <div class="panel-head"><h2>Previous month analysis</h2><span class="muted small">Based on the <?= e($basis) ?></span></div>
  <div class="grid g-2">
    <div><h3>By strategy</h3><?= $table($prev['strategy'], 'Strategy') ?></div>
    <div><h3>By session</h3><?= $table($prev['session'], 'Session') ?></div>
    <div><h3>By instrument</h3><?= $table($prev['instrument'], 'Instrument') ?></div>
    <div><h3>By hour (<?= e($tz) ?>)</h3><?= $table($prev['hour'], 'Hour') ?></div>
    <div><h3>By mistake</h3><?= $table($prev['mistake'], 'Mistake') ?></div>
  </div>
</section>
<?php endif; ?>
