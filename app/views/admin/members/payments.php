<?php $base = admin_url('payments'); ?>
<div class="page-head"><div><h1>Payments</h1><p class="muted">Plan purchases through <?= $gateway === 'none' ? 'your payment gateway (not configured yet)' : e(ucfirst($gateway)) ?> and manual grants. Revenue excludes manual grants.</p></div>
  <div class="page-actions"><?php if (can('integrations')): ?><a class="btn btn-secondary" href="<?= e(admin_url('integrations')) ?>">Payment settings</a><?php endif; ?><a class="btn btn-secondary" href="<?= e(admin_url('plans')) ?>">Plans</a></div></div>
<div class="stat-grid">
  <?php foreach ($revenue as $r): ?>
  <div class="stat-card"><span class="stat-icon c5"><?= icon('zap', 'icon') ?></span><div><small>Revenue (<?= e($r['currency']) ?>)</small><strong><?= e(money($r['total'], $r['currency'])) ?></strong><em><?= e(money($r['last30'], $r['currency'])) ?> in the last 30 days · <?= (int) $r['n'] ?> payments</em></div></div>
  <?php endforeach; ?>
  <?php if (!$revenue): ?><div class="stat-card"><span class="stat-icon c5"><?= icon('zap', 'icon') ?></span><div><small>Revenue</small><strong>—</strong><em>No online payments yet</em></div></div><?php endif; ?>
</div>
<nav class="status-tabs" aria-label="Filter by status">
  <a href="<?= e($base) ?>"<?= $status === '' ? ' class="is-active"' : '' ?>>All <b><?= array_sum($counts) ?></b></a>
  <?php foreach (['paid' => 'Paid', 'created' => 'Started', 'failed' => 'Failed', 'refunded' => 'Refunded'] as $k => $l): ?><a href="<?= e($base . '?status=' . $k) ?>"<?= $status === $k ? ' class="is-active"' : '' ?>><?= $l ?> <b><?= (int) ($counts[$k] ?? 0) ?></b></a><?php endforeach; ?>
</nav>
<div class="card">
  <?php if ($rows): ?>
  <div class="table-wrap"><table class="table table-resp">
    <thead><tr><th>Ref</th><th>Member</th><th>Plan</th><th>Gateway</th><th class="t-right">Amount</th><th>Status</th><th>Date</th><th>Access until</th></tr></thead>
    <tbody><?php foreach ($rows as $p): ?>
      <tr><td data-label="Ref" class="nowrap">JZ-<?= (int) $p['id'] ?><small class="block muted"><?= e(mb_strimwidth((string) ($p['gateway_payment_id'] ?: $p['gateway_order_id']), 0, 28, '…')) ?></small></td>
      <td data-label="Member"><?php if ($p['user_id']): ?><a class="strong" href="<?= e(admin_url('members/' . $p['user_id'])) ?>"><?= e($p['name']) ?></a><?php else: ?><span class="muted">Deleted member</span><?php endif; ?><small class="block muted"><?= e($p['email'] ?? $p['customer_email'] ?? '') ?></small></td>
      <td data-label="Plan"><?= e($p['plan_name'] ?? '—') ?> <small class="muted"><?= (int) $p['period_days'] ?>d</small></td>
      <td data-label="Gateway"><span class="tag"><?= e(ucfirst($p['gateway'])) ?></span></td>
      <td data-label="Amount" class="t-right nowrap"><?= e(money($p['amount'], $p['currency'])) ?></td>
      <td data-label="Status"><span class="badge st-<?= $p['status'] === 'paid' ? 'published' : ($p['status'] === 'created' ? 'draft' : 'warn') ?>"><?= e(ucfirst($p['status'] === 'created' ? 'started' : $p['status'])) ?></span><?php if ($p['note']): ?><small class="block muted"><?= e(mb_strimwidth($p['note'], 0, 50, '…')) ?></small><?php endif; ?></td>
      <td data-label="Date" class="nowrap"><?= e(fmt_date($p['paid_at'] ?? $p['created_at'], 'M j, Y g:i A')) ?></td>
      <td data-label="Access until" class="nowrap"><?= e($p['access_until'] ? fmt_date($p['access_until'], 'M j, Y') : '—') ?></td></tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?= App\Core\View::partial('admin/partials/pagination', ['pager' => $pager]) ?>
  <?php else: ?><div class="empty"><?= icon('zap', 'icon') ?><h2>No payments</h2><p>Payments appear here when members buy a plan at <a href="<?= e(url('/pricing')) ?>" target="_blank" rel="noopener">/pricing</a>.</p></div><?php endif; ?>
</div>
