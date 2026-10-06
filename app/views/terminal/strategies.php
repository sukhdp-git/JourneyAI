<?php
$cur = $acc['currency'];
$rows = [];
foreach ($strategies as $s) { $rows[] = ['s' => $s, 'st' => $stats[(string) $s['id']] ?? null]; }
usort($rows, fn ($a, $b) => ($b['st']['net'] ?? -INF) <=> ($a['st']['net'] ?? -INF));
?>
<section class="panel" style="margin-bottom:12px">
  <div class="panel-head"><h2>Strategy telemetry · <?= e($acc['name']) ?></h2><span class="muted small">All closed trades in this account<?= (int) $acc['has_demo_data'] ? ' · DEMO DATA' : '' ?></span></div>
  <div class="table-wrap"><table class="tbl cards">
    <thead><tr><th>Strategy</th><th class="r">Trades</th><th class="r">Wins</th><th class="r">Losses</th><th class="r">Win rate</th><th class="r">P&amp;L</th><th class="r">Profit factor</th><th class="r">Avg R</th><th class="r">Target R</th><th class="r">Rule compliance</th></tr></thead>
    <tbody>
    <?php foreach ($rows as ['s' => $s, 'st' => $st]): ?>
      <tr><td data-label="Strategy" class="strong"><a href="#s<?= (int) $s['id'] ?>"><?= e($s['name']) ?></a><?= $s['active'] ? '' : ' <span class="badge">inactive</span>' ?></td>
      <?php if ($st): ?>
        <td data-label="Trades" class="r num"><?= $st['trades'] ?></td><td data-label="Wins" class="r num"><?= $st['wins'] ?></td><td data-label="Losses" class="r num"><?= $st['losses'] ?></td>
        <td data-label="Win rate" class="r num"><?= e(pct($st['win_rate'])) ?></td><td data-label="P&amp;L" class="r num <?= $st['net'] >= 0 ? 'up' : 'down' ?>"><?= e(money($st['net'], $cur, true)) ?></td>
        <td data-label="Profit factor" class="r num"><?= $st['profit_factor'] === null ? '∞' : e(number_format($st['profit_factor'], 2)) ?></td><td data-label="Avg R" class="r num"><?= $st['avg_r'] === null ? '—' : e(number_format($st['avg_r'], 2)) . 'R' ?></td>
        <td data-label="Target R" class="r num"><?= $s['target_rr'] ? e($s['target_rr']) . 'R' : '—' ?></td><td data-label="Compliance" class="r num"><?= e(pct($st['compliance'], 0)) ?></td>
      <?php else: ?><td colspan="9" class="muted" data-label="Stats">No closed trades yet</td><?php endif; ?></tr>
    <?php endforeach; ?>
    <?php if ($unassigned['trades']): ?><tr><td data-label="Strategy" class="muted">Unassigned</td><td class="r num" data-label="Trades"><?= $unassigned['trades'] ?></td><td class="r num" data-label="Wins"><?= $unassigned['wins'] ?></td><td class="r num" data-label="Losses"><?= $unassigned['losses'] ?></td><td class="r num" data-label="Win rate"><?= e(pct($unassigned['win_rate'])) ?></td><td class="r num <?= $unassigned['net'] >= 0 ? 'up' : 'down' ?>" data-label="P&amp;L"><?= e(money($unassigned['net'], $cur, true)) ?></td><td colspan="4"></td></tr><?php endif; ?>
    </tbody>
  </table></div>
</section>
<div class="grid g-3">
  <section class="panel">
    <h2>New strategy</h2>
    <form method="post" action="<?= e(url('/terminal/strategies')) ?>" class="stack" style="margin-top:10px"><?= csrf_field() ?>
      <div class="f"><label for="ns-n">Name</label><input id="ns-n" name="name" maxlength="80" required></div>
      <div class="f"><label for="ns-r">Target R</label><input id="ns-r" name="target_rr" type="number" step="any"></div>
      <div class="f"><label for="ns-d">Description</label><textarea id="ns-d" name="description" rows="2" maxlength="500"></textarea></div>
      <div class="f"><label for="ns-c">Checklist (one rule per line)</label><textarea id="ns-c" name="checklist" rows="3"></textarea></div>
      <button class="tm-btn tm-btn-primary" type="submit">Create strategy</button>
    </form>
  </section>
  <?php foreach ($strategies as $s): ?>
  <section class="panel" id="s<?= (int) $s['id'] ?>">
    <form method="post" action="<?= e(url('/terminal/strategies/' . $s['id'])) ?>" class="stack"><?= csrf_field() ?>
      <div class="f"><label for="s<?= $s['id'] ?>-n">Name</label><input id="s<?= $s['id'] ?>-n" name="name" value="<?= e($s['name']) ?>" maxlength="80" required></div>
      <div class="grid g-2"><div class="f"><label for="s<?= $s['id'] ?>-r">Target R</label><input id="s<?= $s['id'] ?>-r" name="target_rr" type="number" step="any" value="<?= e((string) $s['target_rr']) ?>"></div><div class="f"><span class="lbl">Status</span><input type="hidden" name="active" value="0"><label class="check"><input type="checkbox" name="active" value="1"<?= $s['active'] ? ' checked' : '' ?>> Active</label></div></div>
      <div class="f"><label for="s<?= $s['id'] ?>-d">Description</label><textarea id="s<?= $s['id'] ?>-d" name="description" rows="2"><?= e((string) $s['description']) ?></textarea></div>
      <div class="f"><label for="s<?= $s['id'] ?>-c">Checklist</label><textarea id="s<?= $s['id'] ?>-c" name="checklist" rows="3"><?= e((string) $s['checklist']) ?></textarea></div>
      <div style="display:flex;gap:6px"><button class="tm-btn tm-btn-sm tm-btn-primary" type="submit">Save</button></div>
    </form>
    <form method="post" action="<?= e(url('/terminal/strategies/' . $s['id'] . '/delete')) ?>" data-confirm="Delete the “<?= e($s['name']) ?>” strategy? Trades are kept as unassigned." style="margin-top:6px"><?= csrf_field() ?><button class="tm-btn tm-btn-sm tm-btn-ghost" type="submit">Delete</button></form>
  </section>
  <?php endforeach; ?>
</div>
