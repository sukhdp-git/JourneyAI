<section <?= section_attrs($s, 'section-pricing') ?>>
  <div class="container">
    <?= App\Core\View::partial('public/partials/section-head', ['s' => $s, 'align' => 'center']) ?>
    <?= App\Core\View::partial('public/partials/pricing-cards') ?>
  </div>
</section>
