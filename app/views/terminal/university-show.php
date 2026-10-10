<?php
use App\Models\Learn;
use App\Trading\Domain;
/** @var array $s @var int $number @var int $total @var ?array $prev @var ?array $next @var bool $owned */
$rules = Learn::lines($s['setup_rules']);
$targets = Learn::lines($s['take_profit']);
?>
<div class="toolbar"><a class="tm-btn tm-btn-sm" href="<?= e(url('/terminal/university')) ?>"><?= icon('arrow-left', 'icon icon-sm') ?> University</a><span class="muted small">Strategy <?= $number ?> of <?= $total ?><?= $s['style'] ? ' · ' . e(Domain::STRATEGY_STYLES[$s['style']] ?? '') : '' ?></span></div>
<div class="uni-detail">
  <article class="stack">
    <section class="ex-card c-violet">
      <h2 class="uni-title"><?= e($s['title']) ?></h2>
      <p class="uni-lead"><?= e($s['summary']) ?></p>
      <h3 class="uni-h"><?= icon('compass', 'icon icon-sm') ?> Why it works</h3>
      <p><?= nl2br(e((string) $s['logic'])) ?></p>
    </section>
    <?php if ($rules): ?>
    <section class="ex-card c-blue">
      <h3 class="uni-h"><?= icon('list', 'icon icon-sm') ?> Setup rules</h3>
      <ol class="uni-rules"><?php foreach ($rules as $r): ?><li><?= e($r) ?></li><?php endforeach; ?></ol>
    </section>
    <?php endif; ?>
    <div class="uni-exec">
      <section class="ex-card c-cyan"><h3 class="uni-h"><?= icon('target', 'icon icon-sm') ?> Entry</h3><p><?= e((string) $s['entry_trigger']) ?></p></section>
      <section class="ex-card c-red"><h3 class="uni-h"><?= icon('shield', 'icon icon-sm') ?> Stop loss</h3><p><?= e((string) $s['stop_loss']) ?></p></section>
      <section class="ex-card c-green"><h3 class="uni-h"><?= icon('flag', 'icon icon-sm') ?> Take profit</h3><?php if (count($targets) > 1): ?><ul><?php foreach ($targets as $t): ?><li><?= e($t) ?></li><?php endforeach; ?></ul><?php else: ?><p><?= e($targets[0] ?? '') ?></p><?php endif; ?></section>
    </div>
    <?php if (trim((string) $s['educator_note']) !== ''): ?><p class="ex-callout good"><?= icon('clock', 'icon icon-xs') ?> <?= e($s['educator_note']) ?></p><?php endif; ?>
    <nav class="uni-pager">
      <?php if ($prev): ?><a class="tm-btn" href="<?= e(url('/terminal/university/' . $prev['slug'])) ?>"><?= icon('arrow-left', 'icon icon-sm') ?> <?= e($prev['short_title'] ?: $prev['title']) ?></a><?php else: ?><span></span><?php endif; ?>
      <?php if ($next): ?><a class="tm-btn" href="<?= e(url('/terminal/university/' . $next['slug'])) ?>"><?= e($next['short_title'] ?: $next['title']) ?> <?= icon('arrow-right', 'icon icon-sm') ?></a><?php endif; ?>
    </nav>
  </article>
  <aside class="stack uni-aside">
    <section class="ex-card c-amber">
      <h3 class="uni-h">Profile</h3>
      <dl class="uni-dl">
        <dt>Assets</dt><dd><?= e((string) $s['assets']) ?></dd>
        <dt>Session</dt><dd><?= e((string) $s['session_window']) ?></dd>
        <dt>Timeframe</dt><dd><?= e((string) ($s['timeframe_detail'] ?: $s['timeframe'])) ?></dd>
        <dt>Target R:R</dt><dd class="up"><?= e((string) $s['target_rr']) ?></dd>
      </dl>
      <?php if ($owned): ?>
        <p class="ex-callout good"><?= icon('check-circle', 'icon icon-xs') ?> Already in your strategies.</p>
        <a class="tm-btn tm-btn-block" href="<?= e(url('/terminal/strategies')) ?>">Open my strategies</a>
      <?php else: ?>
        <form method="post" action="<?= e(url('/terminal/strategies/import/' . $s['slug'])) ?>"><?= csrf_field() ?><button class="tm-btn tm-btn-primary tm-btn-block" type="submit"><?= icon('plus', 'icon icon-sm') ?> Add to my strategies</button></form>
      <?php endif; ?>
    </section>
    <p class="muted small">Educational content — not financial advice. Past behaviour of a setup does not guarantee future results.</p>
  </aside>
</div>
