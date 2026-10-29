<?php /** Decorative animated backdrop for Confessions (candles drifting up through a slow aurora). Hidden from assistive tech. */ ?>
<div class="cf-bg" aria-hidden="true">
  <span class="cf-aur a1"></span><span class="cf-aur a2"></span><span class="cf-aur a3"></span>
  <svg class="cf-candles" viewBox="0 0 1200 800" preserveAspectRatio="none">
    <?php mt_srand(7); for ($k = 0; $k < 26; $k++): $x = mt_rand(10, 1190); $h = mt_rand(30, 110); $w = mt_rand(6, 12); $up = mt_rand(0, 1); $d = mt_rand(14, 30); $dl = -mt_rand(0, 30); ?>
    <g class="cf-cd <?= $up ? 'up' : 'dn' ?>" style="animation-duration:<?= $d ?>s;animation-delay:<?= $dl ?>s"><line x1="<?= $x ?>" x2="<?= $x ?>" y1="<?= 820 - $h - 22 ?>" y2="<?= 822 ?>"/><rect x="<?= $x - $w / 2 ?>" y="<?= 820 - $h ?>" width="<?= $w ?>" height="<?= $h - 18 ?>" rx="2"/></g>
    <?php endfor; ?>
  </svg>
</div>
