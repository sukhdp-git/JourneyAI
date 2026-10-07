<?php
use App\Controllers\Terminal\EdgeController;
use App\Trading\Edge;
$cur = $acc['currency'];
$money = fn ($v, $sign = true) => money($v, $cur, $sign);
$stat = fn (array $s) => '<span class="num">' . (int) $s['trades'] . ((int) $s['trades'] === 1 ? ' trade' : ' trades') . '</span> · ' . e(pct($s['win_rate'], 0)) . ' win' . ($s['avg_r'] !== null ? ' · ' . e(($s['avg_r'] > 0 ? '+' : '') . number_format($s['avg_r'], 2)) . 'R avg' : '');
?>
<?php if (!$sum['trades']): ?><div class="panel empty"><?= icon('grid', 'icon') ?><p>The Edge Matrix needs closed trades. <a href="<?= e(url('/terminal/trades/new')) ?>">Log trades</a> or load the demo journal from the Home Hub.</p></div><?php else: ?>
<p class="muted small">Historical analysis of <strong><?= e($acc['name']) ?></strong><?= (int) $acc['has_demo_data'] ? ' (DEMO DATA)' : '' ?>. These describe what happened in your journal — <strong>historical performance does not guarantee future results.</strong></p>
<div class="edge-grid">

  <!-- 1. Last Month Audit -->
  <section class="panel edge-card">
    <div class="panel-head"><h2><?= icon('calendar', 'icon icon-sm') ?> Last month audit</h2><span class="muted small"><?= e($auditLabel) ?> · <?= (int) $audit['summary']['trades'] ?> trades · <strong class="<?= $audit['summary']['net'] >= 0 ? 'up' : 'down' ?>"><?= e($money($audit['summary']['net'])) ?></strong></span></div>
    <?php if (!$audit['summary']['trades']): ?><p class="muted">No trades in that month.</p><?php else: ?>
    <div class="edge-cols">
      <div>
        <h3 class="up" style="margin-bottom:8px">High-quality / positive edge</h3>
        <?php foreach ($audit['positive'] as $p): ?>
          <div class="edge-item good"><div><small><?= e($p['dim']) ?></small><strong><?= e($p['key']) ?></strong><small><?= $stat($p['s']) ?></small></div><div class="v up"><?= e($money($p['s']['net'])) ?></div></div>
        <?php endforeach; ?>
        <?php if (!$audit['positive']): ?><p class="muted small">No group had a positive result with at least 2 trades.</p><?php else: ?><p class="muted small">Historically strongest during this period.</p><?php endif; ?>
      </div>
      <div>
        <h3 class="down" style="margin-bottom:8px">Negative expectancy traps</h3>
        <?php foreach (array_slice($audit['negative'], 0, 8) as $n): ?>
          <div class="edge-item bad"><div><small><?= e($n['dim']) ?></small><strong><?= e($n['key']) ?></strong><small><?= $stat($n['s']) ?></small></div><div class="v down"><?= e($money($n['s']['net'])) ?></div></div>
        <?php endforeach; ?>
        <?php if (!$audit['negative']): ?><p class="muted small">No losing trap found — no session, hour, instrument, strategy or tagged behaviour lost money.</p><?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </section>

  <!-- 2. Best trading window + A+ -->
  <section class="panel edge-card">
    <div class="panel-head"><h2><?= icon('target', 'icon icon-sm') ?> Best trading window</h2><span class="muted small">All history · min. <?= Edge::MIN_WINDOW ?> trades per combination</span></div>
    <?php if ($best): ?>
    <div class="window-card">
      <div class="eyebrow">YOUR HISTORICALLY STRONGEST WINDOW</div>
      <div class="headline"><?= e(Edge::windowName($best)) ?></div>
      <div class="sub"><?= $best['setup'] && $best['setup'] !== $best['strategy'] ? 'Most common setup: <strong>' . e($best['setup']) . '</strong> · ' : '' ?>Best hours: <strong><?= e($best['best_hours']['label']) ?></strong> (<?= (int) $best['best_hours']['s']['trades'] ?> trades, <?= e($money($best['best_hours']['s']['net'])) ?>)</div>
      <div class="window-stats">
        <div><small>Win rate</small><strong class="<?= $best['s']['win_rate'] >= 0.5 ? 'up' : 'down' ?>"><?= e(pct($best['s']['win_rate'], 0)) ?></strong></div>
        <div><small>Average R</small><strong class="up"><?= $best['s']['avg_r'] === null ? '—' : e(($best['s']['avg_r'] > 0 ? '+' : '') . number_format($best['s']['avg_r'], 2)) . 'R' ?></strong></div>
        <div><small>Net P&amp;L</small><strong class="up"><?= e($money($best['s']['net'])) ?></strong></div>
        <div><small>Profit factor</small><strong><?= $best['s']['profit_factor'] === null ? '∞' : e(number_format($best['s']['profit_factor'], 2)) ?></strong></div>
        <div><small>Sample size</small><strong><?= (int) $best['s']['trades'] ?> trades</strong></div>
      </div>
      <?php if ($aplus): ?>
      <div class="aplus" role="note">
        <strong class="tag">A+ HISTORICAL SETUP</strong>
        <p style="margin:6px 0 0">Your historical results show unusually strong performance for this window (<?= (int) $aplus['s']['trades'] ?> trades, <?= e(pct($aplus['s']['win_rate'], 0)) ?> win rate, <?= e(number_format($aplus['s']['avg_r'], 2)) ?>R average). If it remains within your personal risk plan, you may consider your configured higher-risk tier<?= $m['a_plus_risk_pct'] ? ' (<strong>' . e(rtrim(rtrim((string) $m['a_plus_risk_pct'], '0'), '.')) . '%</strong>)' : ' (for example 1.5%–1.75% — <a href="' . e(url('/terminal/settings#risk')) . '">set yours in Settings</a>)' ?>.</p>
        <p class="small muted" style="margin:4px 0 0">Historical performance does not guarantee future results. Never exceed your own maximum risk rules.</p>
      </div>
      <?php else: ?>
      <p class="muted small" style="margin:10px 0 0">A+ setup detection needs at least <?= Edge::APLUS['trades'] ?> trades with ≥<?= (int) (Edge::APLUS['win_rate'] * 100) ?>% win rate, ≥<?= Edge::APLUS['avg_r'] ?>R average and profit factor ≥<?= Edge::APLUS['profit_factor'] ?> — not met yet, so no higher-risk suggestion is shown.</p>
      <?php endif; ?>
    </div>
    <?php else: ?><p class="muted">More trading data is required to identify a strongest window reliably (at least <?= Edge::MIN_WINDOW ?> trades in the same session and instrument or strategy, with a positive result).</p><?php endif; ?>
    <div class="kpis" style="margin:12px 0 0">
      <?php foreach ($dims as $label => $g): ?><div class="kpi"><small>Best <?= e(strtolower($label)) ?></small><strong><?= e($g['key'] ?? '—') ?></strong><em><?= $g ? e($money($g['s']['expectancy'])) . ' / trade · ' . $g['s']['trades'] . ' trades' : 'Needs ≥ ' . Edge::MIN_GROUP . ' trades' ?></em></div><?php endforeach; ?>
    </div>
  </section>

  <!-- 3. Anti-window -->
  <section class="panel edge-card">
    <div class="panel-head"><h2 class="down"><?= icon('alert', 'icon icon-sm') ?> Anti-window detector</h2></div>
    <?php if ($anti): ?>
    <div class="window-card anti" role="alert">
      <div class="eyebrow">ANTI-WINDOW DETECTED · HISTORICALLY WEAK</div>
      <div class="headline"><?= e(Edge::windowName($anti)) ?></div>
      <div class="sub">Weakest hours: <strong><?= e($anti['worst_hours']['label']) ?></strong> (<?= (int) $anti['worst_hours']['s']['trades'] ?> trades, <?= e($money($anti['worst_hours']['s']['net'])) ?>)</div>
      <div class="window-stats">
        <div><small>Win rate</small><strong class="down"><?= e(pct($anti['s']['win_rate'], 0)) ?></strong></div>
        <div><small>Net P&amp;L</small><strong class="down"><?= e($money($anti['s']['net'])) ?></strong></div>
        <div><small>Average R</small><strong class="<?= ($anti['s']['avg_r'] ?? 0) < 0 ? 'down' : '' ?>"><?= $anti['s']['avg_r'] === null ? '—' : e(($anti['s']['avg_r'] > 0 ? '+' : '') . number_format($anti['s']['avg_r'], 2)) . 'R' ?></strong></div>
        <div><small>Sample size</small><strong><?= (int) $anti['s']['trades'] ?> trades</strong></div>
      </div>
      <p style="margin:10px 0 0"><strong>Consider reducing risk significantly or avoiding this window until your data improves.</strong></p>
    </div>
    <?php else: ?><p class="muted">No historically losing window with at least <?= Edge::MIN_WINDOW ?> trades. More data may reveal one.</p><?php endif; ?>
  </section>

  <!-- 4. Ruin Probability Radar -->
  <section class="panel edge-card" data-radar>
    <div class="panel-head"><h2><?= icon('activity', 'icon icon-sm') ?> Ruin probability radar</h2>
      <form method="get" action="<?= e(url('/terminal/edge')) ?>"><label class="small muted" for="dd-th">Severe drawdown =</label> <select id="dd-th" name="dd" data-autosubmit style="width:auto"><?php foreach (EdgeController::THRESHOLDS as $k => $l): ?><option value="<?= $k ?>"<?= $th === (string) $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></form></div>
    <?php if (!$radar['ok']): ?><p class="muted">At least <?= (int) $radar['needed'] ?> closed trades with a stop loss are needed (you have <?= (int) $radar['have'] ?>).</p><?php else:
      $sc = $radar['scenarios']; $cp = $radar['current_p']; ?>
    <div class="grid g-2">
      <div>
        <p class="muted small" style="margin:0">Current risk behaviour (<?= e(rtrim(rtrim(number_format($radar['current'], 2), '0'), '.')) ?>% per trade)</p>
        <p class="small" style="margin:4px 0">Estimated 14-day probability of a ≥<?= (int) round($radar['threshold'] * 100) ?>% drawdown:</p>
        <div class="radar-big <?= $cp >= 0.25 ? 'down' : ($cp >= 0.1 ? 'warn' : 'up') ?>" data-radar-p><?= e(number_format($cp * 100, 1)) ?>%</div>
        <div class="radar-meter" aria-hidden="true"><i data-radar-mark style="left:<?= min(100, $cp * 100) ?>%"></i></div>
        <div class="small muted" style="display:flex;justify-content:space-between"><span>Low</span><span>High</span></div>
        <p class="small" style="margin-top:10px">Try another risk level:</p>
        <div class="radar-scen" role="radiogroup" aria-label="Risk per trade scenario">
          <?php foreach ($sc as $x): ?><button type="button" class="chip-btn<?= abs($x['risk'] - $radar['current']) < 0.001 ? ' on' : '' ?>" data-scen='<?= e(json_encode($x)) ?>'><?= e(rtrim(rtrim(number_format($x['risk'], 2), '0'), '.')) ?>%</button><?php endforeach; ?>
        </div>
        <p class="small" data-radar-detail>Median worst drawdown <?= e(number_format($sc[array_search($radar['current'], array_column($sc, 'risk'))]['median_dd'] * 100, 1)) ?>% · bad case (95th pct) <?= e(number_format($sc[array_search($radar['current'], array_column($sc, 'risk'))]['p95_dd'] * 100, 1)) ?>%</p>
      </div>
      <div>
        <h3 style="margin-bottom:8px">Probability by risk per trade</h3>
        <div class="sess-bars">
          <?php foreach ($sc as $x): ?><div class="sess-bar"><span><?= e(rtrim(rtrim(number_format($x['risk'], 2), '0'), '.')) ?>%<?= abs($x['risk'] - $radar['current']) < 0.001 ? ' (now)' : '' ?></span><span class="track"><span class="<?= $x['p'] >= 0.25 ? 'bad' : ($x['p'] < 0.1 ? 'good' : '') ?>" style="width:<?= max(1, min(100, $x['p'] * 100)) ?>%;<?= $x['p'] >= 0.1 && $x['p'] < 0.25 ? 'background:var(--warn)' : '' ?>"></span></span><span class="val"><?= e(number_format($x['p'] * 100, 1)) ?>%</span></div><?php endforeach; ?>
        </div>
        <h3 style="margin:12px 0 6px">Recent behaviour used</h3>
        <dl class="dl small">
          <dt>Sample</dt><dd><?= (int) $radar['inputs']['trades'] ?> trades · <?= e($radar['basis']) ?></dd>
          <dt>Win rate</dt><dd><?= e(pct($radar['inputs']['win_rate'], 0)) ?> · avg win <?= e((string) $radar['inputs']['avg_win_r']) ?>R · avg loss <?= e((string) $radar['inputs']['avg_loss_r']) ?>R</dd>
          <dt>Activity</dt><dd><?= e((string) $radar['inputs']['per_day']) ?> trades/day → <?= (int) $radar['horizon'] ?> trades in the next ~14 days</dd>
          <dt>Behaviour</dt><dd>FOMO <?= (int) $radar['inputs']['fomo'] ?> · revenge <?= (int) $radar['inputs']['revenge'] ?> · overtrading <?= (int) $radar['inputs']['overtrading'] ?> · longest losing streak <?= (int) $radar['inputs']['max_loss_streak'] ?></dd>
        </dl>
      </div>
    </div>
    <p class="panel-note"><strong>Statistical simulation based on recent historical behaviour — not a prediction.</strong> <?= (int) $radar['paths'] ?> simulated paths re-sample your own recent R results (including any FOMO/revenge trades) at each risk level. Real outcomes can differ.</p>
    <?php endif; ?>
  </section>

  <!-- 5. Discipline leak -->
  <section class="panel edge-card" id="leak">
    <div class="leak-hero">
      <div class="headline"><?= $leak['leak'] > 0 ? 'Your emotions cost you <b>' . e(money($leak['leak'], $cur)) . '</b> ' . e($leakLabel) : 'No discipline leak ' . e($leakLabel) ?></div>
      <p class="small" style="margin:6px 0 0">Actual P&amp;L <strong class="<?= $leak['actual'] >= 0 ? 'up' : 'down' ?>"><?= e($money($leak['actual'])) ?></strong> vs rule-compliant / emotion-filtered <strong><?= e($money($leak['flawless'])) ?></strong> · <?= (int) $leak['violations'] ?> <?= (int) $leak['violations'] === 1 ? 'trade' : 'trades' ?> with mistakes or broken rules</p>
    </div>
    <?php if (count($leak['curve']['actual']) > 1): ?>
    <div class="legend"><span><i style="background:var(--series-1)"></i>Actual equity curve (cumulative P&amp;L)</span><span><i style="background:var(--neg)"></i>Rule-compliant hypothetical</span></div>
    <div class="chart" data-chart="lines" data-format="money:<?= e($cur) ?>" data-height="220" data-label="Actual versus rule-compliant cumulative P&L" data-json="<?= e(json_encode(['base' => 0, 'series' => [['name' => 'Actual', 'cls' => 's1', 'points' => $leak['curve']['actual']], ['name' => 'Rule-compliant', 'cls' => 's2 dash', 'points' => $leak['curve']['filtered']]]])) ?>"></div>
    <?php endif; ?>
    <?php if ($leak['by']): ?>
    <h3 style="margin:10px 0 6px">Where the leak came from</h3>
    <?php foreach ($leak['by'] as $b): ?><div class="edge-item bad"><div><strong><?= e($b['label']) ?></strong><small><?= (int) $b['trades'] ?> <?= (int) $b['trades'] === 1 ? 'trade' : 'trades' ?> · actual <?= e($money($b['actual'])) ?></small></div><div class="v down">−<?= e(money($b['leak'], $cur)) ?></div></div><?php endforeach; ?>
    <?php endif; ?>
    <p class="panel-note">Historical hypothetical, not guaranteed profit: trades tagged FOMO, revenge, overtrading, chasing, impulsive or rule-breaking are treated as not taken, and moved-stop / oversized losses are capped at 1R. No hypothetical profit is ever added.</p>
  </section>

  <section class="panel">
    <div class="panel-head"><h2><?= icon('alert', 'icon icon-sm') ?> Tilt circuit breaker</h2><a class="small" href="<?= e(url('/terminal/settings#risk')) ?>">Change rule</a></div>
    <p>Rule: <strong><?= $tiltRule[0] ?> losses within <?= $tiltRule[1] ?> minutes</strong> triggers a <?= $tiltRule[2] ?>-minute cooldown of quick logging in this terminal. journzey.ai cannot block orders at your broker.</p>
  </section>
</div>
<?php endif; ?>
