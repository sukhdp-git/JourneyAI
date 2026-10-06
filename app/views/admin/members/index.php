<?php $base = admin_url('members'); $qs = fn (array $o) => $base . '?' . http_build_query(array_filter(array_merge($f, ['sort' => $sort], $o), fn ($v) => $v !== '' && $v !== null)); ?>
<div class="page-head"><div><h1>Members</h1><p class="muted">Everyone who created an account on the website — with Google or email. Demo trades are excluded from trade counts.</p></div>
  <div class="page-actions"><?php if (can('members')): ?><a class="btn btn-secondary" href="<?= e($qs(['export' => 'csv'])) ?>"><?= icon('download', 'icon icon-sm') ?> Export CSV</a><?php endif; ?></div></div>
<div class="stat-grid">
  <div class="stat-card"><span class="stat-icon c1"><?= icon('users', 'icon') ?></span><div><small>Total</small><strong><?= number_format((int) $stats['total']) ?></strong><em><?= (int) $stats['week'] ?> new this week</em></div></div>
  <a class="stat-card" href="<?= e($base . '?method=google') ?>"><span class="stat-icon c2"><?= icon('globe', 'icon') ?></span><div><small>Google sign-ups</small><strong><?= number_format((int) $stats['google']) ?></strong><em><?= number_format((int) $stats['email']) ?> email sign-ups</em></div></a>
  <a class="stat-card" href="<?= e($base . '?active=today') ?>"><span class="stat-icon c3"><?= icon('activity', 'icon') ?></span><div><small>Signed in today</small><strong><?= number_format((int) $stats['today']) ?></strong><em><a href="<?= e(admin_url('member-logins')) ?>">Sign-in log</a></em></div></a>
  <a class="stat-card" href="<?= e($base . '?plan=paid') ?>"><span class="stat-icon c4"><?= icon('star', 'icon') ?></span><div><small>Paid</small><strong><?= number_format((int) $stats['paid']) ?></strong><em>Active plans</em></div></a>
</div>
<div class="card">
  <form class="toolbar" method="get" action="<?= e($base) ?>">
    <label class="search-input"><?= icon('search', 'icon icon-sm') ?><span class="sr-only">Search</span><input type="search" name="q" value="<?= e($f['q']) ?>" placeholder="Name or email…"></label>
    <label class="sr-only" for="f-m">Sign-up method</label><select id="f-m" name="method"><option value="">Any sign-up</option><option value="google"<?= $f['method'] === 'google' ? ' selected' : '' ?>>Google</option><option value="email"<?= $f['method'] === 'email' ? ' selected' : '' ?>>Email</option></select>
    <label class="sr-only" for="f-p">Plan</label><select id="f-p" name="plan"><option value="">Any plan</option><?php foreach (['paid' => 'Paid (active)', 'free' => 'Free', 'expired' => 'Expired'] as $k => $l): ?><option value="<?= $k ?>"<?= $f['plan'] === $k ? ' selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
    <label class="sr-only" for="f-a">Activity</label><select id="f-a" name="active"><option value="">Any activity</option><?php foreach (['today' => 'Signed in today', '7d' => 'Last 7 days', 'never' => 'Never signed in'] as $k => $l): ?><option value="<?= $k ?>"<?= $f['active'] === $k ? ' selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
    <label class="sr-only" for="f-s">Status</label><select id="f-s" name="status"><option value="">Any status</option><option value="active"<?= $f['status'] === 'active' ? ' selected' : '' ?>>Active</option><option value="suspended"<?= $f['status'] === 'suspended' ? ' selected' : '' ?>>Suspended</option></select>
    <label class="sr-only" for="f-o">Sort</label><select id="f-o" name="sort"><?php foreach (['created' => 'Newest', 'login' => 'Last sign-in', 'logins' => 'Most sign-ins', 'trades' => 'Most trades', 'name' => 'Name'] as $k => $l): ?><option value="<?= $k ?>"<?= $sort === $k ? ' selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
    <button class="btn btn-secondary btn-sm">Apply</button><?php if (array_filter($f)): ?><a class="link small" href="<?= e($base) ?>">Reset</a><?php endif; ?>
  </form>
  <?php if ($rows): ?>
  <div class="table-wrap"><table class="table table-resp">
    <thead><tr><th>Member</th><th>Sign-up</th><th>Plan</th><th>Joined</th><th>Last sign-in</th><th class="t-right">Sign-ins</th><th class="t-right">Trades</th><th class="t-right">Live accts</th></tr></thead>
    <tbody><?php foreach ($rows as $r): ?>
      <tr>
        <td data-label="Member"><a class="strong" href="<?= e(admin_url('members/' . $r['id'])) ?>"><?= e($r['name']) ?></a><?= $r['status'] === 'suspended' ? ' <span class="badge st-warn">Suspended</span>' : '' ?><?= (int) $r['onboarded'] ? '' : ' <span class="badge st-draft">Onboarding</span>' ?><small class="block muted"><?= e($r['email']) ?></small></td>
        <td data-label="Sign-up"><span class="tag"><?= e(ucfirst($r['signup_method'])) ?></span></td>
        <td data-label="Plan"><?= $r['is_paid'] ? '<span class="badge st-published">' . e($r['plan_name']) . '</span><small class="block muted">until ' . e(fmt_date($r['plan_expires_at'], 'M j, Y')) . '</small>' : ($r['plan_expires_at'] ? '<span class="badge st-draft">Expired</span>' : '<span class="badge st-draft">Free</span>') ?></td>
        <td data-label="Joined" class="nowrap"><?= e(fmt_date($r['created_at'], 'M j, Y')) ?></td>
        <td data-label="Last sign-in" class="nowrap"><?= e($r['last_login_at'] ? fmt_date($r['last_login_at'], 'M j, g:i A') : 'Never') ?></td>
        <td data-label="Sign-ins" class="t-right"><?= (int) $r['login_count'] ?></td>
        <td data-label="Trades" class="t-right"><?= (int) $r['trades'] ?></td>
        <td data-label="Live accts" class="t-right"><?= (int) $r['live_accounts'] ?></td>
      </tr><?php endforeach; ?></tbody>
  </table></div>
  <?= App\Core\View::partial('admin/partials/pagination', ['pager' => $pager]) ?>
  <?php else: ?><div class="empty"><?= icon('users', 'icon') ?><h2>No members found</h2><p>Members appear here when they sign up at <a href="<?= e(url('/signup')) ?>" target="_blank" rel="noopener">/signup</a>.</p></div><?php endif; ?>
</div>
