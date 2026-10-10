<?php
use App\Trading\Affiliates;
/** @var array $a @var array $stats @var string $suggested @var float $defaultPct @var array $referred */
$badge = ['pending' => 'st-draft', 'approved' => 'st-published', 'suspended' => 'st-warn', 'rejected' => 'st-warn'];
$cbadge = ['pending' => 'st-draft', 'approved' => 'st-scheduled', 'paid' => 'st-published', 'void' => 'st-warn'];
$pct = rtrim(rtrim(number_format((float) ($a['status'] === 'pending' ? $defaultPct : $a['commission_pct']), 2), '0'), '.');
$base = admin_url('affiliates/' . $a['id']);
?>
<div class="page-head">
  <div><h1><?= e($a['full_name']) ?></h1><p class="muted"><a href="<?= e(admin_url('members/' . $a['user_id'])) ?>"><?= e($a['email']) ?></a> · applied <?= e(fmt_date($a['created_at'], 'M j, Y g:i A')) ?></p></div>
  <div class="page-actions"><span class="badge lg <?= $badge[$a['status']] ?>"><?= e(ucfirst($a['status'])) ?></span><?php if ($a['code']): ?><span class="badge lg st-published"><?= e($a['code']) ?> · <?= e(rtrim(rtrim((string) $a['commission_pct'], '0'), '.')) ?>%</span><?php endif; ?></div>
</div>
<div class="stat-grid">
  <div class="stat-card"><span class="stat-icon c1"><?= icon('link', 'icon') ?></span><div><small>Link clicks</small><strong><?= number_format((int) $a['clicks']) ?></strong><em><?= $a['code'] ? e(url('/r/' . $a['code'])) : 'No link yet' ?></em></div></div>
  <div class="stat-card"><span class="stat-icon c2"><?= icon('users', 'icon') ?></span><div><small>Customers referred</small><strong><?= (int) $stats['referred'] ?></strong><em><?= (int) $stats['paying'] ?> paying</em></div></div>
  <?php foreach ($stats['totals'] as $cur => $by): ?>
  <div class="stat-card"><span class="stat-icon c5"><?= icon('zap', 'icon') ?></span><div><small>Commission (<?= e($cur) ?>)</small><strong><?= e(money(($by['pending']['sum'] ?? 0) + ($by['approved']['sum'] ?? 0), $cur)) ?> due</strong><em>Paid <?= e(money($by['paid']['sum'] ?? 0, $cur)) ?> · sales <?= e(money(array_sum(array_map(fn ($x) => $x['sales'], array_diff_key($by, ['void' => 1]))), $cur)) ?></em></div></div>
  <?php endforeach; ?>
