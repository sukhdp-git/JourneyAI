<?php
use App\Core\View;
$cur = $acc['currency'];
$c = fn ($type, $data, $label, $area = null, $h = null) => View::partial('terminal/partials/chart', ['type' => $type, 'series' => $data, 'format' => 'money:' . $cur, 'label' => $label, 'area' => $area, 'height' => $h]);
?>
<?= View::partial('terminal/partials/range', ['action' => url('/terminal/dashboard'), 'range' => $range, 'from' => $from, 'to' => $to, 'acc' => $acc]) ?>
<?php if ($prop): $st = ['active' => 'IN PROGRESS', 'passed' => 'TARGET MET', 'breached' => 'RULE BREACHED'][$prop['status']]; ?>
<section class="panel prop-panel" aria-label="Prop firm rules">
  <div class="panel-head"><h2><?= icon('shield', 'icon icon-sm') ?> <?= e($prop['firm'] ?: 'Prop firm') ?> rules<?= $prop['preset'] ? ' <span class="muted small">· ' . e($prop['preset']) . '</span>' : '' ?></h2><span class="prop-status <?= e($prop['status']) ?>"><?= $st ?></span></div>
  <?php if ($prop['items']): ?>
  <div class="prop-rules">
    <?php foreach ($prop['items'] as $it): ?>
    <div class="prop-rule <?= e($it['tone']) ?>" data-rule="<?= e($it['key']) ?>">
      <small><?= e($it['label']) ?></small>
      <strong class="<?= e($it['cls'] ?? '') ?>"><?= e($it['value']) ?></strong>
      <div class="bar" role="progressbar" aria-label="<?= e($it['label']) ?>" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int) round($it['progress'] * 100) ?>"><span style="width:<?= round($it['progress'] * 100, 1) ?>%"></span></div>
      <span class="rule"><?= e($it['rule']) ?></span>
      <em><?= e($it['note']) ?></em>
    </div>
    <?php endforeach; ?>
  </div>
  <p class="panel-note">Measured on closed trades all-time for this account (not the date range above). Your prop firm's own dashboard is the official record. <a href="<?= e(url('/terminal/accounts')) ?>">Edit rules</a></p>
  <?php else: ?><p class="muted">No rules set for this account yet. <a href="<?= e(url('/terminal/accounts')) ?>">Add the firm's rules</a> to track them here.</p><?php endif; ?>
</section>
<?php endif; ?>
<?php if (!$sum['trades']): ?>
  <div class="panel empty"><?= icon('chart', 'icon') ?><p>No closed trades in this period for <strong><?= e($acc['name']) ?></strong>.</p><p><a class="tm-btn tm-btn-primary" href="<?= e(url('/terminal/trades/new')) ?>">Log a trade</a></p></div>
<?php else: ?>
<?php $cls = fn (?float $v, float $th = 0.0) => $v === null ? '' : ($v > $th ? 'up' : ($v < $th ? 'down' : '')); $pf = $sum['profit_factor']; $wr = $sum['win_rate']; ?>
<div class="dash-hero">
  <div class="dash-tile <?= $sum['net'] >= 0 ? 'is-up' : 'is-down' ?>"><small>Net P&amp;L</small><strong class="<?= $cls($sum['net']) ?>"><?= e(money($sum['net'], $cur, true)) ?></strong><em><?= e(pct($eq['final'] && $start ? ($eq['final'] - $start) / $start : null, 2)) ?> on capital · <?= (int) $sum['trades'] ?> trades</em></div>
  <div class="dash-tile <?= $wr === null ? '' : ($wr >= 0.5 ? 'is-up' : 'is-down') ?>"><small>Win rate</small><strong class="<?= $wr === null ? '' : ($wr >= 0.5 ? 'up' : 'down') ?>"><?= e(pct($wr)) ?></strong><em><span class="up"><?= $sum['wins'] ?> wins</span> · <span class="down"><?= $sum['losses'] ?> losses</span></em></div>
  <div class="dash-tile <?= $pf === null || $pf >= 1 ? 'is-up' : 'is-down' ?>"><small>Profit factor</small><strong class="<?= $pf === null ? 'up' : $cls($pf, 1.0) ?>"><?= $pf === null ? '∞' : e(number_format($pf, 2)) ?></strong><em>Expectancy <span class="<?= $cls($sum['expectancy']) ?>"><?= e(money($sum['expectancy'], $cur, true)) ?></span> / trade</em></div>
  <div class="dash-tile <?= $eq['max_dd_pct'] > 0 ? 'is-down' : 'is-up' ?>"><small>Max drawdown</small><strong class="<?= $eq['max_dd_pct'] > 0 ? 'down' : 'up' ?>"><?= $eq['max_dd_pct'] > 0 ? '−' : '' ?><?= e(number_format($eq['max_dd_pct'], 2)) ?>%</strong><em><?= e(money(-$eq['max_dd'], $cur)) ?> from peak</em></div>
