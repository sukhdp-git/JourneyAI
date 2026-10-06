<?php
/** Plan cards shared by /pricing and the homepage pricing section. */
$plans = App\Trading\Payments::plans();
$me = member();
$free = parse_lines(setting('free_plan_features'), ['title']);
$current = $me && $me['ent']['paid'] ? (int) $me['ent']['plan']['id'] : 0;
?>
<div class="pricing-grid">
  <article class="price-card reveal">
    <h3><?= e(setting('free_plan_name', 'Free (Demo)')) ?></h3>
    <p class="price"><span class="amount"><?= e(money(0, $plans[0]['currency'] ?? 'USD')) ?></span><span class="per">forever</span></p>
    <p class="muted">Explore every tool with demo accounts and test trades.</p>
    <ul class="price-features"><?php foreach ($free as $f): ?><li><?= icon('check', 'icon icon-sm') ?><?= e($f['title']) ?></li><?php endforeach; ?></ul>
    <?php if ($me): ?>
      <a class="btn btn-ghost btn-block" href="<?= e(url('/terminal')) ?>">Open terminal</a>
    <?php else: ?>
      <a class="btn btn-ghost btn-block" href="<?= e(url('/signup')) ?>">Start free</a>
    <?php endif; ?>
  </article>
  <?php foreach ($plans as $p): $feat = parse_lines($p['features'], ['title']); ?>
  <article class="price-card reveal<?= (int) $p['is_featured'] ? ' is-featured' : '' ?>">
    <?php if ((int) $p['is_featured']): ?><span class="price-badge">Most popular</span><?php endif; ?>
    <h3><?= e($p['name']) ?></h3>
    <p class="price"><span class="amount"><?= e(money($p['price'], $p['currency'])) ?></span><span class="per">/ <?= e($p['interval_label']) ?></span></p>
    <?php if ($p['tagline']): ?><p class="muted"><?= e($p['tagline']) ?></p><?php endif; ?>
    <ul class="price-features"><?php foreach ($feat as $f): ?><li><?= icon('check', 'icon icon-sm') ?><?= e($f['title']) ?></li><?php endforeach; ?></ul>
    <?php if ($current === (int) $p['id']): ?>
      <a class="btn btn-primary btn-block" href="<?= e(url('/checkout/' . $p['slug'])) ?>">Extend plan</a>
    <?php else: ?>
      <a class="btn <?= (int) $p['is_featured'] ? 'btn-primary' : 'btn-ghost' ?> btn-block" href="<?= e(url($me ? '/checkout/' . $p['slug'] : '/signup?plan=' . $p['slug'])) ?>">Get <?= e($p['name']) ?></a>
    <?php endif; ?>
  </article>
  <?php endforeach; ?>
</div>
<p class="pricing-note">Prices are one-time payments for the period shown — no automatic renewal. <?= e(setting('payment_currency_note')) ?></p>
