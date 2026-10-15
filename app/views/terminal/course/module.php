<?php use App\Trading\Course; /** @var int $n @var array $mod — one course module; the content is in course/m{n}.php */
$prev = Course::MODULES[$n - 1] ?? null; $next = Course::MODULES[$n + 1] ?? null; ?>
<div class="cx" data-course data-course-key="jz-course-<?= (int) $m['id'] ?>" data-course-n="<?= $n ?>">
  <div class="cx-top">
    <a class="tm-btn tm-btn-sm" href="<?= e(url('/terminal/university/course')) ?>"><?= icon('arrow-left', 'icon icon-sm') ?> Course overview</a>
    <nav class="cx-steps" aria-label="Modules"><?php foreach (Course::MODULES as $k => $mm): ?><a href="<?= e(url('/terminal/university/course/' . $mm[0])) ?>" class="<?= $k === $n ? 'on' : '' ?>" data-course-mod="<?= $k ?>" title="Module <?= $k ?> · <?= e($mm[1]) ?>"<?= $k === $n ? ' aria-current="page"' : '' ?>><?= $k ?></a><?php endforeach; ?></nav>
  </div>
  <section class="cx-hero">
    <span class="cx-kicker"><?= icon($mod[4], 'icon icon-xs') ?> Module <?= $n ?> of 5 · <?= $mod[3] ?> min read</span>
    <h2><?= e($mod[1]) ?></h2>
    <p><?= e($mod[5]) ?></p>
  </section>
  <svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false"><defs><linearGradient id="cx-g" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#6541e9"/><stop offset=".5" stop-color="#b17ace"/><stop offset="1" stop-color="#feb2b4"/></linearGradient></defs></svg>
  <div class="cx-body">
    <?= App\Core\View::partial('terminal/course/m' . $n, ['mod' => $mod, 'm' => $m]) ?>
    <div class="cx-foot">
      <?php if ($prev): ?><a class="tm-btn" href="<?= e(url('/terminal/university/course/' . $prev[0])) ?>"><?= icon('arrow-left', 'icon icon-sm') ?> Module <?= $n - 1 ?></a><?php else: ?><span></span><?php endif; ?>
      <button type="button" class="tm-btn tm-btn-accent" data-course-complete><?= icon('check', 'icon icon-sm') ?> <span>Mark module complete</span></button>
      <?php if ($next): ?><a class="tm-btn tm-btn-primary" href="<?= e(url('/terminal/university/course/' . $next[0])) ?>">Module <?= $n + 1 ?> <?= icon('arrow-right', 'icon icon-sm') ?></a><?php else: ?><a class="tm-btn tm-btn-primary" href="<?= e(url('/terminal/university/course')) ?>">Finish course <?= icon('check', 'icon icon-sm') ?></a><?php endif; ?>
    </div>
  </div>
  <p class="ex-disclaimer">Educational content — not financial advice. Trading involves substantial risk of loss; past results never guarantee future returns.</p>
</div>
