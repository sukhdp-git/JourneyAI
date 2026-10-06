<?php
/** @var array $m @var bool $canLeads */
use App\Controllers\Admin\LeadController;

$hour = (int) fmt_date(gmdate('Y-m-d H:i:s'), 'G');
$greet = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
$todo = [];
if (can('email.smtp') && !$m['smtp_enabled']) $todo[] = ['SMTP email is not enabled — form submissions are saved but no notifications are sent.', 'email/smtp', 'Configure SMTP'];
if (can('settings.general') && setting('contact_email') === '') $todo[] = ['No contact email is set.', 'contact-details', 'Add contact details'];
if (can('testimonials') && $m['demo_testimonials'] > 0) $todo[] = [$m['demo_testimonials'] . ' demo testimonial(s) are published. Replace them with genuine reviews or unpublish them.', 'testimonials', 'Review testimonials'];
if (can('settings.general') && setting('logo_desktop') === '') $todo[] = ['No logo uploaded — the text logo is being used.', 'settings', 'Upload a logo'];
?>
<div class="page-head">
  <div>
    <h1><?= e($greet) ?>, <?= e(explode(' ', $me['name'])[0]) ?></h1>
    <p class="muted">Here is what is happening on <?= e(setting('site_name', 'your website')) ?>.</p>
  </div>
  <div class="page-actions">
    <?php if (can('blog')): ?><a class="btn btn-secondary" href="<?= e(admin_url('blog/new')) ?>"><?= icon('plus', 'icon icon-sm') ?> New post</a><?php endif; ?>
    <?php if ($canLeads): ?><a class="btn btn-primary" href="<?= e(admin_url('leads')) ?>"><?= icon('inbox', 'icon icon-sm') ?> View leads</a><?php endif; ?>
  </div>
</div>

<?php if ($todo): ?>
<div class="card checklist">
  <h2 class="card-title"><?= icon('flag', 'icon icon-sm') ?> Finish setting up</h2>
  <ul><?php foreach ($todo as [$t, $href, $label]): ?><li><span><?= e($t) ?></span><a class="btn btn-secondary btn-sm" href="<?= e(admin_url($href)) ?>"><?= e($label) ?></a></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<div class="stat-grid">
  <?php if ($canLeads): ?>
  <a class="stat-card" href="<?= e(admin_url('leads')) ?>"><span class="stat-icon c1"><?= icon('inbox', 'icon') ?></span><div><small>Total leads</small><strong><?= number_format($m['leads_total']) ?></strong><em><?= (int) $m['leads_week'] ?> in the last 7 days</em></div></a>
  <a class="stat-card" href="<?= e(admin_url('leads?status=new')) ?>"><span class="stat-icon c2"><?= icon('bell', 'icon') ?></span><div><small>New leads</small><strong><?= number_format($m['leads_new']) ?></strong><em>Awaiting first contact</em></div></a>
  <a class="stat-card" href="<?= e(admin_url('leads?status=follow_up')) ?>"><span class="stat-icon c3"><?= icon('clock', 'icon') ?></span><div><small>Follow-ups</small><strong><?= number_format($m['leads_follow']) ?></strong><em>Need another touchpoint</em></div></a>
  <a class="stat-card" href="<?= e(admin_url('leads?status=converted')) ?>"><span class="stat-icon c4"><?= icon('check-circle', 'icon') ?></span><div><small>Converted</small><strong><?= number_format($m['leads_converted']) ?></strong><em><?= $m['leads_total'] ? round($m['leads_converted'] / $m['leads_total'] * 100) : 0 ?>% of all leads</em></div></a>
  <?php endif; ?>
  <?php if (isset($m['messages_total'])): ?>
  <a class="stat-card" href="<?= e(admin_url('messages')) ?>"><span class="stat-icon c5"><?= icon('message', 'icon') ?></span><div><small>Contact messages</small><strong><?= number_format($m['messages_total']) ?></strong><em><?= (int) $m['messages_new'] ?> unread</em></div></a>
  <?php endif; ?>
  <a class="stat-card" href="<?= e(admin_url('services')) ?>"><span class="stat-icon c6"><?= icon('layers', 'icon') ?></span><div><small>Published services</small><strong><?= $m['services'] ?></strong><em><?= $m['pages'] ?> published pages</em></div></a>
  <a class="stat-card" href="<?= e(admin_url('blog')) ?>"><span class="stat-icon c7"><?= icon('book', 'icon') ?></span><div><small>Blog posts</small><strong><?= $m['posts'] ?></strong><em><?= $m['drafts'] ?> drafts / scheduled</em></div></a>
  <a class="stat-card" href="<?= e(admin_url('media')) ?>"><span class="stat-icon c8"><?= icon('image', 'icon') ?></span><div><small>Media files</small><strong><?= $m['media'] ?></strong><em>In the library</em></div></a>
