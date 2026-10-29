<?php use App\Trading\Course; /** @var int $n @var array $mod @var array $progress @var array $standing — one course module; the content is in course/m{n}.php */
[$pn, $nn] = Course::neighbours($n); $prev = $pn ? Course::MODULES[$pn] : null; $next = $nn ? Course::MODULES[$nn] : null;
$total = count(Course::MODULES); $pos = Course::position($n); ?>
<div class="cx" data-course data-course-total="<?= $total ?>" data-course-n="<?= $n ?>" data-course-done="<?= e(json_encode(array_keys($progress))) ?>" data-course-url="<?= e(url('/terminal/university/progress')) ?>" data-course-pass="<?= Course::PASS ?>" data-course-cert="<?= e(url('/terminal/university/certificate')) ?>" data-badges="<?= e(json_encode(array_column($standing['badges'], 'done'))) ?>">
  <div class="cx-top">
    <a class="tm-btn tm-btn-sm" href="<?= e(url('/terminal/university/course')) ?>"><?= icon('arrow-left', 'icon icon-sm') ?> Course overview</a>
    <nav class="cx-steps" aria-label="Modules"><?php foreach (Course::order() as $i => $k): $mm = Course::MODULES[$k]; ?><a href="<?= e(url('/terminal/university/course/' . $mm[0])) ?>" class="<?= $k === $n ? 'on' : '' ?>" data-course-mod="<?= $k ?>" title="<?= $i + 1 ?> · <?= e($mm[1]) ?>"<?= $k === $n ? ' aria-current="page"' : '' ?>><?= $i + 1 ?></a><?php endforeach; ?></nav>
  </div>
  <section class="cx-hero cx-hero-art">
    <div class="cx-hero-text">
    <span class="cx-kicker"><?= icon($mod[4], 'icon icon-xs') ?> Lesson <?= $pos ?> of <?= $total ?> · <?= $mod[3] ?> min read</span>
    <span class="cx-xp" title="Experience points: 100 per lesson + up to 50 for the quiz"><b data-course-xp><?= (int) $standing['xp'] ?></b> XP · <?= (int) $standing['done'] ?>/<?= $total ?> lessons</span>
    <h2><?= e($mod[1]) ?></h2>
    <div class="cx-tldr">
      <span class="cx-tldr-h"><?= icon('zap', 'icon icon-xs') ?> In 30 seconds</span>
      <ol><?php foreach (Course::TLDR[$n] ?? [] as $t): ?><li><?= e($t) ?></li><?php endforeach; ?></ol>
    </div>
    </div>
    <?= App\Core\View::partial('terminal/course/art', ['n' => $n]) ?>
  </section>
  <svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false"><defs><linearGradient id="cx-g" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#6541e9"/><stop offset=".5" stop-color="#b17ace"/><stop offset="1" stop-color="#feb2b4"/></linearGradient></defs></svg>
  <div class="cx-body" data-cx-acc>
    <?= App\Core\View::partial('terminal/course/m' . $n, ['mod' => $mod, 'm' => $m]) ?>
    <div class="cx-foot">
      <?php if ($prev): ?><a class="tm-btn" href="<?= e(url('/terminal/university/course/' . $prev[0])) ?>"><?= icon('arrow-left', 'icon icon-sm') ?> <?= e($prev[2]) ?></a><?php else: ?><span></span><?php endif; ?>
      <button type="button" class="tm-btn tm-btn-accent" data-course-complete><?= icon('check', 'icon icon-sm') ?> <span>Complete lesson</span></button>
      <?php if ($next): ?><a class="tm-btn tm-btn-primary" href="<?= e(url('/terminal/university/course/' . $next[0])) ?>"><?= e($next[2]) ?> <?= icon('arrow-right', 'icon icon-sm') ?></a><?php else: ?><a class="tm-btn tm-btn-primary" href="<?= e(url('/terminal/university/course')) ?>">Finish course <?= icon('check', 'icon icon-sm') ?></a><?php endif; ?>
    </div>
  </div>
  <p class="ex-disclaimer">Educational content — not financial advice. Trading involves substantial risk of loss; past results never guarantee future returns.</p>
</div>
