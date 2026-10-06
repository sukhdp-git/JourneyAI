<?php if (empty($process)) return; ?>
<section <?= section_attrs($s, 'section-process') ?>>
  <div class="container">
    <?= App\Core\View::partial('public/partials/section-head', ['s' => $s]) ?>
    <?= App\Core\View::partial('public/partials/process-steps', ['process' => $process]) ?>
    <?php if ($s['cta_label']): ?><div class="center-actions reveal"><?= cms_button($s['cta_label'], $s['cta_url'], 'btn btn-primary', true) ?></div><?php endif; ?>
  </div>
</section>
