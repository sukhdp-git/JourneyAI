<?php
/** Accessible form field with server-side error + old input. */
$type ??= 'text'; $required ??= false; $hint ??= ''; $id = 'f-' . $name;
$val = old($name, '');
$err = has_error($name);
$aria = ($err ? ' aria-invalid="true"' : '') . ' aria-describedby="' . ($err ? 'err-' . e($name) . ' ' : '') . ($hint ? e($id) . '-hint' : '') . '"';
$attrs = ($required ? ' required' : '') . (isset($max) ? ' maxlength="' . (int) $max . '"' : '') . (isset($min) ? ' minlength="' . (int) $min . '"' : '') . (isset($autocomplete) ? ' autocomplete="' . e($autocomplete) . '"' : '');
?>
<div class="field<?= $err ? ' has-error' : '' ?>">
  <label for="<?= e($id) ?>"><?= e($label) ?><?= $required ? ' <span class="req" aria-hidden="true">*</span>' : '' ?></label>
  <?php if ($type === 'textarea'): ?>
    <textarea id="<?= e($id) ?>" name="<?= e($name) ?>" rows="5"<?= $attrs . $aria ?>><?= e($val) ?></textarea>
  <?php elseif ($type === 'select'): ?>
    <select id="<?= e($id) ?>" name="<?= e($name) ?>"<?= $attrs . $aria ?>>
      <?php foreach ($options as $k => $l): ?><option value="<?= e($k) ?>"<?= (string) $val === (string) $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
    </select>
  <?php else: ?>
    <input id="<?= e($id) ?>" type="<?= e($type) ?>" name="<?= e($name) ?>" value="<?= e($val) ?>"<?= $attrs . $aria ?><?= isset($minDate) ? ' min="' . e($minDate) . '"' : '' ?>>
  <?php endif; ?>
  <?php if ($hint): ?><small class="hint" id="<?= e($id) ?>-hint"><?= e($hint) ?></small><?php endif; ?>
  <?= field_error($name) ?>
</div>
