<?php if (empty($testimonials)) return; ?>
<section <?= section_attrs($s, 'section-testimonials') ?>>
  <div class="container">
    <?= App\Core\View::partial('public/partials/section-head', ['s' => $s]) ?>
    <?= App\Core\View::partial('public/partials/testimonials', ['testimonials' => $testimonials]) ?>
  </div>
</section>
