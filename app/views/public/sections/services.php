<?php if (empty($services)) return; ?>
<section <?= section_attrs($s, 'section-services') ?>>
  <div class="container">
    <?= App\Core\View::partial('public/partials/section-head', ['s' => $s]) ?>
    <?= App\Core\View::partial('public/partials/service-cards', ['services' => $services]) ?>
    <?php if ($s['cta_label']): ?><div class="center-actions reveal"><?= cms_button($s['cta_label'], $s['cta_url'], 'btn btn-ghost', true) ?></div><?php endif; ?>
  </div>
</section>
