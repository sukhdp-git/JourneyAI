<?php
$cur = $acc['currency'];
$prev = $month->modify('-1 month')->format('Y-m'); $next = $month->modify('+1 month')->format('Y-m');
?>
<div class="toolbar">
  <a class="tm-btn tm-btn-sm" href="<?= e(url('/terminal/calendar?month=' . $prev)) ?>" aria-label="Previous month"><?= icon('chevron-left', 'icon icon-sm') ?></a>
  <h2 style="font-size:16px;min-width:150px;text-align:center"><?= e($month->format('F Y')) ?></h2>
  <a class="tm-btn tm-btn-sm" href="<?= e(url('/terminal/calendar?month=' . $next)) ?>" aria-label="Next month"><?= icon('chevron-right', 'icon icon-sm') ?></a>
  <a class="tm-btn tm-btn-sm tm-btn-ghost" href="<?= e(url('/terminal/calendar')) ?>">Today</a>
</div>
<?php
$mk = $month->format('Y-m'); $inMonth = array_filter($days, fn ($v, $k) => str_starts_with($k, $mk), ARRAY_FILTER_USE_BOTH);
$greenDays = count(array_filter($inMonth, fn ($v) => $v['pnl'] > 0)); $redDays = count(array_filter($inMonth, fn ($v) => $v['pnl'] < 0));
$bestDay = $inMonth ? max(array_column($inMonth, 'pnl')) : null; $worstDay = $inMonth ? min(array_column($inMonth, 'pnl')) : null;
$up = $monthSum['net'] >= 0;
?>
<section class="month-box <?= $monthSum['trades'] ? ($up ? 'is-up' : 'is-down') : '' ?>" aria-label="Monthly P&L">
  <div class="month-main"><small><?= e($month->format('F Y')) ?> P&amp;L</small><strong class="<?= $monthSum['trades'] ? ($up ? 'up' : 'down') : '' ?>"><?= e(money($monthSum['net'], $cur, true)) ?></strong><em><?= (int) $monthSum['trades'] ?> trades · <?= e(pct($monthSum['win_rate'], 0)) ?> win rate</em></div>
  <div class="month-stats">
    <div><small>Green days</small><b class="up"><?= $greenDays ?></b></div>
    <div><small>Red days</small><b class="down"><?= $redDays ?></b></div>
    <div><small>Best day</small><b class="<?= ($bestDay ?? 0) >= 0 ? 'up' : 'down' ?>"><?= $bestDay === null ? '—' : e(money($bestDay, $cur, true)) ?></b></div>
    <div><small>Worst day</small><b class="<?= ($worstDay ?? 0) >= 0 ? 'up' : 'down' ?>"><?= $worstDay === null ? '—' : e(money($worstDay, $cur, true)) ?></b></div>
  </div>
</section>
<section class="panel">
  <div class="cal" role="grid" aria-label="Daily P&L calendar">
    <?php foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $w): ?><div class="cal-h" role="columnheader"><?= $w ?></div><?php endforeach; ?><div class="cal-h wk">Week</div>
    <?php for ($d = $gridStart; $d <= $gridEnd; $d = $d->modify('+1 day')):
        $k = $d->format('Y-m-d'); $info = $days[$k] ?? null; $cls = 'cal-d' . ($d->format('m') !== $month->format('m') ? ' out' : '') . ($k === $today ? ' today' : '') . ($info ? ($info['pnl'] >= 0 ? ' pos' : ' neg') : '') . ($k === $sel ? ' sel' : '');
        if ($d->format('N') === '1') { $wk = ['pnl' => 0, 'n' => 0]; } if ($info) { $wk['pnl'] += $info['pnl']; $wk['n'] += $info['n']; } ?>
      <a class="<?= $cls ?>" role="gridcell" href="<?= e(url('/terminal/calendar?month=' . $month->format('Y-m') . '&day=' . $k . '#day')) ?>" aria-label="<?= e($d->format('D j M') . ($info ? ': ' . money($info['pnl'], $cur, true) . ', ' . $info['n'] . ' trades' : ': no trades')) ?>">
        <span class="n"><?= $d->format('j') ?></span>
        <?php if ($info): ?><small><?= $info['n'] ?> trade<?= $info['n'] > 1 ? 's' : '' ?></small><strong><?= $info['pnl'] >= 0 ? '▲' : '▼' ?> <?= e(money($info['pnl'], $cur, true)) ?></strong><?php endif; ?>
      </a>
      <?php if ($d->format('N') === '7'): ?><div class="cal-w"><span>Week</span><strong class="<?= $wk['pnl'] >= 0 ? 'up' : 'down' ?>"><?= $wk['n'] ? e(money($wk['pnl'], $cur, true)) : '—' ?></strong><span><?= $wk['n'] ?> trades</span></div><?php endif; ?>
    <?php endfor; ?>
  </div>
</section>
<?php if ($sel): ?>
<section class="panel" id="day" style="margin-top:12px">
  <div class="panel-head"><h2><?= e((new DateTimeImmutable($sel))->format('l j F Y')) ?></h2><span class="head-actions"><?php if ($selTrades): ?><button type="button" class="tm-btn tm-btn-sm flex-btn" data-flex-day="<?= e($sel) ?>"><?= icon('share', 'icon icon-sm') ?> Flex card</button><?php endif; ?><a class="small" href="<?= e(url('/terminal/trades?from=' . $sel . '&to=' . $sel)) ?>">Open in trade log →</a></span></div>
  <?php if ($selTrades): ?>
  <div class="table-wrap"><table class="tbl cards"><thead><tr><th>Time</th><th>Symbol</th><th>Side</th><th>Setup</th><th class="r">P&amp;L</th><th class="r">R</th></tr></thead><tbody>
  <?php foreach ($selTrades as $t): ?><tr><td data-label="Time"><a href="<?= e(url('/terminal/trades/' . $t['id'])) ?>"><?= e(fmt_date($t['executed_at'], 'H:i')) ?></a></td><td data-label="Symbol" class="strong"><?= e($t['symbol']) ?></td><td data-label="Side"><span class="badge <?= strtolower($t['side']) ?>"><?= e($t['side']) ?></span></td><td data-label="Setup"><?= e($t['strategy_name'] ?: $t['setup_tag'] ?: '—') ?></td><td data-label="P&amp;L" class="r num <?= (float) $t['pnl'] >= 0 ? 'up' : 'down' ?>"><?= e(money($t['pnl'], $cur, true)) ?></td><td data-label="R" class="r num"><?= $t['rr'] === null ? '—' : e(number_format((float) $t['rr'], 2)) . 'R' ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
  <?php else: ?><p class="muted">No closed trades on this day.</p><?php endif; ?>
</section>
<?php endif; ?>
