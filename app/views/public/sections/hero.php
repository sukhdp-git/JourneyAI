<?php
/** @var array $s @var array $items @var array $opt */
$type = $opt['media_type'] ?? 'visual';
$align = ($opt['alignment'] ?? 'left') === 'center' ? 'center' : 'left';
$overlay = safe_color((string) ($opt['overlay_color'] ?? '#070a12'), '#070a12');
$opacity = max(0, min(95, (int) ($opt['overlay_opacity'] ?? 55))) / 100;
$hasImage = $type === 'image' && !empty($s['image']);
$hasVideo = $type === 'video' && !empty($opt['video_url']) && preg_match('#^(https://|uploads/)[^\s"<>]+\.(mp4|webm)$#i', (string) $opt['video_url']);
$showVisual = $type === 'visual' && $align === 'left';
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
  </div>
  <div class="container hero-inner<?= $showVisual ? ' has-visual' : '' ?>">
    <div class="hero-copy">
      <?php if ($s['eyebrow']): ?><p class="hero-eyebrow"><span class="pulse" aria-hidden="true"></span><?= e($s['eyebrow']) ?></p><?php endif; ?>
      <h1 id="hero-title"><?= e($s['heading']) ?></h1>
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
    <div class="hero-visual" aria-hidden="true">
      <div class="terminal">
        <div class="terminal-bar"><span></span><span></span><span></span><em>Illustrative interface · sample data</em></div>
        <div class="terminal-body">
          <div class="t-head">
            <div><small>Equity curve</small><strong>All accounts</strong></div>
            <div class="t-tabs"><b>1W</b><b class="on">1M</b><b>3M</b></div>
          </div>
          <svg class="t-chart" viewBox="0 0 320 120" preserveAspectRatio="none">
            <defs><linearGradient id="hg" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="var(--primary)" stop-opacity=".45"/><stop offset="1" stop-color="var(--primary)" stop-opacity="0"/></linearGradient></defs>
            <path class="t-area" d="M0 98 L20 92 L40 95 L60 84 L80 86 L100 74 L120 78 L140 66 L160 70 L180 58 L200 61 L220 49 L240 52 L260 40 L280 43 L300 30 L320 26 L320 120 L0 120Z" fill="url(#hg)"/>
            <path class="t-line" d="M0 98 L20 92 L40 95 L60 84 L80 86 L100 74 L120 78 L140 66 L160 70 L180 58 L200 61 L220 49 L240 52 L260 40 L280 43 L300 30 L320 26" fill="none" stroke="var(--primary)" stroke-width="2"/>
          </svg>
          <div class="t-rules">
            <div class="t-rule ok"><span><?= icon('check-circle', 'icon icon-sm') ?>Daily loss limit</span><b>Within plan</b></div>
            <div class="t-rule ok"><span><?= icon('check-circle', 'icon icon-sm') ?>Max trades per session</span><b>3 / 5</b></div>
            <div class="t-rule warn"><span><?= icon('alert', 'icon icon-sm') ?>Entered before confirmation</span><b>Review</b></div>
          </div>
          <div class="t-coach"><?= icon('sparkles', 'icon icon-sm') ?><p>Pattern noticed: trade frequency rises after a losing trade. Consider a pause rule.</p></div>
        </div>
      </div>
      <div class="float-chip chip-a"><?= icon('layers', 'icon icon-sm') ?>Multi-broker</div>
      <div class="float-chip chip-b"><?= icon('shield', 'icon icon-sm') ?>Rules engine</div>
    </div>
    <?php endif; ?>
  </div>
</section>
