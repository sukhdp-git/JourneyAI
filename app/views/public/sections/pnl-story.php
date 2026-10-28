<?php
/** Scroll story: the same trader's P&L as journaling habits build up (illustration), then the 3D feature cards. */
$steps = [
    ['Week 1', 'No journal.', 'Same mistakes, every day. Nobody is counting.', -1240],
    ['Week 3', 'Every trade logged.', 'The leaks start to show: one hour, one habit.', -80],
    ['Week 6', 'Danger hour cut.', 'Same strategy. One less bad hour.', 640],
    ['Week 10', 'Rules on autopilot.', 'The journal does the remembering.', 2180],
];
$features = [
    ['shield', 'Tilt Circuit Breaker', 'Locks new entries after a losing streak.', 'tilt'],
    ['radar', 'Edge Matrix', 'Your best hour. Your danger hour.', 'edge'],
    ['target', 'Risk Calculator', 'Lot size from your stop, in one tap.', 'risk'],
    ['users', 'The Ghost', 'Race the version of you that followed the rules.', 'ghost'],
    ['message', 'Roast Mode', 'Break a rule. Get roasted tomorrow.', 'roast'],
    ['book', 'University', 'From zero to your first plan.', 'uni'],
];
?>
<section class="pnl-story" data-pnl-story aria-labelledby="ps-title">
  <div class="ps-sticky">
    <div class="container ps-grid">
      <div class="ps-copy">
        <p class="eyebrow">Same trader · 10 weeks</p>
        <h2 id="ps-title">Watch the P&amp;L change<br><span class="ps-grad">when the habits do.</span></h2>
        <ol class="ps-steps">
          <?php foreach ($steps as $i => [$w, $t, $d]): ?>
          <li class="ps-step<?= $i ? '' : ' on' ?>" data-ps-step="<?= $i ?>"><span class="ps-week"><?= e($w) ?></span><b><?= e($t) ?></b><small><?= e($d) ?></small></li>
          <?php endforeach; ?>
        </ol>
      </div>
      <div class="ps-card" data-tilt3d>
        <div class="ps-card-top"><span>Account P&amp;L</span><span class="ps-pill" data-ps-pill>Week 1</span></div>
        <div class="ps-num" data-ps-num data-values="<?= e(json_encode(array_column($steps, 3))) ?>">−$1,240</div>
        <svg class="ps-chart" viewBox="0 0 600 260" preserveAspectRatio="none" aria-hidden="true">
          <defs><linearGradient id="psFill" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="currentColor" stop-opacity=".28"/><stop offset="1" stop-color="currentColor" stop-opacity="0"/></linearGradient></defs>
          <line x1="0" x2="600" y1="150" y2="150" class="ps-zero"/>
          <path class="ps-area" data-ps-area d=""/>
          <path class="ps-line" data-ps-line d=""/>
          <circle class="ps-dot" data-ps-dot r="7" cx="0" cy="150"/>
        </svg>
        <div class="ps-tags"><span data-ps-tag="0">3 revenge trades</span><span data-ps-tag="1">14:00 = danger hour</span><span data-ps-tag="2">hour skipped</span><span data-ps-tag="3">plan followed 9/10</span></div>
        <p class="ps-note">Illustration of the process, not a promise of results.</p>
      </div>
    </div>
  </div>
</section>

<section class="section feat3d" aria-labelledby="f3-title">
  <div class="container">
    <div class="section-head reveal"><p class="eyebrow">The tools</p><h2 id="f3-title">Built for the trader you are <span class="ps-grad">at 3 PM.</span></h2></div>
    <div class="f3-grid">
      <?php foreach ($features as $i => [$ic, $t, $d, $k]): ?>
      <article class="f3-card reveal" style="--d:<?= $i % 3 ?>" data-tilt3d>
        <div class="f3-viz f3-<?= $k ?>" aria-hidden="true">
          <?php if ($k === 'tilt'): ?><i class="l"></i><i class="l"></i><i class="l"></i><span class="f3-lock"><?= icon('lock', 'icon') ?> 19:59</span>
          <?php elseif ($k === 'edge'): ?><?php for ($c = 0; $c < 24; $c++): ?><i style="--h:<?= [0.2, .5, .9, .7, .3, .1, .6, .4, .8, .2, .5, .3, .4, .9, .6, .2, .1, .4, .3, .7, .2, .5, .8, .3][$c] ?>" class="<?= $c === 13 ? 'best' : ($c === 16 ? 'bad' : '') ?>"></i><?php endfor; ?>
          <?php elseif ($k === 'risk'): ?><b>0.50</b><small>lots · 1% risk · 20-pip stop</small>
          <?php elseif ($k === 'ghost'): ?><svg viewBox="0 0 200 80"><path class="you" d="M0 50 L30 44 L55 58 L80 40 L105 62 L130 48 L160 66 L200 58"/><path class="gh" d="M0 50 L30 44 L55 46 L80 34 L105 38 L130 26 L160 22 L200 12"/></svg>
          <?php elseif ($k === 'roast'): ?><span class="bub">3 revenge trades yesterday. Again? 💀</span>
          <?php else: ?><span class="bar"><i></i></span><small>Lesson 4 of 12</small><?php endif; ?>
        </div>
        <span class="f3-ic"><?= icon($ic, 'icon') ?></span>
        <h3><?= e($t) ?></h3>
        <p><?= e($d) ?></p>
        <span class="f3-glare" aria-hidden="true"></span>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
