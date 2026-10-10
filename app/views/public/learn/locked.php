<?php
use App\Core\View;
/** Teaser for a members-only strategy. @var array $strategy @var int $total */
$s = $strategy;
?>
<?= View::partial('public/partials/page-hero', ['eyebrow' => 'Members-only strategy', 'title' => $s['title'], 'subtitle' => $s['summary'], 'crumbs' => [['Home', '/'], ['Learn', '/learn'], [$s['short_title'] ?: $s['title'], '/learn/' . $s['slug']]]]) ?>
<section class="section section-tight">
  <div class="container narrow">
    <div class="cta-panel learn-unlock reveal">
      <p class="eyebrow"><?= icon('lock', 'icon icon-xs') ?> journzey.ai University</p>
      <h2>Unlock this strategy — free</h2>
      <p class="lead">The full setup rules, entry trigger, stop loss and take profit for <strong><?= e($s['short_title'] ?: $s['title']) ?></strong> — and all <?= (int) $total ?> playbook strategies — are in the University inside the journzey.ai terminal. Create a free account to read them and copy any strategy into your journal.</p>
      <div class="center-actions"><a class="btn btn-light btn-lg" href="<?= e(url('/signup')) ?>">Sign up free</a><a class="btn btn-outline-light btn-lg" href="<?= e(url('/login')) ?>">Sign in</a></div>
    </div>
    <p class="center" style="margin-top:24px"><a class="card-link" href="<?= e(url('/learn')) ?>"><?= icon('arrow-left', 'icon icon-sm') ?> Back to the free strategies</a></p>
  </div>
</section>
