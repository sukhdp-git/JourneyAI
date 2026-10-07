<?php /** Progress ring. @var ?float $value 0..1 @var string $label @var string $tone good|bad|neutral */
$r = 26; $c = 2 * M_PI * $r; $v = $value === null ? 0 : max(0, min(1, $value));
$color = ['good' => 'var(--bar-pos)', 'bad' => 'var(--bar-neg)', 'neutral' => 'var(--series-1)'][$tone ?? 'neutral'];
?>
<div class="ring-wrap"><span class="ring" role="img" aria-label="<?= e($label . ': ' . ($value === null ? 'no data' : round($v * 100) . '%')) ?>">
  <svg width="64" height="64" viewBox="0 0 64 64" aria-hidden="true"><circle class="bg" cx="32" cy="32" r="<?= $r ?>"/><circle class="fg" cx="32" cy="32" r="<?= $r ?>" style="stroke:<?= $color ?>" stroke-dasharray="<?= round($c * $v, 2) ?> <?= round($c, 2) ?>"/></svg>
  <b><?= $value === null ? '—' : round($v * 100) . '%' ?></b></span><?= e($label) ?></div>
