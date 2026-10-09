<?php
use App\Trading\Domain;
$cur = $acc['currency'];
$qs = function (array $over) { $q = array_merge($_GET, $over); unset($q['page']); return '?' . http_build_query(array_filter($q, fn ($v) => $v !== '' && $v !== null)); };
$sortLink = function (string $col, string $label) use ($f, $qs) { $on = ($f['sort'] ?? 'executed_at') === $col; $dir = $on && ($f['dir'] ?? 'desc') === 'desc' ? 'asc' : 'desc'; return '<a href="' . e($qs(['sort' => $col, 'dir' => $dir])) . '">' . e($label) . ($on ? (($f['dir'] ?? 'desc') === 'desc' ? ' ↓' : ' ↑') : '') . '</a>'; };
?>
<?php $shots = ($f['view'] ?? '') === 'shots'; ?>
<nav class="seg view-seg" aria-label="Trade log view"><a href="<?= e(url('/terminal/trades') . $qs(['view' => null])) ?>"<?= $shots ? '' : ' class="on" aria-current="page"' ?>><?= icon('list', 'icon icon-sm') ?> Table</a><a href="<?= e(url('/terminal/trades') . $qs(['view' => 'shots'])) ?>"<?= $shots ? ' class="on" aria-current="page"' : '' ?>><?= icon('camera', 'icon icon-sm') ?> Screenshot journal</a></nav>
<form class="toolbar" method="get" action="<?= e(url('/terminal/trades')) ?>">
  <?php if ($shots): ?><input type="hidden" name="view" value="shots"><?php endif; ?>
  <label class="sr-only" for="tq">Search</label><input id="tq" class="grow" type="search" name="q" value="<?= e($f['q'] ?? '') ?>" placeholder="Search symbol, setup or notes">
  <label class="sr-only" for="tsym">Instrument</label><select id="tsym" name="symbol" data-autosubmit><option value="">All instruments</option><?php foreach ($symbols as $s): ?><option<?= ($f['symbol'] ?? '') === $s ? ' selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select>
  <label class="sr-only" for="tstr">Strategy</label><select id="tstr" name="strategy" data-autosubmit><option value="">All strategies</option><?php foreach ($strategies as $s): ?><option value="<?= (int) $s['id'] ?>"<?= ($f['strategy'] ?? '') == $s['id'] ? ' selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach; ?></select>
  <label class="sr-only" for="tses">Session</label><select id="tses" name="session" data-autosubmit><option value="">All sessions</option><?php foreach (Domain::SESSIONS as $k => $l): ?><option value="<?= e($k) ?>"<?= ($f['session'] ?? '') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
  <label class="sr-only" for="tside">Side</label><select id="tside" name="side" data-autosubmit><option value="">Long &amp; short</option><option value="LONG"<?= ($f['side'] ?? '') === 'LONG' ? ' selected' : '' ?>>Long</option><option value="SHORT"<?= ($f['side'] ?? '') === 'SHORT' ? ' selected' : '' ?>>Short</option></select>
  <label class="sr-only" for="tres">Result</label><select id="tres" name="result" data-autosubmit><option value="">All results</option><option value="win"<?= ($f['result'] ?? '') === 'win' ? ' selected' : '' ?>>Winners</option><option value="loss"<?= ($f['result'] ?? '') === 'loss' ? ' selected' : '' ?>>Losers</option><option value="open"<?= ($f['result'] ?? '') === 'open' ? ' selected' : '' ?>>Open</option></select>
  <label class="sr-only" for="tfrom">From</label><input id="tfrom" type="date" name="from" value="<?= e($f['from'] ?? '') ?>" style="width:auto">
  <label class="sr-only" for="tto">To</label><input id="tto" type="date" name="to" value="<?= e($f['to'] ?? '') ?>" style="width:auto">
  <button class="tm-btn tm-btn-sm" type="submit">Filter</button>
  <?php if (array_diff_key($f, ['sort' => 1, 'dir' => 1])): ?><a class="small" href="<?= e(url('/terminal/trades')) ?>">Reset</a><?php endif; ?>
  <span style="margin-left:auto;display:flex;gap:6px">
    <a class="tm-btn tm-btn-sm" href="<?= e(url('/terminal/trades/export') . $qs([])) ?>"><?= icon('download', 'icon icon-sm') ?> <?= e(t('common.export')) ?></a>
    <?php if ($acc['writable']): ?><a class="tm-btn tm-btn-sm tm-btn-primary" href="<?= e(url('/terminal/trades/new')) ?>"><?= icon('plus', 'icon icon-sm') ?> <?= e(t('common.new_trade')) ?></a><?php endif; ?>
  </span>
