<?php $base = admin_url('member-logins'); ?>
<div class="page-head"><div><h1>Sign-in log</h1><p class="muted">Every member sign-in, sign-up and password reset — successful and failed. Passwords and tokens are never logged.</p></div></div>
<div class="stat-grid">
  <div class="stat-card"><span class="stat-icon c3"><?= icon('activity', 'icon') ?></span><div><small>Sign-ins today</small><strong><?= (int) $today['ok'] ?></strong><em><?= (int) $today['people'] ?> different members</em></div></div>
  <a class="stat-card" href="<?= e($base . '?result=failed') ?>"><span class="stat-icon c2"><?= icon('alert', 'icon') ?></span><div><small>Failed today</small><strong><?= (int) $today['failed'] ?></strong><em>Wrong password, rate limits, suspended</em></div></a>
</div>
<div class="card">
  <form class="toolbar" method="get" action="<?= e($base) ?>">
    <label class="search-input"><?= icon('search', 'icon icon-sm') ?><span class="sr-only">Search</span><input type="search" name="q" value="<?= e($q) ?>" placeholder="Email or IP…"></label>
    <label class="sr-only" for="l-m">Method</label><select id="l-m" name="method"><option value="">Any method</option><?php foreach (['google' => 'Google sign-in', 'email' => 'Email sign-in', 'signup_google' => 'Google sign-up', 'signup_email' => 'Email sign-up', 'reset' => 'Password reset'] as $k => $l): ?><option value="<?= $k ?>"<?= $method === $k ? ' selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
    <label class="sr-only" for="l-r">Result</label><select id="l-r" name="result"><option value="">Any result</option><option value="ok"<?= $result === 'ok' ? ' selected' : '' ?>>Successful</option><option value="failed"<?= $result === 'failed' ? ' selected' : '' ?>>Failed</option></select>
    <button class="btn btn-secondary btn-sm">Apply</button><?php if ($q !== '' || $method !== '' || $result !== ''): ?><a class="link small" href="<?= e($base) ?>">Reset</a><?php endif; ?>
  </form>
  <?php if ($rows): ?>
  <div class="table-wrap"><table class="table table-resp">
    <thead><tr><th>When</th><th>Member</th><th>Method</th><th>Result</th><th>IP</th><th>Device</th></tr></thead>
    <tbody><?php foreach ($rows as $l): ?>
      <tr><td data-label="When" class="nowrap"><?= e(fmt_date($l['created_at'], 'M j, Y g:i:s A')) ?></td>
      <td data-label="Member"><?php if ($l['user_id']): ?><a class="strong" href="<?= e(admin_url('members/' . $l['user_id'])) ?>"><?= e($l['name'] ?? $l['email']) ?></a><?php else: ?><span class="muted">No account</span><?php endif; ?><small class="block muted"><?= e($l['email'] ?? '') ?></small></td>
      <td data-label="Method"><span class="tag"><?= e(ucfirst(str_replace('_', ' ', $l['method']))) ?></span></td>
      <td data-label="Result"><?= $l['success'] ? '<span class="badge st-published">OK</span>' : '<span class="badge st-warn">' . e(str_replace('_', ' ', $l['reason'] ?: 'failed')) . '</span>' ?></td>
      <td data-label="IP"><?= e($l['ip'] ?? '') ?></td><td data-label="Device" class="muted small"><?= e(mb_strimwidth((string) $l['user_agent'], 0, 70, '…')) ?></td></tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?= App\Core\View::partial('admin/partials/pagination', ['pager' => $pager]) ?>
  <?php else: ?><div class="empty"><?= icon('activity', 'icon') ?><h2>No sign-ins</h2><p>Nothing matches these filters yet.</p></div><?php endif; ?>
</div>
