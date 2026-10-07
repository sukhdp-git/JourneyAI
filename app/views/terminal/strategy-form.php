<?php
use App\Trading\Domain;
$v = fn (string $k) => (string) old($k, $s[$k] ?? '');
$rr = old('target_rr', $s && $s['target_rr'] !== null ? '1:' . rtrim(rtrim((string) $s['target_rr'], '0'), '.') : '');
$action = $s ? url('/terminal/strategies/' . $s['id']) : url('/terminal/strategies');
$list = function (string $name, string $label, string $hint, string $placeholder) use ($v) { ?>
  <div class="f list-editor" data-list-editor="#le-<?= e($name) ?>">
    <span class="lbl"><?= e($label) ?></span>
    <span class="f-hint"><?= e($hint) ?></span>
    <textarea id="le-<?= e($name) ?>" name="<?= e($name) ?>" hidden><?= e($v($name)) ?></textarea>
    <ol aria-label="<?= e($label) ?>"></ol>
    <div class="le-add"><label class="sr-only" for="le-<?= e($name) ?>-in">Add item</label><input id="le-<?= e($name) ?>-in" type="text" maxlength="200" data-le-input placeholder="<?= e($placeholder) ?>"><button type="button" class="tm-btn tm-btn-sm" data-le-add>+ Add</button></div>
  </div>
<?php };
?>
<form method="post" action="<?= e($action) ?>" class="panel stack" style="max-width:860px" novalidate>
  <?= csrf_field() ?>
  <div class="panel-head"><h2><?= $s ? 'Edit ' . e($s['name']) : 'Add personal strategy' ?></h2><a class="small" href="<?= e(url('/terminal/strategies')) ?>">← Strategy analysis</a></div>
  <div class="grid-form">
    <div class="f<?= has_error('name') ? ' has-error' : '' ?>"><label for="sb-n">Strategy name *</label><input id="sb-n" type="text" name="name" maxlength="80" required value="<?= e($v('name')) ?>" placeholder="e.g. Volume Profile Reversal"><?= field_error('name') ?></div>
    <div class="f<?= has_error('target_rr') ? ' has-error' : '' ?>"><label for="sb-r">Target / typical risk-to-reward</label><input id="sb-r" type="text" name="target_rr" maxlength="10" value="<?= e((string) $rr) ?>" placeholder="1:3"><?= field_error('target_rr') ?></div>
    <div class="f"><label for="sb-s">Trading style</label><select id="sb-s" name="style"><option value="">—</option><?php foreach (Domain::STRATEGY_STYLES as $k => $l): ?><option value="<?= e($k) ?>"<?= $v('style') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
    <div class="f"><span class="lbl">Status</span><input type="hidden" name="active" value="0"><label class="check"><input type="checkbox" name="active" value="1"<?= !$s || $s['active'] ? ' checked' : '' ?>> Active</label></div>
  </div>
  <div class="f"><label for="sb-t">Core edge / thesis</label><span class="f-hint">What makes this strategy profitable or gives it an edge?</span><textarea id="sb-t" name="thesis" rows="5" maxlength="5000" placeholder="e.g. Auctions that fail above value tend to rotate back to the POC; I only take them with order-flow confirmation."><?= e($v('thesis')) ?></textarea>
    <div style="margin-top:6px"><button type="button" class="tm-btn tm-btn-sm" data-voice-into="#sb-t" data-voice-append><?= icon('mic', 'icon icon-sm') ?> Dictate</button></div></div>
  <div class="f"><label for="sb-d">Short description</label><input id="sb-d" type="text" name="description" maxlength="500" value="<?= e($v('description')) ?>"></div>
  <?php $list('checklist', 'Strategy rules', 'Add, edit, remove and reorder the rules you check before every entry.', 'e.g. Wait for rejection confirmation'); ?>
  <?php $list('setups', 'Sub-setups / components', 'Variations of this strategy you want to tag and compare (used in the trade form).', 'e.g. VAL Bounce'); ?>
  <div class="tm-modal-actions"><a class="tm-btn" href="<?= e(url('/terminal/strategies')) ?>">Cancel</a><button class="tm-btn tm-btn-primary" type="submit"><?= $s ? 'Save strategy' : 'Create strategy' ?></button></div>
</form>
<?php if ($s): ?>
<form method="post" action="<?= e(url('/terminal/strategies/' . $s['id'] . '/delete')) ?>" data-confirm="Delete the “<?= e($s['name']) ?>” strategy? Trades are kept as unassigned." style="margin-top:10px"><?= csrf_field() ?><button class="tm-btn tm-btn-sm tm-btn-ghost" type="submit">Delete strategy</button></form>
<?php endif; ?>
