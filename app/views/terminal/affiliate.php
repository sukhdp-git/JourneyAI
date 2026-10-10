<?php
use App\Trading\Affiliates;
/** @var array $a @var ?array $stats */
$link = $a['code'] ? url('/r/' . $a['code']) : '';
$labels = ['pending' => 'Pending', 'approved' => 'Approved', 'paid' => 'Paid', 'void' => 'Void'];
?>
<?php if ($a['status'] === 'pending' || $a['status'] === 'rejected'): ?>
<section class="panel aff-state">
  <div class="panel-head"><h2>Affiliate programme</h2><span class="badge<?= $a['status'] === 'rejected' ? ' loss' : '' ?>"><?= e($a['status']) ?></span></div>
  <?php if ($a['status'] === 'pending'): ?>
    <p>Thanks, <?= e($a['full_name']) ?> — your application was received on <?= e(fmt_date($a['created_at'], 'M j, Y')) ?>. The journzey.ai team reviews every application by hand. Once approved, your personal link, coupon code and earnings appear on this page.</p>
  <?php else: ?>
    <p>Your affiliate application was not approved this time.<?= $a['admin_note'] ? ' Note from the team: ' . e($a['admin_note']) : '' ?></p>
  <?php endif; ?>
  <a class="tm-btn tm-btn-sm" href="<?= e(url('/affiliates')) ?>">About the programme</a>
</section>
<?php else: $t = $stats['totals']; ?>
<?php if ($a['status'] === 'suspended'): ?><div class="tm-alert tm-alert-error" role="alert">Your affiliate account is paused — your code does not attribute new customers right now.<?= $a['admin_note'] ? ' ' . e($a['admin_note']) : '' ?></div><?php endif; ?>
<section class="aff-hero panel">
  <div>
    <span class="ex-kicker"><?= icon('link', 'icon icon-xs') ?> Your affiliate link</span>
    <div class="aff-link-row"><code id="aff-link"><?= e($link) ?></code><button type="button" class="tm-btn tm-btn-sm" data-copy-target="#aff-link"><?= icon('copy', 'icon icon-xs') ?> Copy link</button></div>
    <div class="aff-link-row"><span class="muted small">Coupon code</span><code id="aff-code" class="aff-code"><?= e($a['code']) ?></code><button type="button" class="tm-btn tm-btn-sm" data-copy-target="#aff-code"><?= icon('copy', 'icon icon-xs') ?> Copy code</button></div>
    <p class="muted small">Customers must enter <b><?= e($a['code']) ?></b> at checkout (your link fills it in automatically). You earn <b><?= rtrim(rtrim(number_format((float) $a['commission_pct'], 2), '0'), '.') ?>%</b> of every plan payment they make — every month they renew.</p>
  </div>
</section>
<div class="aff-tiles">
  <div class="dash-tile"><small>Link clicks</small><strong><?= number_format((int) $a['clicks']) ?></strong><em>visits to your link</em></div>
  <div class="dash-tile"><small>Customers referred</small><strong><?= number_format($stats['referred']) ?></strong><em>used your code</em></div>
  <div class="dash-tile"><small>Paying customers</small><strong><?= number_format($stats['paying']) ?></strong><em>made at least one payment</em></div>
  <?php if (!$t): ?>
  <div class="dash-tile"><small>Earnings</small><strong>—</strong><em>no commissions yet</em></div>
  <?php endif; ?>
  <?php foreach ($t as $cur => $by):
      $earned = ($by['pending']['sum'] ?? 0) + ($by['approved']['sum'] ?? 0) + ($by['paid']['sum'] ?? 0); ?>
  <div class="dash-tile is-up"><small>Total earned (<?= e($cur) ?>)</small><strong class="up"><?= e(money($earned, $cur)) ?></strong><em>Paid <?= e(money($by['paid']['sum'] ?? 0, $cur)) ?> · due <?= e(money(($by['pending']['sum'] ?? 0) + ($by['approved']['sum'] ?? 0), $cur)) ?></em></div>
  <?php endforeach; ?>
</div>
<div class="grid g-main" style="margin-top:12px">
  <section class="panel">
    <div class="panel-head"><h2>Commissions</h2><span class="muted small">Pending → approved by the team → paid</span></div>
    <?php if ($stats['rows']): ?>
    <div class="table-wrap"><table class="tbl cards">
      <thead><tr><th>Date</th><th>Customer</th><th>Plan</th><th class="r">Payment</th><th class="r">Rate</th><th class="r">Commission</th><th>Status</th></tr></thead>
      <tbody><?php foreach ($stats['rows'] as $r): ?>
        <tr><td data-label="Date"><?= e(fmt_date($r['paid_at'] ?? $r['created_at'], 'M j, Y')) ?></td><td data-label="Customer"><?= e(Affiliates::maskEmail($r['email'])) ?></td><td data-label="Plan"><?= e($r['plan_name'] ?? '—') ?></td>
        <td data-label="Payment" class="r num"><?= e(money($r['payment_amount'], $r['currency'])) ?></td><td data-label="Rate" class="r num"><?= rtrim(rtrim((string) $r['rate'], '0'), '.') ?>%</td>
        <td data-label="Commission" class="r num <?= $r['status'] === 'void' ? 'muted' : 'up' ?>"><?= e(money($r['commission'], $r['currency'])) ?></td>
        <td data-label="Status"><span class="badge <?= $r['status'] === 'paid' ? 'win' : ($r['status'] === 'void' ? 'loss' : '') ?>"><?= e($labels[$r['status']] ?? $r['status']) ?></span></td></tr>
      <?php endforeach; ?></tbody>
    </table></div>
    <?php else: ?><p class="muted">No commissions yet. They appear here as soon as a customer who used your code pays for a plan.</p><?php endif; ?>
  </section>
  <aside class="stack">
    <form method="post" action="<?= e(url('/terminal/affiliate/payout')) ?>" class="panel stack">
      <?= csrf_field() ?>
      <h2>Payout details</h2>
      <p class="muted small">How the team should pay you (e.g. PayPal email, UPI ID or bank reference). Never enter passwords or card numbers.</p>
      <div class="f"><label class="sr-only" for="aff-pay">Payout details</label><textarea id="aff-pay" name="payout_details" rows="3" maxlength="500"><?= e($a['payout_details'] ?? '') ?></textarea></div>
      <button class="tm-btn tm-btn-primary tm-btn-sm">Save</button>
    </form>
    <section class="panel stack small">
      <h2>How payouts work</h2>
      <ul class="aff-list">
        <li>Each customer payment creates a <b>pending</b> commission.</li>
        <li>The team approves it after the refund window, then pays it out manually.</li>
        <li>Refunded payments are voided.</li>
        <li>Your own purchases never earn commission.</li>
      </ul>
    </section>
  </aside>
</div>
<?php endif; ?>
