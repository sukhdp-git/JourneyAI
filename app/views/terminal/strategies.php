<?php
use App\Controllers\Terminal\StrategyController as SC;
use App\Core\View;
use App\Trading\Domain;
$cur = $acc['currency'];
$ring = fn (?float $v, string $l, string $tone) => View::partial('terminal/partials/ring', ['value' => $v, 'label' => $l, 'tone' => $tone]);
$sign = fn ($v) => $v === null ? '' : ($v >= 0 ? 'up' : 'down');
$bars = array_map(fn ($r) => ['label' => $r['key'], 'value' => $r['s']['net'], 'sub' => $r['s']['trades'] . ' trades · ' . pct($r['s']['win_rate'], 0) . ' win'], $scoreboard);
?>
<div class="toolbar">
  <a class="tm-btn tm-btn-primary" href="<?= e(url('/terminal/strategies/new')) ?>"><?= icon('plus', 'icon icon-sm') ?> Add personal strategy</a>
  <span class="muted small">Account <?= e($acc['name']) ?><?= (int) $acc['has_demo_data'] ? ' · DEMO DATA' : '' ?> · <?= (int) $sum['trades'] ?> closed trades</span>
</div>

<?php if (!$sum['trades']): ?>
<div class="panel empty"><?= icon('target', 'icon') ?><p>No closed trades yet. Create a strategy, then pick it when you log trades to see its performance here.</p></div>
<?php else: ?>
<div class="grid g-2" style="margin-bottom:12px">
  <section class="panel">
    <div class="panel-head"><h2>Strategy scoreboard</h2>
      <form method="get" action="<?= e(url('/terminal/strategies')) ?>"><label class="sr-only" for="sb-sort">Rank by</label><select id="sb-sort" name="sort" data-autosubmit style="width:auto"><?php foreach (SC::SORTS as $k => $l): ?><option value="<?= $k ?>"<?= $sort === $k ? ' selected' : '' ?>>Rank by <?= e($l) ?></option><?php endforeach; ?></select></form></div>
    <ol class="scoreboard">
      <?php foreach ($scoreboard as $i => $r): $s = $r['s']; ?>
      <li>
        <span class="rank">#<?= $i + 1 ?></span>
        <span class="nm"><strong><?= e($r['key']) ?></strong><small><?= (int) $s['trades'] ?> trades · PF <?= $s['profit_factor'] === null ? '∞' : e(number_format($s['profit_factor'], 2)) ?></small></span>
        <span class="v <?= $sign($s['net']) ?>"><?= e(money($s['net'], $cur, true)) ?><small>Profit</small></span>
        <span class="v <?= ($s['win_rate'] ?? 0) >= 0.5 ? 'up' : 'down' ?> hide-m"><?= e(pct($s['win_rate'], 0)) ?><small>Win rate</small></span>
        <span class="v <?= $sign($s['avg_r']) ?> hide-m"><?= $s['avg_r'] === null ? '—' : e(($s['avg_r'] > 0 ? '+' : '') . number_format($s['avg_r'], 2)) . 'R' ?><small>Avg R</small></span>
      </li>
      <?php endforeach; ?>
    </ol>
  </section>
  <section class="panel">
    <div class="panel-head"><h2>Win rate by trading session</h2>
      <form method="get" action="<?= e(url('/terminal/strategies')) ?>"><input type="hidden" name="sort" value="<?= e($sort) ?>"><label class="sr-only" for="ss-f">Strategy</label><select id="ss-f" name="s" data-autosubmit style="width:auto"><option value="">All strategies</option><?php foreach ($strategies as $s): ?><option value="<?= (int) $s['id'] ?>"<?= $filter === (int) $s['id'] ? ' selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach; ?></select></form></div>
    <div class="sess-bars">
      <?php foreach ($sessions as $k => $x): $w = $x['s']['win_rate']; ?>
      <div class="sess-bar"><strong><?= e($x['label']) ?></strong><span class="track" role="img" aria-label="<?= e($x['label']) ?> win rate <?= e(pct($w, 0)) ?>"><span class="<?= $w === null ? '' : ($w >= 0.5 ? 'good' : 'bad') ?>" style="width:<?= $w === null ? 0 : round($w * 100) ?>%"></span></span><span class="val"><?= e(pct($w, 0)) ?> <small class="muted">· <?= (int) $x['s']['trades'] ?> tr · <span class="<?= $sign($x['s']['net']) ?>"><?= e(money($x['s']['net'], $cur, true)) ?></span></small></span></div>
      <?php endforeach; ?>
    </div>
    <p class="panel-note">Asian = before London opens; London = 08:00 London time until New York opens; New York = 08:00–17:00 New York time. Daylight-saving changes are handled.</p>
    <h3 style="margin:12px 0 6px">Net P&amp;L by strategy</h3>
    <div class="chart" data-chart="bars" data-format="money:<?= e($cur) ?>" data-label="Net P&L by strategy" data-json="<?= e(json_encode($bars)) ?>"></div>
  </section>
