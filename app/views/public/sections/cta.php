<section <?= section_attrs($s, 'section-cta') ?>>
  <div class="container">
    <div class="cta-panel reveal">
      <?php if ($s['eyebrow']): ?><p class="eyebrow"><?= e($s['eyebrow']) ?></p><?php endif; ?>
      <h2><?= e($s['heading']) ?></h2>
      <?php if ($s['subheading']): ?><p class="lead"><?= nl2br(e($s['subheading'])) ?></p><?php endif; ?>
      <div class="actions center"><?= cms_button($s['cta_label'], $s['cta_url'], 'btn btn-light btn-lg', true) ?><?= cms_button($s['cta2_label'], $s['cta2_url'], 'btn btn-outline-light btn-lg') ?></div>
    </div>
  </div>
</section>
