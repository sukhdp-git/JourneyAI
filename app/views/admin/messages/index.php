<?php use App\Controllers\Admin\MessageController; $base = admin_url('messages'); ?>
<div class="page-head"><div><h1>Contact messages</h1><p class="muted">Messages from the <a href="<?= e(url('/contact')) ?>" target="_blank" rel="noopener">/contact</a> form.</p></div></div>
<nav class="status-tabs" aria-label="Filter by status">
  <a href="<?= e($base) ?>"<?= $status === '' ? ' class="is-active"' : '' ?>>All <b><?= array_sum($counts) ?></b></a>
  <?php foreach (MessageController::STATUSES as $k => $l): ?><a href="<?= e($base . '?status=' . $k) ?>"<?= $status === $k ? ' class="is-active"' : '' ?>><?= e($l) ?> <b><?= (int) ($counts[$k] ?? 0) ?></b></a><?php endforeach; ?>
</nav>
<div class="card">
  <form class="toolbar" method="get" action="<?= e($base) ?>"><?php if ($status): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?><label class="search-input"><?= icon('search', 'icon icon-sm') ?><span class="sr-only">Search</span><input type="search" name="q" value="<?= e($q) ?>" placeholder="Search messages…"></label><button class="btn btn-secondary btn-sm">Search</button><?php if ($q !== ''): ?><a class="link small" href="<?= e($base) ?>">Reset</a><?php endif; ?></form>
  <?php if ($rows): ?>
  <div class="table-wrap"><table class="table table-resp">
    <thead><tr><th>From</th><th>Subject</th><th>Status</th><th>Received</th><th class="t-right">Actions</th></tr></thead>
    <tbody><?php foreach ($rows as $m): ?>
      <tr class="<?= $m['status'] === 'new' ? 'is-unread' : '' ?>">
        <td data-label="From"><a class="strong" href="<?= e(admin_url('messages/' . $m['id'])) ?>"><?= e($m['name']) ?></a><small class="block muted"><?= e($m['email']) ?></small></td>
        <td data-label="Subject"><?= e($m['subject'] ?: excerpt($m['message'], 60)) ?></td>
        <td data-label="Status"><span class="badge st-<?= e($m['status']) ?>"><?= e(MessageController::STATUSES[$m['status']]) ?></span><?php if ($m['email_status'] === 'failed'): ?> <span class="badge st-warn">Email failed</span><?php endif; ?></td>
        <td data-label="Received" class="nowrap"><?= e(fmt_date($m['created_at'], 'M j, Y g:i A')) ?></td>
        <td class="t-right actions-cell"><a class="icon-btn" href="<?= e(admin_url('messages/' . $m['id'])) ?>" aria-label="Open" title="Open"><?= icon('eye', 'icon icon-sm') ?></a><button type="button" class="icon-btn danger" aria-label="Delete" title="Delete" data-confirm="Delete this message?" data-confirm-action="<?= e(admin_url('messages/' . $m['id'] . '/delete')) ?>"><?= icon('trash', 'icon icon-sm') ?></button></td>
      </tr><?php endforeach; ?></tbody>
  </table></div>
  <?= App\Core\View::partial('admin/partials/pagination', ['pager' => $pager]) ?>
  <?php else: ?><div class="empty"><?= icon('message', 'icon') ?><h2>No messages</h2><p>Messages from the contact form will appear here.</p></div><?php endif; ?>
</div>
