<?php /** @var array $s @var string $align */ $align ??= 'center'; ?>
<?php if (!empty($s['eyebrow']) || !empty($s['heading']) || !empty($s['subheading'])): ?>
<div class="section-head align-<?= e($align) ?> reveal">
  <?php if (!empty($s['eyebrow'])): ?><p class="eyebrow"><?= e($s['eyebrow']) ?></p><?php endif; ?>
  <?php if (!empty($s['heading'])): ?><h2><?= e($s['heading']) ?></h2><?php endif; ?>
  <?php if (!empty($s['subheading'])): ?><p class="lead"><?= nl2br(e($s['subheading'])) ?></p><?php endif; ?>
</div>
<?php endif; ?>
