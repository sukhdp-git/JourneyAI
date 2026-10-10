<?php
use App\Trading\Domain;
$cur = $acc['currency'];
$total = 9; $doneN = count($done);
$first = trim(explode(' ', trim((string) $m['name']))[0] ?? '') ?: 'trader';
$hour = (int) (new DateTimeImmutable('now', new DateTimeZone($m['timezone'] ?: 'UTC')))->format('G');
$part = $hour < 5 ? 'Late session' : ($hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening'));
?>
<section class="hello" aria-label="Greeting">
  <span class="hello-wave" aria-hidden="true">👋</span>
  <div><h2>Hi <span><?= e($first) ?></span>,</h2><p><?= e($part) ?> — trade your plan, not your emotions.</p></div>
</section>
<section class="quote-hero" data-quotes="<?= e(json_encode(Domain::QUOTES)) ?>" data-start="<?= (int) $quoteStart ?>" aria-label="Trading psychology">
  <?php $q = Domain::QUOTES[$quoteStart]; ?>
  <div class="quote-hero-body">
    <span class="quote-theme" data-q-theme><?= e($q[2]) ?></span>
    <blockquote><p data-q-text>“<?= e($q[0]) ?>”</p><cite data-q-by>— <?= e($q[1]) ?></cite></blockquote>
  </div>
  <button type="button" class="tm-btn tm-btn-sm tm-btn-ghost" data-q-next aria-label="Next quote">Next thought →</button>
</section>

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
      <?php if ($limits['daily']['limit'] !== null): ?><p class="small">Today’s remaining loss budget: <strong class="<?= $limits['daily']['remaining'] > 0 ? 'up' : 'down' ?>"><?= e(money($limits['daily']['remaining'], $cur)) ?></strong></p><?php endif; ?>
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
      <?php $hubSpecs = []; foreach (App\Trading\Instruments::all() as $s => $i) { $hubSpecs[$s] = ['a' => array_values($i['aliases'])]; } ?>
      <form class="tm-quick" data-quick-form data-hub-voice data-specs="<?= e(json_encode($hubSpecs)) ?>">
        <div class="cmd-box"><button type="button" class="hub-mic" data-hub-mic aria-pressed="false" aria-label="Speak your trade — it is logged automatically"<?= $tilt['active'] ? ' disabled' : '' ?>><?= icon('mic', 'icon') ?></button><label for="hub-cmd" class="sr-only">Quick trade command</label><input id="hub-cmd" class="tm-cmd" autocomplete="off" spellcheck="false" maxlength="300" placeholder="<?= e(t('quick.placeholder')) ?>" data-quick-input<?= $tilt['active'] ? ' disabled' : '' ?>><button class="tm-btn tm-btn-primary" type="submit"<?= $tilt['active'] ? ' disabled' : '' ?>>Log ↵</button></div>
        <div class="hub-voice-bar" data-hub-voice-bar>
          <span class="hub-voice-status" data-hub-status>🎙 Tap the mic and say your trade in one sentence — e.g. “Bought gold at 2860, stop 2855, exit 2872, half a lot”. It is logged automatically.</span>
          <span class="hub-locales" role="group" aria-label="Voice language"><?php foreach (['en-US' => 'EN', 'ru-RU' => 'RU', 'zh-CN' => '中文', 'pt-BR' => 'PT'] as $code => $lbl): ?><button type="button" class="lt-chip" data-hub-locale="<?= $code ?>"><?= $lbl ?></button><?php endforeach; ?></span>
        </div>
        <div class="tm-quick-preview" data-quick-preview aria-live="polite"></div>
        <p class="tm-hint">Examples: <code>short us500 5880 sl 5890 2.5r 1.0 silver bullet</code> · <code>long eurusd 1.0850 sl 1.0830 2r</code> · <code>sell btc 62500 sl 63000 tp 61000 0.2</code> · add <code>loss</code>, <code>be</code> or <code>#fomo</code></p>
      </form>
      <?php endif; ?>
      <?php if (App\Trading\MarketWidgets::enabled()): ?><div class="hub-ticker"><?= App\Core\View::partial('terminal/partials/tv-widget', ['type' => 'ticker', 'm' => $m, 'height' => 46, 'label' => 'Live market ticker']) ?></div><?php endif; ?>
    </section>
    <section class="panel">
      <div class="panel-head"><h2>Today</h2><span class="head-actions"><?php if ($todaySum['trades']): ?><button type="button" class="tm-btn tm-btn-sm flex-btn" data-flex-day="<?= e($today) ?>"><?= icon('share', 'icon icon-sm') ?> Flex card</button><?php endif; ?><a class="small" href="<?= e(url('/terminal/trades')) ?>">Trade log →</a></span></div>
      <div class="kpis" style="margin:0 0 10px">
        <div class="kpi"><small>Net P&amp;L</small><strong class="<?= $todaySum['net'] >= 0 ? 'up' : 'down' ?>"><?= e(money($todaySum['net'], $cur, true)) ?></strong></div>
        <div class="kpi"><small>Trades</small><strong><?= (int) $todaySum['trades'] ?></strong><em><?= (int) $todaySum['wins'] ?>W · <?= (int) $todaySum['losses'] ?>L</em></div>
        <?php $D = $limits['daily']; ?><div class="kpi"><small>Daily loss budget left</small><strong class="<?= $D['limit'] === null ? '' : ($D['reached'] ? 'down' : ($D['warning'] ? 'warn' : 'up')) ?>"><?= $D['limit'] === null ? '—' : e(money($D['remaining'], $cur)) ?></strong><em><?= $D['limit'] === null ? '<a href="' . e(url('/terminal/accounts#limits')) . '">Set a daily limit</a>' : 'of ' . e(money($D['limit'], $cur)) ?></em></div>
      </div>
      <?php if ($latest): ?>
      <div class="table-wrap"><table class="tbl cards"><thead><tr><th>Time</th><th>Symbol</th><th>Side</th><th class="r">P&amp;L</th><th class="r">R</th><th>Setup</th></tr></thead><tbody>
        <?php foreach ($latest as $t): ?><tr><td data-label="Time"><a href="<?= e(url('/terminal/trades/' . $t['id'])) ?>"><?= e(fmt_date($t['executed_at'], 'M j H:i')) ?></a></td><td data-label="Symbol" class="strong"><?= e($t['symbol']) ?></td><td data-label="Side"><span class="badge <?= strtolower($t['side']) ?>"><?= e($t['side']) ?></span></td><td data-label="P&amp;L" class="r num <?= (float) $t['pnl'] >= 0 ? 'up' : 'down' ?>"><?= $t['pnl'] === null ? 'OPEN' : e(money($t['pnl'], $cur, true)) ?></td><td data-label="R" class="r num"><?= $t['rr'] === null ? '—' : e(number_format((float) $t['rr'], 2)) . 'R' ?></td><td data-label="Setup"><?= e($t['strategy_name'] ?: $t['setup_tag'] ?: '—') ?></td></tr><?php endforeach; ?>
      </tbody></table></div>
      <?php else: ?><div class="empty"><?= icon('journal', 'icon') ?><?= e(t('empty.trades')) ?></div><?php endif; ?>
    </section>

  </div>
  <div class="stack">
    <section class="panel clock-panel" data-clock="<?= e($tz) ?>">
      <div class="panel-head"><h2><?= icon('clock', 'icon icon-sm') ?> Your time</h2>
        <form method="post" action="<?= e(url('/terminal/timezone')) ?>"><?= csrf_field() ?><label class="sr-only" for="hub-tz">Timezone</label>
          <select id="hub-tz" name="timezone" data-autosubmit class="tz-select"><?php $zones = App\Trading\Sessions::ZONES; if (!in_array($tz, $zones, true)) $zones = [$tz => $tz] + $zones; foreach ($zones as $label => $zone): ?><option value="<?= e($zone) ?>"<?= $zone === $tz ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></form></div>
      <strong class="clock-time num" data-clock-time><?= e((new DateTimeImmutable('now', new DateTimeZone($tz)))->format('H:i:s')) ?></strong>
      <span class="muted small" data-clock-date><?= e((new DateTimeImmutable('now', new DateTimeZone($tz)))->format('l j F')) ?></span>
      <div class="sessions" aria-label="Trading sessions">
        <?php foreach ($sessions as $key => $sx): ?><div class="session<?= $sx['active'] ? ' on' : '' ?>" data-session data-zone="<?= e($sx['zone']) ?>" data-open="<?= $sx['open'] ?>" data-close="<?= $sx['close'] ?>"><strong><?= e($sx['label']) ?></strong><small data-session-state><?= $sx['active'] ? 'ACTIVE' : 'closed' ?></small><em><?= e($sx['local_hours']) ?> your time</em></div><?php endforeach; ?>
      </div>
      <p class="muted small" style="margin:6px 0 0">Main cash sessions Mon–Fri in each market's local time (London &amp; New York daylight-saving handled). Holidays not modelled.</p>
    </section>
    <section class="panel">
      <div class="panel-head"><h2><?= icon('alert', 'icon icon-sm') ?> Risk limits</h2><a class="small" href="<?= e(url('/terminal/accounts#limits')) ?>">Edit</a></div>
      <?php foreach (['daily' => 'Today', 'weekly' => 'This week'] as $lk => $ll): $L = $limits[$lk]; ?>
      <div class="limit-row">
        <div class="limit-head"><span><?= $ll ?></span><strong class="num <?= $L['net'] >= 0 ? 'up' : 'down' ?>"><?= e(money($L['net'], $cur, true)) ?></strong></div>
        <?php if ($L['limit'] !== null): $pc = min(100, (int) round(($L['used'] ?? 0) * 100)); ?>
        <div class="progress <?= $L['reached'] ? 'bad' : ($L['warning'] ? 'warn' : '') ?>" role="progressbar" aria-label="<?= $ll ?> loss limit used" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $pc ?>"><span style="width:<?= $pc ?>%"></span></div>
        <small class="muted">Loss <?= e(money($L['loss'], $cur)) ?> of <?= e(money($L['limit'], $cur)) ?><?= $L['type'] === 'percent' ? ' (' . e(rtrim(rtrim(number_format((float) $L['value'], 2), '0'), '.')) . '%)' : '' ?> · risked <?= e(money($L['risked'], $cur)) ?><?= $L['reached'] ? ' · <strong class="down">LIMIT REACHED</strong>' : '' ?></small>
        <?php else: ?><small class="muted">No <?= $lk ?> limit set.</small><?php endif; ?>
      </div>
      <?php endforeach; ?>
    </section>
    <section class="panel checklist" data-checklist>
      <div class="panel-head"><h2><?= icon('check-circle', 'icon icon-sm') ?> Discipline checklist</h2><span class="small num" data-check-count><?= $doneN ?> / <?= $total ?></span></div>
      <div class="progress" role="progressbar" aria-label="Checklist progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int) round($doneN / $total * 100) ?>" style="margin-bottom:10px"><span data-check-progress style="width:<?= (int) round($doneN / $total * 100) ?>%"></span></div>
      <?php foreach (Domain::CHECKLIST as $phase => $items): ?>
      <fieldset><legend><?= e(str_replace('_', '-', strtolower($phase))) ?></legend>
        <?php foreach ($items as $k => $label): ?><label><input type="checkbox" value="<?= e($k) ?>" data-date="<?= e($today) ?>"<?= in_array($k, $done, true) ? ' checked' : '' ?>><span><?= e($label) ?></span></label><?php endforeach; ?>
      </fieldset>
      <?php endforeach; ?>
    </section>
    <a class="panel calc-link" href="<?= e(url('/terminal/calculator')) ?>"><?= icon('scale', 'icon') ?><div><strong>Position size &amp; risk calculator</strong><small class="muted">Lot size from % or fixed risk, P&amp;L and R:R for any instrument →</small></div></a>
  </div>
</div>
