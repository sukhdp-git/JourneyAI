<?php
use App\Controllers\Terminal\EdgeController;
use App\Trading\Edge;
use App\Trading\Runner;
$cur = $acc['currency'];
$money = fn ($v, $sign = true) => money($v, $cur, $sign);
$tone = fn ($v) => $v === null ? '' : ((float) $v >= 0 ? 'up' : 'down');
$num = fn ($v, $d = 2) => rtrim(rtrim(number_format((float) $v, $d), '0'), '.');
$rFmt = fn ($v) => $v === null ? '—' : (($v > 0 ? '+' : '') . number_format((float) $v, 2) . 'R');
/** Card header with an icon, title, one-line subtitle and a "?" help popover. */
$head = function (string $id, string $icon, string $title, string $sub, string $helpTitle, array $help, string $extra = '') {
    $h = '<header class="ex-head"><span class="ex-icon">' . icon($icon, 'icon icon-sm') . '</span><div class="ex-title"><h2>' . e($title) . '</h2>' . ($sub !== '' ? '<p>' . $sub . '</p>' : '') . '</div>' . $extra;
    $h .= '<button type="button" class="ex-help-btn" popovertarget="help-' . $id . '" aria-label="' . e('What does ' . $title . ' show?') . '">?</button></header>';
    $h .= '<div class="ex-help" id="help-' . $id . '" popover><div class="ex-help-top"><span class="ex-icon">' . icon($icon, 'icon icon-sm') . '</span><h3>' . e($helpTitle) . '</h3></div><ul>';
    foreach ($help as $line) {
        $h .= '<li>' . $line . '</li>';
    }
    return $h . '</ul><button type="button" class="tm-btn tm-btn-sm tm-btn-primary" popovertarget="help-' . $id . '" popovertargetaction="hide">Got it</button></div>';
};
?>
<?php if (!$sum['trades']): ?><div class="panel empty"><?= icon('grid', 'icon') ?><p>The Edge Matrix needs closed trades. <a href="<?= e(url('/terminal/trades/new')) ?>">Log trades</a> or load the demo journal from the Home Hub.</p></div><?php else:
  $rp = $radar['ok'] ? $radar['current_p'] : null; ?>

<section class="ex-hero">
  <div class="ex-hero-text">
    <span class="ex-kicker"><?= icon('sparkles', 'icon icon-xs') ?> Edge Matrix<?= (int) $acc['has_demo_data'] ? ' · DEMO DATA' : '' ?></span>
    <h2>Your trading edge, decoded.</h2>
    <p><?= e($acc['name']) ?> · last 30 days. Tap <b class="ex-q">?</b> on any card to see what it means.</p>
  </div>
  <div class="ex-hero-stats">
    <div class="ex-stat"><small>Net P&amp;L · 30d</small><strong class="<?= $tone($last30['net']) ?>"><?= e($money($last30['net'])) ?></strong><em><?= (int) $last30['trades'] ?> trades</em></div>
    <div class="ex-stat"><small>Win rate</small><strong class="<?= $last30['trades'] ? ($last30['win_rate'] >= 0.5 ? 'up' : 'down') : '' ?>"><?= $last30['trades'] ? e(pct($last30['win_rate'], 0)) : '—' ?></strong><em>last 30 days</em></div>
    <div class="ex-stat"><small>Profit factor</small><strong class="<?= $last30['profit_factor'] === null ? ($last30['trades'] ? 'up' : '') : ($last30['profit_factor'] >= 1 ? 'up' : 'down') ?>"><?= $last30['trades'] ? ($last30['profit_factor'] === null ? '∞' : e(number_format($last30['profit_factor'], 2))) : '—' ?></strong><em>gross win ÷ gross loss</em></div>
    <div class="ex-stat ex-stat-risk"><small>Blow-up risk · 14d</small><strong class="<?= $rp === null ? '' : $radar['level'][1] ?>"><?= $rp === null ? '—' : e(number_format($rp * 100, 1)) . '%' ?></strong><em><?= $rp === null ? 'needs 8 trades' : e($radar['level'][0]) . ' risk' ?></em></div>
  </div>
