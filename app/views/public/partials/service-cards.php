<div class="card-grid">
  <?php foreach ($services as $i => $sv): ?>
  <a class="service-card reveal" style="--d:<?= $i % 3 ?>" href="<?= e(url('/services/' . $sv['slug'])) ?>">
    <?php if ($sv['thumbnail']): ?><div class="card-thumb"><?= cms_image($sv['thumbnail'], $sv['title'], '', false, '(min-width: 900px) 33vw, 100vw') ?></div><?php endif; ?>
    <span class="card-icon"><?= icon($sv['icon'] ?: 'sparkles', 'icon') ?></span>
    <h3><?= e($sv['title']) ?></h3>
    <p><?= e(excerpt($sv['short_description'], 170)) ?></p>
    <span class="card-link">Learn more <?= icon('arrow-right', 'icon icon-sm') ?></span>
  </a>
  <?php endforeach; ?>
</div>
