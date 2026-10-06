<?php
use App\Core\View;
/** @var array $service @var array $faqs @var array $others */
$benefits = array_values(array_filter(json_list($service['benefits']), fn ($b) => trim($b['title'] ?? '') !== ''));
$steps = array_values(array_filter(json_list($service['process']), fn ($b) => trim($b['title'] ?? '') !== ''));
$ctaLabel = $service['cta_label'] ?: 'Start free';
$ctaUrl = $service['cta_url'] ?: '/signup';
$extra = '<div class="hero-actions">' . cms_button($ctaLabel, $ctaUrl, 'btn btn-primary btn-lg', true) . cms_button('All capabilities', '/services', 'btn btn-ghost btn-lg') . '</div>';
?>
<?= View::partial('public/partials/page-hero', ['eyebrow' => 'Platform', 'title' => $service['title'], 'subtitle' => $service['short_description'], 'image' => $service['hero_image'], 'crumbs' => [['Home', '/'], ['Services', '/services'], [$service['title'], '/services/' . $service['slug']]], 'extra' => $extra]) ?>
<section class="section">
  <div class="container detail-layout">
    <article class="prose reveal"><?= $service['full_description'] ?></article>
    <aside class="detail-aside">
      <div class="aside-card reveal">
        <span class="card-icon"><?= icon($service['icon'] ?: 'sparkles', 'icon') ?></span>
        <h2 class="h4"><?= e($ctaLabel) ?></h2>
        <p>Try <?= e($service['title']) ?> free in demo mode — no card needed.</p>
        <?= cms_button($ctaLabel, $ctaUrl, 'btn btn-primary btn-block', true) ?>
      </div>
      <?php if ($service['thumbnail']): ?><div class="media-frame reveal"><?= cms_image($service['thumbnail'], $service['title'], '', false, '360px') ?></div><?php endif; ?>
    </aside>
  </div>
</section>
<?php if ($benefits): ?>
<section class="section bg-muted">
  <div class="container">
    <div class="section-head align-center reveal"><p class="eyebrow">Benefits</p><h2>What you get</h2></div>
    <div class="feature-grid">
      <?php foreach ($benefits as $i => $b): ?>
      <div class="feature reveal" style="--d:<?= $i % 3 ?>"><span class="feature-icon"><?= icon('check', 'icon') ?></span><div><h3><?= e($b['title']) ?></h3><?php if (!empty($b['text'])): ?><p><?= e($b['text']) ?></p><?php endif; ?></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<?php if ($steps): ?>
<section class="section">
  <div class="container">
    <div class="section-head align-center reveal"><p class="eyebrow">Process</p><h2>How it works</h2></div>
    <?= View::partial('public/partials/process-steps', ['process' => array_map(fn ($st, $i) => ['step_number' => str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT), 'title' => $st['title'], 'description' => $st['text'] ?? '', 'icon' => '', 'image' => ''], $steps, array_keys($steps))]) ?>
  </div>
</section>
<?php endif; ?>
<?php if ($faqs): ?>
<section class="section bg-muted">
  <div class="container faq-layout">
    <div class="section-head align-left reveal"><p class="eyebrow">FAQ</p><h2>Questions about <?= e($service['title']) ?></h2></div>
    <?= View::partial('public/partials/faq-list', ['faqs' => $faqs, 'id' => 'svc']) ?>
  </div>
</section>
<?php endif; ?>
<?php if ($others): ?>
<section class="section">
  <div class="container">
    <div class="section-head align-center reveal"><p class="eyebrow">Explore more</p><h2>Other capabilities</h2></div>
    <?= View::partial('public/partials/service-cards', ['services' => $others]) ?>
  </div>
</section>
<?php endif; ?>
