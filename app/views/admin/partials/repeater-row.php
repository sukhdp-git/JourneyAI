<li class="rep-row" data-rep-row>
  <div class="rep-head">
    <span class="drag" data-rep-drag title="Drag to reorder" aria-hidden="true"><?= icon('drag', 'icon icon-sm') ?></span>
    <strong class="rep-title" data-rep-title><?= e(($rowv['heading'] ?? '') ?: ($rowv['title'] ?? '') ?: ($rowv['question'] ?? '') ?: ('Item ' . $n)) ?></strong>
    <span class="rep-tools">
      <button type="button" class="icon-btn sm" data-rep-up aria-label="Move up"><?= icon('chevron-down', 'icon icon-sm flip') ?></button>
      <button type="button" class="icon-btn sm" data-rep-down aria-label="Move down"><?= icon('chevron-down', 'icon icon-sm') ?></button>
      <button type="button" class="icon-btn sm" data-rep-collapse aria-label="Collapse"><?= icon('minus', 'icon icon-sm') ?></button>
      <button type="button" class="icon-btn sm danger" data-rep-remove aria-label="Remove"><?= icon('trash', 'icon icon-sm') ?></button>
    </span>
  </div>
  <div class="rep-body form-grid">
    <?php foreach ($f['subfields'] as $sf): ?>
      <?= App\Core\View::partial('admin/partials/field', ['f' => $sf, 'name' => $name . '[' . $idx . '][' . $sf['name'] . ']', 'value' => $rowv[$sf['name']] ?? ($sf['type'] === 'checkbox' ? '0' : ''), 'errKey' => null, 'uid' => $idx]) ?>
    <?php endforeach; ?>
  </div>
</li>