</div>

<div class="dash-grid">
  <?php if ($canLeads):
      $max = max(1, max(array_column($m['series'], 'count')));
      $w = 720; $h = 220; $pad = 28; $bw = ($w - $pad) / 30; $ticks = array_unique([0, (int) ceil($max / 2), $max]); ?>
  <section class="card span-2">
    <div class="card-head"><h2 class="card-title">Leads — last 30 days</h2><span class="muted small"><?= array_sum(array_column($m['series'], 'count')) ?> total</span></div>
    <figure class="chart" aria-describedby="chart-desc">
      <svg viewBox="0 0 <?= $w ?> <?= $h + 24 ?>" role="img" aria-label="Bar chart of leads per day for the last 30 days">
        <?php foreach ($ticks as $t): $y = $h - ($t / $max) * ($h - 16); ?>
        <line x1="<?= $pad ?>" x2="<?= $w ?>" y1="<?= $y ?>" y2="<?= $y ?>" class="grid"/><text x="<?= $pad - 8 ?>" y="<?= $y + 4 ?>" class="axis" text-anchor="end"><?= $t ?></text>
        <?php endforeach; ?>
        <?php foreach ($m['series'] as $i => $pt): $bh = $pt['count'] ? max(3, ($pt['count'] / $max) * ($h - 16)) : 0; $x = $pad + $i * $bw + 2; ?>
        <g class="bar-g" tabindex="0"><rect class="hit" x="<?= $x - 2 ?>" y="0" width="<?= $bw ?>" height="<?= $h ?>"/><?php if ($bh): ?><path class="bar" d="M<?= round($x, 1) ?> <?= $h ?>V<?= round($h - $bh + 4, 1) ?>q0 -4 4 -4h<?= round($bw - 12, 1) ?>q4 0 4 4V<?= $h ?>z"/><?php endif; ?><title><?= e(date('M j', strtotime($pt['date']))) ?>: <?= $pt['count'] ?> lead<?= $pt['count'] === 1 ? '' : 's' ?></title></g>
        <?php if ($i % 5 === 0 || $i === 29): ?><text x="<?= $x + ($bw - 4) / 2 ?>" y="<?= $h + 18 ?>" class="axis" text-anchor="middle"><?= e(date('M j', strtotime($pt['date']))) ?></text><?php endif; ?>
        <?php endforeach; ?>
        <line x1="<?= $pad ?>" x2="<?= $w ?>" y1="<?= $h ?>" y2="<?= $h ?>" class="base"/>
      </svg>
      <figcaption id="chart-desc" class="sr-only">Daily lead counts from <?= e($m['series'][0]['date']) ?> to <?= e($m['series'][29]['date']) ?>.</figcaption>
    </figure>
    <details class="chart-table"><summary>View as table</summary>
      <table class="table compact"><thead><tr><th>Date</th><th>Leads</th></tr></thead><tbody><?php foreach (array_reverse($m['series']) as $pt): ?><tr><td><?= e($pt['date']) ?></td><td><?= $pt['count'] ?></td></tr><?php endforeach; ?></tbody></table>
    </details>
  </section>
  <section class="card">
    <h2 class="card-title">Lead pipeline</h2>
    <ul class="pipeline">
      <?php foreach (LeadController::STATUSES as $k => $label): $c = (int) ($m['leads_status'][$k] ?? 0); ?>
      <li><a href="<?= e(admin_url('leads?status=' . $k)) ?>"><span class="badge st-<?= e($k) ?>"><?= e($label) ?></span><span class="pipe-bar"><span style="width:<?= $m['leads_total'] ? round($c / $m['leads_total'] * 100) : 0 ?>%"></span></span><strong><?= $c ?></strong></a></li>
      <?php endforeach; ?>
    </ul>
    <?php if ($m['email_failed']): ?><p class="alert alert-warning small"><?= icon('alert', 'icon icon-sm') ?> <?= $m['email_failed'] ?> lead notification email(s) failed in the last 30 days. Leads were still saved.</p><?php endif; ?>
  </section>
  <section class="card span-2">
    <div class="card-head"><h2 class="card-title">Recent leads</h2><a class="link" href="<?= e(admin_url('leads')) ?>">View all</a></div>
    <?php if ($m['recent_leads']): ?>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Name</th><th class="hide-sm">Service</th><th>Status</th><th class="hide-sm">Received</th></tr></thead>
      <tbody><?php foreach ($m['recent_leads'] as $l): ?><tr><td><a class="strong" href="<?= e(admin_url('leads/' . $l['id'])) ?>"><?= e($l['name']) ?></a><small class="block muted"><?= e($l['email']) ?></small></td><td class="hide-sm"><?= e($l['service_name'] ?: '—') ?></td><td><span class="badge st-<?= e($l['status']) ?>"><?= e(LeadController::STATUSES[$l['status']]) ?></span></td><td class="hide-sm nowrap"><?= e(fmt_date($l['created_at'], 'M j, g:i A')) ?></td></tr><?php endforeach; ?></tbody>
    </table></div>
    <?php else: ?><div class="empty"><?= icon('inbox', 'icon') ?><p>No leads yet. Submissions from <a href="<?= e(url('/book-consultation')) ?>" target="_blank" rel="noopener">/book-consultation</a> will appear here.</p></div><?php endif; ?>
  </section>
  <?php endif; ?>
  <section class="card">
    <h2 class="card-title">Quick actions</h2>
    <div class="quick">
      <?php foreach ([['pages/new', 'Add page', 'file', 'pages'], ['services/new', 'Add service', 'layers', 'services'], ['blog/new', 'Add blog post', 'book', 'blog'], ['leads', 'View leads', 'inbox', 'leads'], ['media', 'Upload media', 'upload', 'media'], ['settings', 'Website settings', 'settings', 'settings.general']] as [$h, $l, $i, $p]): if (!can($p)) continue; ?>
      <a href="<?= e(admin_url($h)) ?>"><?= icon($i, 'icon') ?><span><?= e($l) ?></span></a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php if (!empty($m['recent_messages'])): ?>
  <section class="card">
    <div class="card-head"><h2 class="card-title">Latest messages</h2><a class="link" href="<?= e(admin_url('messages')) ?>">View all</a></div>
    <ul class="mini-list"><?php foreach ($m['recent_messages'] as $msg): ?><li><a href="<?= e(admin_url('messages/' . $msg['id'])) ?>"><strong><?= e($msg['name']) ?></strong><?php if ($msg['status'] === 'new'): ?><span class="badge st-new">New</span><?php endif; ?><small><?= e($msg['subject'] ?: 'No subject') ?> · <?= e(fmt_date($msg['created_at'], 'M j')) ?></small></a></li><?php endforeach; ?></ul>
  </section>
  <?php endif; ?>
  <?php if ($m['activity']): ?>
  <section class="card">
    <div class="card-head"><h2 class="card-title">Recent activity</h2><a class="link" href="<?= e(admin_url('activity-logs')) ?>">View log</a></div>
    <ul class="mini-list"><?php foreach ($m['activity'] as $a): ?><li><div><strong><?= e($a['name'] ?: 'System') ?></strong><small><?= e($a['details'] ?: $a['action'] . ' ' . $a['module']) ?> · <?= e(fmt_date($a['created_at'], 'M j, g:i A')) ?></small></div></li><?php endforeach; ?></ul>
  </section>
  <?php endif; ?>
</div>
