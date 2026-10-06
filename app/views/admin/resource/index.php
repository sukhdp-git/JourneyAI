<?php
/** @var string $key @var array $d @var array $rows @var App\Core\Paginator $pager @var array $filters */
$canCreate = $d['can_create'] ?? true;
$toggle = $d['toggle'] ?? null;
$qs = function (array $over) {
    $q = array_merge($_GET, $over);
    unset($q['page']);
    return '?' . http_build_query(array_filter($q, fn ($v) => $v !== '' && $v !== null));
};
$newQuery = '';
foreach ($filters as $col => $f) {
    if ($f['value'] !== '') $newQuery .= ($newQuery ? '&' : '?') . rawurlencode($col) . '=' . rawurlencode($f['value']);
}
?>
<div class="page-head">
  <div><h1><?= e($d['label']) ?></h1><p class="muted"><?= number_format($pager->total) ?> item<?= $pager->total === 1 ? '' : 's' ?></p></div>
  <div class="page-actions">
    <?php if ($key === 'blog'): ?><a class="btn btn-secondary" href="<?= e(admin_url('blog/categories')) ?>">Categories</a><a class="btn btn-secondary" href="<?= e(admin_url('blog/tags')) ?>">Tags</a><?php endif; ?>
    <?php if ($canCreate): ?><a class="btn btn-primary" href="<?= e(admin_url("$key/new") . $newQuery) ?>"><?= icon('plus', 'icon icon-sm') ?> New <?= e(strtolower($d['singular'])) ?></a><?php endif; ?>
  </div>
