<?php
/** A band of real terminal screenshots (demo data) for inner pages. @var string $title @var string $text */
$pi = fn ($f) => e(asset('images/product/' . $f));
$shots = [['dashboard', 'Dashboard'], ['edge', 'Edge Matrix'], ['dashboard-prop', 'Prop firm rules'], ['logtrade', 'Voice trade logging']];
?>
<section class="section section-tight product-band" aria-label="Product screens">
  <div class="container">
    <div class="pb-head reveal"><h2 class="h3"><?= e($title) ?></h2><p class="muted"><?= e($text) ?></p></div>
    <div class="pb-grid">
      <?php foreach ($shots as $i => [$f, $lbl]): ?>
      <figure class="pb-item reveal" style="--d:<?= $i ?>">
        <div class="shot-frame"><div class="shot-bar" aria-hidden="true"><span></span><span></span><span></span><em><?= e($lbl) ?></em></div><img src="<?= $pi($f . '-sm.webp') ?>" width="800" height="500" alt="<?= e($lbl) ?> in the journzey.ai terminal (demo data)" loading="lazy" decoding="async"></div>
        <figcaption><?= e($lbl) ?></figcaption>
      </figure>
      <?php endforeach; ?>
    </div>
    <p class="sc-note">Real screens from the terminal with the built-in demo journal (sample data).</p>
  </div>
</section>
