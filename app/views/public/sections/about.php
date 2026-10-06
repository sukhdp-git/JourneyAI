<section <?= section_attrs($s, 'section-about') ?>>
  <div class="container split<?= $s['image'] ? '' : ' split-visual' ?>">
    <div class="split-copy reveal">
      <?php if ($s['eyebrow']): ?><p class="eyebrow"><?= e($s['eyebrow']) ?></p><?php endif; ?>
      <h2><?= e($s['heading']) ?></h2>
      <?php if ($s['subheading']): ?><p class="lead"><?= nl2br(e($s['subheading'])) ?></p><?php endif; ?>
      <?php if ($s['body']): ?><div class="prose"><?= $s['body'] ?></div><?php endif; ?>
      <div class="actions"><?= cms_button($s['cta_label'], $s['cta_url'], 'btn btn-ghost', true) ?></div>
    </div>
    <div class="split-media reveal">
      <?php if ($s['image']): ?>
        <div class="media-frame"><?= cms_image($s['image'], $s['heading'] ?? '', '', false, '(min-width: 900px) 50vw, 100vw') ?></div>
      <?php else: ?>
        <div class="loop-visual" aria-hidden="true">
          <?php foreach ([['target', 'Plan'], ['zap', 'Execute'], ['eye', 'Review'], ['refresh', 'Adjust']] as $i => [$ic, $label]): ?>
          <div class="loop-node n<?= $i ?>"><?= icon($ic, 'icon') ?><span><?= $label ?></span></div>
          <?php endforeach; ?>
          <svg class="loop-ring" viewBox="0 0 200 200"><circle cx="100" cy="100" r="78" fill="none" stroke="currentColor" stroke-width="1.5" stroke-dasharray="4 7"/></svg>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
