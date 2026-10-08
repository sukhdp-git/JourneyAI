<?php
/**
 * Renders one admin form field. @var array $f definition @var mixed $value @var ?string $name (HTML name) @var bool $locked
 */
$name ??= $f['name'];
$errKey = $errKey ?? $f['name'];
$id = 'fld-' . preg_replace('/[^a-z0-9]+/i', '-', $name) . (isset($uid) ? '-' . $uid : '');
$type = $f['type'];
$locked ??= false;
$err = isset($errKey) ? has_error($errKey) : false;
$required = str_contains($f['rules'] ?? '', 'required');
$opts = isset($f['options']) ? (is_callable($f['options']) ? $f['options']() : $f['options']) : [];
$value = $value ?? '';
$width = $f['width'] ?? 'full';
$desc = ($f['help'] ?? '') !== '' ? ' aria-describedby="' . $id . '-help"' : '';
$inv = $err ? ' aria-invalid="true"' : '';
$dis = $locked ? ' disabled' : '';
?>
<div class="f w-<?= e($width) ?><?= $err ? ' has-error' : '' ?> f-<?= e($type) ?>">
  <?php if ($type === 'checkbox'): ?>
    <label class="switch">
      <input type="hidden" name="<?= e($name) ?>" value="0"<?= $dis ?>>
      <input type="checkbox" id="<?= e($id) ?>" name="<?= e($name) ?>" value="1"<?= (string) $value === '1' ? ' checked' : '' ?><?= $dis . $desc ?>>
      <span class="switch-ui" aria-hidden="true"></span><span><?= e($f['label']) ?></span>
    </label>
  <?php else: ?>
    <label for="<?= e($id) ?>"><?= e($f['label']) ?><?= $required ? ' <span class="req" aria-hidden="true">*</span>' : '' ?><?php if (!empty($f['counter'])): ?><span class="counter" data-counter-for="<?= e($id) ?>" data-max="<?= (int) $f['counter'] ?>"></span><?php endif; ?></label>
    <?php switch ($type):
      case 'textarea': ?>
      <textarea id="<?= e($id) ?>" name="<?= e($name) ?>" rows="<?= (int) ($f['rows'] ?? 4) ?>"<?= $required ? ' required' : '' ?><?= $dis . $desc . $inv ?>><?= e($value) ?></textarea>
      <?php break; case 'code': ?>
      <textarea id="<?= e($id) ?>" class="code" name="<?= e($name) ?>" rows="18" spellcheck="false"<?= $dis . $desc . $inv ?>><?= e($value) ?></textarea>
      <?php break; case 'richtext': ?>
      <div class="rte" data-rte>
        <div class="rte-bar" role="toolbar" aria-label="Formatting">
          <button type="button" data-cmd="formatBlock" data-val="h2" title="Heading">H2</button><button type="button" data-cmd="formatBlock" data-val="h3" title="Subheading">H3</button><button type="button" data-cmd="formatBlock" data-val="p" title="Paragraph">¶</button>
          <span class="sep"></span>
          <button type="button" data-cmd="bold" title="Bold"><b>B</b></button><button type="button" data-cmd="italic" title="Italic"><i>I</i></button><button type="button" data-cmd="underline" title="Underline"><u>U</u></button>
          <span class="sep"></span>
          <button type="button" data-cmd="insertUnorderedList" title="Bulleted list">• List</button><button type="button" data-cmd="insertOrderedList" title="Numbered list">1. List</button><button type="button" data-cmd="formatBlock" data-val="blockquote" title="Quote">❝</button>
          <span class="sep"></span>
          <button type="button" data-cmd="link" title="Link"><?= icon('link', 'icon icon-xs') ?></button><button type="button" data-cmd="unlink" title="Remove link">⨯🔗</button><?php if (can('media')): ?><button type="button" data-cmd="image" title="Insert image"><?= icon('image', 'icon icon-xs') ?></button><?php endif; ?>
          <button type="button" data-cmd="removeFormat" title="Clear formatting">Tx</button>
          <button type="button" data-cmd="source" class="rte-source" title="Edit HTML">&lt;/&gt;</button>
        </div>
        <div class="rte-area prose-admin" contenteditable="<?= $locked ? 'false' : 'true' ?>" role="textbox" aria-multiline="true" aria-labelledby="<?= e($id) ?>-lbl"></div>
        <textarea id="<?= e($id) ?>" name="<?= e($name) ?>" class="rte-src code" rows="12" hidden<?= $dis ?>><?= e($value) ?></textarea>
        <span id="<?= e($id) ?>-lbl" class="sr-only"><?= e($f['label']) ?></span>
      </div>
      <?php break; case 'select': ?>
      <select id="<?= e($id) ?>" name="<?= e($name) ?>"<?= $dis . $desc . $inv ?>>
        <?php foreach ($opts as $k => $l): ?><option value="<?= e($k) ?>"<?= (string) $value === (string) $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
      </select>
      <?php break; case 'icon': ?>
      <div class="icon-select">
        <span class="icon-preview" data-icon-preview><?= icon((string) ($value ?: 'dot'), 'icon') ?></span>
        <select id="<?= e($id) ?>" name="<?= e($name) ?>" data-icon-select<?= $dis . $desc ?>>
          <option value="">— None —</option>
          <?php foreach (icon_names() as $n): if (in_array($n, ['dot'], true)) continue; ?><option value="<?= e($n) ?>"<?= $value === $n ? ' selected' : '' ?>><?= e($n) ?></option><?php endforeach; ?>
        </select>
        <template data-icon-sprites><?php foreach (icon_names() as $n): ?><span data-n="<?= e($n) ?>"><?= icon($n, 'icon') ?></span><?php endforeach; ?></template>
      </div>
      <?php break; case 'image': ?>
      <div class="img-field" data-image-field>
        <div class="img-preview" data-image-preview><?php if ($value): ?><img src="<?= e(media_medium((string) $value)) ?>" alt=""><?php else: ?><?= icon('image', 'icon') ?><?php endif; ?></div>
        <div class="img-actions">
          <input type="text" id="<?= e($id) ?>" name="<?= e($name) ?>" value="<?= e($value) ?>" placeholder="No image selected" readonly data-image-input<?= $dis . $desc . $inv ?>>
          <?php if (!$locked && can('media')): ?><button type="button" class="btn btn-secondary btn-sm" data-image-choose><?= icon('image', 'icon icon-sm') ?> Choose</button><?php endif; ?>
          <?php if (!$locked): ?><button type="button" class="btn btn-ghost btn-sm" data-image-clear>Remove</button><?php endif; ?>
        </div>
      </div>
      <?php break; case 'color': ?>
      <div class="color-field"><input type="color" value="<?= e($value ?: '#000000') ?>" data-color-picker aria-label="<?= e($f['label']) ?> picker"<?= $dis ?>><input type="text" id="<?= e($id) ?>" name="<?= e($name) ?>" value="<?= e($value) ?>" pattern="#[0-9a-fA-F]{6}" maxlength="7" data-color-text<?= $dis . $desc . $inv ?>></div>
      <?php break; case 'slug': ?>
      <div class="input-prefix"><?php if (isset($f['prefix'])): ?><span><?= e(rtrim(parse_url(BASE_URL, PHP_URL_HOST) . (parse_url(BASE_URL, PHP_URL_PATH) ?? ''), '/') . $f['prefix']) ?></span><?php endif; ?><input type="text" id="<?= e($id) ?>" name="<?= e($name) ?>" value="<?= e($value) ?>" data-slug-source="<?= e($f['source'] ?? '') ?>" maxlength="150"<?= $locked ? ' readonly' : '' ?><?= $desc . $inv ?>></div>
      <?php if ($locked): ?><small class="help">This URL is fixed because the page is used by the system.</small><?php endif; ?>
      <?php break; case 'permissions': $vals = is_array($value) ? $value : json_list((string) $value); ?>
      <?php if (in_array('*', $vals, true)): ?><p class="alert alert-info small">Full access to every module (Super Admin). This cannot be changed.</p><?php else: ?>
      <div class="perm-grid">
        <?php foreach (App\Core\Auth::PERMISSIONS as $group => $perms): ?>
        <fieldset><legend><?= e($group) ?></legend>
          <?php foreach ($perms as $k => $l): ?><label class="check"><input type="checkbox" name="<?= e($name) ?>[]" value="<?= e($k) ?>"<?= in_array($k, $vals, true) ? ' checked' : '' ?>> <?= e($l) ?></label><?php endforeach; ?>
        </fieldset>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <?php break; case 'repeater': $rows = is_array($value) ? array_values($value) : json_list((string) $value); ?>
      <div class="repeater" data-repeater data-name="<?= e($name) ?>">
        <ol class="rep-list" data-rep-list>
          <?php foreach ($rows as $ri => $rowv): ?>
          <?= App\Core\View::partial('admin/partials/repeater-row', ['f' => $f, 'name' => $name, 'idx' => 'r' . $ri, 'rowv' => is_array($rowv) ? $rowv : [], 'n' => $ri + 1]) ?>
          <?php endforeach; ?>
        </ol>
        <template data-rep-template><?= App\Core\View::partial('admin/partials/repeater-row', ['f' => $f, 'name' => $name, 'idx' => '__i__', 'rowv' => [], 'n' => '']) ?></template>
        <button type="button" class="btn btn-secondary btn-sm" data-rep-add><?= icon('plus', 'icon icon-sm') ?> <?= e($f['add_label'] ?? 'Add item') ?></button>
      </div>
      <?php break; case 'password': ?>
      <div class="pw-wrap"><input type="password" id="<?= e($id) ?>" name="<?= e($name) ?>" value="" autocomplete="new-password"<?= $desc . $inv ?>><button type="button" class="pw-toggle" data-pw-toggle aria-label="Show password"><?= icon('eye', 'icon icon-sm') ?></button></div>
      <?php break; case 'tags': ?>
      <input type="text" id="<?= e($id) ?>" name="<?= e($name) ?>" value="<?= e($value) ?>" placeholder="e.g. Journaling, Discipline"<?= $desc . $inv ?>>
      <?php break; default:
        $htmlType = ['email' => 'email', 'number' => 'number', 'date' => 'date', 'datetime' => 'datetime-local', 'link' => 'text'][$type] ?? 'text'; ?>
      <input type="<?= $htmlType ?>" id="<?= e($id) ?>" name="<?= e($name) ?>" value="<?= e($value) ?>"<?= $required ? ' required' : '' ?><?= $type === 'link' ? ' placeholder="/page or https://…"' : '' ?><?= $type === 'number' ? ' step="any"' : '' ?><?= $dis . $desc . $inv ?>>
    <?php endswitch; ?>
  <?php endif; ?>
  <?php if (($f['help'] ?? '') !== ''): ?><small class="help" id="<?= e($id) ?>-help"><?= e($f['help']) ?></small><?php endif; ?>
  <?= isset($errKey) ? field_error($errKey) : '' ?>
</div>