</div>
<div class="dash-strip">
  <span><small>Avg R</small><b class="<?= $cls($sum['avg_r']) ?>"><?= $sum['avg_r'] === null ? '—' : e(($sum['avg_r'] > 0 ? '+' : '') . number_format($sum['avg_r'], 2)) . 'R' ?></b></span>
  <span><small>Avg win</small><b class="up"><?= e(money($sum['avg_win'], $cur, true)) ?></b></span>
  <span><small>Avg loss</small><b class="down"><?= $sum['avg_loss'] === null ? '—' : e(money(-$sum['avg_loss'], $cur)) ?></b></span>
  <span><small>Payoff</small><b class="<?= $sum['payoff'] === null ? '' : $cls($sum['payoff'], 1.0) ?>"><?= $sum['payoff'] === null ? '—' : e(number_format($sum['payoff'], 2)) ?></b></span>
  <span><small>Best</small><b class="up"><?= e(money($sum['best'], $cur, true)) ?></b></span>
  <span><small>Worst</small><b class="down"><?= e(money($sum['worst'], $cur, true)) ?></b></span>
  <span><small>Rules followed</small><b class="<?= ($sum['compliance'] ?? 1) >= 0.8 ? 'up' : 'down' ?>"><?= e(pct($sum['compliance'], 0)) ?></b></span>
</div>
<section class="panel dash-equity">
  <div class="panel-head"><h2><?= icon('trend-up', 'icon icon-sm') ?> Account equity</h2><span class="muted small"><?= e(money($equity['base'], $cur)) ?> → <strong class="strong"><?= e(money($equity['points'] ? $equity['points'][count($equity['points']) - 1]['y'] : $equity['base'], $cur)) ?></strong></span></div>
  <?= $c('line', $equity, 'Account equity', '1', 260) ?>
  <div class="legend"><span><i style="background:var(--series-1)"></i>Equity (trading P&amp;L + deposits/withdrawals)</span><span><i class="dot-in"></i>Deposit</span><span><i class="dot-out"></i>Withdrawal</span></div>
</section>
<div class="grid g-2">
  <section class="panel"><div class="panel-head"><h2>Cumulative P&amp;L</h2><span class="muted small">Trading only</span></div><?= $c('line', ['points' => $cum, 'base' => 0], 'Cumulative P&L', '1', 200) ?></section>
  <section class="panel"><div class="panel-head"><h2>Drawdown</h2><span class="muted small">Max <span class="down">−<?= e(number_format($eq['max_dd_pct'], 2)) ?>%</span></span></div><?= View::partial('terminal/partials/chart', ['type' => 'line', 'series' => ['points' => $dd, 'base' => 0], 'format' => 'pct', 'label' => 'Drawdown %', 'area' => 'neg', 'height' => 200]) ?></section>
  <section class="panel"><div class="panel-head"><h2>By weekday</h2></div><?= $c('bars', $byWeekday, 'Net P&L') ?></section>
  <section class="panel"><div class="panel-head"><h2>By session</h2></div><?= $c('bars', $bySession, 'Net P&L') ?></section>
  <section class="panel"><div class="panel-head"><h2>By instrument</h2></div><?= $c('bars', $bySymbol, 'Net P&L') ?></section>
  <section class="panel"><div class="panel-head"><h2>By strategy</h2><a class="small" href="<?= e(url('/terminal/strategies')) ?>">Strategy analysis →</a></div><?= $c('bars', $byStrategy, 'Net P&L') ?></section>
</div>
<p class="panel-note">Gains are green and losses red; every value is also shown with its sign. Historical performance does not predict future results.</p>
<?php endif; ?>
