<?php use App\Trading\Course; /** Trading Foundations — course overview, grouped by level */
$total = count(Course::MODULES); ?>
<div class="cx" data-course data-course-total="<?= $total ?>" data-course-key="jz-course-<?= (int) $m['id'] ?>">
  <section class="cx-hero">
    <span class="cx-kicker"><?= icon('book', 'icon icon-xs') ?> journzey.ai University · free course</span>
    <h2>From <em>zero</em> to a trader with a plan.</h2>
    <p>Everything a beginner needs, in plain words: <?= $total ?> short lessons in 4 levels.</p>
    <div class="cx-stats">
      <span><?= icon('layers', 'icon icon-sm') ?> <?= $total ?> lessons</span>
      <span><?= icon('clock', 'icon icon-sm') ?> about <?= round(Course::minutes() / 60, 1) ?> hours</span>
      <span><?= icon('check-circle', 'icon icon-sm') ?> Quiz in every lesson</span>
    </div>
    <div class="cx-progress"><div class="bar"><span data-course-bar></span></div><small data-course-label>0 of <?= $total ?> lessons complete</small></div>
  </section>

  <?php $pos = 0; foreach (Course::LEVELS as $li => [$lt, $ld, $nums]): ?>
  <section class="cx-level" style="--i:<?= $li ?>">
    <header class="cx-level-h"><span class="cx-level-n">Level <?= $li + 1 ?></span><div><h3><?= e($lt) ?></h3><p><?= e($ld) ?></p></div></header>
    <ol class="cx-mods">
      <?php foreach ($nums as $n): [$slug, $title, $short, $min, $ic] = Course::MODULES[$n]; $pos++; ?>
      <li><a class="cx-mod" href="<?= e(url('/terminal/university/course/' . $slug)) ?>" data-course-mod="<?= $n ?>">
        <span class="cx-mod-n" aria-hidden="true"><?= $pos ?></span>
        <span><h3><?= e($title) ?></h3><span class="cx-mod-meta"><span><?= icon('clock', 'icon icon-xs') ?> <?= $min ?> min</span><span><?= icon($ic, 'icon icon-xs') ?> <?= e($short) ?></span></span></span>
        <span><b class="cx-done-badge"><?= icon('check', 'icon icon-xs') ?> Done</b><span class="cx-go"><?= icon('arrow-right', 'icon') ?></span></span>
      </a></li>
      <?php endforeach; ?>
    </ol>
  </section>
  <?php endforeach; ?>
  <p class="ex-disclaimer">Educational content — not financial advice. Trading involves substantial risk of loss; past results never guarantee future returns. Practise in demo first.</p>
</div>
