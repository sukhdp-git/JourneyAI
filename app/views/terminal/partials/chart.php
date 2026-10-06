<?php /** @var string $type @var array $series @var string $format @var string $label @var ?string $area */ ?>
<div class="chart" data-chart="<?= e($type) ?>" data-json="<?= e(json_encode($series)) ?>" data-format="<?= e($format) ?>" data-label="<?= e($label) ?>"<?= !empty($area) ? ' data-area="' . e($area) . '"' : '' ?><?= !empty($height) ? ' data-height="' . (int) $height . '"' : '' ?>></div>
<?php $rows = $type === 'line' ? ($series['points'] ?? $series) : $series; if ($rows): ?>
<details class="tview"><summary>View as table</summary><div class="table-wrap"><table class="tbl"><thead><tr><th><?= $type === 'line' ? 'Date' : 'Group' ?></th><th class="r"><?= e($label) ?></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['x'] ?? $r['label']) ?><?= isset($r['sub']) ? ' <span class="muted">· ' . e($r['sub']) . '</span>' : '' ?></td><td class="r num"><?= e(number_format((float) ($r['y'] ?? $r['value']), 2)) ?></td></tr><?php endforeach; ?>
</tbody></table></div></details>
<?php endif; ?>
