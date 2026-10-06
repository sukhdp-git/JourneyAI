<section <?= section_attrs($s, 'section-intro') ?>>
  <div class="container">
    <?= App\Core\View::partial('public/partials/section-head', ['s' => $s]) ?>
    <?php if ($items): ?>
    <div class="pillars">
      <?php foreach ($items as $i => $it): ?>
      <div class="pillar reveal" style="--d:<?= $i ?>">
        <span class="pillar-icon"><?= icon($it['icon'] ?: 'dot', 'icon') ?></span>
        <h3><?= e($it['title']) ?></h3>
        <?php if (!empty($it['text'])): ?><p><?= e($it['text']) ?></p><?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php if ($s['body']): ?><div class="prose narrow reveal"><?= $s['body'] ?></div><?php endif; ?>
  </div>
</section>
