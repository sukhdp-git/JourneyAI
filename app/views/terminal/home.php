<?php
use App\Trading\Domain;
use App\Trading\Sessions;
$cur = $acc['currency'];
$total = 9; $doneN = count($done);
?>
<div class="ticker" data-ticker data-live="<?= $market['live'] ? '1' : '0' ?>" aria-label="Market ticker">
  <span class="ticker-flag<?= $market['live'] ? ' live' : '' ?>" title="<?= $market['live'] ? 'Quotes from Twelve Data, refreshed every 60 seconds' : 'No market-data API configured — static reference levels, not live prices' ?>"><?= $market['live'] ? 'LIVE' : 'DEMO DATA' ?></span>
  <?php foreach ($market['quotes'] as $q): ?>
  <div class="ticker-item" data-sym="<?= e($q['symbol']) ?>"><small><?= e($q['name']) ?></small><strong><?= e($q['price']) ?></strong><em class="<?= $q['up'] ? 'up' : 'down' ?>"><?= e($q['change']) ?></em></div>
  <?php endforeach; ?>
</div>
<?php if ($market['error']): ?><div class="tm-alert tm-alert-warn"><p><?= e($market['error']) ?></p></div><?php endif; ?>

<?php if ($tilt['active']): ?>
<section class="panel tilt" role="alert" style="margin-bottom:12px">
  <div class="grid g-3">
    <div>
      <h2>⚠ TILT RISK DETECTED</h2>
      <p><?= (int) $m['tilt_loss_count'] ?> losing trades within <?= (int) $m['tilt_window_minutes'] ?> minutes. Step away from the screen.</p>
      <p class="strong">Cooldown: <span class="num" data-countdown="<?= (int) $tilt['ends_at'] ?>">–</span></p>
      <p class="lock-note"><strong>JOURNZEY TERMINAL LOCK</strong> — quick trade logging is paused in this terminal during the cooldown.<br><strong>BROKER EXECUTION LOCK</strong> — not available: journzey.ai cannot block orders at your broker.</p>
    </div>
    <div style="text-align:center"><div class="breath" aria-hidden="true"></div><p class="muted small">Breathe in as the circle grows, out as it shrinks. Four slow cycles.</p></div>
    <div>
      <h3>Recent losses</h3>
      <ul class="small"><?php foreach ($tilt['losses'] as $l): ?><li><?= e(fmt_date($l['executed_at'], 'H:i')) ?> · <?= e($l['symbol']) ?> · <span class="down"><?= e(money($l['pnl'], $cur)) ?></span></li><?php endforeach; ?></ul>
      <?php if ($budget !== null): ?><p class="small">Today’s remaining loss budget: <strong class="<?= $budget > 0 ? 'up' : 'down' ?>"><?= e(money(max(0, $budget), $cur)) ?></strong></p><?php endif; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (!$hasDemoData && !$latest): ?>
<div class="tm-alert tm-alert-info"><?= icon('info', 'icon icon-sm') ?><p>New here? <strong>Load the demo journal</strong> to explore every chart with 42 sample trades (clearly marked DEMO DATA, kept in a separate account).</p>
  <form method="post" action="<?= e(url('/terminal/demo/load')) ?>" style="margin-left:auto"><?= csrf_field() ?><button class="tm-btn tm-btn-sm tm-btn-primary">Load demo data</button></form></div>
<?php endif; ?>

