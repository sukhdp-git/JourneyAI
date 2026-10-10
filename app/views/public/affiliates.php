<?php
use App\Controllers\AffiliateController as AC;
use App\Core\View;
/** @var ?array $member @var ?array $mine @var float $pct @var ?array $plan @var bool $enabled */
$pctTxt = rtrim(rtrim(number_format($pct, 2), '0'), '.') . '%';
$opt = fn (array $list) => ['' => '— choose —'] + array_combine($list, $list);
?>
<?= View::partial('public/partials/page-hero', ['eyebrow' => 'Affiliate programme', 'title' => 'Earn ' . $pctTxt . ' on every payment you refer', 'subtitle' => 'Share journzey.ai with your audience. Every trader who joins with your personal code earns you ' . $pctTxt . ' of each plan payment they make — month after month, for as long as they stay subscribed.', 'crumbs' => [['Home', '/'], ['Affiliates', '/affiliates']], 'extra' => '<div class="hero-actions"><a class="btn btn-primary btn-lg" href="#apply">Apply now</a></div>']) ?>
<section class="section section-tight">
  <div class="container">
    <div class="aff-steps">
      <div class="aff-step reveal"><span>1</span><h3>Apply</h3><p>Tell us about your channel. We review every application personally.</p></div>
      <div class="aff-step reveal" style="--d:1"><span>2</span><h3>Get your code &amp; link</h3><p>Once approved you receive a personal coupon code (e.g. <b>ARJUN25</b>) and a link that pre-fills it at checkout.</p></div>
      <div class="aff-step reveal" style="--d:2"><span>3</span><h3>Earn every month</h3><p>Customers who pay with your code earn you <?= e($pctTxt) ?> of every plan payment. Track it live in your affiliate dashboard.</p></div>
    </div>
    <?php if ($plan): $per = (float) $plan['price'] * $pct / 100; ?>
    <div class="aff-example reveal"><?= icon('trend-up', 'icon') ?><p><strong>Example:</strong> if 20 traders join the <?= e($plan['name']) ?> plan (<?= e(money($plan['price'], $plan['currency'])) ?> / <?= (int) $plan['interval_days'] ?> days) with your code, each renewal period earns you <strong><?= e(money($per * 20, $plan['currency'])) ?></strong>. Illustration only — earnings depend entirely on the customers you refer.</p></div>
    <?php endif; ?>
  </div>
</section>
<section class="section bg-muted" id="apply">
  <div class="container form-layout">
    <div class="form-card reveal">
      <?= View::partial('public/partials/flash', ['flash' => $flash]) ?>
      <?php if (!$enabled): ?>
        <h2 class="h3">Applications are closed</h2><p class="muted">Please check back later.</p>
      <?php elseif (!$member): ?>
        <h2 class="h3">Apply with your journzey.ai account</h2>
        <p>Your affiliate dashboard lives inside your account, so first create a free account (or sign in) — then come back to this page to apply.</p>
        <div class="center-actions" style="justify-content:flex-start"><a class="btn btn-primary" href="<?= e(url('/signup')) ?>">Create free account</a><a class="btn btn-ghost" href="<?= e(url('/login')) ?>">Sign in</a></div>
      <?php elseif ($mine && $mine['status'] !== 'rejected'): ?>
        <h2 class="h3">Your application</h2>
        <p>Status: <span class="badge"><?= e(ucfirst($mine['status'])) ?></span></p>
        <?php if ($mine['status'] === 'approved'): ?><p>Your code is <strong><?= e($mine['code']) ?></strong>.</p><a class="btn btn-primary" href="<?= e(url('/terminal/affiliate')) ?>">Open affiliate dashboard</a>
        <?php elseif ($mine['status'] === 'pending'): ?><p class="muted">We are reviewing your application. Your code and dashboard appear in the terminal as soon as it is approved.</p>
        <?php else: ?><p class="muted">Your affiliate account is paused. Contact us if you have questions.</p><?php endif; ?>
      <?php else: ?>
        <h2 class="h3"><?= $mine ? 'Apply again' : 'Apply to become an affiliate' ?></h2>
        <form method="post" action="<?= e(url('/affiliates')) ?>" class="form" novalidate>
          <?= csrf_field() ?>
          <?= View::partial('public/partials/field', ['name' => 'full_name', 'label' => 'Your name or brand', 'required' => true, 'max' => 120]) ?>
          <div class="form-row">
            <?= View::partial('public/partials/field', ['name' => 'platform', 'label' => 'Main platform', 'type' => 'select', 'options' => $opt(AC::PLATFORMS), 'required' => true]) ?>
            <?= View::partial('public/partials/field', ['name' => 'audience', 'label' => 'Audience size', 'type' => 'select', 'options' => $opt(AC::AUDIENCES)]) ?>
          </div>
          <?= View::partial('public/partials/field', ['name' => 'channel_url', 'label' => 'Link to your channel / profile', 'type' => 'url', 'required' => true, 'max' => 255]) ?>
          <?= View::partial('public/partials/field', ['name' => 'message', 'label' => 'How will you promote journzey.ai?', 'type' => 'textarea', 'max' => 2000, 'hint' => 'Optional']) ?>
          <label class="check"><input type="checkbox" name="terms" value="1"> I will promote honestly — no income or profit promises, no spam, no paid ads on the journzey.ai brand name.</label><?= field_error('terms') ?>
          <button class="btn btn-primary btn-lg" type="submit">Submit application</button>
        </form>
      <?php endif; ?>
    </div>
    <aside class="reveal">
      <div class="aside-card">
        <h2 class="h4">How it works</h2>
        <ul class="check-list">
          <li><?= icon('check', 'icon icon-sm') ?> <span><?= e($pctTxt) ?> of every plan payment from customers who used your code — including renewals.</span></li>
          <li><?= icon('check', 'icon icon-sm') ?> <span>Customers enter your coupon code at checkout. Your link (<code>/r/YOURCODE</code>) fills it in for 60 days.</span></li>
          <li><?= icon('check', 'icon icon-sm') ?> <span>Live dashboard: clicks, sign-ups, paying customers and earnings.</span></li>
          <li><?= icon('check', 'icon icon-sm') ?> <span>Commissions are approved after the payment clears and paid out by the journzey.ai team to the payout method you provide.</span></li>
          <li><?= icon('check', 'icon icon-sm') ?> <span>Your own purchases don’t earn commission.</span></li>
        </ul>
      </div>
    </aside>
  </div>
</section>