</form>
<?php if ($shots): ?>
<section class="panel">
  <div class="panel-head"><h2><?= icon('camera', 'icon icon-sm') ?> Screenshot journal · <?= e($acc['name']) ?></h2><span class="muted small"><?= number_format($pager->total) ?> charts</span></div>
  <?php if ($rows): ?>
  <div class="shot-grid">
    <?php foreach ($rows as $t): $p = $t['pnl']; ?>
    <a class="shot-card <?= $p === null ? '' : ((float) $p >= 0 ? 'is-up' : 'is-down') ?>" href="<?= e(url('/terminal/trades/' . $t['id'])) ?>">
      <img src="<?= e(url('/terminal/trades/' . $t['id'] . '/screenshot')) ?>" alt="<?= e($t['symbol'] . ' chart, ' . fmt_date($t['executed_at'], 'M j')) ?>" loading="lazy">
      <span class="shot-meta"><b><?= e($t['symbol']) ?></b> <span class="badge <?= strtolower($t['side']) ?>"><?= $t['side'] === 'LONG' ? 'BUY' : 'SELL' ?></span><span class="shot-pnl <?= $p === null ? '' : ((float) $p >= 0 ? 'up' : 'down') ?>"><?= $p === null ? 'OPEN' : e(money($p, $cur, true)) ?></span></span>
      <span class="shot-sub"><?= e(fmt_date($t['executed_at'], 'M j, H:i')) ?><?= $t['strategy_name'] || $t['setup_tag'] ? ' · ' . e($t['strategy_name'] ?: $t['setup_tag']) : '' ?><?= $t['rr'] !== null ? ' · ' . e(((float) $t['rr'] > 0 ? '+' : '') . number_format((float) $t['rr'], 2)) . 'R' : '' ?></span>
    </a>
    <?php endforeach; ?>
  </div>
  <?php if ($pager->pages > 1): ?><div class="pager"><span>Page <?= $pager->page ?> of <?= $pager->pages ?></span><span><?php if ($pager->page > 1): ?><a class="tm-btn tm-btn-sm" href="<?= e($pager->url($pager->page - 1)) ?>">← Prev</a><?php endif; ?> <?php if ($pager->page < $pager->pages): ?><a class="tm-btn tm-btn-sm" href="<?= e($pager->url($pager->page + 1)) ?>">Next →</a><?php endif; ?></span></div><?php endif; ?>
  <?php else: ?><div class="empty"><?= icon('camera', 'icon') ?><p>No chart screenshots yet. Add one when you <a href="<?= e(url('/terminal/trades/new')) ?>">log a trade</a> or from any trade’s page.</p></div><?php endif; ?>
