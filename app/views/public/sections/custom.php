<?php /** @var array $c custom section */ ?>
<section class="section bg-<?= e($c['background']) ?> custom-section layout-<?= e($c['layout']) ?>">
  <div class="container<?= $c['layout'] === 'split' ? ' split' : '' ?>">
    <?php if ($c['layout'] === 'banner'): ?>
    <div class="banner reveal">
      <div><?php if ($c['eyebrow']): ?><p class="eyebrow"><?= e($c['eyebrow']) ?></p><?php endif; ?><h2><?= e($c['heading']) ?></h2><?php if ($c['content']): ?><div class="prose"><?= $c['content'] ?></div><?php endif; ?></div>
      <?= cms_button($c['cta_label'], $c['cta_url'], 'btn btn-primary', true) ?>
    </div>
    <?php else: ?>
    <div class="split-copy reveal<?= $c['layout'] === 'text' ? ' narrow center-block' : '' ?>">
      <?php if ($c['eyebrow']): ?><p class="eyebrow"><?= e($c['eyebrow']) ?></p><?php endif; ?>
      <?php if ($c['heading']): ?><h2><?= e($c['heading']) ?></h2><?php endif; ?>
      <?php if ($c['content']): ?><div class="prose"><?= $c['content'] ?></div><?php endif; ?>
      <div class="actions"><?= cms_button($c['cta_label'], $c['cta_url'], 'btn btn-primary', true) ?></div>
    </div>
    <?php if ($c['layout'] === 'split' && $c['image']): ?><div class="split-media reveal"><div class="media-frame"><?= cms_image($c['image'], $c['heading'] ?? '', '', false, '(min-width: 900px) 50vw, 100vw') ?></div></div><?php endif; ?>
    <?php endif; ?>
  </div>
</section>
