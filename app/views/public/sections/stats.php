<?php if (!$items) return; ?>
<section <?= section_attrs($s, 'section-stats') ?>>
  <div class="container">
    <?= App\Core\View::partial('public/partials/section-head', ['s' => $s]) ?>
    <dl class="stats">
      <?php foreach ($items as $it): ?>
      <div class="stat reveal"><dt><?= e($it['text'] ?? '') ?></dt><dd><?= e($it['title']) ?></dd></div>
      <?php endforeach; ?>
    </dl>
  </div>
</section>
