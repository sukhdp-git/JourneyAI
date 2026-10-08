<?php
use App\Core\View;
use App\Models\Learn;
use App\Trading\Domain;
/** @var array $strategy @var int $number @var ?array $prev @var ?array $next @var array $related @var ?array $member */
$s = $strategy;
$rules = Learn::lines($s['setup_rules']);
$targets = Learn::lines($s['take_profit']);
$styleLabel = $s['style'] && isset(Domain::STRATEGY_STYLES[$s['style']]) ? Domain::STRATEGY_STYLES[$s['style']] : '';
$facts = array_filter([
    'Target assets' => $s['assets'],
    'Ideal session' => $s['session_window'],
    'Execution timeframe' => $s['timeframe_detail'] ?: $s['timeframe'],
    'Target R:R' => $s['target_rr'],
]);
?>
<?= View::partial('public/partials/page-hero', ['eyebrow' => 'Strategy ' . $number . ($styleLabel ? ' · ' . $styleLabel : ''), 'title' => $s['title'], 'subtitle' => $s['summary'], 'crumbs' => [['Home', '/'], ['Learn', '/learn'], [$s['short_title'] ?: $s['title'], '/learn/' . $s['slug']]]]) ?>
<section class="section section-tight">
  <div class="container detail-layout">
    <article class="learn-article">
      <div class="learn-disclaimer reveal" role="note"><?= icon('info', 'icon') ?><p><strong>Educational content — not financial advice.</strong> Past behaviour of a setup does not guarantee future results. Practise in demo first and never risk more than your plan allows.</p></div>

      <section class="learn-block reveal" aria-labelledby="h-logic">
        <h2 id="h-logic"><?= icon('compass', 'icon') ?>Institutional logic</h2>
        <p><?= nl2br(e((string) $s['logic'])) ?></p>
      </section>

      <?php if ($rules): ?>
      <section class="learn-block reveal" aria-labelledby="h-rules">
        <h2 id="h-rules"><?= icon('list', 'icon') ?>Setup rules</h2>
        <ol class="learn-rules"><?php foreach ($rules as $r): ?><li><?= e($r) ?></li><?php endforeach; ?></ol>
      </section>
      <?php endif; ?>

      <div class="learn-exec reveal">
        <section class="learn-exec-card is-entry" aria-labelledby="h-entry">
          <h2 id="h-entry"><?= icon('target', 'icon icon-sm') ?>Entry trigger</h2>
          <p><?= e((string) $s['entry_trigger']) ?></p>
        </section>
        <section class="learn-exec-card is-stop" aria-labelledby="h-stop">
          <h2 id="h-stop"><?= icon('shield', 'icon icon-sm') ?>Stop loss</h2>
          <p><?= e((string) $s['stop_loss']) ?></p>
        </section>
        <section class="learn-exec-card is-target" aria-labelledby="h-target">
          <h2 id="h-target"><?= icon('flag', 'icon icon-sm') ?>Take profit</h2>
          <?php if (count($targets) > 1): ?><ul><?php foreach ($targets as $t): ?><li><?= e($t) ?></li><?php endforeach; ?></ul><?php else: ?><p><?= e($targets[0] ?? '') ?></p><?php endif; ?>
        </section>
      </div>

      <?php if (trim((string) $s['educator_note']) !== ''): ?>
      <p class="learn-note reveal"><?= icon('clock', 'icon icon-sm') ?><span><?= e($s['educator_note']) ?></span></p>
      <?php endif; ?>

      <nav class="learn-pager reveal" aria-label="More strategies">
        <?php if ($prev): ?><a class="learn-pager-prev" href="<?= e(url('/learn/' . $prev['slug'])) ?>"><small><?= icon('arrow-left', 'icon icon-xs') ?> Previous</small><span><?= e($prev['short_title'] ?: $prev['title']) ?></span></a><?php else: ?><span></span><?php endif; ?>
        <?php if ($next): ?><a class="learn-pager-next" href="<?= e(url('/learn/' . $next['slug'])) ?>"><small>Next <?= icon('arrow-right', 'icon icon-xs') ?></small><span><?= e($next['short_title'] ?: $next['title']) ?></span></a><?php endif; ?>
      </nav>
    </article>

    <aside class="detail-aside">
      <div class="sticky-aside">
        <div class="aside-card reveal">
          <h2 class="h4">Strategy profile</h2>
          <dl class="learn-profile">
            <?php foreach ($facts as $k => $v): ?><div><dt><?= e($k) ?></dt><dd><?= e((string) $v) ?></dd></div><?php endforeach; ?>
          </dl>
        </div>
        <div class="aside-card reveal">
          <span class="card-icon"><?= icon('journal', 'icon') ?></span>
          <h2 class="h4">Track this strategy</h2>
          <?php if ($member): ?>
            <p>Copy these rules into your personal strategies, then tag your trades to measure win rate, average R and profit factor.</p>
            <form method="post" action="<?= e(url('/terminal/strategies/import/' . $s['slug'])) ?>">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-primary btn-block"><?= icon('plus', 'icon icon-sm') ?> Add to my strategies</button>
            </form>
          <?php else: ?>
            <p>Create a free account to save this strategy to your journal and measure how it performs for you — starting in demo mode.</p>
            <a class="btn btn-primary btn-block" href="<?= e(url('/signup')) ?>">Start free</a>
            <a class="btn btn-ghost btn-block learn-signin" href="<?= e(url('/login')) ?>">Sign in</a>
          <?php endif; ?>
        </div>
        <a class="card-link" href="<?= e(url('/learn')) ?>#matrix"><?= icon('grid', 'icon icon-sm') ?> All 10 strategies</a>
      </div>
    </aside>
  </div>
</section>
<?php if ($related): ?>
<section class="section bg-muted">
  <div class="container">
    <div class="section-head reveal"><p class="eyebrow">Same style</p><h2>Related strategies</h2></div>
    <div class="card-grid">
      <?php foreach ($related as $i => $o): ?>
      <a class="service-card learn-card reveal" style="--d:<?= $i ?>" href="<?= e(url('/learn/' . $o['slug'])) ?>">
        <h3><?= e($o['title']) ?></h3>
        <p><?= e($o['summary']) ?></p>
        <span class="card-link">Read the playbook <?= icon('arrow-right', 'icon icon-sm') ?></span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
