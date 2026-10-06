<section <?= section_attrs($s, 'section-benefits') ?>>
  <div class="container">
    <?= App\Core\View::partial('public/partials/section-head', ['s' => $s]) ?>
    <?php if ($s['body']): ?><div class="prose narrow center reveal"><?= $s['body'] ?></div><?php endif; ?>
    <div class="feature-grid">
      <?php foreach ($items as $i => $it): ?>
      <div class="feature reveal" style="--d:<?= $i % 3 ?>">
        <span class="feature-icon"><?= icon($it['icon'] ?: 'check', 'icon') ?></span>
        <div><h3><?= e($it['title']) ?></h3><?php if (!empty($it['text'])): ?><p><?= e($it['text']) ?></p><?php endif; ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php if ($s['cta_label']): ?><div class="center-actions reveal"><?= cms_button($s['cta_label'], $s['cta_url'], 'btn btn-primary', true) ?></div><?php endif; ?>
  </div>
</section>
