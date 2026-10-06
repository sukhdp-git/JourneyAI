<?php $ranges = ['all' => 'All time', 'today' => 'Today', 'week' => 'This week', 'month' => 'This month', 'last_month' => 'Last month']; ?>
<form class="toolbar" method="get" action="<?= e($action) ?>">
  <nav class="seg" aria-label="Date range"><?php foreach ($ranges as $k => $l): ?><a href="<?= e($action . '?range=' . $k) ?>"<?= $range === $k ? ' class="on" aria-current="true"' : '' ?>><?= e($l) ?></a><?php endforeach; ?></nav>
  <input type="hidden" name="range" value="custom">
  <label class="sr-only" for="r-from">From</label><input id="r-from" type="date" name="from" value="<?= e($range === 'custom' ? (string) $from : '') ?>" style="width:auto">
  <label class="sr-only" for="r-to">To</label><input id="r-to" type="date" name="to" value="<?= e($range === 'custom' ? (string) $to : '') ?>" style="width:auto">
  <button class="tm-btn tm-btn-sm" type="submit">Custom range</button>
  <?php if ((int) $acc['has_demo_data']): ?><span class="mode demo-data">DEMO DATA</span><?php endif; ?>
</form>
