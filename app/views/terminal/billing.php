<?php $ent = $m['ent']; ?>
<div class="grid g-main">
  <section class="panel stack">
    <div class="panel-head"><h2>Current plan</h2><?= $ent['paid'] ? '<span class="badge win">active</span>' : '<span class="badge">free</span>' ?></div>
    <dl class="dl">
      <dt>Plan</dt><dd><?= e($ent['plan_name']) ?></dd>
      <?php if ($ent['paid']): ?><dt>Access until</dt><dd><?= e(fmt_date($ent['expires_at'], 'M j, Y H:i')) ?></dd><?php endif; ?>
      <dt>Live accounts</dt><dd><?= $ent['live'] ? (int) $ent['max_live'] . ' allowed' : 'Not included — demo accounts only' ?></dd>
      <dt>AI Coach</dt><dd><?= (int) $ent['ai_daily'] ?> messages per day</dd>
    </dl>
    <?php if (!$ent['paid'] && $m['plan_expires_at']): ?><p class="muted small">Your previous plan ended on <?= e(fmt_date($m['plan_expires_at'], 'M j, Y')) ?>. Live accounts are read-only until you renew; nothing has been deleted.</p><?php endif; ?>
    <?php if ($gateway === 'none'): ?><p class="muted small">Online payments are not switched on yet. Contact the site team to upgrade — an administrator can activate a plan for you.</p><?php endif; ?>
  </section>
  <section class="panel stack">
    <h2><?= $ent['paid'] ? 'Extend or change plan' : 'Upgrade to go live' ?></h2>
    <?php foreach ($plans as $p): ?>
      <div class="plan-card" style="display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap">
        <div><strong><?= e($p['name']) ?></strong> <span class="muted small"><?= e(money($p['price'], $p['currency'])) ?> / <?= e($p['interval_label']) ?></span><?php if ($p['tagline']): ?><br><span class="muted small"><?= e($p['tagline']) ?></span><?php endif; ?></div>
        <a class="tm-btn tm-btn-sm<?= (int) $p['is_featured'] ? ' tm-btn-primary' : '' ?>" href="<?= e(url('/checkout/' . $p['slug'])) ?>"><?= $ent['paid'] && (int) $ent['plan']['id'] === (int) $p['id'] ? 'Extend' : 'Choose' ?></a>
      </div>
    <?php endforeach; ?>
    <a class="small" href="<?= e(url('/pricing')) ?>">Compare plans</a>
  </section>
</div>
<section class="panel" style="margin-top:12px">
  <div class="panel-head"><h2>Payment history</h2></div>
  <?php if ($payments): ?>
  <div class="table-wrap"><table class="tbl cards">
    <thead><tr><th>Date</th><th>Plan</th><th>Gateway</th><th class="r">Amount</th><th>Status</th><th>Access until</th><th>Reference</th></tr></thead>
    <tbody><?php foreach ($payments as $p): ?>
      <tr><td data-label="Date"><?= e(fmt_date($p['paid_at'] ?? $p['created_at'], 'M j, Y')) ?></td><td data-label="Plan"><?= e($p['plan_name'] ?? '—') ?></td><td data-label="Gateway"><?= e(ucfirst($p['gateway'])) ?></td>
      <td data-label="Amount" class="r num"><?= e(money($p['amount'], $p['currency'])) ?></td>
      <td data-label="Status"><span class="badge <?= $p['status'] === 'paid' ? 'win' : ($p['status'] === 'failed' ? 'loss' : '') ?>"><?= e($p['status']) ?></span></td>
      <td data-label="Access until"><?= e($p['access_until'] ? fmt_date($p['access_until'], 'M j, Y') : '—') ?></td><td data-label="Reference" class="muted small">JZ-<?= (int) $p['id'] ?></td></tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?php else: ?><p class="muted">No payments yet.</p><?php endif; ?>
</section>
