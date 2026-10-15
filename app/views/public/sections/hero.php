<?php
/** @var array $s @var array $items @var array $opt */
$type = $opt['media_type'] ?? 'visual';
$align = ($opt['alignment'] ?? 'left') === 'center' ? 'center' : 'left';
$overlay = safe_color((string) ($opt['overlay_color'] ?? '#070a12'), '#070a12');
$opacity = max(0, min(95, (int) ($opt['overlay_opacity'] ?? 55))) / 100;
$hasImage = $type === 'image' && !empty($s['image']);
$hasVideo = $type === 'video' && !empty($opt['video_url']) && preg_match('#^(https://|uploads/)[^\s"<>]+\.(mp4|webm)$#i', (string) $opt['video_url']);
$showVisual = $type === 'visual' && $align === 'left';
// Presentation only: the first sentence of the CMS heading stays plain, the rest is set as the gold serif accent.
$heading = (string) $s['heading'];
$split = preg_match('/^(.+?[.!?])\s+(\S.*)$/u', $heading, $hm) ? [$hm[1], $hm[2]] : [$heading, ''];
?>
<section class="hero hero-<?= e($align) ?><?= ($hasImage || $hasVideo) ? ' hero-media' : '' ?><?= ($opt['animate'] ?? '1') === '1' ? ' hero-animate' : '' ?>" aria-labelledby="hero-title">
  <div class="hero-bg" aria-hidden="true">
    <?php if ($hasVideo): ?>
      <video class="hero-video" autoplay muted loop playsinline preload="metadata"<?= !empty($s['image']) ? ' poster="' . e(media_url($s['image'])) . '"' : '' ?>><source src="<?= e(media_url((string) $opt['video_url'])) ?>" type="video/<?= str_ends_with(strtolower((string) $opt['video_url']), '.webm') ? 'webm' : 'mp4' ?>"></video>
    <?php elseif ($hasImage): ?>
      <picture>
        <?php if (!empty($opt['mobile_image'])): ?><source media="(max-width: 767px)" srcset="<?= e(media_url((string) $opt['mobile_image'])) ?>"><?php endif; ?>
        <img src="<?= e(media_url($s['image'])) ?>" alt="" fetchpriority="high">
      </picture>
    <?php endif; ?>
    <?php if ($hasImage || $hasVideo): ?><span class="hero-overlay" style="background:<?= e($overlay) ?>;opacity:<?= e((string) $opacity) ?>"></span><?php endif; ?>
    <span class="hero-glow"></span><span class="hero-grid"></span>
    <?php if ($type === 'visual'): ?>
    <?php // Decorative market strip (no values): a deterministic candlestick sequence and an equity line that draw in once.
    $cs = []; $p = 60.0;
    for ($i = 0; $i < 56; $i++) { $o = $p; $p = max(20, min(100, $p + sin($i * 1.7) * 9 + cos($i * .55) * 4 + .7)); $cs[] = [$o, $p, max($o, $p) + 2 + abs(sin($i * 2.3)) * 4, min($o, $p) - 2 - abs(cos($i * 1.9)) * 4]; }
    ?>
    <svg class="hero-candles" viewBox="0 0 1280 140" preserveAspectRatio="none" aria-hidden="true" focusable="false">
      <defs><linearGradient id="hc-brand" gradientUnits="userSpaceOnUse" x1="0" y1="0" x2="1280" y2="0"><stop offset="0" stop-color="var(--primary)"/><stop offset="1" stop-color="var(--secondary)"/></linearGradient></defs>
      <?php foreach ($cs as $i => [$o, $c, $hi, $lo]): $x = 14 + $i * 22.6; $y = fn ($v) => round(150 - $v * 1.25, 1); ?>
      <g class="hc <?= $c >= $o ? 'hc-up' : 'hc-dn' ?>" style="--i:<?= $i ?>"><line x1="<?= $x ?>" x2="<?= $x ?>" y1="<?= $y($hi) ?>" y2="<?= $y($lo) ?>"/><rect x="<?= $x - 5 ?>" y="<?= $y(max($o, $c)) ?>" width="10" height="<?= max(1.5, round(abs($c - $o) * 1.25, 1)) ?>" rx="1.5"/></g>
      <?php endforeach; ?>
      <polyline class="hc-eq" points="<?= e(implode(' ', array_map(fn ($i, $k) => round(14 + $i * 22.6, 1) . ',' . round(150 - $k[1] * 1.25 - 18, 1), array_keys($cs), $cs))) ?>" pathLength="1"/>
    </svg>
    <?php endif; ?>
  </div>
  <div class="container hero-inner<?= $showVisual ? ' has-visual' : '' ?>">
    <div class="hero-copy">
      <?php if ($s['eyebrow']): ?><p class="hero-eyebrow"><span class="pulse" aria-hidden="true"></span><?= e($s['eyebrow']) ?></p><?php endif; ?>
      <h1 id="hero-title"><span class="h-plain"><?= e($split[0]) ?></span><?php if ($split[1] !== ''): ?> <em class="h-accent"><?= e($split[1]) ?></em><?php endif; ?></h1>
      <?php if ($s['subheading']): ?><p class="hero-sub"><?= nl2br(e($s['subheading'])) ?></p><?php endif; ?>
      <?php if ($s['body']): ?><div class="hero-body prose"><?= $s['body'] ?></div><?php endif; ?>
      <div class="hero-actions">
        <?= cms_button($s['cta_label'], $s['cta_url'], 'btn btn-primary btn-lg', true) ?>
        <?= cms_button($s['cta2_label'], $s['cta2_url'], 'btn btn-ghost btn-lg') ?>
      </div>
      <?php if ($items): ?>
      <ul class="hero-points">
        <?php foreach ($items as $it): if (trim($it['title'] ?? '') === '') continue; ?>
        <li><?= icon($it['icon'] ?: 'check', 'icon icon-sm') ?><?= e($it['title']) ?></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
    <?php if ($showVisual): ?>
    <div class="hero-visual hero-shot" data-tilt>
      <?php $pi = fn ($f) => e(asset('images/product/' . $f)); ?>
      <div class="shot-frame">
        <div class="shot-bar" aria-hidden="true"><span></span><span></span><span></span><em>journzey.ai · terminal</em></div>
        <img src="<?= $pi('dashboard.webp') ?>" srcset="<?= $pi('dashboard-sm.webp') ?> 800w, <?= $pi('dashboard.webp') ?> 1600w" sizes="(max-width: 999px) 92vw, 640px" width="1600" height="1000" alt="journzey.ai dashboard: equity curve, win rate, profit factor and drawdown (demo data)" fetchpriority="high">
        <span class="shot-sweep" aria-hidden="true"></span>
      </div>
      <img class="shot-float f-voice" src="<?= $pi('voice.webp') ?>" width="1000" height="373" alt="Voice logging: a spoken trade logged in one sentence" loading="lazy">
      <img class="shot-float f-flex" src="<?= $pi('flexcard.webp') ?>" width="720" height="900" alt="Verified flex card with QR code" loading="lazy">
      <div class="float-chip chip-a" aria-hidden="true"><?= icon('mic', 'icon icon-sm') ?>Speak your trades</div>
      <div class="float-chip chip-b" aria-hidden="true"><?= icon('shield', 'icon icon-sm') ?>Prop firm rules</div>
      <p class="shot-caption">Real product screens · demo data</p>
    </div>
    <?php endif; ?>
  </div>
</section>
