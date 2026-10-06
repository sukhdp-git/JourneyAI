<?php /** @var string $title @var ?string $eyebrow @var ?string $subtitle @var array $crumbs [[label, path]] @var ?string $image */ ?>
<section class="page-hero<?= !empty($image) ? ' has-image' : '' ?>">
  <?php if (!empty($image)): ?><div class="page-hero-bg" aria-hidden="true"><?= cms_image($image, '', '', true) ?></div><?php endif; ?>
  <span class="hero-glow" aria-hidden="true"></span><span class="hero-grid" aria-hidden="true"></span>
  <div class="container">
    <?php if (!empty($crumbs)): ?>
    <nav class="breadcrumbs" aria-label="Breadcrumb">
      <ol>
        <?php foreach ($crumbs as $i => [$label, $path]): $last = $i === count($crumbs) - 1; ?>
        <li><?php if ($last): ?><span aria-current="page"><?= e($label) ?></span><?php else: ?><a href="<?= e(url($path)) ?>"><?= e($label) ?></a><?= icon('chevron-right', 'icon icon-xs') ?><?php endif; ?></li>
        <?php endforeach; ?>
      </ol>
    </nav>
    <?php endif; ?>
    <?php if (!empty($eyebrow)): ?><p class="eyebrow"><?= e($eyebrow) ?></p><?php endif; ?>
    <h1><?= e($title) ?></h1>
    <?php if (!empty($subtitle)): ?><p class="lead"><?= nl2br(e($subtitle)) ?></p><?php endif; ?>
    <?= $extra ?? '' ?>
  </div>
</section>
