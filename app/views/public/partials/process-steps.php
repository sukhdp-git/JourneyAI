<ol class="steps">
  <?php foreach ($process as $i => $st): ?>
  <li class="step reveal" style="--d:<?= $i ?>">
    <div class="step-top">
      <span class="step-num"><?= e($st['step_number'] ?: str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT)) ?></span>
      <?php if ($st['image']): ?><span class="step-img"><?= cms_image($st['image'], '', '', false, '64px') ?></span><?php else: ?><span class="step-icon"><?= icon($st['icon'] ?: 'dot', 'icon') ?></span><?php endif; ?>
    </div>
    <h3><?= e($st['title']) ?></h3>
    <?php if ($st['description']): ?><p><?= e($st['description']) ?></p><?php endif; ?>
  </li>
  <?php endforeach; ?>
</ol>
