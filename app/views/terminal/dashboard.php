<?php
use App\Core\View;
$cur = $acc['currency'];
$c = fn ($type, $data, $label, $area = null, $h = null) => View::partial('terminal/partials/chart', ['type' => $type, 'series' => $data, 'format' => 'money:' . $cur, 'label' => $label, 'area' => $area, 'height' => $h]);
?>
<?= View::partial('terminal/partials/range', ['action' => url('/terminal/dashboard'), 'range' => $range, 'from' => $from, 'to' => $to, 'acc' => $acc]) ?>
<?php if (!$sum['trades']): ?>
  <div class="panel empty"><?= icon('chart', 'icon') ?><p>No closed trades in this period for <strong><?= e($acc['name']) ?></strong>.</p><p><a class="tm-btn tm-btn-primary" href="<?= e(url('/terminal/trades/new')) ?>">Log a trade</a></p></div>
<?php else: ?>
<div class="kpis">
  <div class="kpi"><small>Net P&amp;L</small><strong class="<?= $sum['net'] >= 0 ? 'up' : 'down' ?>"><?= $sum['net'] >= 0 ? '▲ ' : '▼ ' ?><?= e(money($sum['net'], $cur, true)) ?></strong></div>
  <div class="kpi"><small>Win rate</small><strong><?= e(pct($sum['win_rate'])) ?></strong><em><?= $sum['wins'] ?>W · <?= $sum['losses'] ?>L · <?= $sum['breakeven'] ?>BE</em></div>
  <div class="kpi"><small>Profit factor</small><strong><?= $sum['profit_factor'] === null ? '∞' : e(number_format($sum['profit_factor'], 2)) ?></strong></div>
  <div class="kpi"><small>Average win</small><strong><?= e(money($sum['avg_win'], $cur)) ?></strong></div>
  <div class="kpi"><small>Average loss</small><strong><?= $sum['avg_loss'] === null ? '—' : e(money(-$sum['avg_loss'], $cur)) ?></strong></div>
  <div class="kpi"><small>Payoff ratio</small><strong><?= $sum['payoff'] === null ? '—' : e(number_format($sum['payoff'], 2)) ?></strong></div>
  <div class="kpi"><small>Best trade</small><strong class="up"><?= e(money($sum['best'], $cur, true)) ?></strong></div>
  <div class="kpi"><small>Worst trade</small><strong class="down"><?= e(money($sum['worst'], $cur, true)) ?></strong></div>
  <div class="kpi"><small>Trades</small><strong><?= (int) $sum['trades'] ?></strong><em>Avg <?= $sum['avg_r'] === null ? '—' : e(number_format($sum['avg_r'], 2)) . 'R' ?> · Max DD <?= e(number_format($eq['max_dd_pct'], 1)) ?>%</em></div>
</div>
<div class="grid g-2">
  <section class="panel"><div class="panel-head"><h2>Account equity</h2><span class="muted small">Start <?= e(money($start, $cur)) ?> → <?= e(money($eq['final'], $cur)) ?></span></div><?= $c('line', ['points' => $equity, 'base' => round($start, 2)], 'Equity', '1') ?></section>
  <section class="panel"><div class="panel-head"><h2>Cumulative P&amp;L</h2></div><?= $c('line', ['points' => $cum, 'base' => 0], 'Cumulative P&L', '1') ?></section>
  <section class="panel"><div class="panel-head"><h2>Drawdown</h2><span class="muted small">Max <?= e(number_format($eq['max_dd_pct'], 2)) ?>% (<?= e(money($eq['max_dd'], $cur)) ?>)</span></div><?= View::partial('terminal/partials/chart', ['type' => 'line', 'series' => ['points' => $dd, 'base' => 0], 'format' => 'pct', 'label' => 'Drawdown %', 'area' => 'neg', 'height' => 180]) ?></section>
  <section class="panel"><div class="panel-head"><h2>By weekday</h2></div><?= $c('bars', $byWeekday, 'Net P&L') ?></section>
  <section class="panel"><div class="panel-head"><h2>By session</h2><span class="muted small">London / New York / Asia</span></div><?= $c('bars', $bySession, 'Net P&L') ?></section>
  <section class="panel"><div class="panel-head"><h2>By instrument</h2></div><?= $c('bars', $bySymbol, 'Net P&L') ?></section>
  <section class="panel" style="grid-column:1/-1"><div class="panel-head"><h2>By strategy</h2><a class="small" href="<?= e(url('/terminal/strategies')) ?>">Strategy analysis →</a></div><?= $c('bars', $byStrategy, 'Net P&L') ?></section>
</div>
<p class="panel-note">Gains are drawn in blue and losses in red; every value is also shown with its sign. Historical performance does not predict future results.</p>
<?php endif; ?>
