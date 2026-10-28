<?php
/** Mind tab. @var array|null $roast @var array $clock @var array $ghost @var array|null $boss @var array $art @var string $level */
use App\Trading\Mind;
$cur = $acc['currency'];
?>
<div class="mind" data-mind data-level-url="<?= e(url('/terminal/mind/level')) ?>">
  <header class="mind-head">
    <h2>Your trading <span class="mind-grad">mind</span>.</h2>
    <p class="muted">Built only from your journal on <?= e($acc['name']) ?>. Honest, a little savage, never financial advice.</p>
  </header>

  <!-- ROAST -->
  <section class="panel mind-roast<?= $roast ? ' is-' . e($roast['kind']) : '' ?>" data-roast-card>
    <div class="mr-top">
      <span class="mr-who"><span class="mr-av">j</span><b>journzey</b><small><?= $roast ? 'about ' . e(fmt_date($roast['date'] . ' 12:00:00', 'D, M j')) : 'roast mode' ?></small></span>
      <div class="seg mr-seg" role="radiogroup" aria-label="Roast level">
        <?php foreach (Mind::LEVELS as $k => $l): ?><button type="button" role="radio" aria-checked="<?= $level === $k ? 'true' : 'false' ?>" class="<?= $level === $k ? 'on' : '' ?>" data-roast-level="<?= e($k) ?>"><?= e($l) ?></button><?php endforeach; ?>
      </div>
    </div>
    <?php if ($level === 'off'): ?>
      <p class="mr-bubble mr-muted">Roast Mode is off. Turn it on and journzey will text you the morning after you break your own rules.</p>
    <?php elseif ($roast): ?>
      <p class="mr-bubble" data-roast-text><?= e($roast['text']) ?></p>
      <div class="mr-actions"><span class="badge<?= $roast['kind'] === 'roast' ? ' loss' : ' win' ?>"><?= e($roast['title']) ?></span>
        <button type="button" class="tm-btn tm-btn-sm" data-share-card="roast" data-card='<?= e(json_encode(['text' => $roast['text'], 'title' => $roast['kind'] === 'roast' ? 'journzey roasted me 💀' : 'journzey approves ✅'])) ?>'><?= icon('share', 'icon icon-sm') ?> Share to story</button></div>
    <?php else: ?>
      <p class="mr-bubble mr-muted">Nothing to roast yet. Log your trades (and be honest with the mistake tags). Tomorrow morning I'll have notes. 👀</p>
    <?php endif; ?>
  </section>

  <div class="mind-grid">
    <!-- DEATH CLOCK -->
    <section class="panel mind-clock is-<?= e($clock['state']) ?>" data-death-clock data-death-at="<?= (int) ($clock['death_at'] ?? 0) ?>" data-speed="<?= e($clock['speed'] ?? 'slow') ?>">
      <div class="panel-head"><h2><?= icon('clock', 'icon icon-sm') ?> Account Death Clock</h2>
        <button type="button" class="tm-btn tm-btn-sm mc-sound" data-clock-sound aria-pressed="false" title="Ticking sound"><?= icon('volume-off', 'icon icon-sm') ?></button></div>
      <?php if ($clock['state'] === 'need'): ?>
        <div class="mc-face mc-idle">— : — : — : —</div><p class="muted small">Needs 10 closed trades to read your pace (you have <?= (int) $clock['trades'] ?>).</p>
      <?php elseif ($clock['state'] === 'alive'): ?>
        <div class="mc-face mc-alive">∞</div>
        <p class="small"><b>Not dying.</b> At your pace over the last <?= (int) $clock['trades'] ?> trades the account grows about <b class="pos"><?= e($clock['avg_fmt']) ?></b> per trading day. Keep the clock frozen.</p>
      <?php elseif ($clock['state'] === 'dead'): ?>
        <div class="mc-face">00 : 00 : 00 : 00</div><p class="small">This account has no equity left. Add a deposit or switch accounts.</p>
      <?php else: ?>
        <div class="mc-face" data-clock-face aria-live="off"><span data-u="d">--</span><i>d</i> <span data-u="h">--</span><i>h</i> <span data-u="m">--</span><i>m</i> <span data-u="s">--</span><i>s</i></div>
        <p class="small">At your last <?= (int) $clock['trades'] ?> trades' pace (<b class="neg"><?= e($clock['avg_fmt']) ?></b> a trading day) the account hits zero on <b><?= e(fmt_date(gmdate('Y-m-d H:i:s', (int) $clock['death_at']), 'M j, Y')) ?></b><?= !empty($clock['capped']) ? ' (or later)' : '' ?>.</p>
        <p class="small muted"><?= $clock['speed'] === 'fast' ? '⚡ Ticking fast: you broke a rule on your last trading day.' : '🐢 Ticking slower: your last trading day was clean.' ?> <?= (int) $clock['broken_pct'] ?>% of these trades broke a rule.<?= $clock['clean_positive'] ? ' Your rule-following trades alone make ' . e($clock['clean_avg_fmt']) . ' a day: follow the rules and the clock stops.' : '' ?></p>
        <button type="button" class="tm-btn tm-btn-sm" data-share-card="clock" data-card='<?= e(json_encode(['title' => 'My account death date', 'text' => fmt_date(gmdate('Y-m-d H:i:s', (int) $clock['death_at']), 'M j, Y'), 'sub' => 'at my current revenge-trading pace 💀'])) ?>'><?= icon('share', 'icon icon-sm') ?> Share</button>
      <?php endif; ?>
      <p class="mc-note">A projection from your recent trades, not a prediction.</p>
    </section>

    <!-- BOSS FIGHT -->
    <section class="panel mind-boss<?= $boss ? ' is-' . e($boss['state']) : '' ?>">
      <div class="panel-head"><h2><?= icon('target', 'icon icon-sm') ?> Boss fight · <?= e($boss['month'] ?? date('F')) ?></h2></div>
      <?php if (!$boss): ?>
        <p class="muted small">No trades this month yet. Your boss appears when your journal does.</p>
      <?php elseif ($boss['state'] === 'none'): ?>
        <div class="mb-empty">🕊️</div><p class="small"><b>No boss this month.</b> Not a single rule broken in <?= e($boss['month']) ?>. Keep it that way.</p>
      <?php else: ?>
        <div class="mb-row">
          <div class="mb-av" aria-hidden="true"><?= $boss['emoji'] ?></div>
          <div class="mb-info">
            <b class="mb-name"><?= e($boss['name']) ?></b>
            <div class="mb-hp" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int) $boss['hp'] ?>" aria-label="Boss health"><i style="--hp:<?= (int) $boss['hp'] ?>%"></i><span><?= (int) $boss['hp'] ?> HP</span></div>
            <small class="muted">Stole <b class="neg"><?= e($boss['stolen_fmt']) ?></b> from you this month · <?= (int) $boss['hits'] ?>× <?= e(\App\Trading\Domain::label($boss['code'])) ?></small>
          </div>
        </div>
        <?php if ($boss['state'] === 'defeated'): ?>
          <p class="mb-taunt mb-win">💀 You defeated <?= e($boss['name']) ?>.</p>
          <button type="button" class="tm-btn tm-btn-sm tm-btn-primary" data-share-card="boss" data-card='<?= e(json_encode(['title' => 'I defeated ' . $boss['name'], 'text' => $boss['emoji'] . ' ⚔️', 'sub' => 'Disciplined days only. ' . $boss['month'] . ' on journzey.'])) ?>'><?= icon('share', 'icon icon-sm') ?> Share the win</button>
        <?php else: ?>
          <p class="mb-taunt">“<?= e($boss['taunt']) ?>”</p>
        <?php endif; ?>
        <p class="mc-note">Every clean trading day hits it for 15 HP. Every <?= e(strtolower(\App\Trading\Domain::label($boss['code']))) ?> heals it by 10.</p>
      <?php endif; ?>
    </section>
  </div>

  <!-- GHOST -->
  <section class="panel mind-ghost">
    <div class="panel-head"><h2><?= icon('users', 'icon icon-sm') ?> The Ghost</h2><span class="muted small">last <?= (int) $ghost['trades'] ?> trades</span></div>
    <?php if ($ghost['trades'] < 3): ?>
      <p class="muted small">The Ghost copies your trades but follows your rules. Log a few trades and the race begins.</p>
    <?php else:
      $pts = $ghost['points']; $all = array_merge(array_column($pts, 0), array_column($pts, 1), [0]); $hi = max($all); $lo = min($all); $rng = max(1, $hi - $lo); $n = count($pts) - 1;
      $X = fn ($i) => round(10 + $i / max(1, $n) * 880, 1); $Y = fn ($v) => round(200 - ($v - $lo) / $rng * 180, 1);
      $line = fn ($k) => implode(' ', array_map(fn ($i) => $X($i) . ',' . $Y($pts[$i][$k]), array_keys($pts))); ?>
      <div class="mg-score">
        <div><small>You</small><b class="<?= $ghost['you'] < 0 ? 'neg' : 'pos' ?>"><?= e($ghost['you_fmt']) ?></b></div>
        <div><small>Your Ghost</small><b class="mg-gh"><?= e($ghost['ghost_fmt']) ?></b></div>
        <div class="mg-gap"><?= $ghost['gap'] > 0 ? 'The Ghost is <b>' . e($ghost['gap_fmt']) . '</b> ahead. Same trades. The only difference is you.' : ($ghost['gap'] < 0 ? 'You beat your Ghost by <b>' . e($ghost['gap_fmt']) . '</b>.' : 'Dead heat: you followed your rules.') ?></div>
      </div>
      <svg class="mg-chart" viewBox="0 0 900 210" preserveAspectRatio="none" role="img" aria-label="Your cumulative P&amp;L versus your Ghost's">
        <line x1="10" x2="890" y1="<?= $Y(0) ?>" y2="<?= $Y(0) ?>" class="mg-zero"/>
        <polyline class="mg-gl" points="<?= $line(1) ?>"/><polyline class="mg-yl" points="<?= $line(0) ?>"/>
        <circle class="mg-gd" cx="<?= $X($n) ?>" cy="<?= $Y($pts[$n][1]) ?>" r="6"/><circle class="mg-yd" cx="<?= $X($n) ?>" cy="<?= $Y($pts[$n][0]) ?>" r="6"/>
      </svg>
      <div class="mg-legend"><span class="y">You</span><span class="g">Ghost: skipped <?= (int) $ghost['skipped'] ?> rule-breaking trade<?= $ghost['skipped'] === 1 ? '' : 's' ?>, capped <?= (int) $ghost['capped'] ?> loss<?= $ghost['capped'] === 1 ? '' : 'es' ?> at your planned risk</span></div>
      <p class="mc-note">The Ghost never invents profits: it only removes trades that broke your rules and limits losses where you moved or skipped your stop.</p>
    <?php endif; ?>
  </section>

  <!-- PSYCH ART -->
  <section class="panel mind-art" data-art='<?= e(json_encode($art)) ?>' data-art-name="<?= e(explode(' ', (string) $m['name'])[0]) ?>">
    <div class="panel-head"><h2><?= icon('sparkles', 'icon icon-sm') ?> Psych Art · <?= e($art['week']) ?></h2>
      <div class="ma-nav"><a class="tm-btn tm-btn-sm" href="<?= e(url('/terminal/mind?week=' . max(-8, $art['offset'] - 1))) ?>" aria-label="Previous week"><?= icon('chevron-left', 'icon icon-sm') ?></a><?php if ($art['offset'] < 0): ?><a class="tm-btn tm-btn-sm" href="<?= e(url('/terminal/mind?week=' . ($art['offset'] + 1))) ?>" aria-label="Next week"><?= icon('chevron-right', 'icon icon-sm') ?></a><?php endif; ?></div></div>
    <div class="ma-grid">
      <canvas class="ma-canvas" width="540" height="960" data-art-canvas aria-label="Generative art of this week's trading psychology"></canvas>
      <div class="ma-side">
        <span class="ma-type"><?= e($art['type']) ?></span>
        <p class="small muted">Your week, painted by your trades. It changes every week.</p>
        <ul class="ma-key">
          <li><i class="k-cl"></i> Clean symmetry · disciplined trades <b><?= (int) $art['disciplined'] ?></b></li>
          <li><i class="k-rv"></i> Red spikes · revenge <b><?= (int) $art['revenge'] ?></b></li>
          <li><i class="k-fo"></i> Black tentacles · FOMO &amp; chasing <b><?= (int) $art['fomo'] ?></b></li>
          <li><i class="k-st"></i> Cracks · moved or no stop <b><?= (int) $art['stop'] ?></b></li>
          <li><i class="k-ot"></i> Haze · other rule breaks <b><?= (int) $art['other'] ?></b></li>
        </ul>
        <div class="ma-actions"><button type="button" class="tm-btn tm-btn-primary" data-art-download><?= icon('download', 'icon icon-sm') ?> 4K wallpaper</button><button type="button" class="tm-btn" data-art-share><?= icon('share', 'icon icon-sm') ?> Share</button></div>
      </div>
    </div>
  </section>
</div>
