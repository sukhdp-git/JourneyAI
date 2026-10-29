<?php use App\Trading\Course; /** Trading Foundations — course overview, grouped by level. @var array $progress @var array $standing @var ?array $cert */
$total = count(Course::MODULES); $st = $standing; ?>
<div class="cx" data-course data-course-total="<?= $total ?>" data-course-done="<?= e(json_encode(array_keys($progress))) ?>">
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

  <svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false"><defs><linearGradient id="cx-g" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#6541e9"/><stop offset=".5" stop-color="#b17ace"/><stop offset="1" stop-color="#feb2b4"/></linearGradient></defs></svg>
  <section class="cx-game" aria-label="Your progress">
    <div class="cx-xpbox">
      <div class="cx-xp-top"><span><?= icon('zap', 'icon icon-sm') ?> <b data-countup><?= (int) $st['xp'] ?></b> XP</span><small><?= (int) $st['max_xp'] ?> XP to master the course</small></div>
      <div class="bar"><span style="width:<?= $st['max_xp'] ? round($st['xp'] / $st['max_xp'] * 100) : 0 ?>%"></span></div>
      <small class="muted">100 XP per lesson, plus up to 50 for your quiz score. Pass each quiz with <?= Course::PASS ?>%+.</small>
    </div>
    <ol class="cx-badges">
      <?php foreach ($st['badges'] as $i => $b): ?>
      <li class="cx-badge<?= $b['done'] ? ' on' : '' ?>" style="--i:<?= $i ?>">
        <svg viewBox="0 0 48 56" aria-hidden="true"><path d="M24 2 L44 12 V30 C44 42 35 50 24 54 C13 50 4 42 4 30 V12 Z" class="cx-badge-shield"/><text x="24" y="34" text-anchor="middle"><?= $i + 1 ?></text></svg>
        <span><b><?= e($b['title']) ?></b><small><?= $b['done'] ? 'Unlocked' : $b['n'] . ' / ' . $b['of'] . ' lessons' ?></small></span>
      </li>
      <?php endforeach; ?>
    </ol>
    <a class="cx-cert-card<?= $st['eligible'] ? ' ready' : '' ?>" href="<?= e(url('/terminal/university/certificate')) ?>">
      <span class="cx-cert-ico" aria-hidden="true">🎓</span>
      <span><b><?= $cert ? 'Your certificate' : ($st['eligible'] ? 'Claim your certificate' : 'Certificate of completion') ?></b>
        <small><?= $cert ? 'Issued ' . e(fmt_date($cert['issued_at'], 'M j, Y')) . ' · ID ' . e($cert['code']) : ($st['eligible'] ? 'All lessons passed. Add your name and download it.' : 'Unlocks when all ' . $total . ' lessons are passed (' . (int) $st['done'] . '/' . $total . ').') ?></small></span>
      <?= icon($st['eligible'] ? 'arrow-right' : 'lock', 'icon') ?>
    </a>
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