</div>
<div class="detail-grid">
  <div class="stack-lg">
    <section class="card">
      <h2 class="card-title">Application</h2>
      <dl class="kv">
        <dt>Platform</dt><dd><?= e((string) $a['platform']) ?></dd>
        <dt>Channel</dt><dd><?php if ($a['channel_url']): ?><a href="<?= e($a['channel_url']) ?>" target="_blank" rel="noopener nofollow"><?= e($a['channel_url']) ?></a><?php else: ?>—<?php endif; ?></dd>
        <dt>Audience size</dt><dd><?= e((string) ($a['audience'] ?: '—')) ?></dd>
        <dt>Payout details</dt><dd><?= $a['payout_details'] ? nl2br(e($a['payout_details'])) : '<span class="muted">Not provided yet</span>' ?></dd>
      </dl>
      <?php if ($a['message']): ?><p class="small" style="margin-top:12px;white-space:pre-line"><?= e($a['message']) ?></p><?php endif; ?>
    </section>
    <section class="card">
      <div class="card-head"><h2 class="card-title">Commissions</h2></div>
      <?php if ($stats['rows']): ?>
      <form method="post" action="<?= e($base . '/commissions') ?>" id="comm-form"><?= csrf_field() ?>
        <div class="table-wrap"><table class="table compact table-resp">
          <thead><tr><th><span class="sr-only">Select</span></th><th>Date</th><th>Customer</th><th class="t-right">Payment</th><th class="t-right">Commission</th><th>Status</th></tr></thead>
          <tbody><?php foreach ($stats['rows'] as $r): ?>
            <tr><td><?php if (in_array($r['status'], ['pending', 'approved'], true)): ?><input type="checkbox" name="ids[]" value="<?= (int) $r['id'] ?>" aria-label="Select commission <?= (int) $r['id'] ?>"><?php endif; ?></td>
            <td data-label="Date" class="nowrap"><?= e(fmt_date($r['paid_at'] ?? $r['created_at'], 'M j, Y')) ?></td>
            <td data-label="Customer"><?php if ($r['user_id']): ?><a href="<?= e(admin_url('members/' . $r['user_id'])) ?>"><?= e($r['email'] ?? '') ?></a><?php else: ?><span class="muted">Deleted</span><?php endif; ?><small class="block muted">JZ-<?= (int) $r['payment_id'] ?> · <?= e($r['plan_name'] ?? '—') ?></small></td>
            <td data-label="Payment" class="t-right nowrap"><?= e(money($r['payment_amount'], $r['currency'])) ?></td>
            <td data-label="Commission" class="t-right nowrap"><strong><?= e(money($r['commission'], $r['currency'])) ?></strong> <small class="muted"><?= e(rtrim(rtrim((string) $r['rate'], '0'), '.')) ?>%</small></td>
            <td data-label="Status"><span class="badge <?= $cbadge[$r['status']] ?>"><?= e(ucfirst($r['status'])) ?></span></td></tr>
          <?php endforeach; ?></tbody>
        </table></div>
        <div class="toolbar" style="margin-top:12px">
          <span class="small muted">Selected:</span>
          <button class="btn btn-secondary btn-sm" name="to" value="approved">Approve</button>
          <button class="btn btn-primary btn-sm" name="to" value="paid">Mark paid</button>
          <button class="btn btn-secondary btn-sm" name="to" value="void">Void</button>
        </div>
      </form>
      <form method="post" action="<?= e($base . '/commissions') ?>" class="toolbar" data-confirm="Mark every approved commission of this affiliate as paid?"><?= csrf_field() ?><input type="hidden" name="only" value="approved"><input type="hidden" name="to" value="paid"><button class="btn btn-secondary btn-sm">Mark all approved as paid</button></form>
      <?php else: ?><p class="muted">No commissions yet.</p><?php endif; ?>
    </section>
    <section class="card">
      <h2 class="card-title">Referred customers</h2>
      <?php if ($referred): ?>
      <div class="table-wrap"><table class="table compact"><thead><tr><th>Customer</th><th>Linked</th><th>Plan</th></tr></thead><tbody>
        <?php foreach ($referred as $u): $paid = $u['plan_id'] && $u['plan_expires_at'] && strtotime($u['plan_expires_at'] . ' UTC') > time(); ?>
        <tr><td><a href="<?= e(admin_url('members/' . $u['id'])) ?>"><?= e($u['name']) ?></a><small class="block muted"><?= e($u['email']) ?></small></td><td class="nowrap"><?= e(fmt_date($u['referred_at'], 'M j, Y')) ?></td><td><?= $paid ? '<span class="badge st-published">Paid</span>' : '<span class="badge st-draft">Free</span>' ?></td></tr>
        <?php endforeach; ?>
      </tbody></table></div>
      <?php else: ?><p class="muted">Nobody has used this code yet.</p><?php endif; ?>
    </section>
  </div>
  <aside class="stack-lg">
    <?php if ($a['status'] !== 'suspended'): ?>
    <form method="post" action="<?= e($base . '/approve') ?>" class="card"><?= csrf_field() ?>
      <h2 class="card-title"><?= $a['status'] === 'approved' ? 'Code & commission' : 'Approve affiliate' ?></h2>
      <div class="f"><label for="ap-code">Coupon code</label><input id="ap-code" name="code" maxlength="32" pattern="[A-Za-z0-9]{3,32}" value="<?= e($suggested) ?>" required><small class="muted">Letters and numbers. Link: <?= e(url('/r/')) ?>CODE</small></div>
      <div class="f"><label for="ap-pct">Commission (%)</label><input id="ap-pct" type="number" name="commission_pct" min="0.01" max="90" step="0.01" value="<?= e($pct) ?>" required></div>
      <button class="btn btn-primary btn-block"><?= $a['status'] === 'approved' ? 'Save' : ($a['status'] === 'pending' ? 'Approve & issue code' : 'Approve again') ?></button>
    </form>
    <?php endif; ?>
    <?php if ($a['status'] !== 'rejected'):
        [$next, $title, $btn, $cls, $confirm] = match ($a['status']) {
            'pending' => ['rejected', 'Reject application', 'Reject', 'btn-secondary', ''],
            'suspended' => ['approved', 'Suspended', 'Re-activate', 'btn-primary', ''],
            default => ['suspended', 'Pause affiliate', 'Suspend', 'btn-secondary', 'Pause this affiliate? Their code stops attributing new customers.'],
        }; ?>
    <form method="post" action="<?= e($base . '/status') ?>" class="card"<?= $confirm ? ' data-confirm="' . e($confirm) . '"' : '' ?>><?= csrf_field() ?>
      <h2 class="card-title"><?= e($title) ?></h2>
      <input type="hidden" name="status" value="<?= e($next) ?>">
      <div class="f"><label for="st-note">Note to the affiliate (optional)</label><textarea id="st-note" name="admin_note" rows="3" maxlength="500"><?= e($a['admin_note'] ?? '') ?></textarea></div>
      <button class="btn <?= $cls ?> btn-block"><?= e($btn) ?></button>
    </form>
    <?php endif; ?>
  </aside>
</div>
