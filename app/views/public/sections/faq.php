<?php if (empty($faqs)) return; ?>
<section <?= section_attrs($s, 'section-faq') ?>>
  <div class="container faq-layout">
    <div>
      <?= App\Core\View::partial('public/partials/section-head', ['s' => $s, 'align' => 'left']) ?>
      <div class="reveal"><?= cms_button($s['cta_label'], $s['cta_url'], 'btn btn-ghost', true) ?></div>
    </div>
    <?= App\Core\View::partial('public/partials/faq-list', ['faqs' => $faqs, 'id' => 'home']) ?>
  </div>
</section>
