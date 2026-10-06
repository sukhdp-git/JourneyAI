<?php
/** @var string $slug @var string $title @var string $intro @var array $fields @var array $values */
use App\Controllers\Admin\SettingsController;
$tabs = [];
foreach ($fields as $f) $tabs[$f['tab'] ?? 'General'][] = $f;
$errs = $GLOBALS['__errors'] ?? [];
$siblings = str_starts_with($slug, 'appearance/') ? ['appearance/colors' => 'Colours', 'appearance/typography' => 'Typography', 'appearance/buttons' => 'Buttons', 'appearance/layout' => 'Layout', 'appearance/custom-css' => 'Custom CSS'] : [];
?>
<div class="page-head">
  <div><h1><?= e($title) ?></h1><p class="muted"><?= e($intro) ?></p></div>
  <div class="page-actions"><a class="btn btn-secondary" href="<?= e(url('/')) ?>" target="_blank" rel="noopener"><?= icon('external', 'icon icon-sm') ?> Preview website</a></div>
</div>
<?php if ($siblings): ?>
<nav class="subnav" aria-label="Appearance sections"><?php foreach ($siblings as $s => $l): if ($s === 'appearance/custom-css' && !can('custom_css')) continue; ?><a href="<?= e(admin_url($s)) ?>"<?= $s === $slug ? ' class="is-active" aria-current="page"' : '' ?>><?= e($l) ?></a><?php endforeach; ?></nav>
<?php endif; ?>
<form method="post" action="<?= e(admin_url(SettingsController::url($slug))) ?>" class="card form-card" data-dirty-check novalidate>
  <?= csrf_field() ?>
  <?php if (count($tabs) > 1): ?>
  <div class="tabs" role="tablist">
    <?php $ti = 0; foreach ($tabs as $tab => $tf): $hasErr = (bool) array_intersect(array_column($tf, 'name'), array_keys($errs)); ?>
    <button type="button" role="tab" id="tab-<?= $ti ?>" aria-controls="panel-<?= $ti ?>" aria-selected="<?= $ti === 0 ? 'true' : 'false' ?>" data-tab="<?= $ti ?>"<?= $ti ? ' tabindex="-1"' : '' ?>><?= e($tab) ?><?= $hasErr ? ' <span class="tab-err">!</span>' : '' ?></button>
    <?php $ti++; endforeach; ?>
  </div>
  <?php endif; ?>
  <?php $ti = 0; foreach ($tabs as $tab => $tf): ?>
  <div class="tab-panel form-grid" role="tabpanel" id="panel-<?= $ti ?>" aria-labelledby="tab-<?= $ti ?>"<?= $ti ? ' hidden' : '' ?>>
    <?php foreach ($tf as $f) echo App\Core\View::partial('admin/partials/field', ['f' => $f, 'value' => old($f['name'], $values[$f['name']] ?? '')]); ?>
  </div>
  <?php $ti++; endforeach; ?>
  <?php if ($slug === 'appearance/colors'): ?>
  <div class="theme-preview" data-theme-preview aria-hidden="true">
    <div class="tp-card"><span class="tp-eyebrow">Preview</span><strong class="tp-h">Trade your plan.</strong><p class="tp-p">Body text on a card surface with <span class="tp-m">muted text</span>.</p><span class="tp-btn">Primary button</span> <span class="tp-acc">● Accent</span></div>
  </div>
  <?php endif; ?>
  <div class="form-foot"><span class="spacer"></span><button type="submit" class="btn btn-primary"><?= icon('check', 'icon icon-sm') ?> Save settings</button></div>
</form>
