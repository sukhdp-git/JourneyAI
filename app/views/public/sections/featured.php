<?php if (empty($posts)) return; ?>
<section <?= section_attrs($s, 'section-featured') ?>>
  <div class="container">
    <div class="section-head-row">
      <?= App\Core\View::partial('public/partials/section-head', ['s' => $s, 'align' => 'left']) ?>
      <?= cms_button($s['cta_label'], $s['cta_url'], 'btn btn-ghost reveal', true) ?>
    </div>
    <div class="post-grid">
      <?php foreach ($posts as $p): ?><?= App\Core\View::partial('public/partials/post-card', ['p' => $p]) ?><?php endforeach; ?>
    </div>
  </div>
</section>
