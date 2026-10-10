<?php
/** @var array $rows @var array $counts @var array $due @var array $totals @var array $statuses */
$base = admin_url('affiliates');
$pctTxt = rtrim(rtrim(number_format($defaultPct, 2), '0'), '.');
$sum = [];
foreach ($totals as $t) {
    $sum[$t['currency']][$t['status']] = (float) $t['s'];
}
$badge = ['pending' => 'st-draft', 'approved' => 'st-published', 'suspended' => 'st-warn', 'rejected' => 'st-warn'];
?>
<div class="page-head"><div><h1>Affiliates</h1><p class="muted">Influencers apply at <a href="<?= e(url('/affiliates')) ?>" target="_blank" rel="noopener">/affiliates</a>. Approve them to issue a personal code and link; customers who enter the code at checkout earn the affiliate a commission on every plan payment.</p></div></div>
<div class="stat-grid">
  <a class="stat-card" href="<?= e($base . '?status=pending') ?>"><span class="stat-icon c1"><?= icon('users', 'icon') ?></span><div><small>New applications</small><strong><?= (int) ($counts['pending'] ?? 0) ?></strong><em>waiting for review</em></div></a>
  <a class="stat-card" href="<?= e($base . '?status=approved') ?>"><span class="stat-icon c2"><?= icon('link', 'icon') ?></span><div><small>Active affiliates</small><strong><?= (int) ($counts['approved'] ?? 0) ?></strong><em>default commission <?= e($pctTxt) ?>%</em></div></a>
  <?php foreach ($sum ?: ['' => []] as $cur => $s): ?>
  <div class="stat-card"><span class="stat-icon c5"><?= icon('zap', 'icon') ?></span><div><small>Commission due<?= $cur ? ' (' . e($cur) . ')' : '' ?></small><strong><?= $cur ? e(money(($s['pending'] ?? 0) + ($s['approved'] ?? 0), $cur)) : '—' ?></strong><em><?= $cur ? 'Paid out ' . e(money($s['paid'] ?? 0, $cur)) : 'No commissions yet' ?></em></div></div>
  <?php endforeach; ?>
</div>
<div class="detail-grid">
  <div class="card">
    <nav class="status-tabs" aria-label="Filter by status">
      <a href="<?= e($base) ?>"<?= $status === '' ? ' class="is-active"' : '' ?>>All <b><?= array_sum($counts) ?></b></a>
      <?php foreach ($statuses as $k => $l): ?><a href="<?= e($base . '?status=' . $k) ?>"<?= $status === $k ? ' class="is-active"' : '' ?>><?= e($l) ?> <b><?= (int) ($counts[$k] ?? 0) ?></b></a><?php endforeach; ?>
    </nav>
    <form class="toolbar" method="get" action="<?= e($base) ?>">
      <?php if ($status): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
      <label class="search-input"><?= icon('search', 'icon icon-sm') ?><span class="sr-only">Search</span><input type="search" name="q" value="<?= e($q) ?>" placeholder="Name, code or email…"></label>
      <button class="btn btn-secondary btn-sm">Search</button>
    </form>
    <?php if ($rows): ?>
    <div class="table-wrap"><table class="table table-resp">
      <thead><tr><th>Affiliate</th><th>Code</th><th>Channel</th><th class="t-right">Clicks</th><th class="t-right">Referred</th><th class="t-right">Paying</th><th class="t-right">Due</th><th>Status</th></tr></thead>
      <tbody><?php foreach ($rows as $r): ?>
        <tr>
          <td data-label="Affiliate"><a class="strong" href="<?= e(admin_url('affiliates/' . $r['id'])) ?>"><?= e($r['full_name']) ?></a><small class="block muted"><?= e($r['email']) ?> · applied <?= e(fmt_date($r['created_at'], 'M j, Y')) ?></small></td>
          <td data-label="Code"><?= $r['code'] ? '<code>' . e($r['code']) . '</code> <small class="muted">' . e(rtrim(rtrim((string) $r['commission_pct'], '0'), '.')) . '%</small>' : '<span class="muted">—</span>' ?></td>
          <td data-label="Channel"><?= e((string) $r['platform']) ?><?php if ($r['channel_url']): ?><small class="block muted"><a href="<?= e($r['channel_url']) ?>" target="_blank" rel="noopener nofollow"><?= e(mb_strimwidth(preg_replace('#^https?://(www\.)?#', '', $r['channel_url']), 0, 32, '…')) ?></a></small><?php endif; ?></td>
          <td data-label="Clicks" class="t-right"><?= number_format((int) $r['clicks']) ?></td>
          <td data-label="Referred" class="t-right"><?= (int) $r['referred'] ?></td>
          <td data-label="Paying" class="t-right"><?= (int) $r['paying'] ?></td>
          <td data-label="Due" class="t-right nowrap"><?= e(implode(' · ', $due[$r['id']] ?? ['—'])) ?></td>
          <td data-label="Status"><span class="badge <?= $badge[$r['status']] ?>"><?= e(ucfirst($r['status'])) ?></span></td>
        </tr>
      <?php endforeach; ?></tbody>
    </table></div>
    <?= App\Core\View::partial('admin/partials/pagination', ['pager' => $pager]) ?>
    <?php else: ?><div class="empty"><?= icon('link', 'icon') ?><h2>No affiliates<?= $status || $q ? ' match' : ' yet' ?></h2><p>Applications from <a href="<?= e(url('/affiliates')) ?>" target="_blank" rel="noopener">/affiliates</a> appear here.</p></div><?php endif; ?>
  </div>
  <aside class="stack-lg">
    <form method="post" action="<?= e(admin_url('affiliates/settings')) ?>" class="card">
      <?= csrf_field() ?>
      <h2 class="card-title">Programme settings</h2>
      <div class="f"><label class="check"><input type="checkbox" name="affiliates_enabled" value="1"<?= $enabled ? ' checked' : '' ?>> Accept new applications</label></div>
      <div class="f"><label for="a-pct">Default commission (%)</label><input id="a-pct" type="number" name="affiliate_commission_pct" min="0.01" max="90" step="0.01" value="<?= e($pctTxt) ?>"><small class="muted">Used for new approvals and shown on the public page. Each affiliate can have their own rate.</small></div>
      <button class="btn btn-primary btn-block">Save</button>
    </form>
    <div class="card"><h2 class="card-title">How commissions work</h2>
      <ul class="small muted" style="padding-left:18px;margin:0;display:grid;gap:6px">
        <li>A customer is linked to an affiliate the first time they enter a valid code at checkout (the /r/CODE link pre-fills it).</li>
        <li>Every paid plan payment from that customer — online or a manual grant with an amount — creates a <b>pending</b> commission.</li>
        <li>Refunds void unpaid commissions automatically.</li>
        <li>Approve, then mark as paid after you pay the affiliate outside the website.</li>
      </ul>
    </div>
  </aside>
</div>
