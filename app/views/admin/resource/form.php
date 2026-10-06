<?php
/** @var string $key @var array $d @var array $fields @var array $values @var ?array $row */
$tabs = [];
foreach ($fields as $f) {
    $tabs[$f['tab'] ?? 'General'][] = $f;
}
$action = $row ? admin_url("$key/{$row['id']}") : admin_url($key);
$errs = $GLOBALS['__errors'] ?? [];
?>
<div class="page-head">
  <div>
    <h1><?= e($pageTitle) ?></h1>
    <?php if ($row && isset($row['updated_at'])): ?><p class="muted small">Last updated <?= e(fmt_date($row['updated_at'], 'M j, Y g:i A')) ?></p><?php endif; ?>
  </div>
  <div class="page-actions">
    <?php if (!empty($viewUrl)): ?><a class="btn btn-secondary" href="<?= e(url($viewUrl)) ?>" target="_blank" rel="noopener"><?= icon('external', 'icon icon-sm') ?> View on site</a><?php endif; ?>
    <a class="btn btn-ghost" href="<?= e(admin_url($key)) ?>"><?= icon('arrow-left', 'icon icon-sm') ?> Back</a>
  </div>
</div>
<?php if (!empty($d['notice'])): ?><div class="alert alert-info"><?= icon('info', 'icon icon-sm') ?><p><?= e($d['notice']) ?></p></div><?php endif; ?>
<form method="post" action="<?= e($action) ?>" class="card form-card" data-dirty-check novalidate>
  <?= csrf_field() ?>
  <?php if (count($tabs) > 1): ?>
  <div class="tabs" role="tablist">
    <?php $ti = 0; foreach ($tabs as $tab => $tf): $hasErr = (bool) array_intersect(array_column($tf, 'name'), array_keys($errs)); ?>
    <button type="button" role="tab" id="tab-<?= $ti ?>" aria-controls="panel-<?= $ti ?>" aria-selected="<?= $ti === 0 ? 'true' : 'false' ?>" data-tab="<?= $ti ?>"<?= $ti ? ' tabindex="-1"' : '' ?>><?= e($tab) ?><?= $hasErr ? ' <span class="tab-err" aria-label="has errors">!</span>' : '' ?></button>
    <?php $ti++; endforeach; ?>
  </div>
  <?php endif; ?>
  <?php $ti = 0; foreach ($tabs as $tab => $tf): ?>
  <div class="tab-panel form-grid" role="tabpanel" id="panel-<?= $ti ?>" aria-labelledby="tab-<?= $ti ?>"<?= $ti ? ' hidden' : '' ?>>
    <?php foreach ($tf as $f):
        $val = old($f['name'], $values[$f['name']] ?? '');
        $locked = $row && !empty($f['lock_if']) && !empty($row[$f['lock_if']]);
        echo App\Core\View::partial('admin/partials/field', ['f' => $f, 'value' => $val, 'locked' => $locked]);
    endforeach; ?>
  </div>
  <?php $ti++; endforeach; ?>
  <div class="form-foot">
    <?php if ($row && ($d['can_delete'] ?? true)): ?>
      <button type="button" class="btn btn-danger-ghost" data-confirm="Delete this <?= e(strtolower($d['singular'])) ?>? This cannot be undone." data-confirm-action="<?= e(admin_url("$key/{$row['id']}/delete")) ?>"><?= icon('trash', 'icon icon-sm') ?> Delete</button>
    <?php endif; ?>
    <span class="spacer"></span>
    <a class="btn btn-ghost" href="<?= e(admin_url($key)) ?>">Cancel</a>
    <button type="submit" class="btn btn-primary"><?= icon('check', 'icon icon-sm') ?> <?= $row ? 'Save changes' : 'Create ' . e(strtolower($d['singular'])) ?></button>
  </div>
</form>
