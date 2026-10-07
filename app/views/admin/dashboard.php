<?php
/** @var array $m @var bool $canMembers */
$hour = (int) fmt_date(gmdate('Y-m-d H:i:s'), 'G');
$greet = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
$todo = [];
if (can('integrations') && !$m['google']) $todo[] = ['Google sign-in is not configured — visitors can only sign up with email.', 'integrations', 'Add Google keys'];
if (can('integrations') && $m['gateway'] === 'none') $todo[] = ['No payment gateway is configured — members cannot buy plans online (you can still grant plans manually).', 'integrations', 'Set up payments'];
if (can('integrations') && !$m['ai']) $todo[] = ['AI Coach is not configured — members see “AI Coach requires server configuration.”', 'integrations', 'Add an AI key'];
if (can('email.smtp') && !$m['smtp_enabled']) $todo[] = ['SMTP email is not enabled — welcome, password reset and receipt emails are not sent.', 'email/smtp', 'Configure SMTP'];
if (can('testimonials') && $m['demo_testimonials'] > 0) $todo[] = [$m['demo_testimonials'] . ' demo testimonial(s) are published. Replace them with genuine reviews or unpublish them.', 'testimonials', 'Review testimonials'];
$u = $m['users'] ?? null;
?>
<div class="page-head">
  <div>
    <h1><?= e($greet) ?>, <?= e(explode(' ', $me['name'])[0]) ?></h1>
    <p class="muted">Here is what is happening on <?= e(setting('site_name', 'your website')) ?>. Times are UTC days.</p>
  </div>
  <div class="page-actions">
    <?php if ($canMembers): ?><a class="btn btn-secondary" href="<?= e(admin_url('member-logins')) ?>"><?= icon('activity', 'icon icon-sm') ?> Sign-in log</a><a class="btn btn-primary" href="<?= e(admin_url('members')) ?>"><?= icon('users', 'icon icon-sm') ?> All members</a><?php endif; ?>
  </div>
</div>

<?php if ($todo): ?>
<div class="card checklist">
  <h2 class="card-title"><?= icon('flag', 'icon icon-sm') ?> Finish setting up</h2>
  <ul><?php foreach ($todo as [$t, $href, $label]): ?><li><span><?= e($t) ?></span><a class="btn btn-secondary btn-sm" href="<?= e(admin_url($href)) ?>"><?= e($label) ?></a></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<div class="stat-grid">
  <?php if ($u): ?>
  <a class="stat-card" href="<?= e(admin_url('members')) ?>"><span class="stat-icon c1"><?= icon('users', 'icon') ?></span><div><small>Total members</small><strong><?= number_format((int) $u['total']) ?></strong><em><?= (int) $u['week'] ?> joined in the last 7 days</em></div></a>
  <a class="stat-card" href="<?= e(admin_url('members?method=google')) ?>"><span class="stat-icon c2"><?= icon('globe', 'icon') ?></span><div><small>Signed up with Google</small><strong><?= number_format((int) $u['google']) ?></strong><em><?= number_format((int) $u['email']) ?> with email</em></div></a>
  <a class="stat-card" href="<?= e(admin_url('members?active=today')) ?>"><span class="stat-icon c3"><?= icon('activity', 'icon') ?></span><div><small>Signed in today</small><strong><?= number_format((int) $u['today']) ?></strong><em><?= (int) $m['logins_today'] ?> sign-ins · <?= (int) $m['failed_today'] ?> failed</em></div></a>
  <a class="stat-card" href="<?= e(admin_url('members?plan=paid')) ?>"><span class="stat-icon c4"><?= icon('star', 'icon') ?></span><div><small>Paid subscribers</small><strong><?= number_format((int) $u['paid']) ?></strong><em><?= (int) $u['active30'] ?> active in 30 days</em></div></a>
  <div class="stat-card"><span class="stat-icon c6"><?= icon('bars', 'icon') ?></span><div><small>Trades journaled</small><strong><?= number_format($m['trades']) ?></strong><em><?= number_format($m['trades_week']) ?> this week (demo data excluded)</em></div></div>
  <div class="stat-card"><span class="stat-icon c7"><?= icon('message', 'icon') ?></span><div><small>AI Coach questions today</small><strong><?= number_format($m['ai_today']) ?></strong><em><?= $m['ai'] ? 'AI configured' : 'AI not configured' ?></em></div></div>
  <?php endif; ?>
  <?php if (isset($m['revenue'])): ?>
  <a class="stat-card" href="<?= e(admin_url('payments')) ?>"><span class="stat-icon c5"><?= icon('zap', 'icon') ?></span><div><small>Revenue · last 30 days</small><strong><?= $m['revenue'] ? e(implode(' + ', array_map(fn ($r) => money($r['last30'], $r['currency']), $m['revenue']))) : '—' ?></strong><em><?= $m['revenue'] ? e(implode(' + ', array_map(fn ($r) => money($r['total'], $r['currency']), $m['revenue']))) . ' all time' : 'No online payments yet' ?></em></div></a>
  <?php endif; ?>
  <?php if (isset($m['messages_new'])): ?>
  <a class="stat-card" href="<?= e(admin_url('messages')) ?>"><span class="stat-icon c8"><?= icon('mail', 'icon') ?></span><div><small>Unread messages</small><strong><?= (int) $m['messages_new'] ?></strong><em>From the contact form</em></div></a>
  <?php endif; ?>