</section>

<div class="ex-board">

  <!-- Last month audit (compact) -->
  <section class="ex-card c-blue ex-span-2">
    <?= $head('audit', 'calendar', 'Last month audit', e($auditLabel) . ' · ' . (int) $audit['summary']['trades'] . ' trades · <b class="' . $tone($audit['summary']['net']) . '">' . e($money($audit['summary']['net'])) . '</b>', 'Last month audit', [
        'Looks only at <b>last calendar month</b> and finds what made money and what lost money.',
        '<b class="up">Green chips</b> = your best strategy, instrument, session and hour last month.',
        '<b class="down">Red chips</b> = traps: the groups and behaviours (FOMO, revenge, broken rules…) that cost you money.',
        'Use it as your monthly to-do: do more of the green, cut the red.',
    ]) ?>
    <?php if (!$audit['summary']['trades']): ?><p class="ex-empty">No trades in that month.</p><?php else: ?>
    <div class="ex-audit">
      <div class="ex-audit-row"><span class="ex-audit-label up"><?= icon('trend-up', 'icon icon-xs') ?> Keep doing</span>
        <div class="ex-chips"><?php foreach (array_slice($audit['positive'], 0, 4) as $p): ?><span class="ex-chip good" title="<?= e($p['dim'] . ': ' . (int) $p['s']['trades'] . ' trades, ' . pct($p['s']['win_rate'], 0) . ' win') ?>"><small><?= e($p['dim']) ?></small><?= e($p['key']) ?> <b><?= e($money($p['s']['net'])) ?></b></span><?php endforeach; ?>
        <?php if (!$audit['positive']): ?><span class="muted small">No group made money with 2+ trades.</span><?php endif; ?></div></div>
      <div class="ex-audit-row"><span class="ex-audit-label down"><?= icon('trend-down', 'icon icon-xs') ?> Cut out</span>
        <div class="ex-chips"><?php foreach (array_slice($audit['negative'], 0, 4) as $n): ?><span class="ex-chip bad" title="<?= e($n['dim'] . ': ' . (int) $n['s']['trades'] . ' trades, ' . pct($n['s']['win_rate'], 0) . ' win') ?>"><small><?= e($n['dim']) ?></small><?= e($n['key']) ?> <b><?= e($money($n['s']['net'])) ?></b></span><?php endforeach; ?>
        <?php if (!$audit['negative']): ?><span class="muted small">No losing trap found. Clean month.</span><?php endif; ?></div></div>
    </div>
    <?php endif; ?>
  </section>

  <!-- Best trading window -->
  <section class="ex-card c-green">
    <?= $head('best', 'target', 'Best trading window', 'Where you historically win most', 'Best trading window', [
        'Scans <b>all your history</b> for the session + instrument (or strategy) combination with the best results, needing at least ' . Edge::MIN_WINDOW . ' trades.',
        'It also finds the <b>2-hour slot</b> inside that session where you perform best.',
        '<b>A+ setup</b> appears only when a window is exceptional (' . Edge::APLUS['trades'] . '+ trades, ' . (int) (Edge::APLUS['win_rate'] * 100) . '%+ win rate, ' . Edge::APLUS['avg_r'] . 'R+ average, profit factor ' . Edge::APLUS['profit_factor'] . '+).',
        'Past results do not guarantee future results — treat it as where to focus, not a promise.',
    ]) ?>
    <?php if ($best): ?>
      <div class="ex-big"><?= e(Edge::windowName($best)) ?></div>
      <p class="ex-line"><?= icon('clock', 'icon icon-xs') ?> Best hours <b><?= e($best['best_hours']['label']) ?></b><?= $best['setup'] && $best['setup'] !== $best['strategy'] ? ' · setup <b>' . e($best['setup']) . '</b>' : '' ?></p>
      <div class="ex-mini">
        <div><small>Win rate</small><b class="<?= $best['s']['win_rate'] >= 0.5 ? 'up' : 'down' ?>"><?= e(pct($best['s']['win_rate'], 0)) ?></b></div>
        <div><small>Avg R</small><b class="<?= $tone($best['s']['avg_r']) ?>"><?= e($rFmt($best['s']['avg_r'])) ?></b></div>
        <div><small>Net</small><b class="<?= $tone($best['s']['net']) ?>"><?= e($money($best['s']['net'])) ?></b></div>
        <div><small>Trades</small><b><?= (int) $best['s']['trades'] ?></b></div>
      </div>
      <?php if ($aplus): ?><div class="ex-aplus"><b>A+ SETUP</b> Exceptional history here.<?= $m['a_plus_risk_pct'] ? ' Your A+ risk tier: <b>' . e($num($m['a_plus_risk_pct'])) . '%</b>.' : '' ?> <span class="muted">Never exceed your own max risk.</span></div><?php endif; ?>
    <?php else: ?><p class="ex-empty">Needs at least <?= Edge::MIN_WINDOW ?> profitable trades in the same session and instrument.</p><?php endif; ?>
    <?php if (array_filter($dims)): ?>
    <div class="ex-tags"><?php $seen = []; foreach ($dims as $label => $g): if (!$g || in_array($g['key'], $seen, true)) continue; $seen[] = $g['key']; ?><span><small><?= e($label) ?></small><?= e($g['key']) ?></span><?php endforeach; ?></div>
    <?php endif; ?>
  </section>

  <!-- Anti-window -->
  <section class="ex-card c-red">
    <?= $head('anti', 'alert', 'Anti-window', 'Where you historically lose most', 'Anti-window detector', [
        'The opposite of your best window: the session + instrument combination that <b>lost the most</b> (at least ' . Edge::MIN_WINDOW . ' trades).',
        'Shows the 2-hour slot where the losses cluster.',
        'If you see one, consider trading smaller there — or not at all — until your numbers improve.',
    ]) ?>
    <?php if ($anti): ?>
      <div class="ex-big"><?= e(Edge::windowName($anti)) ?></div>
      <p class="ex-line"><?= icon('clock', 'icon icon-xs') ?> Weakest hours <b><?= e($anti['worst_hours']['label']) ?></b></p>
      <div class="ex-mini">
        <div><small>Win rate</small><b class="down"><?= e(pct($anti['s']['win_rate'], 0)) ?></b></div>
        <div><small>Avg R</small><b class="<?= $tone($anti['s']['avg_r']) ?>"><?= e($rFmt($anti['s']['avg_r'])) ?></b></div>
        <div><small>Net</small><b class="down"><?= e($money($anti['s']['net'])) ?></b></div>
        <div><small>Trades</small><b><?= (int) $anti['s']['trades'] ?></b></div>
      </div>
      <div class="ex-callout bad"><?= icon('alert', 'icon icon-xs') ?> Reduce size or avoid this window until your data improves.</div>
    <?php else: ?><div class="ex-good-state"><?= icon('check-circle', 'icon') ?><p>No losing window found. Nice.</p></div><?php endif; ?>
  </section>

  <!-- Blow-up radar -->
  <section class="ex-card c-violet ex-span-2" data-radar>
    <?= $head('radar', 'activity', 'Blow-up probability radar', 'Chance your account is blown in the next 14 days', 'Blow-up probability radar', [
        'Reads your <b>last 30 days</b>: win rate, average reward-to-risk, drawdown and position size (how much of the account you risk per trade).',
        'It then replays those exact trades in random order ' . ($radar['ok'] ? number_format($radar['paths']) : '2,000') . ' times over the number of trades you usually take in <b>14 days</b>.',
        'The big number = the share of those futures where your account fell by your “blown” level (choose −5%, −10%, −20% or −50%).',
        '<b class="up">Low</b> under 5% · <b class="warn">Elevated</b> 5–20% · <b class="down">High</b> 20%+. Use the risk buttons to see how a smaller or bigger position changes it.',
        'A statistical estimate from your own history — not a prediction.',
    ], '<form method="get" action="' . e(url('/terminal/edge')) . '" class="ex-head-form"><label class="sr-only" for="dd-th">Account blown when it falls</label><select id="dd-th" name="dd" data-autosubmit>' . implode('', array_map(fn ($k, $l) => '<option value="' . $k . '"' . ($th === (string) $k ? ' selected' : '') . '>Blown at ' . e($l) . '</option>', array_keys(EdgeController::THRESHOLDS), EdgeController::THRESHOLDS)) . '</select></form>') ?>
    <?php if (!$radar['ok']): ?><p class="ex-empty">Needs at least <?= (int) $radar['needed'] ?> closed trades with a stop loss (you have <?= (int) $radar['have'] ?>).</p><?php else:
      $in = $radar['inputs']; $len = 157.08; $sc = $radar['scenarios']; ?>
    <div class="ex-radar">
      <div class="ex-gauge-wrap">
        <svg class="ex-gauge" viewBox="0 0 120 70" role="img" aria-label="<?= e(number_format($rp * 100, 1)) ?>% probability of a <?= (int) round($radar['threshold'] * 100) ?>% drawdown in 14 days">
          <defs><linearGradient id="g-gauge" x1="0" x2="1"><stop offset="0" stop-color="var(--pos)"/><stop offset=".5" stop-color="var(--warn)"/><stop offset="1" stop-color="var(--neg)"/></linearGradient></defs>
          <path d="M10 62 A50 50 0 0 1 110 62" class="ex-gauge-track"/>
          <path d="M10 62 A50 50 0 0 1 110 62" class="ex-gauge-fill" stroke="url(#g-gauge)" style="stroke-dasharray:<?= round(max(0.02, min(1, $rp)) * $len, 2) ?> <?= $len ?>"/>
        </svg>
        <div class="ex-gauge-val"><strong class="<?= $radar['level'][1] ?>"><?= e(number_format($rp * 100, 1)) ?>%</strong><span class="ex-level <?= $radar['level'][1] ?>"><?= e($radar['level'][0]) ?> risk</span></div>
        <p class="ex-gauge-cap">chance of losing <b><?= (int) round($radar['threshold'] * 100) ?>%</b> from peak in the next 14 days at your current behaviour</p>
      </div>
      <div class="ex-radar-inputs">
        <h3>What your last 30 days look like</h3>
        <div class="ex-inputs">
          <div><small>Win rate</small><b class="<?= $in['win_rate'] >= 0.5 ? 'up' : 'down' ?>"><?= e(pct($in['win_rate'], 0)) ?></b></div>
          <div><small>Avg reward : risk</small><b class="<?= $in['rr'] !== null && $in['rr'] >= 1 ? 'up' : 'down' ?>"><?= $in['rr'] === null ? '∞' : '1 : ' . e(number_format($in['rr'], 2)) ?></b></div>
          <div><small>Max drawdown</small><b class="<?= $in['max_dd'] > 0 ? 'down' : 'up' ?>"><?= $in['max_dd'] > 0 ? '−' : '' ?><?= e(number_format($in['max_dd'] * 100, 1)) ?>%</b></div>
          <div><small>Avg drawdown</small><b class="<?= $in['avg_dd'] > 0 ? 'down' : 'up' ?>"><?= $in['avg_dd'] > 0 ? '−' : '' ?><?= e(number_format($in['avg_dd'] * 100, 1)) ?>%</b></div>
          <div><small>Avg position risk</small><b class="<?= ($in['risk_avg'] ?? 0) > 2 ? 'down' : '' ?>"><?= $in['risk_avg'] === null ? e($num($radar['current'])) . '%' : e($num($in['risk_avg'])) . '%' ?></b></div>
          <div><small>Biggest position risk</small><b class="<?= ($in['risk_max'] ?? 0) > 2 ? 'down' : '' ?>"><?= $in['risk_max'] === null ? '—' : e($num($in['risk_max'])) . '%' ?></b></div>
          <div><small>Trades / day</small><b><?= e((string) $in['per_day']) ?></b></div>
          <div><small>Worst losing streak</small><b class="<?= $in['max_loss_streak'] >= 3 ? 'down' : '' ?>"><?= (int) $in['max_loss_streak'] ?></b></div>
        </div>
        <div class="ex-whatif">
          <p><b>What if you risked…</b> <span class="muted">per trade</span></p>
          <div class="ex-scen" role="group" aria-label="Risk per trade scenario">
            <?php foreach ($sc as $x): $on = abs($x['risk'] - $radar['current']) < 0.001; ?><button type="button" class="ex-scen-btn <?= $x['p'] >= 0.2 ? 'bad' : ($x['p'] >= 0.05 ? 'mid' : 'good') ?><?= $on ? ' on' : '' ?>" data-scen='<?= e(json_encode($x)) ?>'><span><?= e($num($x['risk'])) ?>%</span><b><?= e(number_format($x['p'] * 100, 1)) ?>%</b></button><?php endforeach; ?>
          </div>
          <p class="small" data-radar-detail>At <?= e($num($radar['current'])) ?>% per trade (your median): typical worst dip −<?= e(number_format($radar['median_dd'] * 100, 1)) ?>%, bad case −<?= e(number_format($radar['p95_dd'] * 100, 1)) ?>%.</p>
        </div>
      </div>
    </div>
    <p class="ex-foot">Based on <?= (int) $in['trades'] ?> trades (<?= e($radar['basis']) ?>) · ~<?= (int) $radar['horizon'] ?> trades simulated over 14 days · statistical estimate, not a prediction.</p>
    <?php endif; ?>
  </section>

  <!-- Runner audit -->
  <section class="ex-card c-cyan" data-runner-card>
    <?= $head('runner', 'trend-up', '20% runner audit', 'What if you let 20% run 2 more hours?', '20% runner audit', [
        'For every <b>winning</b> trade, the system checks what price did in the <b>' . Runner::HOURS . ' hours after you closed</b>.',
        'It imagines you had closed 80% where you did, kept <b>20% open</b>, and moved its stop to breakeven (your entry).',
        '<b class="up">Green</b> = the runner would have added profit. <b class="down">Red</b> = closing everything was better.',
        'Tells you whether you tend to exit too early. Hypothetical — not a recommendation.',
    ]) ?>
    <?php if (!$runnerReady): ?>
      <p class="ex-empty">Needs market price history. The site owner can switch it on in Control Panel → Integrations (market data).</p>
    <?php elseif (!$runner['count']): ?>
      <p class="ex-empty"><?= $runnerPending ? 'Ready to check ' . (int) $runnerPending . ' winning trades.' : 'No winning trades with a stop loss to check yet.' ?></p>
    <?php else: ?>
      <div class="ex-big <?= $tone($runner['extra_pnl'] ?? $runner['extra_r']) ?>"><?= $runner['extra_pnl'] !== null ? e($money($runner['extra_pnl'])) : e($rFmt($runner['extra_r'])) ?></div>
      <p class="ex-line"><?= $runner['extra_pnl'] !== null ? e($rFmt($runner['extra_r'])) . ' · ' : '' ?><?= (int) $runner['count'] ?> trades checked<?= $runner['demo'] ? ' · <b class="warn">DEMO — simulated prices</b>' : '' ?></p>
      <div class="ex-split" aria-label="Runner outcomes">
        <?php $tot = max(1, $runner['count']); ?>
        <span class="good" style="flex:<?= $runner['helped'] ?>"></span><span class="flat" style="flex:<?= $runner['flat'] ?>"></span><span class="bad" style="flex:<?= $runner['hurt'] ?>"></span>
      </div>
      <p class="ex-legend"><span class="up">● <?= (int) $runner['helped'] ?> would gain</span><span class="down">● <?= (int) $runner['hurt'] ?> would give back</span><span class="muted"><?= (int) $runner['stopped'] ?> stopped at breakeven</span></p>
      <p class="ex-callout <?= ($runner['extra_r'] ?? 0) > 0 ? 'good' : 'bad' ?>"><?= ($runner['extra_r'] ?? 0) > 0 ? 'You often exit early — a small runner has historically added profit.' : 'Your exits have been well-timed — runners would mostly have given profit back.' ?></p>
    <?php endif; ?>
    <?php if ($runnerReady && $runnerPending): ?>
      <button type="button" class="tm-btn tm-btn-sm ex-runner-btn" data-runner-run="<?= e(url('/terminal/edge/runner')) ?>"><?= icon('refresh', 'icon icon-sm') ?> Check <?= min(Runner::BATCH, $runnerPending) ?> more trade<?= min(Runner::BATCH, $runnerPending) === 1 ? '' : 's' ?></button>
      <p class="small muted" data-runner-msg aria-live="polite"></p>
    <?php endif; ?>
  </section>

  <!-- Discipline leak -->
  <section class="ex-card c-amber" id="leak">
    <?= $head('leak', 'heart', 'Discipline leak', 'What emotions cost you ' . e($leakLabel), 'Discipline leak', [
        'Compares your real P&amp;L with a version where you <b>skipped every emotional or rule-breaking trade</b> (FOMO, revenge, overtrading, chasing, impulsive, broken rules).',
        'Losses from moved stops or oversized positions are capped at a normal 1R loss.',
        'The difference is your <b>discipline leak</b> — money lost to behaviour, not to the market.',
        'No hypothetical profit is ever added.',
    ]) ?>
    <div class="ex-big <?= $leak['leak'] > 0 ? 'down' : 'up' ?>"><?= $leak['leak'] > 0 ? '−' . e(money($leak['leak'], $cur)) : e(money(0, $cur)) ?></div>
    <p class="ex-line">Actual <b class="<?= $tone($leak['actual']) ?>"><?= e($money($leak['actual'])) ?></b> vs disciplined <b class="<?= $tone($leak['flawless']) ?>"><?= e($money($leak['flawless'])) ?></b></p>
    <?php if (count($leak['curve']['actual']) > 1): ?>
    <div class="chart" data-chart="lines" data-format="money:<?= e($cur) ?>" data-height="150" data-label="Actual versus disciplined cumulative P&L" data-json="<?= e(json_encode(['base' => 0, 'series' => [['name' => 'Actual', 'cls' => 's1', 'points' => $leak['curve']['actual']], ['name' => 'Disciplined', 'cls' => 's2 dash', 'points' => $leak['curve']['filtered']]]])) ?>"></div>
    <div class="legend"><span><i style="background:var(--series-1)"></i>Actual</span><span><i style="background:var(--neg)"></i>Disciplined</span></div>
    <?php endif; ?>
    <?php if ($leak['by']): ?><div class="ex-chips"><?php foreach (array_slice($leak['by'], 0, 4) as $b): ?><span class="ex-chip bad"><?= e($b['label']) ?> <b>−<?= e(money($b['leak'], $cur)) ?></b></span><?php endforeach; ?></div><?php endif; ?>
  </section>

  <!-- Tilt rule -->
  <section class="ex-card c-pink ex-span-2 ex-slim">
    <?= $head('tilt', 'shield', 'Tilt circuit breaker', '<b>' . $tiltRule[0] . ' losses in ' . $tiltRule[1] . ' min</b> → ' . $tiltRule[2] . '-minute cooldown on quick logging · <a href="' . e(url('/terminal/settings#risk')) . '">Change rule</a>', 'Tilt circuit breaker', [
        'Tilt = trading emotionally after a run of losses.',
        'When you hit the loss count inside the time window, quick logging pauses for the cooldown so you step away.',
        'journzey.ai cannot block orders at your broker — it is a reminder, not a lock.',
    ]) ?>
  </section>
</div>
<p class="ex-disclaimer">Historical analysis of your own journal — historical performance does not guarantee future results.</p>
<?php endif; ?>
