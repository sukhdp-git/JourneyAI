<?php /** @var array $rows @var string $status @var array $counts @var array $statuses */ $base = admin_url('wall'); ?>
<div class="page-head"><div><h1>Wall of L's</h1><p class="muted">Members submit anonymised losses from their terminal with consent. Approved posts appear on <a href="<?= e(url('/wall')) ?>" target="_blank" rel="noopener">/wall</a> with their nickname only — never their name or email.</p></div></div>
<div class="card">
  <nav class="status-tabs" aria-label="Filter by status">
    <?php foreach ($statuses as $k => $l): ?><a href="<?= e($base . '?status=' . $k) ?>"<?= $status === $k ? ' class="is-active"' : '' ?>><?= e($l) ?> <b><?= (int) ($counts[$k] ?? 0) ?></b></a><?php endforeach; ?>
  </nav>
  <?php if (!$rows): ?><p class="muted" style="padding:16px">Nothing here.</p><?php else: ?>
  <div class="table-wrap"><table class="table table-resp">
    <thead><tr><th>Loss</th><th>Story &amp; lesson</th><th>From</th><th class="t-right">Reactions</th><th>Actions</th></tr></thead>
    <tbody><?php foreach ($rows as $r): ?>
      <tr>
        <td data-label="Loss"><strong><?= e($r['loss_text']) ?></strong><small class="block muted"><?= e(\App\Trading\Domain::label($r['mistake'])) ?><?= $r['instrument'] ? ' · ' . e($r['instrument']) : '' ?></small></td>
        <td data-label="Story"><?= e($r['story']) ?><small class="block"><b>Lesson:</b> <?= e($r['lesson']) ?></small></td>
        <td data-label="From"><?= e($r['handle'] ?: 'Anonymous trader') ?><small class="block muted"><?= e((string) $r['email']) ?> · <?= e(fmt_date($r['created_at'], 'M j, Y')) ?></small></td>
        <td data-label="Reactions" class="t-right">💀 <?= (int) $r['react_skull'] ?> · 🫠 <?= (int) $r['react_melt'] ?> · 🤝 <?= (int) $r['react_hug'] ?></td>
        <td data-label="Actions"><form method="post" action="<?= e($base . '/' . (int) $r['id']) ?>" class="inline-actions"><?= csrf_field() ?>
          <?php if ($r['status'] !== 'approved'): ?><button class="btn btn-primary btn-sm" name="action" value="approve">Publish</button><?php endif; ?>
          <?php if ($r['status'] !== 'rejected'): ?><button class="btn btn-secondary btn-sm" name="action" value="reject"><?= $r['status'] === 'approved' ? 'Unpublish' : 'Reject' ?></button><?php endif; ?>
          <button class="btn btn-ghost btn-sm" name="action" value="delete" onclick="return confirm('Delete this post permanently?')">Delete</button></form></td>
      </tr>
    <?php endforeach; ?></tbody></table></div>
  <?php endif; ?>
</div>