</div>
<?php endif; ?>

<div class="grid g-3">
  <?php foreach ($strategies as $s): $st = $stats[(string) $s['id']] ?? null; $rules = array_filter(preg_split('/\R/', (string) $s['checklist'])); $setups = array_filter(preg_split('/\R/', (string) ($s['setups'] ?? ''))); ?>
  <section class="panel strat-card" id="s<?= (int) $s['id'] ?>">
    <div class="panel-head"><h2><?= e($s['name']) ?><?= $s['active'] ? '' : ' <span class="badge">inactive</span>' ?></h2><a class="tm-btn tm-btn-sm" href="<?= e(url('/terminal/strategies/' . $s['id'] . '/edit')) ?>"><?= icon('edit', 'icon icon-sm') ?> Edit</a></div>
    <p class="muted small" style="margin:0"><?= e(Domain::STRATEGY_STYLES[$s['style'] ?? ''] ?? 'No style set') ?><?= $s['target_rr'] ? ' · target 1:' . e(rtrim(rtrim((string) $s['target_rr'], '0'), '.')) : '' ?></p>
    <?php if ($st): ?>
    <div class="rings"><?= $ring($st['win_rate'], 'Win rate', ($st['win_rate'] ?? 0) >= 0.5 ? 'good' : 'bad') ?><?= $ring($st['compliance'], 'Rule compliance', ($st['compliance'] ?? 0) >= 0.8 ? 'good' : 'bad') ?></div>
    <div class="strat-kpis">
      <div><small>P&amp;L</small><strong class="<?= $sign($st['net']) ?>"><?= e(money($st['net'], $cur, true)) ?></strong></div>
      <div><small>Avg R</small><strong class="<?= $sign($st['avg_r']) ?>"><?= $st['avg_r'] === null ? '—' : e(($st['avg_r'] > 0 ? '+' : '') . number_format($st['avg_r'], 2)) . 'R' ?></strong></div>
      <div><small>Trades</small><strong><?= (int) $st['trades'] ?></strong></div>
      <div><small>Profit factor</small><strong class="<?= $st['profit_factor'] === null ? 'up' : $sign($st['profit_factor'] - 1) ?>"><?= $st['profit_factor'] === null ? '∞' : e(number_format($st['profit_factor'], 2)) ?></strong></div>
    </div>
    <?php else: ?><p class="muted small">No closed trades tagged with this strategy in this account yet.</p><?php endif; ?>
    <?php if ($s['thesis']): ?><details><summary class="small strong">Edge / thesis</summary><p class="small" style="white-space:pre-wrap;margin-top:6px"><?= e($s['thesis']) ?></p></details><?php endif; ?>
    <?php if ($rules): ?><details><summary class="small strong">Rules (<?= count($rules) ?>)</summary><ol class="small" style="margin:6px 0 0;padding-left:18px"><?php foreach ($rules as $r): ?><li><?= e($r) ?></li><?php endforeach; ?></ol></details><?php endif; ?>
    <?php if ($setups): ?><div class="chips" style="margin:0"><?php foreach ($setups as $su): ?><span class="badge"><?= e($su) ?></span><?php endforeach; ?></div><?php endif; ?>
  </section>
  <?php endforeach; ?>
  <a class="panel calc-link" href="<?= e(url('/terminal/strategies/new')) ?>"><?= icon('plus', 'icon') ?><div><strong>Add personal strategy</strong><small class="muted">Name, style, edge, rules and sub-setups</small></div></a>
</div>