</section>
<?php else: ?>
<section class="panel">
  <div class="panel-head"><h2>Execution ledger · <?= e($acc['name']) ?></h2><span class="muted small"><?= number_format($pager->total) ?> trades<?= (int) $acc['has_demo_data'] ? ' · DEMO DATA' : '' ?></span></div>
  <?php if ($rows): ?>
  <div class="table-wrap"><table class="tbl trades">
    <thead><tr><th><?= $sortLink('executed_at', 'Date / time') ?></th><th><?= $sortLink('symbol', 'Instrument') ?></th><th>Side</th><th class="r">Entry</th><th class="r">Exit</th><th class="r">Stop</th><th class="r">Lot</th><th class="r"><?= $sortLink('pnl', 'P&L') ?></th><th class="r"><?= $sortLink('rr', 'R') ?></th><th>Strategy</th><th class="r"><span class="sr-only">Actions</span></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $t): $pnl = $t['pnl']; $fmt = fn ($x) => App\Trading\Instruments::format($t['symbol'], $x); $lots = rtrim(rtrim(number_format((float) $t['lot_size'], 4, '.', ''), '0'), '.');
      $rTxt = $t['rr'] === null ? '' : ((float) $t['rr'] > 0 ? '+' : '') . number_format((float) $t['rr'], 2) . 'R';
      $share = ['symbol' => $t['symbol'], 'side' => $t['side'], 'pnl' => $pnl === null ? 'OPEN' : money($pnl, $cur, true), 'positive' => $pnl === null || (float) $pnl >= 0, 'r' => $rTxt,
        'entry' => $fmt($t['entry_price']), 'exit' => $fmt($t['exit_price']), 'lots' => $lots, 'strategy' => $t['strategy_name'] ?: ($t['setup_tag'] ?: '—'), 'brand' => setting('site_name', 'journzey.ai'), 'demo' => (bool) $acc['has_demo_data']]; ?>
      <tr>
        <td class="dt hide-m" data-label="Date"><?= e(fmt_date($t['executed_at'], 'Y-m-d')) ?><small class="num"><?= e(fmt_date($t['executed_at'], 'H:i')) ?></small></td>
        <td class="head" data-label="Instrument"><a href="<?= e(url('/terminal/trades/' . $t['id'])) ?>"><strong><?= e($t['symbol']) ?></strong></a> <span class="badge <?= strtolower($t['side']) ?> show-m-inline"><?= $t['side'] === 'LONG' ? 'BUY' : 'SELL' ?></span><small class="muted show-m-block"><?= e(fmt_date($t['executed_at'], 'M j, H:i')) ?></small></td>
        <td class="hide-m" data-label="Side"><span class="badge <?= strtolower($t['side']) ?>"><?= $t['side'] === 'LONG' ? '▲ BUY' : '▼ SELL' ?></span></td>
        <td data-label="Entry" class="r num"><?= e($fmt($t['entry_price'])) ?></td>
        <td data-label="Exit" class="r num"><?= e($fmt($t['exit_price'])) ?></td>
        <td data-label="Stop" class="r num"><?= e($fmt($t['stop_loss'])) ?></td>
        <td data-label="Lot" class="r num"><?= e($lots) ?></td>
        <td data-label="P&amp;L" class="pnl r num <?= $pnl === null ? '' : ((float) $pnl >= 0 ? 'up' : 'down') ?>"><?= $pnl === null ? '<span class="badge">OPEN</span>' : e(money($pnl, $cur, true)) ?></td>
        <td data-label="R" class="r num <?= $t['rr'] === null ? '' : ((float) $t['rr'] >= 0 ? 'up' : 'down') ?>"><?= $rTxt ?: '—' ?></td>
        <td data-label="Strategy"><?= e($t['strategy_name'] ?: '—') ?></td>
        <td class="act" data-label="Actions">
          <a class="tm-icon-btn" href="<?= e(url('/terminal/trades/' . $t['id'])) ?>" title="View details (setup, emotion, notes)" aria-label="View trade"><?= icon('eye', 'icon icon-sm') ?></a>
          <button type="button" class="tm-icon-btn" data-share-trade="<?= e(json_encode($share)) ?>" title="Share image" aria-label="Share <?= e($t['symbol']) ?> trade"<?= $pnl === null ? ' disabled' : '' ?>><?= icon('share', 'icon icon-sm') ?></button>
          <?php if ($acc['writable']): ?>
          <a class="tm-icon-btn" href="<?= e(url('/terminal/trades/' . $t['id'] . '/edit')) ?>" title="Edit" aria-label="Edit trade"><?= icon('edit', 'icon icon-sm') ?></a>
          <form method="post" action="<?= e(url('/terminal/trades/' . $t['id'] . '/delete')) ?>" style="display:inline" data-confirm="Delete this <?= e($t['symbol']) ?> trade permanently?"><?= csrf_field() ?><button class="tm-icon-btn danger" type="submit" title="Delete" aria-label="Delete trade"><?= icon('trash', 'icon icon-sm') ?></button></form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <p class="muted small" style="margin-top:8px">Setup, session, emotion, mistakes, notes and screenshots are on each trade's detail page.</p>
  <?php if ($pager->pages > 1): ?><div class="pager"><span>Page <?= $pager->page ?> of <?= $pager->pages ?></span><span><?php if ($pager->page > 1): ?><a class="tm-btn tm-btn-sm" href="<?= e($pager->url($pager->page - 1)) ?>">← Prev</a><?php endif; ?> <?php if ($pager->page < $pager->pages): ?><a class="tm-btn tm-btn-sm" href="<?= e($pager->url($pager->page + 1)) ?>">Next →</a><?php endif; ?></span></div><?php endif; ?>
  <?php else: ?><div class="empty"><?= icon('list', 'icon') ?><p><?= $f ? 'No trades match these filters.' : e(t('empty.trades')) ?></p><?php if ($acc['writable']): ?><a class="tm-btn tm-btn-primary" href="<?= e(url('/terminal/trades/new')) ?>">Log your first trade</a><?php endif; ?></div><?php endif; ?>
</section>
<?php endif; ?>
