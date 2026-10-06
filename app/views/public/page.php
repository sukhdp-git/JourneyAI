<?php
use App\Core\View;
/** @var array $page @var array $blocks */
$legal = $page['template'] === 'legal';
?>
<?= View::partial('public/partials/page-hero', ['eyebrow' => $page['hero_eyebrow'], 'title' => $page['hero_title'] ?: $page['title'], 'subtitle' => $page['hero_subtitle'], 'image' => $page['featured_image'], 'crumbs' => [['Home', '/'], [$page['title'], '/' . $page['slug']]], 'extra' => $legal ? '<p class="article-meta"><span>' . icon('calendar', 'icon icon-sm') . 'Last updated ' . e(fmt_date($page['updated_at'])) . '</span></p>' : '']) ?>
<?php if (trim(strip_tags((string) $page['content'], '<img><iframe>')) !== ''): ?>
<section class="section section-tight">
  <div class="container<?= $legal ? ' legal-layout' : '' ?>">
    <article class="prose <?= $legal ? 'legal' : 'narrow center-block' ?>"><?= $page['content'] ?></article>
  </div>
</section>
<?php endif; ?>
<?php foreach ($blocks as $b):
    $bg = in_array($b['background'] ?? 'default', ['default', 'muted', 'dark', 'brand'], true) ? $b['background'] : 'default';
    $items = parse_lines($b['items'] ?? '');
    $type = $b['type'] ?? 'rich';
?>
<section class="section bg-<?= e($bg) ?> block-<?= e($type) ?>">
  <div class="container">
  <?php if ($type === 'split'): ?>
    <div class="split<?= ($b['image_side'] ?? 'right') === 'left' ? ' split-reverse' : '' ?><?= empty($b['image']) ? ' split-visual' : '' ?>">
      <div class="split-copy reveal">
        <?php if (!empty($b['eyebrow'])): ?><p class="eyebrow"><?= e($b['eyebrow']) ?></p><?php endif; ?>
        <?php if (!empty($b['heading'])): ?><h2><?= e($b['heading']) ?></h2><?php endif; ?>
        <div class="prose"><?= $b['body'] ?? '' ?></div>
        <div class="actions"><?= cms_button($b['cta_label'] ?? '', $b['cta_url'] ?? '', 'btn btn-primary', true) ?></div>
      </div>
      <div class="split-media reveal">
        <?php if (!empty($b['image'])): ?><div class="media-frame"><?= cms_image($b['image'], $b['heading'] ?? '', '', false, '(min-width: 900px) 50vw, 100vw') ?></div>
        <?php else: ?><div class="abstract-visual" aria-hidden="true"><span></span><span></span><span></span><?= icon('trend-up', 'icon') ?></div><?php endif; ?>
      </div>
    </div>
  <?php elseif ($type === 'cta'): ?>
    <div class="cta-panel reveal">
      <?php if (!empty($b['eyebrow'])): ?><p class="eyebrow"><?= e($b['eyebrow']) ?></p><?php endif; ?>
      <h2><?= e($b['heading'] ?? '') ?></h2>
      <div class="prose center"><?= $b['body'] ?? '' ?></div>
      <div class="actions center"><?= cms_button($b['cta_label'] ?? '', $b['cta_url'] ?? '', 'btn btn-light btn-lg', true) ?></div>
    </div>
  <?php else: ?>
    <?= View::partial('public/partials/section-head', ['s' => ['eyebrow' => $b['eyebrow'] ?? '', 'heading' => $b['heading'] ?? '', 'subheading' => '']]) ?>
    <?php if (!empty($b['body']) && $type !== 'stats'): ?><div class="prose narrow center-block reveal"><?= $b['body'] ?></div><?php endif; ?>
    <?php if ($type === 'cards' && $items): ?>
      <div class="feature-grid cols-<?= min(4, max(2, count($items))) ?>">
        <?php foreach ($items as $i => $it): ?>
        <div class="feature feature-card reveal" style="--d:<?= $i % 4 ?>"><span class="feature-icon"><?= icon($it['icon'] ?: 'check', 'icon') ?></span><div><h3><?= e($it['title']) ?></h3><?php if ($it['text']): ?><p><?= e($it['text']) ?></p><?php endif; ?></div></div>
        <?php endforeach; ?>
      </div>
    <?php elseif ($type === 'stats' && $items): ?>
      <dl class="stats"><?php foreach ($items as $it): ?><div class="stat reveal"><dt><?= e($it['text']) ?></dt><dd><?= e($it['title']) ?></dd></div><?php endforeach; ?></dl>
    <?php elseif ($type === 'process' && !empty($process)): ?>
      <?= View::partial('public/partials/process-steps', ['process' => $process]) ?>
    <?php elseif ($type === 'faq' && !empty($faqs)): ?>
      <div class="narrow center-block"><?= View::partial('public/partials/faq-list', ['faqs' => $faqs, 'id' => 'pg']) ?></div>
    <?php elseif ($type === 'services' && !empty($services)): ?>
      <?= View::partial('public/partials/service-cards', ['services' => $services]) ?>
    <?php elseif ($type === 'testimonials' && !empty($testimonials)): ?>
      <?= View::partial('public/partials/testimonials', ['testimonials' => $testimonials]) ?>
    <?php endif; ?>
    <?php if ($type !== 'split' && !empty($b['cta_label'])): ?><div class="center-actions reveal"><?= cms_button($b['cta_label'], $b['cta_url'] ?? '', 'btn btn-primary', true) ?></div><?php endif; ?>
  <?php endif; ?>
  </div>
</section>
<?php endforeach; ?>
<?php foreach ($custom as $c) echo View::partial('public/sections/custom', ['c' => $c]); ?>