</div>
<?php if (!empty($d['notice'])): ?><div class="alert alert-info"><?= icon('info', 'icon icon-sm') ?><p><?= e($d['notice']) ?></p></div><?php endif; ?>
<div class="card">
  <?php if (!empty($d['search']) || $filters || $sortable): ?>
  <form class="toolbar" method="get" action="<?= e(admin_url($key)) ?>">
    <?php if (!empty($d['search'])): ?><label class="search-input"><?= icon('search', 'icon icon-sm') ?><span class="sr-only">Search</span><input type="search" name="q" value="<?= e($q) ?>" placeholder="Search <?= e(strtolower($d['label'])) ?>…"></label><?php endif; ?>
    <?php foreach ($filters as $col => $f): ?>
    <label class="sel"><span class="sr-only"><?= e($f['label']) ?></span><select name="<?= e($col) ?>" data-autosubmit>
      <?php if (!$f['required']): ?><option value="">All <?= e(strtolower($f['label'])) ?></option><?php endif; ?>
      <?php foreach ($f['options'] as $k => $l): ?><option value="<?= e($k) ?>"<?= (string) $f['value'] === (string) $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
    </select></label>
    <?php endforeach; ?>
    <?php if ($sortable): ?>
    <label class="sel"><span class="sr-only">Sort by</span><select name="sort" data-autosubmit><option value="">Default order</option><?php foreach ($sortable as $k => $l): ?><option value="<?= e($k) ?>"<?= $sort === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
    <label class="sel"><span class="sr-only">Direction</span><select name="dir" data-autosubmit><option value="asc"<?= $dir === 'ASC' ? ' selected' : '' ?>>Ascending</option><option value="desc"<?= $dir === 'DESC' ? ' selected' : '' ?>>Descending</option></select></label>
    <?php endif; ?>
    <button class="btn btn-secondary btn-sm" type="submit">Apply</button>
    <?php if ($q !== '' || $sort !== '' || array_filter(array_column($filters, 'value')) && empty($d['default_filter'])): ?><a class="link small" href="<?= e(admin_url($key)) ?>">Reset</a><?php endif; ?>
  </form>
  <?php endif; ?>
  <?php if ($rows): ?>
  <?php if ($canReorder): ?><p class="reorder-hint"><?= icon('drag', 'icon icon-sm') ?> Drag rows (or use the arrow buttons) to change the order. It saves automatically.</p><?php endif; ?>
  <div class="table-wrap">
    <table class="table table-resp"<?= $canReorder ? ' data-reorder="' . e(admin_url("$key/reorder")) . '"' : '' ?>>
      <thead><tr>
        <?php if ($canReorder): ?><th class="w-drag"><span class="sr-only">Order</span></th><?php endif; ?>
        <?php foreach ($d['columns'] as [$col, $label]): ?><th><?= e($label) ?></th><?php endforeach; ?>
        <th class="t-right">Actions</th>
      </tr></thead>
      <tbody>
      <?php foreach ($rows as $r): $edit = admin_url("$key/{$r['id']}/edit"); ?>
        <tr data-id="<?= (int) $r['id'] ?>"<?= $canReorder ? ' draggable="true"' : '' ?>>
          <?php if ($canReorder): ?><td class="w-drag"><span class="drag" title="Drag to reorder"><?= icon('drag', 'icon icon-sm') ?></span><span class="move-btns"><button type="button" class="icon-btn xs" data-move="up" aria-label="Move up"><?= icon('chevron-down', 'icon icon-xs flip') ?></button><button type="button" class="icon-btn xs" data-move="down" aria-label="Move down"><?= icon('chevron-down', 'icon icon-xs') ?></button></span></td><?php endif; ?>
          <?php foreach ($d['columns'] as [$col, $label, $kind]): $v = $r[$col] ?? ''; ?>
          <td data-label="<?= e($label) ?>"><?php
            if ($kind === 'title') {
                echo '<a class="strong" href="' . e($edit) . '">' . e($v !== '' ? $v : '(untitled)') . '</a>';
            } elseif ($kind === 'nav') {
                echo ($r['parent_id'] ? '<span class="indent">↳</span> ' : '') . '<a class="strong" href="' . e($edit) . '">' . e($v) . '</a>';
            } elseif (str_starts_with($kind, 'path')) {
                $prefix = substr($kind, 5) ?: '/';
                echo '<code>' . e($prefix . $v) . '</code>';
            } elseif ($kind === 'status') {
                echo '<span class="badge st-' . e($v) . '">' . e(ucfirst((string) $v)) . '</span>';
                if ($key === 'blog' && $v === 'scheduled' && $r['published_at'] && strtotime($r['published_at'] . ' UTC') <= time()) echo ' <span class="badge st-published">Live</span>';
            } elseif ($kind === 'enabled' || $kind === 'bool') {
                echo (string) $v === '1' ? '<span class="badge st-published">Yes</span>' : '<span class="badge st-draft">No</span>';
            } elseif ($kind === 'demo') {
                echo (string) $v === '1' ? '<span class="badge st-warn">Demo</span>' : '<span class="badge st-published">Genuine</span>';
            } elseif ($kind === 'date') {
                echo '<span class="nowrap">' . e(fmt_date($v)) . '</span>';
            } elseif ($kind === 'datetime') {
                echo '<span class="nowrap">' . e($v ? fmt_date($v, 'M j, Y g:i A') : '—') . '</span>';
            } elseif ($kind === 'tag') {
                echo $v !== '' ? '<span class="tag">' . e($v) . '</span>' : '—';
            } elseif ($kind === 'mono') {
                echo '<code>' . e(mb_strimwidth((string) $v, 0, 60, '…')) . '</code>';
            } elseif ($kind === 'excerpt') {
                echo '<span class="muted">' . e(excerpt((string) $v, 80)) . '</span>';
            } elseif ($kind === 'count') {
                echo (int) $v;
            } else {
                echo e($v !== '' && $v !== null ? $v : '—');
            }
          ?></td>
          <?php endforeach; ?>
          <td class="t-right actions-cell">
            <?php if ($toggle): $on = (string) $r[$toggle['field']] === (string) $toggle['on']; ?>
              <button type="button" class="switch-btn<?= $on ? ' on' : '' ?>" role="switch" aria-checked="<?= $on ? 'true' : 'false' ?>" data-toggle-url="<?= e(admin_url("$key/{$r['id']}/toggle")) ?>" title="<?= $on ? 'Enabled / published — click to disable' : 'Disabled / draft — click to enable' ?>"><span class="sr-only">Enabled</span></button>
            <?php endif; ?>
            <?php if (!empty($r['_view'])): ?><a class="icon-btn" href="<?= e(url($r['_view'])) ?>" target="_blank" rel="noopener" title="View on site" aria-label="View on site"><?= icon('external', 'icon icon-sm') ?></a><?php endif; ?>
            <a class="icon-btn" href="<?= e($edit) ?>" title="Edit" aria-label="Edit"><?= icon('edit', 'icon icon-sm') ?></a>
            <?php if ($d['can_delete'] ?? true): ?><button type="button" class="icon-btn danger" title="Delete" aria-label="Delete" data-confirm="Delete this <?= e(strtolower($d['singular'])) ?>? This cannot be undone." data-confirm-action="<?= e(admin_url("$key/{$r['id']}/delete")) ?>"><?= icon('trash', 'icon icon-sm') ?></button><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= App\Core\View::partial('admin/partials/pagination', ['pager' => $pager]) ?>
  <?php else: ?>
  <div class="empty"><?= icon($d['icon'] ?? 'inbox', 'icon') ?><h2>No <?= e(strtolower($d['label'])) ?> found</h2><p><?= $q !== '' ? 'Try a different search.' : 'Nothing has been added yet.' ?></p><?php if ($canCreate): ?><a class="btn btn-primary" href="<?= e(admin_url("$key/new") . $newQuery) ?>"><?= icon('plus', 'icon icon-sm') ?> Add <?= e(strtolower($d['singular'])) ?></a><?php endif; ?></div>
  <?php endif; ?>
</div>
