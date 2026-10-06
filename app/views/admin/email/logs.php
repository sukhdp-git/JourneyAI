<div class="page-head"><div><h1>Email delivery log</h1><p class="muted">Every notification attempt with its result. Passwords are never logged.</p></div></div>
<nav class="status-tabs"><?php foreach (['' => 'All', 'sent' => 'Sent', 'failed' => 'Failed', 'disabled' => 'Skipped'] as $k => $l): ?><a href="<?= e(admin_url('email/logs') . ($k ? '?status=' . $k : '')) ?>"<?= $status === $k ? ' class="is-active"' : '' ?>><?= e($l) ?></a><?php endforeach; ?></nav>
<div class="card">
  <?php if ($rows): ?>
  <div class="table-wrap"><table class="table table-resp"><thead><tr><th>When</th><th>Recipient</th><th>Subject</th><th>Template</th><th>Result</th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?><tr>
      <td data-label="When" class="nowrap"><?= e(fmt_date($r['created_at'], 'M j, Y g:i A')) ?></td>
      <td data-label="Recipient"><?= e($r['recipient']) ?></td>
      <td data-label="Subject"><?= e($r['subject']) ?><?php if ($r['related_type'] === 'member'): ?> <a class="link small" href="<?= e(admin_url('users/' . $r['related_id'])) ?>">member #<?= (int) $r['related_id'] ?></a><?php elseif ($r['related_type'] === 'contact'): ?> <a class="link small" href="<?= e(admin_url('messages/' . $r['related_id'])) ?>">message #<?= (int) $r['related_id'] ?></a><?php endif; ?></td>
      <td data-label="Template"><code><?= e($r['template_key'] ?? '—') ?></code></td>
      <td data-label="Result"><span class="badge st-<?= $r['status'] === 'sent' ? 'published' : ($r['status'] === 'failed' ? 'warn' : 'draft') ?>"><?= e($r['status'] === 'disabled' ? 'Skipped' : ucfirst($r['status'])) ?></span><?php if ($r['error']): ?><small class="block err-text"><?= e($r['error']) ?></small><?php endif; ?></td>
    </tr><?php endforeach; ?>
  </tbody></table></div>
  <?= App\Core\View::partial('admin/partials/pagination', ['pager' => $pager]) ?>
  <?php else: ?><div class="empty"><?= icon('mail', 'icon') ?><h2>No emails yet</h2></div><?php endif; ?>
</div>
