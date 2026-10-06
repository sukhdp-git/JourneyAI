<?php
use App\Trading\Domain;
use App\Trading\Instruments as I;
$cur = $tacc['currency'];
$fmt = fn ($v) => I::format($t['symbol'], $v);
$share = ['symbol' => $t['symbol'], 'side' => $t['side'], 'pnl' => $t['pnl'] === null ? 'OPEN' : money($t['pnl'], $cur, true), 'r' => $t['rr'] === null ? '' : ((float) $t['rr'] > 0 ? '+' : '') . number_format((float) $t['rr'], 2) . 'R',
  'entry' => $fmt($t['entry_price']), 'exit' => $fmt($t['exit_price']), 'stop' => $fmt($t['stop_loss']), 'lots' => rtrim(rtrim((string) $t['lot_size'], '0'), '.'), 'strategy' => $t['strategy_name'] ?: ($t['setup_tag'] ?: '—'),
  'session' => Domain::SESSIONS[$t['session']] ?? '—', 'brand' => setting('site_name', 'journzey.ai'), 'demo' => (bool) $tacc['has_demo_data']];
$writable = App\Trading\Ledger::writable($m, $tacc);
?>
<div class="toolbar"><a class="tm-btn tm-btn-sm" href="<?= e(url('/terminal/trades')) ?>">← Trade log</a>
  <?php if ($writable): ?><a class="tm-btn tm-btn-sm" href="<?= e(url('/terminal/trades/' . $t['id'] . '/edit')) ?>"><?= icon('edit', 'icon icon-sm') ?> Edit</a>
  <form method="post" action="<?= e(url('/terminal/trades/' . $t['id'] . '/delete')) ?>" data-confirm="Delete this trade permanently?"><?= csrf_field() ?><button class="tm-btn tm-btn-sm" type="submit"><?= icon('trash', 'icon icon-sm') ?> Delete</button></form><?php endif; ?>
</div>
<div class="grid g-2">
  <section class="panel">
    <div class="panel-head"><h2><?= e($t['symbol']) ?> <span class="badge <?= strtolower($t['side']) ?>"><?= e($t['side']) ?></span></h2><span class="badge"><?= e($t['source']) ?></span></div>
    <dl class="dl">
      <dt>Executed</dt><dd><?= e(fmt_date($t['executed_at'], 'D j M Y, H:i')) ?> (<?= e($tz) ?>)</dd>
      <dt>Status</dt><dd><?= e($t['status']) ?></dd>
      <dt>Entry / Exit</dt><dd class="num"><?= e($fmt($t['entry_price'])) ?> → <?= e($fmt($t['exit_price'])) ?></dd>
      <dt>Stop / Target</dt><dd class="num"><?= e($fmt($t['stop_loss'])) ?> / <?= e($fmt($t['take_profit'])) ?></dd>
      <dt>Lot size</dt><dd class="num"><?= e($share['lots']) ?></dd>
      <dt>P&amp;L</dt><dd class="num <?= (float) $t['pnl'] >= 0 ? 'up' : 'down' ?>"><?= e($share['pnl']) ?><?= $t['pnl_override'] ? ' <span class="muted small">(broker-reported)</span>' : '' ?></dd>
      <dt>R multiple</dt><dd class="num"><?= e($share['r'] ?: '—') ?></dd>
      <dt>Planned risk (1R)</dt><dd class="num"><?= e(money($t['risk_amount'], $cur)) ?></dd>
      <dt>Fees</dt><dd class="num"><?= e(money($t['fees'], $cur)) ?></dd>
      <dt>Strategy</dt><dd><?= e($share['strategy']) ?></dd>
      <dt>Session</dt><dd><?= e($share['session']) ?></dd>
      <dt>Emotion</dt><dd><?= e($t['emotion'] ? ucfirst(strtolower($t['emotion'])) : '—') ?></dd>
      <dt>Mistake</dt><dd><?= e(Domain::MISTAKES[$t['mistake_tag']] ?? '—') ?> · rules <?= $t['rules_followed'] ? 'followed ✓' : 'broken ✕' ?></dd>
    </dl>
    <?php if (App\Trading\MarketData::configured() && $t['status'] === 'CLOSED' && $t['stop_loss'] !== null): ?>
    <div style="margin-top:12px"><button type="button" class="tm-btn tm-btn-sm" data-runner="<?= e(url('/terminal/trades/' . $t['id'] . '/runner')) ?>"><?= icon('trend-up', 'icon icon-sm') ?> Runner audit (hypothetical)</button><p class="small" data-runner-out aria-live="polite"></p></div>
    <?php endif; ?>
    <?php if ($t['notes']): ?><h3 style="margin-top:12px">Notes</h3><p style="white-space:pre-wrap"><?= e($t['notes']) ?></p><?php endif; ?>
  </section>
  <section class="panel">
    <div class="panel-head"><h2>Screenshot</h2><?php if ($t['screenshot_path'] && $writable): ?><form method="post" action="<?= e(url('/terminal/trades/' . $t['id'] . '/screenshot/delete')) ?>" data-confirm="Remove this screenshot?"><?= csrf_field() ?><button class="tm-btn tm-btn-sm">Remove</button></form><?php endif; ?></div>
    <?php if ($t['screenshot_path']): ?><a href="<?= e(url('/terminal/trades/' . $t['id'] . '/screenshot')) ?>" target="_blank" rel="noopener"><img class="share-preview" src="<?= e(url('/terminal/trades/' . $t['id'] . '/screenshot')) ?>" alt="Chart screenshot for this trade"></a><?php endif; ?>
    <?php if ($writable): ?>
    <form class="panel" style="border-style:dashed;text-align:center;margin-top:8px" action="<?= e(url('/terminal/trades/' . $t['id'] . '/screenshot')) ?>" data-shot-form>
      <p class="muted small">Drop an image here, paste from the clipboard (Ctrl/⌘ + V) or</p>
      <label class="tm-btn tm-btn-sm">Choose file<input type="file" accept="image/png,image/jpeg,image/webp" hidden></label>
      <p class="muted small" style="margin-top:6px">PNG, JPEG or WebP · max 5 MB · stored privately</p>
    </form>
    <?php endif; ?>
  </section>
  <section class="panel" style="grid-column:1/-1" data-share="<?= e(json_encode($share)) ?>">
    <div class="panel-head"><h2>Flex card</h2><span class="muted small">No account names or IDs are included</span></div>
    <div class="theme-dots" role="radiogroup" aria-label="Card theme">
      <?php foreach (['neon' => 'Neon Cyan', 'matrix' => 'Matrix Emerald', 'amethyst' => 'Cyber Amethyst', 'gold' => 'Gold Bullion'] as $k => $l): ?><label><input type="radio" name="card_theme" value="<?= $k ?>"<?= $k === 'neon' ? ' checked' : '' ?>><span><?= e($l) ?></span></label><?php endforeach; ?>
    </div>
    <canvas width="1080" height="560" class="share-preview" style="margin-top:10px;width:100%;max-width:720px" aria-label="Share card preview"></canvas>
    <div class="tm-modal-actions" style="justify-content:flex-start"><button type="button" class="tm-btn tm-btn-primary" data-card-download><?= icon('download', 'icon icon-sm') ?> Download PNG</button><button type="button" class="tm-btn" data-card-copy><?= icon('copy', 'icon icon-sm') ?> Copy image</button></div>
  </section>
</div>