</div>

<div class="dash-grid">
  <?php if ($u):
      $max = max(1, max(array_map(fn ($p) => $p['google'] + $p['email'], $m['series'])));
      $w = 720; $h = 220; $pad = 28; $bw = ($w - $pad) / 30; $ticks = array_unique([0, (int) ceil($max / 2), $max]); ?>
  <section class="card span-2">
    <div class="card-head"><h2 class="card-title">New members — last 30 days</h2>
      <span class="legend small"><span class="key k-google"></span> Google <span class="key k-email"></span> Email · <?= array_sum(array_map(fn ($p) => $p['google'] + $p['email'], $m['series'])) ?> total</span></div>
    <figure class="chart" aria-describedby="chart-desc">
      <svg viewBox="0 0 <?= $w ?> <?= $h + 24 ?>" role="img" aria-label="Stacked bar chart of member sign-ups per day by method for the last 30 days">
        <?php foreach ($ticks as $t): $y = $h - ($t / $max) * ($h - 16); ?>
        <line x1="<?= $pad ?>" x2="<?= $w ?>" y1="<?= $y ?>" y2="<?= $y ?>" class="grid"/><text x="<?= $pad - 8 ?>" y="<?= $y + 4 ?>" class="axis" text-anchor="end"><?= $t ?></text>
        <?php endforeach; ?>
        <?php foreach ($m['series'] as $i => $pt): $x = $pad + $i * $bw + 2; $gh = $pt['google'] / $max * ($h - 16); $eh = $pt['email'] / $max * ($h - 16); ?>
        <g class="bar-g" tabindex="0"><rect class="hit" x="<?= $x - 2 ?>" y="0" width="<?= $bw ?>" height="<?= $h ?>"/>
          <?php if ($gh): ?><rect class="bar-google" x="<?= round($x, 1) ?>" y="<?= round($h - $gh, 1) ?>" width="<?= round($bw - 4, 1) ?>" height="<?= round($gh, 1) ?>" rx="2"/><?php endif; ?>
          <?php if ($eh): ?><rect class="bar-email" x="<?= round($x, 1) ?>" y="<?= round($h - $gh - $eh, 1) ?>" width="<?= round($bw - 4, 1) ?>" height="<?= round(max(0, $eh - ($gh ? 2 : 0)), 1) ?>" rx="2"/><?php endif; ?>
          <title><?= e(date('M j', strtotime($pt['date']))) ?>: <?= $pt['google'] ?> Google, <?= $pt['email'] ?> email</title></g>
        <?php if ($i % 5 === 0 || $i === 29): ?><text x="<?= $x + ($bw - 4) / 2 ?>" y="<?= $h + 18 ?>" class="axis" text-anchor="middle"><?= e(date('M j', strtotime($pt['date']))) ?></text><?php endif; ?>
        <?php endforeach; ?>
        <line x1="<?= $pad ?>" x2="<?= $w ?>" y1="<?= $h ?>" y2="<?= $h ?>" class="base"/>
      </svg>
      <figcaption id="chart-desc" class="sr-only">Daily member sign-ups from <?= e($m['series'][0]['date']) ?> to <?= e($m['series'][29]['date']) ?>, split by Google and email.</figcaption>
    </figure>
    <details class="chart-table"><summary>View as table</summary>
      <table class="table compact"><thead><tr><th>Date</th><th>Google</th><th>Email</th></tr></thead><tbody><?php foreach (array_reverse($m['series']) as $pt): ?><tr><td><?= e($pt['date']) ?></td><td><?= $pt['google'] ?></td><td><?= $pt['email'] ?></td></tr><?php endforeach; ?></tbody></table>
    </details>
  </section>
  <section class="card">
    <div class="card-head"><h2 class="card-title">Latest sign-ins</h2><a class="link" href="<?= e(admin_url('member-logins')) ?>">View log</a></div>
    <?php if ($m['recent_logins']): ?>
    <ul class="mini-list"><?php foreach ($m['recent_logins'] as $l): ?><li><<?= $l['user_id'] ? 'a href="' . e(admin_url('members/' . $l['user_id'])) . '"' : 'div' ?>><strong><?= e($l['name'] ?: $l['email'] ?: 'Unknown') ?></strong><?= $l['success'] ? '' : '<span class="badge st-warn">Failed</span>' ?><small><?= e(ucfirst(str_replace('_', ' ', $l['method']))) ?> · <?= e(fmt_date($l['created_at'], 'M j, g:i A')) ?></small></<?= $l['user_id'] ? 'a' : 'div' ?>></li><?php endforeach; ?></ul>
    <?php else: ?><div class="empty"><p>No sign-ins yet.</p></div><?php endif; ?>
  </section>
  <section class="card span-2">
    <div class="card-head"><h2 class="card-title">Newest members</h2><a class="link" href="<?= e(admin_url('members')) ?>">View all</a></div>
    <?php if ($m['recent_users']): ?>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Member</th><th>Signed up with</th><th>Plan</th><th class="hide-sm">Joined</th><th class="hide-sm">Last sign-in</th></tr></thead>
      <tbody><?php foreach ($m['recent_users'] as $r): ?><tr><td><a class="strong" href="<?= e(admin_url('members/' . $r['id'])) ?>"><?= e($r['name']) ?></a><small class="block muted"><?= e($r['email']) ?></small></td><td><span class="tag"><?= e(ucfirst($r['signup_method'])) ?></span></td><td><?= $r['paid'] ? '<span class="badge st-published">Paid</span>' : '<span class="badge st-draft">Free</span>' ?></td><td class="hide-sm nowrap"><?= e(fmt_date($r['created_at'], 'M j, g:i A')) ?></td><td class="hide-sm nowrap"><?= e($r['last_login_at'] ? fmt_date($r['last_login_at'], 'M j, g:i A') : '—') ?></td></tr><?php endforeach; ?></tbody>
    </table></div>
    <?php else: ?><div class="empty"><?= icon('users', 'icon') ?><p>No members yet. People who sign up at <a href="<?= e(url('/signup')) ?>" target="_blank" rel="noopener">/signup</a> (email or Google) appear here.</p></div><?php endif; ?>
  </section>
  <?php endif; ?>
  <section class="card">
    <h2 class="card-title">Integrations</h2>
    <ul class="mini-list">
      <?php foreach ([['Google sign-in', $m['google']], ['Payments', $m['gateway'] !== 'none', $m['gateway'] !== 'none' ? ucfirst($m['gateway']) : null], ['AI Coach', $m['ai']], ['Market data (Runner Auditor)', $m['market'], $m['market'] ? null : 'Optional'], ['SMTP email', $m['smtp_enabled']]] as $row): ?>
      <li><div><strong><?= e($row[0]) ?></strong> <?= $row[1] ? '<span class="badge st-published">Connected</span>' : '<span class="badge st-draft">Not configured</span>' ?><?php if (!empty($row[2])): ?><small><?= e($row[2]) ?></small><?php endif; ?></div></li>
      <?php endforeach; ?>
    </ul>
    <?php if (can('integrations')): ?><a class="btn btn-secondary btn-sm" href="<?= e(admin_url('integrations')) ?>">Manage integrations</a><?php endif; ?>
  </section>
  <?php if ($m['activity']): ?>
  <section class="card">
    <div class="card-head"><h2 class="card-title">Admin activity</h2><a class="link" href="<?= e(admin_url('activity-logs')) ?>">View log</a></div>
    <ul class="mini-list"><?php foreach ($m['activity'] as $a): ?><li><div><strong><?= e($a['name'] ?: 'System') ?></strong><small><?= e($a['details'] ?: $a['action'] . ' ' . $a['module']) ?> · <?= e(fmt_date($a['created_at'], 'M j, g:i A')) ?></small></div></li><?php endforeach; ?></ul>
  </section>
  <?php endif; ?>
</div>
