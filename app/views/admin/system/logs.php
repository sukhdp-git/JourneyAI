<div class="page-head"><div><h1>Activity logs</h1><p class="muted">Sign-ins, content changes, settings and user management. Secrets are never recorded.</p></div></div>
<div class="card">
  <form class="toolbar" method="get" action="<?= e(admin_url('activity-logs')) ?>">
    <label class="search-input"><?= icon('search', 'icon icon-sm') ?><span class="sr-only">Search</span><input type="search" name="q" value="<?= e($q) ?>" placeholder="Search details, action or IP…"></label>
    <label class="sel"><span class="sr-only">Module</span><select name="module" data-autosubmit><option value="">All modules</option><?php foreach ($modules as $mo): ?><option value="<?= e($mo) ?>"<?= $module === $mo ? ' selected' : '' ?>><?= e($mo) ?></option><?php endforeach; ?></select></label>
    <label class="sel"><span class="sr-only">Admin</span><select name="admin" data-autosubmit><option value="">All admins</option><?php foreach ($admins as $a): ?><option value="<?= (int) $a['id'] ?>"<?= $admin === (string) $a['id'] ? ' selected' : '' ?>><?= e($a['name']) ?></option><?php endforeach; ?></select></label>
    <button class="btn btn-secondary btn-sm">Apply</button>
  </form>
  <?php if ($rows): ?>
  <div class="table-wrap"><table class="table table-resp"><thead><tr><th>When</th><th>Admin</th><th>Action</th><th>Module</th><th>Details</th><th>IP</th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?><tr>
    <td data-label="When" class="nowrap"><?= e(fmt_date($r['created_at'], 'M j, Y g:i:s A')) ?></td>
    <td data-label="Admin"><?= e($r['name'] ?: 'System') ?></td>
    <td data-label="Action"><span class="tag act-<?= e($r['action']) ?>"><?= e($r['action']) ?></span></td>
    <td data-label="Module"><?= e($r['module']) ?><?= $r['record_id'] ? ' <span class="muted">#' . (int) $r['record_id'] . '</span>' : '' ?></td>
    <td data-label="Details"><?= e($r['details']) ?></td>
    <td data-label="IP"><code><?= e($r['ip']) ?></code></td>
  </tr><?php endforeach; ?>
  </tbody></table></div>
  <?= App\Core\View::partial('admin/partials/pagination', ['pager' => $pager]) ?>
  <?php else: ?><div class="empty"><?= icon('activity', 'icon') ?><h2>No activity found</h2></div><?php endif; ?>
</div>
<div class="card">
  <h2 class="card-title">Recent sign-in attempts</h2>
  <div class="table-wrap"><table class="table table-resp compact"><thead><tr><th>When</th><th>Email</th><th>IP</th><th>Result</th></tr></thead><tbody>
  <?php foreach ($logins as $l): ?><tr><td data-label="When" class="nowrap"><?= e(fmt_date($l['created_at'], 'M j, g:i:s A')) ?></td><td data-label="Email"><?= e($l['email']) ?></td><td data-label="IP"><code><?= e($l['ip']) ?></code></td><td data-label="Result"><span class="badge <?= $l['success'] ? 'st-published' : 'st-warn' ?>"><?= $l['success'] ? 'Success' : e(ucfirst(str_replace('_', ' ', (string) $l['reason']))) ?></span></td></tr><?php endforeach; ?>
  </tbody></table></div>
</div>
