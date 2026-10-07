<?php $paid = $u['plan_id'] && $u['plan_expires_at'] && strtotime($u['plan_expires_at'] . ' UTC') > time(); ?>
<div class="page-head">
  <div style="display:flex;gap:14px;align-items:center">
    <?php if ($u['avatar_url'] && preg_match('#^https://#', $u['avatar_url'])): ?><img class="avatar-img" src="<?= e($u['avatar_url']) ?>" alt="" referrerpolicy="no-referrer"><?php endif; ?>
    <div><h1><?= e($u['name']) ?></h1><p class="muted"><?= e($u['email']) ?> · signed up with <?= $u['signup_method'] === 'google' ? 'Google' : 'email' ?> on <?= e(fmt_date($u['created_at'], 'M j, Y g:i A')) ?></p></div>
  </div>
  <div class="page-actions">
    <?= $u['status'] === 'suspended' ? '<span class="badge lg st-warn">Suspended</span>' : '<span class="badge lg st-published">Active</span>' ?>
    <?= $paid ? '<span class="badge lg st-published">' . e($u['plan_name']) . '</span>' : '<span class="badge lg st-draft">Free</span>' ?>
  </div>
</div>
<div class="detail-grid">
  <div class="stack-lg">
    <section class="card">
      <h2 class="card-title">Profile</h2>
      <dl class="kv">
        <dt>Member ID</dt><dd>#<?= (int) $u['id'] ?></dd>
        <dt>Sign-in methods</dt><dd><?= $u['google_sub'] ? 'Google' : '' ?><?= $u['google_sub'] && $hasPassword ? ' + ' : '' ?><?= $hasPassword ? 'Email & password' : '' ?></dd>
        <dt>Email verified</dt><dd><?= (int) $u['email_verified'] ? 'Yes' : 'No' ?></dd>
        <dt>Onboarding</dt><dd><?= (int) $u['onboarded'] ? 'Completed' : 'Not finished' ?><?= $u['primary_markets'] ? ' · markets: ' . e($u['primary_markets']) : '' ?></dd>
        <dt>Preferences</dt><dd><?= e(($u['timezone'] ?? 'UTC') . ' · ' . strtoupper((string) ($u['language'] ?? 'en')) . ' · ' . ($u['base_currency'] ?? 'USD') . ' · ' . ($u['theme'] ?? '')) ?></dd>
        <dt>Last sign-in</dt><dd><?= e($u['last_login_at'] ? fmt_date($u['last_login_at'], 'M j, Y g:i A') : 'Never') ?><?= $u['last_login_ip'] ? ' from ' . e($u['last_login_ip']) : '' ?></dd>
        <dt>Total sign-ins</dt><dd><?= (int) $u['login_count'] ?></dd>
        <dt>Sign-up IP</dt><dd><?= e($u['signup_ip'] ?? '—') ?></dd>
        <dt>Plan</dt><dd><?= $paid ? e($u['plan_name']) . ' until ' . e(fmt_date($u['plan_expires_at'], 'M j, Y g:i A')) : ($u['plan_expires_at'] ? 'Expired on ' . e(fmt_date($u['plan_expires_at'], 'M j, Y')) : 'Free (demo only)') ?></dd>
        <dt>Usage</dt><dd><?= (int) $counts['trades'] ?> own trades<?= (int) $counts['demo_trades'] ? ' (+' . (int) $counts['demo_trades'] . ' demo)' : '' ?> · <?= (int) $counts['journals'] ?> journal entries · <?= (int) $counts['ai'] ?> AI questions · <?= (int) $counts['strategies'] ?> strategies</dd>
      </dl>
    </section>
    <section class="card">
      <h2 class="card-title">Trading accounts</h2>
      <?php if ($accounts): ?>
      <div class="table-wrap"><table class="table compact"><thead><tr><th>Account</th><th>Mode</th><th class="t-right">Trades</th><th class="t-right">P&amp;L</th><th>Created</th></tr></thead><tbody>
        <?php foreach ($accounts as $a): ?><tr><td><?= e($a['name']) ?><?= (int) $a['is_archived'] ? ' <span class="badge st-draft">Archived</span>' : '' ?></td><td><?= (int) $a['has_demo_data'] ? '<span class="badge st-warn">Demo data</span>' : ((int) $a['is_demo'] ? '<span class="badge st-draft">Demo</span>' : '<span class="badge st-published">Live</span>') ?></td><td class="t-right"><?= (int) $a['trades'] ?></td><td class="t-right nowrap"><?= e(money($a['pnl'], $a['currency'], true)) ?></td><td class="nowrap"><?= e(fmt_date($a['created_at'], 'M j, Y')) ?></td></tr><?php endforeach; ?>
      </tbody></table></div>
      <?php else: ?><p class="muted">No trading accounts yet.</p><?php endif; ?>
    </section>
    <section class="card">
      <div class="card-head"><h2 class="card-title">Sign-in history</h2><a class="link" href="<?= e(admin_url('member-logins?q=' . rawurlencode($u['email']))) ?>">All</a></div>
      <?php if ($logins): ?>
      <div class="table-wrap"><table class="table compact"><thead><tr><th>When</th><th>Method</th><th>Result</th><th>IP</th><th class="hide-sm">Device</th></tr></thead><tbody>
        <?php foreach ($logins as $l): ?><tr><td class="nowrap"><?= e(fmt_date($l['created_at'], 'M j, Y g:i A')) ?></td><td><?= e(ucfirst(str_replace('_', ' ', $l['method']))) ?></td><td><?= $l['success'] ? '<span class="badge st-published">OK</span>' : '<span class="badge st-warn">' . e($l['reason'] ?: 'Failed') . '</span>' ?></td><td><?= e($l['ip'] ?? '') ?></td><td class="hide-sm muted small"><?= e(mb_strimwidth((string) $l['user_agent'], 0, 60, '…')) ?></td></tr><?php endforeach; ?>
      </tbody></table></div>
      <?php else: ?><p class="muted">No sign-ins recorded.</p><?php endif; ?>
    </section>
    <section class="card">
      <h2 class="card-title">Payments</h2>
      <?php if ($payments): ?>
      <div class="table-wrap"><table class="table compact"><thead><tr><th>Date</th><th>Plan</th><th>Gateway</th><th class="t-right">Amount</th><th>Status</th><th>Access until</th></tr></thead><tbody>
        <?php foreach ($payments as $p): ?><tr><td class="nowrap"><?= e(fmt_date($p['paid_at'] ?? $p['created_at'], 'M j, Y')) ?></td><td><?= e($p['plan_name'] ?? '—') ?></td><td><?= e(ucfirst($p['gateway'])) ?></td><td class="t-right nowrap"><?= e(money($p['amount'], $p['currency'])) ?></td><td><span class="badge st-<?= $p['status'] === 'paid' ? 'published' : ($p['status'] === 'created' ? 'draft' : 'warn') ?>"><?= e(ucfirst($p['status'])) ?></span></td><td class="nowrap"><?= e($p['access_until'] ? fmt_date($p['access_until'], 'M j, Y') : '—') ?></td></tr><?php endforeach; ?>
      </tbody></table></div>
      <?php else: ?><p class="muted">No payments.</p><?php endif; ?>
    </section>
    <section class="card">
      <h2 class="card-title">Member activity</h2>
      <?php if ($audit): ?><ul class="mini-list"><?php foreach ($audit as $a): ?><li><div><strong><?= e(str_replace('_', ' ', $a['action'])) ?></strong><small><?= e($a['details'] ?? '') ?> · <?= e(fmt_date($a['created_at'], 'M j, g:i A')) ?><?= $a['ip'] ? ' · ' . e($a['ip']) : '' ?></small></div></li><?php endforeach; ?></ul>
      <?php else: ?><p class="muted">No activity yet.</p><?php endif; ?>
    </section>
  </div>
  <aside class="stack-lg">
    <?php if (can('members') && can('billing')): ?>
    <form method="post" action="<?= e(admin_url('members/' . $u['id'] . '/grant')) ?>" class="card">
      <?= csrf_field() ?>
      <h2 class="card-title"><?= icon('star', 'icon icon-sm') ?> Grant or extend a plan</h2>
      <p class="muted small">For bank transfers, trials or support. Recorded as a manual payment; same-plan time stacks on remaining access.</p>
      <div class="f"><label for="g-plan">Plan</label><select id="g-plan" name="plan_id"><?php foreach ($plans as $p): ?><option value="<?= (int) $p['id'] ?>" data-days="<?= (int) $p['interval_days'] ?>"><?= e($p['name']) ?> (<?= (int) $p['interval_days'] ?> days)</option><?php endforeach; ?></select></div>
      <div class="f"><label for="g-days">Days</label><input id="g-days" type="number" name="days" min="1" max="3660" value="<?= (int) ($plans[0]['interval_days'] ?? 30) ?>" required></div>
      <div class="f"><label for="g-amt">Amount received (optional)</label><input id="g-amt" type="number" name="amount" min="0" step="0.01" value="0"></div>
      <div class="f"><label for="g-note">Note</label><input id="g-note" name="note" maxlength="200" placeholder="e.g. Bank transfer ref 1234"></div>
      <button class="btn btn-primary btn-block">Grant access</button>
    </form>
    <?php if ($paid): ?>
    <form method="post" action="<?= e(admin_url('members/' . $u['id'] . '/revoke')) ?>" class="card" data-confirm="End this member's plan access now?"><?= csrf_field() ?>
      <h2 class="card-title">End plan now</h2><p class="muted small">Live accounts become read-only. No refund is issued automatically.</p>
      <button class="btn btn-secondary btn-block">End access</button>
    </form>
    <?php endif; endif; ?>
    <?php if (can('members')): ?>
    <form method="post" action="<?= e(admin_url('members/' . $u['id'] . '/note')) ?>" class="card"><?= csrf_field() ?>
      <h2 class="card-title">Internal note</h2>
      <div class="f"><label class="sr-only" for="n-note">Note</label><textarea id="n-note" name="admin_note" rows="4" maxlength="5000"><?= e($u['admin_note'] ?? '') ?></textarea></div>
      <button class="btn btn-secondary btn-block">Save note</button>
    </form>
    <form method="post" action="<?= e(admin_url('members/' . $u['id'] . '/status')) ?>" class="card" data-confirm="<?= $u['status'] === 'active' ? 'Suspend this member? They will be signed out and blocked from signing in.' : 'Re-activate this member?' ?>"><?= csrf_field() ?>
      <h2 class="card-title"><?= $u['status'] === 'active' ? 'Suspend member' : 'Re-activate member' ?></h2>
      <button class="btn btn-secondary btn-block"><?= $u['status'] === 'active' ? 'Suspend' : 'Re-activate' ?></button>
    </form>
    <form method="post" action="<?= e(admin_url('members/' . $u['id'] . '/delete')) ?>" class="card danger-zone" data-confirm="Permanently delete this member and all of their data?"><?= csrf_field() ?>
      <h2 class="card-title">Delete member</h2>
      <p class="muted small">Deletes the account, trades, journal, screenshots and AI history. Payment records are kept anonymised.</p>
      <div class="f"><label for="d-email">Type <?= e($u['email']) ?> to confirm</label><input id="d-email" name="confirm_email" autocomplete="off" required></div>
      <button class="btn btn-danger btn-block">Delete permanently</button>
    </form>
    <?php endif; ?>
  </aside>
</div>
<script>document.getElementById('g-plan')?.addEventListener('change', function () { document.getElementById('g-days').value = this.selectedOptions[0].dataset.days; });</script>
