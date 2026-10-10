<?php
/** Product showcase: real terminal screenshots (demo data) with a scroll-driven stage, an auto-playing tour and feature rows. @var array $s */
$pi = fn ($f) => e(asset('images/product/' . $f));
$img = fn (string $f, string $alt, string $cls = '', bool $lazy = true) => '<img class="' . $cls . '" src="' . $pi($f . '.webp') . '" srcset="' . $pi($f . '-sm.webp') . ' 800w, ' . $pi($f . '.webp') . ' 1600w" sizes="(max-width: 999px) 94vw, 760px" width="1600" height="1000" alt="' . e($alt) . '"' . ($lazy ? ' loading="lazy" decoding="async"' : '') . '>';
$tour = [
    ['voice', 'mic', 'Voice logging', 'Say it. It’s logged.', 'Speak one sentence — “sold gold at 2860, stop 2870, exit 2840, half a lot” — in English, Русский, 中文 or Português. Risk, R:R and P&L are calculated instantly.', 'logtrade', 'Log Trade with voice: every field filled from one spoken sentence'],
    ['dash', 'dashboard', 'Dashboard', 'Your whole account, one glance.', 'Equity curve with deposits and withdrawals, win rate, profit factor, drawdown and P&L by weekday, session, instrument and strategy — charts draw themselves live.', 'dashboard', 'Dashboard with equity curve, KPIs and drawdown'],
    ['edge', 'radar', 'Edge Matrix', 'Find the edge. Cut the leak.', 'Your best and worst trading windows, a blow-up probability radar, rule violations that cost money and a 20% runner audit — decoded into plain language.', 'edge', 'Edge Matrix: best trading window, anti-window and audit'],
    ['prop', 'shield', 'Prop firm rules', 'Never breach a challenge by surprise.', 'Add any prop firm, pick the challenge type and the rules set themselves up: profit target, daily loss, max drawdown (static or trailing), trading days and consistency — live on your dashboard.', 'dashboard-prop', 'Prop firm rules panel with live progress bars'],
    ['strat', 'target', 'Strategy analysis', 'Know which setup actually pays.', 'Rank every strategy by profit, win rate, average R and rule compliance, with win rate by Asian, London and New York session.', 'strategies', 'Strategy scoreboard and win rate by session'],
    ['cal', 'calendar', 'Calendar & flex cards', 'Green days you can prove.', 'A P&L calendar with weekly totals — and a shareable flex card for any day or trade, with a QR code that opens “Verified by journzey.ai”.', 'calendar', 'P&L calendar with green and red days'],
];
$chips = ['Voice logging in 4 languages', 'Prop firm rule tracker', 'Unlimited broker accounts', 'Tilt breaker with alert beep', 'Blow-up probability radar', 'Verified flex cards', 'AI Coach', 'TradingView live charts', 'Position-size calculator', 'Daily psychology notepad', '10-strategy University', 'Dark & light premium themes'];
?>
<section class="section showcase" aria-labelledby="sc-title">
  <div class="container">
    <div class="section-head reveal"><p class="eyebrow"><?= e($s['eyebrow'] ?: 'Inside the terminal') ?></p><h2 id="sc-title"><?= e($s['heading'] ?: 'Everything a serious trader needs — in one terminal') ?></h2><?php if ($s['subheading']): ?><p class="lead"><?= e($s['subheading']) ?></p><?php endif; ?></div>
    <div class="sc-stage" data-sc-stage>
      <div class="sc-aura" aria-hidden="true"></div>
      <div class="shot-frame sc-main">
        <div class="shot-bar" aria-hidden="true"><span></span><span></span><span></span><em>journzey.ai · Home Hub</em></div>
        <?= $img('home', 'journzey.ai Home Hub: greeting, daily focus quote, execution desk with voice logging and session clock (demo data)') ?>
      </div>
      <img class="sc-pop sc-pop-a" src="<?= $pi('prop.webp') ?>" width="1400" height="300" alt="Prop firm rules: profit target, daily loss, max loss and trading days" loading="lazy">
      <img class="sc-pop sc-pop-b" src="<?= $pi('flexcard.webp') ?>" width="720" height="900" alt="Shareable flex card with Verified by journzey.ai QR code" loading="lazy">
    </div>
  </div>

  <div class="sc-marquee" aria-label="Features">
    <div class="sc-track"><?php for ($r = 0; $r < 2; $r++): foreach ($chips as $c): ?><span<?= $r ? ' aria-hidden="true"' : '' ?>><?= icon('check', 'icon icon-sm') ?><?= e($c) ?></span><?php endforeach; endfor; ?></div>
  </div>

  <div class="container">
    <div class="sc-tour" data-sc-tour>
      <div class="sc-tabs" role="tablist" aria-label="Product tour">
        <?php foreach ($tour as $i => [$k, $ic, $tab, $h, $p]): ?>
        <button type="button" role="tab" id="sct-<?= $k ?>" aria-controls="scp-<?= $k ?>" aria-selected="<?= $i ? 'false' : 'true' ?>" class="sc-tab<?= $i ? '' : ' on' ?>" data-sc-tab="<?= $i ?>">
          <span class="sc-tab-ic"><?= icon($ic, 'icon icon-sm') ?></span><span class="sc-tab-txt"><b><?= e($tab) ?></b><small><?= e($h) ?></small></span><i class="sc-progress" aria-hidden="true"></i>
        </button>
        <?php endforeach; ?>
      </div>
      <div class="sc-panels">
        <?php foreach ($tour as $i => [$k, $ic, $tab, $h, $p, $file, $alt]): ?>
        <div class="sc-panel<?= $i ? '' : ' on' ?>" role="tabpanel" id="scp-<?= $k ?>" aria-labelledby="sct-<?= $k ?>"<?= $i ? ' hidden' : '' ?>>
          <div class="sc-copy"><h3><?= e($h) ?></h3><p><?= e($p) ?></p></div>
          <div class="shot-frame"><div class="shot-bar" aria-hidden="true"><span></span><span></span><span></span><em><?= e($tab) ?></em></div><?= $img($file, $alt . ' (demo data)') ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="sc-rows">
      <article class="sc-row reveal">
        <div class="sc-row-copy"><p class="eyebrow">Discipline, measured</p><h3>The tilt breaker that actually stops you</h3><p>Three losses in twenty minutes? journzey.ai sounds an alert, starts a cooldown and pauses quick logging. Daily and weekly loss limits on every account beep and turn red before the damage gets worse.</p>
          <ul class="sc-list"><li><?= icon('check', 'icon icon-sm') ?>Per-account daily &amp; weekly limits</li><li><?= icon('check', 'icon icon-sm') ?>Audio alert + breathing reset</li><li><?= icon('check', 'icon icon-sm') ?>Rules-followed score on every trade</li></ul></div>
        <div class="shot-frame sc-row-shot"><div class="shot-bar" aria-hidden="true"><span></span><span></span><span></span><em>Edge Matrix</em></div><?= $img('edge', 'Edge Matrix with keep-doing and cut-out audit (demo data)') ?></div>
      </article>
      <article class="sc-row sc-row-flip reveal">
        <div class="sc-row-copy"><p class="eyebrow">Two premium themes</p><h3>Obsidian Pro by night. Clean Light by day.</h3><p>A dense, data-first terminal designed for long sessions — every number tabular, every gain green and every loss red, in a theme that matches your desk.</p>
          <ul class="sc-list"><li><?= icon('check', 'icon icon-sm') ?>Works on desktop, tablet and phone</li><li><?= icon('check', 'icon icon-sm') ?>English, Русский, 中文, Português</li><li><?= icon('check', 'icon icon-sm') ?>TradingView charts, heatmaps, news &amp; calendar built in</li></ul></div>
        <div class="sc-duo"><div class="shot-frame"><?= $img('dashboard-light', 'Dashboard in the Clean Light theme (demo data)') ?></div><div class="shot-frame"><?= $img('university', 'journzey.ai University: 10 institutional strategies (demo data)') ?></div></div>
      </article>
    </div>
    <p class="sc-note">Screens are from the journzey.ai terminal using the built-in demo journal (sample data). Past performance — yours or anyone’s — does not guarantee future results.</p>
  </div>
</section>
