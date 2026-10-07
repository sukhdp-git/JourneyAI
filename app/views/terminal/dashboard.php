<?php
use App\Core\View;
$cur = $acc['currency'];
$c = fn ($type, $data, $label, $area = null, $h = null) => View::partial('terminal/partials/chart', ['type' => $type, 'series' => $data, 'format' => 'money:' . $cur, 'label' => $label, 'area' => $area, 'height' => $h]);
?>
<?= View::partial('terminal/partials/range', ['action' => url('/terminal/dashboard'), 'range' => $range, 'from' => $from, 'to' => $to, 'acc' => $acc]) ?>
<?php if (!$sum['trades']): ?>
  <div class="panel empty"><?= icon('chart', 'icon') ?><p>No closed trades in this period for <strong><?= e($acc['name']) ?></strong>.</p><p><a class="tm-btn tm-btn-primary" href="<?= e(url('/terminal/trades/new')) ?>">Log a trade</a></p></div>
<?php else: ?>
<?php $cls = fn (?float $v, float $th = 0.0) => $v === null ? '' : ($v > $th ? 'up' : ($v < $th ? 'down' : '')); $pf = $sum['profit_factor']; $wr = $sum['win_rate']; ?>
<div class="kpis">
  <div class="kpi <?= $sum['net'] >= 0 ? 'pos' : 'neg' ?>"><small>Net P&amp;L</small><strong class="<?= $cls($sum['net']) ?>"><?= $sum['net'] >= 0 ? '▲ ' : '▼ ' ?><?= e(money($sum['net'], $cur, true)) ?></strong><em><?= e(pct($eq['final'] && $start ? ($eq['final'] - $start) / $start : null, 2)) ?> on capital</em></div>
  <div class="kpi"><small>Win rate</small><strong class="<?= $wr === null ? '' : ($wr >= 0.5 ? 'up' : 'down') ?>"><?= e(pct($wr)) ?></strong><em><span class="up"><?= $sum['wins'] ?>W</span> · <span class="down"><?= $sum['losses'] ?>L</span> · <?= $sum['breakeven'] ?>BE</em></div>
  <div class="kpi"><small>Expectancy / trade</small><strong class="<?= $cls($sum['expectancy']) ?>"><?= e(money($sum['expectancy'], $cur, true)) ?></strong><em><?= ($sum['expectancy'] ?? 0) >= 0 ? 'Positive expectancy' : 'Negative expectancy' ?></em></div>
  <div class="kpi"><small>Average R</small><strong class="<?= $cls($sum['avg_r']) ?>"><?= $sum['avg_r'] === null ? '—' : e(($sum['avg_r'] > 0 ? '+' : '') . number_format($sum['avg_r'], 2)) . 'R' ?></strong></div>
  <div class="kpi"><small>Profit factor</small><strong class="<?= $pf === null ? 'up' : $cls($pf, 1.0) ?>"><?= $pf === null ? '∞' : e(number_format($pf, 2)) ?></strong></div>
  <div class="kpi"><small>Average win</small><strong class="up"><?= e(money($sum['avg_win'], $cur, true)) ?></strong></div>
  <div class="kpi"><small>Average loss</small><strong class="down"><?= $sum['avg_loss'] === null ? '—' : e(money(-$sum['avg_loss'], $cur)) ?></strong></div>
  <div class="kpi"><small>Payoff ratio</small><strong class="<?= $sum['payoff'] === null ? '' : $cls($sum['payoff'], 1.0) ?>"><?= $sum['payoff'] === null ? '—' : e(number_format($sum['payoff'], 2)) ?></strong></div>
  <div class="kpi"><small>Best trade</small><strong class="up"><?= e(money($sum['best'], $cur, true)) ?></strong></div>
  <div class="kpi"><small>Worst trade</small><strong class="down"><?= e(money($sum['worst'], $cur, true)) ?></strong></div>
  <div class="kpi"><small>Max drawdown</small><strong class="<?= $eq['max_dd_pct'] > 0 ? 'down' : '' ?>"><?= $eq['max_dd_pct'] > 0 ? '−' : '' ?><?= e(number_format($eq['max_dd_pct'], 2)) ?>%</strong><em><?= e(money(-$eq['max_dd'], $cur)) ?></em></div>
  <div class="kpi"><small>Trades</small><strong><?= (int) $sum['trades'] ?></strong><em>Rule compliance <?= e(pct($sum['compliance'], 0)) ?></em></div>
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
<p class="panel-note">Gains are green and losses red; every value is also shown with its sign. Historical performance does not predict future results.</p>
<?php endif; ?>