<div class="grid g-main">
  <div class="stack">
    <section class="panel">
      <div class="panel-head"><h2><?= icon('zap', 'icon icon-sm') ?> Execution desk</h2><span class="muted small">Logging to <strong><?= e($acc['name']) ?></strong> (<?= $acc['is_demo'] ? 'demo' : 'live' ?>)</span></div>
      <?php if (!$acc['writable']): ?>
        <p class="tm-alert tm-alert-error">This live account is read-only without an active plan. <a href="<?= e(url('/pricing')) ?>">Upgrade</a> or switch to a demo account.</p>
      <?php else: ?>
      <form class="tm-quick" data-quick-form>
        <div class="cmd-box"><label for="hub-cmd" class="sr-only">Quick trade command</label><input id="hub-cmd" class="tm-cmd" autocomplete="off" spellcheck="false" maxlength="300" placeholder="<?= e(t('quick.placeholder')) ?>" data-quick-input<?= $tilt['active'] ? ' disabled' : '' ?>><button class="tm-btn tm-btn-primary" type="submit"<?= $tilt['active'] ? ' disabled' : '' ?>>Log ↵</button></div>
        <div class="tm-quick-preview" data-quick-preview aria-live="polite"></div>
        <p class="tm-hint">Examples: <code>short us500 5880 sl 5890 2.5r 1.0 silver bullet</code> · <code>long eurusd 1.0850 sl 1.0830 2r</code> · <code>sell btc 62500 sl 63000 tp 61000 0.2</code> · add <code>loss</code>, <code>be</code> or <code>#fomo</code></p>
      </form>
      <?php endif; ?>
    </section>
    <section class="panel">
      <div class="panel-head"><h2>Today</h2><a class="small" href="<?= e(url('/terminal/trades')) ?>">Trade log →</a></div>
      <div class="kpis" style="margin:0 0 10px">
        <div class="kpi"><small>Net P&amp;L</small><strong class="<?= $todaySum['net'] >= 0 ? 'up' : 'down' ?>"><?= e(money($todaySum['net'], $cur, true)) ?></strong></div>
        <div class="kpi"><small>Trades</small><strong><?= (int) $todaySum['trades'] ?></strong><em><?= (int) $todaySum['wins'] ?>W · <?= (int) $todaySum['losses'] ?>L</em></div>
        <div class="kpi"><small>Loss budget left</small><strong><?= $budget === null ? '—' : e(money(max(0, $budget), $cur)) ?></strong><em><?= $m['max_daily_loss'] === null ? 'Set in Settings' : 'of ' . e(money($m['max_daily_loss'], $cur)) ?></em></div>
      </div>
      <?php if ($latest): ?>
      <div class="table-wrap"><table class="tbl cards"><thead><tr><th>Time</th><th>Symbol</th><th>Side</th><th class="r">P&amp;L</th><th class="r">R</th><th>Setup</th></tr></thead><tbody>
        <?php foreach ($latest as $t): ?><tr><td data-label="Time"><a href="<?= e(url('/terminal/trades/' . $t['id'])) ?>"><?= e(fmt_date($t['executed_at'], 'M j H:i')) ?></a></td><td data-label="Symbol" class="strong"><?= e($t['symbol']) ?></td><td data-label="Side"><span class="badge <?= strtolower($t['side']) ?>"><?= e($t['side']) ?></span></td><td data-label="P&amp;L" class="r num <?= (float) $t['pnl'] >= 0 ? 'up' : 'down' ?>"><?= $t['pnl'] === null ? 'OPEN' : e(money($t['pnl'], $cur, true)) ?></td><td data-label="R" class="r num"><?= $t['rr'] === null ? '—' : e(number_format((float) $t['rr'], 2)) . 'R' ?></td><td data-label="Setup"><?= e($t['strategy_name'] ?: $t['setup_tag'] ?: '—') ?></td></tr><?php endforeach; ?>
      </tbody></table></div>
      <?php else: ?><div class="empty"><?= icon('journal', 'icon') ?><?= e(t('empty.trades')) ?></div><?php endif; ?>
    </section>
    <section class="panel">
      <div class="panel-head"><h2><?= icon('clock', 'icon icon-sm') ?> World markets</h2><span class="muted small">Cash sessions, Mon–Fri (holidays not modelled)</span></div>
      <div class="clocks"><?php foreach (Sessions::CLOCKS as $city => [$zone, $o, $c]): ?><div class="clock" data-clock="<?= e($zone) ?>" data-open="<?= $o ?>" data-close="<?= $c ?>"><small><?= e($city) ?> <span class="st">–</span></small><strong class="num">--:--:--</strong></div><?php endforeach; ?></div>
    </section>
  </div>
  <div class="stack">
    <section class="panel"><blockquote class="quote" style="margin:0">“<?= e($quote[0]) ?>”<cite>— <?= e($quote[1]) ?></cite></blockquote></section>
    <section class="panel checklist" data-checklist>
      <div class="panel-head"><h2><?= icon('check-circle', 'icon icon-sm') ?> Discipline checklist</h2><span class="small num" data-check-count><?= $doneN ?> / <?= $total ?></span></div>
      <div class="progress" role="progressbar" aria-label="Checklist progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int) round($doneN / $total * 100) ?>" style="margin-bottom:10px"><span data-check-progress style="width:<?= (int) round($doneN / $total * 100) ?>%"></span></div>
      <?php foreach (Domain::CHECKLIST as $phase => $items): ?>
      <fieldset><legend><?= e(str_replace('_', '-', strtolower($phase))) ?></legend>
        <?php foreach ($items as $k => $label): ?><label><input type="checkbox" value="<?= e($k) ?>" data-date="<?= e($today) ?>"<?= in_array($k, $done, true) ? ' checked' : '' ?>><span><?= e($label) ?></span></label><?php endforeach; ?>
      </fieldset>
      <?php endforeach; ?>
    </section>
    <section class="panel">
      <div class="panel-head"><h2><?= icon('scale', 'icon icon-sm') ?> Lot size calculator</h2></div>
      <form class="grid-form" data-lot-form>
        <div class="f"><label for="lc-s">Instrument</label><select id="lc-s" name="symbol"><?php foreach (App\Trading\Instruments::all() as $s => $i): ?><option value="<?= e($s) ?>"><?= e($s) ?></option><?php endforeach; ?></select></div>
        <div class="f"><label for="lc-e">Equity</label><input id="lc-e" name="equity" type="number" step="any" value="<?= e((string) $balance['equity']) ?>"></div>
        <div class="f"><label for="lc-r">Risk %</label><input id="lc-r" name="risk" type="number" step="any" value="<?= e((string) $m['default_risk_pct']) ?>"></div>
        <div class="f"><label for="lc-en">Entry</label><input id="lc-en" name="entry" type="number" step="any" required></div>
        <div class="f"><label for="lc-st">Stop</label><input id="lc-st" name="stop" type="number" step="any" required></div>
        <div class="f" style="align-self:end"><button class="tm-btn tm-btn-block" type="submit">Calculate</button></div>
        <p class="span-all strong num" data-lot-out aria-live="polite"></p>
      </form>
    </section>
  </div>
</div>
