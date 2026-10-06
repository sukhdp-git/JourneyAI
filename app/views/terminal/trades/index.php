<?php
use App\Trading\Domain;
$cur = $acc['currency'];
$qs = function (array $over) { $q = array_merge($_GET, $over); unset($q['page']); return '?' . http_build_query(array_filter($q, fn ($v) => $v !== '' && $v !== null)); };
$sortLink = function (string $col, string $label) use ($f, $qs) { $on = ($f['sort'] ?? 'executed_at') === $col; $dir = $on && ($f['dir'] ?? 'desc') === 'desc' ? 'asc' : 'desc'; return '<a href="' . e($qs(['sort' => $col, 'dir' => $dir])) . '">' . e($label) . ($on ? (($f['dir'] ?? 'desc') === 'desc' ? ' ↓' : ' ↑') : '') . '</a>'; };
?>
<form class="toolbar" method="get" action="<?= e(url('/terminal/trades')) ?>">
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
<section class="panel">
  <div class="panel-head"><h2>Execution ledger · <?= e($acc['name']) ?></h2><span class="muted small"><?= number_format($pager->total) ?> trades<?= (int) $acc['has_demo_data'] ? ' · DEMO DATA' : '' ?></span></div>
  <?php if ($rows): ?>
  <div class="table-wrap"><table class="tbl cards">
    <thead><tr><th><?= $sortLink('executed_at', 'Date') ?></th><th>Time</th><th><?= $sortLink('symbol', 'Symbol') ?></th><th>Side</th><th class="r">Entry</th><th class="r">Exit</th><th class="r">Stop</th><th class="r">Lots</th><th class="r"><?= $sortLink('pnl', 'P&L') ?></th><th class="r"><?= $sortLink('rr', 'R') ?></th><th>Strategy</th><th>Setup</th><th>Session</th><th>Emotion</th><th class="r">Actions</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $t): $pnl = $t['pnl']; ?>
      <tr>
        <td data-label="Date"><?= e(fmt_date($t['executed_at'], 'Y-m-d')) ?></td>
        <td data-label="Time" class="num"><?= e(fmt_date($t['executed_at'], 'H:i')) ?></td>
        <td data-label="Symbol" class="strong"><?= e($t['symbol']) ?></td>
        <td data-label="Side"><span class="badge <?= strtolower($t['side']) ?>"><?= $t['side'] === 'LONG' ? '▲' : '▼' ?> <?= e($t['side']) ?></span></td>
        <td data-label="Entry" class="r num"><?= e(App\Trading\Instruments::format($t['symbol'], $t['entry_price'])) ?></td>
        <td data-label="Exit" class="r num"><?= e(App\Trading\Instruments::format($t['symbol'], $t['exit_price'])) ?></td>
        <td data-label="Stop" class="r num"><?= e(App\Trading\Instruments::format($t['symbol'], $t['stop_loss'])) ?></td>
        <td data-label="Lots" class="r num"><?= e(rtrim(rtrim(number_format((float) $t['lot_size'], 4, '.', ''), '0'), '.')) ?></td>
        <td data-label="P&amp;L" class="r num <?= $pnl === null ? '' : ((float) $pnl >= 0 ? 'up' : 'down') ?>"><?= $pnl === null ? '<span class="badge">OPEN</span>' : e(money($pnl, $cur, true)) ?></td>
        <td data-label="R" class="r num"><?= $t['rr'] === null ? '—' : e(((float) $t['rr'] > 0 ? '+' : '') . number_format((float) $t['rr'], 2)) . 'R' ?></td>
        <td data-label="Strategy"><?= e($t['strategy_name'] ?: '—') ?></td>
        <td data-label="Setup"><?= e($t['setup_tag'] ?: '—') ?></td>
        <td data-label="Session"><?= e(Domain::SESSIONS[$t['session']] ?? '—') ?></td>
        <td data-label="Emotion"><?= e($t['emotion'] ? ucfirst(strtolower($t['emotion'])) : '—') ?><?= $t['mistake_tag'] !== 'NONE' ? ' <span class="badge warn">' . e(Domain::MISTAKES[$t['mistake_tag']] ?? $t['mistake_tag']) . '</span>' : '' ?></td>
        <td class="r nowrap" data-label="Actions">
          <a class="tm-icon-btn" href="<?= e(url('/terminal/trades/' . $t['id'])) ?>" title="View &amp; share card" aria-label="View trade"><?= icon('eye', 'icon icon-sm') ?></a>
          <?php if ($acc['writable']): ?>
          <a class="tm-icon-btn" href="<?= e(url('/terminal/trades/' . $t['id'] . '/edit')) ?>" title="Edit" aria-label="Edit trade"><?= icon('edit', 'icon icon-sm') ?></a>
          <form method="post" action="<?= e(url('/terminal/trades/' . $t['id'] . '/delete')) ?>" style="display:inline" data-confirm="Delete this <?= e($t['symbol']) ?> trade permanently?"><?= csrf_field() ?><button class="tm-icon-btn danger" type="submit" title="Delete" aria-label="Delete trade"><?= icon('trash', 'icon icon-sm') ?></button></form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php if ($pager->pages > 1): ?><div class="pager"><span>Page <?= $pager->page ?> of <?= $pager->pages ?></span><span><?php if ($pager->page > 1): ?><a class="tm-btn tm-btn-sm" href="<?= e($pager->url($pager->page - 1)) ?>">← Prev</a><?php endif; ?> <?php if ($pager->page < $pager->pages): ?><a class="tm-btn tm-btn-sm" href="<?= e($pager->url($pager->page + 1)) ?>">Next →</a><?php endif; ?></span></div><?php endif; ?>
  <?php else: ?><div class="empty"><?= icon('list', 'icon') ?><p><?= $f ? 'No trades match these filters.' : e(t('empty.trades')) ?></p><?php if ($acc['writable']): ?><a class="tm-btn tm-btn-primary" href="<?= e(url('/terminal/trades/new')) ?>">Log your first trade</a><?php endif; ?></div><?php endif; ?>
</section>
