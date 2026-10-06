<?php
use App\Controllers\Admin\LeadController;
/** @var array $rows @var array $f @var array $counts */
$base = admin_url('leads');
$qsExport = http_build_query(array_filter($f, fn ($v) => $v !== ''));
$sortLink = function (string $col, string $label) use ($sort, $dir) {
    $next = $sort === $col && $dir === 'DESC' ? 'asc' : 'desc';
    $q = array_merge($_GET, ['sort' => $col, 'dir' => $next]);
    unset($q['page']);
    return '<a class="th-sort' . ($sort === $col ? ' is-sorted' : '') . '" href="?' . e(http_build_query($q)) . '">' . e($label) . ($sort === $col ? ($dir === 'DESC' ? ' ↓' : ' ↑') : '') . '</a>';
};
?>
<div class="page-head">
  <div><h1>Consultation leads</h1><p class="muted">Requests from the <a href="<?= e(url('/book-consultation')) ?>" target="_blank" rel="noopener">/book-consultation</a> form. Every submission is stored even if the email notification fails.</p></div>
  <div class="page-actions"><a class="btn btn-secondary" href="<?= e(admin_url('leads/export') . ($qsExport ? '?' . $qsExport : '')) ?>"><?= icon('download', 'icon icon-sm') ?> Export CSV</a></div>
</div>
<nav class="status-tabs" aria-label="Filter by status">
  <a href="<?= e($base) ?>"<?= $f['status'] === '' ? ' class="is-active" aria-current="page"' : '' ?>>All <b><?= array_sum($counts) ?></b></a>
  <?php foreach (LeadController::STATUSES as $k => $l): ?><a href="<?= e($base . '?status=' . $k) ?>"<?= $f['status'] === $k ? ' class="is-active" aria-current="page"' : '' ?>><?= e($l) ?> <b><?= (int) ($counts[$k] ?? 0) ?></b></a><?php endforeach; ?>
</nav>
<div class="card">
  <form class="toolbar" method="get" action="<?= e($base) ?>">
    <?php if ($f['status'] !== ''): ?><input type="hidden" name="status" value="<?= e($f['status']) ?>"><?php endif; ?>
    <label class="search-input"><?= icon('search', 'icon icon-sm') ?><span class="sr-only">Search</span><input type="search" name="q" value="<?= e($f['q']) ?>" placeholder="Name, email, phone, company…"></label>
    <label class="sel"><span class="sr-only">Assigned</span><select name="assigned" data-autosubmit><option value="">Anyone</option><option value="me"<?= $f['assigned'] === 'me' ? ' selected' : '' ?>>Assigned to me</option><option value="none"<?= $f['assigned'] === 'none' ? ' selected' : '' ?>>Unassigned</option><?php foreach ($admins as $a): ?><option value="<?= (int) $a['id'] ?>"<?= $f['assigned'] === (string) $a['id'] ? ' selected' : '' ?>><?= e($a['name']) ?></option><?php endforeach; ?></select></label>
    <label class="sel"><span class="sr-only">Service</span><select name="service" data-autosubmit><option value="">All services</option><?php foreach ($services as $s): ?><option value="<?= (int) $s['id'] ?>"<?= $f['service'] === (string) $s['id'] ? ' selected' : '' ?>><?= e($s['title']) ?></option><?php endforeach; ?></select></label>
    <label class="date-in"><span>From</span><input type="date" name="from" value="<?= e($f['from']) ?>"></label>
    <label class="date-in"><span>To</span><input type="date" name="to" value="<?= e($f['to']) ?>"></label>
    <button class="btn btn-secondary btn-sm" type="submit">Apply</button>
    <?php if (array_filter($f)): ?><a class="link small" href="<?= e($base) ?>">Reset</a><?php endif; ?>
  </form>
  <?php if ($rows): ?>
  <div class="table-wrap"><table class="table table-resp">
    <thead><tr><th><?= $sortLink('name', 'Name') ?></th><th>Phone</th><th>Service</th><th><?= $sortLink('status', 'Status') ?></th><th>Assigned</th><th><?= $sortLink('created_at', 'Received') ?></th><th class="t-right">Actions</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $l): ?>
      <tr class="<?= $l['status'] === 'new' ? 'is-unread' : '' ?>">
        <td data-label="Name"><a class="strong" href="<?= e(admin_url('leads/' . $l['id'])) ?>"><?= e($l['name']) ?></a><small class="block muted"><?= e($l['email']) ?></small></td>
        <td data-label="Phone"><?= $l['phone'] ? '<a href="tel:' . e(preg_replace('/[^0-9+]/', '', $l['phone'])) . '">' . e($l['phone']) . '</a>' : '—' ?></td>
        <td data-label="Service"><?= e($l['service_name'] ?: '—') ?></td>
        <td data-label="Status">
          <form method="post" action="<?= e(admin_url('leads/' . $l['id'] . '/status')) ?>" class="inline-status"><?= csrf_field() ?>
            <label class="sr-only" for="st-<?= (int) $l['id'] ?>">Status</label>
            <select id="st-<?= (int) $l['id'] ?>" name="status" class="badge-select st-<?= e($l['status']) ?>" data-ajax-status><?php foreach (LeadController::STATUSES as $k => $lab): ?><option value="<?= e($k) ?>"<?= $l['status'] === $k ? ' selected' : '' ?>><?= e($lab) ?></option><?php endforeach; ?></select>
          </form>
          <?php if ($l['email_status'] === 'failed'): ?><span class="badge st-warn" title="<?= e($l['email_error'] ?? '') ?>">Email failed</span><?php endif; ?>
        </td>
        <td data-label="Assigned"><?= e($l['assignee'] ?: '—') ?></td>
        <td data-label="Received" class="nowrap"><?= e(fmt_date($l['created_at'], 'M j, Y g:i A')) ?></td>
        <td class="t-right actions-cell">
          <a class="icon-btn" href="<?= e(admin_url('leads/' . $l['id'])) ?>" aria-label="Open lead" title="Open"><?= icon('eye', 'icon icon-sm') ?></a>
          <a class="icon-btn" href="mailto:<?= e($l['email']) ?>" aria-label="Email" title="Email"><?= icon('mail', 'icon icon-sm') ?></a>
          <button type="button" class="icon-btn danger" aria-label="Delete" title="Delete" data-confirm="Delete the lead from <?= e($l['name']) ?>? Notes and history are deleted too." data-confirm-action="<?= e(admin_url('leads/' . $l['id'] . '/delete')) ?>"><?= icon('trash', 'icon icon-sm') ?></button>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?= App\Core\View::partial('admin/partials/pagination', ['pager' => $pager]) ?>
  <?php else: ?>
  <div class="empty"><?= icon('inbox', 'icon') ?><h2>No leads found</h2><p><?= array_filter($f) ? 'No leads match these filters.' : 'New requests from the booking form will appear here.' ?></p></div>
  <?php endif; ?>
</div>
