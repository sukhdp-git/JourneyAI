<?php
/** Public verified P&L. @var array $l @var array $r @var string $first */
use App\Trading\PublicPnl;
$cur = $l['currency']; $amt = (bool) $l['show_amounts']; $s = $r['sum']; $start = $r['start'];
$fmt = function (?float $v, bool $signed = true) use ($amt, $cur, $start): string {
    if ($v === null) return '—';
    if ($amt) return money($v, $cur, $signed);
    if ($start <= 0) return '—';
    $p = $v / $start * 100;
    return ($signed && $p > 0 ? '+' : ($p < 0 ? '−' : '')) . number_format(abs($p), 2) . '%';
};
$cls = fn (?float $v) => $v === null ? '' : ($v > 0 ? 'up' : ($v < 0 ? 'down' : ''));
$kind = (int) $l['has_demo_data'] ? 'Demo data' : (in_array($l['account_type'], ['PROP_CHALLENGE', 'PROP_FUNDED'], true) ? 'Prop firm account' : ((int) $l['is_demo'] ? 'Demo / practice account' : 'Live account'));
// Equity curve points (value or % of start)
$pts = $r['eq']['points'];
$svg = '';
if ($l['show_equity'] && count($pts) > 1) {
    $vals = array_merge([$start], array_map(fn ($p) => (float) $p['equity'], $pts));
    $min = min($vals); $max = max($vals); $span = ($max - $min) ?: 1; $W = 1000; $H = 300; $n = count($vals) - 1;
    $xy = [];
    foreach ($vals as $i => $v) { $xy[] = round($i / $n * $W, 1) . ',' . round($H - 12 - ($v - $min) / $span * ($H - 24), 1); }
    $line = implode(' ', $xy);
    $up = end($vals) >= $start;
    $svg = '<svg class="pnl-eq ' . ($up ? 'is-up' : 'is-down') . '" viewBox="0 0 ' . $W . ' ' . $H . '" role="img" aria-label="Equity curve for the period">'
        . '<defs><linearGradient id="pnlg" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#8a5ddc" stop-opacity=".34"/><stop offset="1" stop-color="#feb2b4" stop-opacity="0"/></linearGradient><linearGradient id="pnls" gradientUnits="userSpaceOnUse" x1="0" y1="0" x2="' . $W . '" y2="0"><stop offset="0" stop-color="#6541e9"/><stop offset=".35" stop-color="#8a5ddc"/><stop offset=".65" stop-color="#b17ace"/><stop offset="1" stop-color="#feb2b4"/></linearGradient></defs>'
        . '<polygon class="pnl-eq-area" points="0,' . $H . ' ' . $line . ' ' . $W . ',' . $H . '" fill="url(#pnlg)"/>'
        . '<polyline class="pnl-eq-line" points="' . $line . '" fill="none" stroke="url(#pnls)" stroke-width="3.5" stroke-linejoin="round" stroke-linecap="round" pathLength="1"/></svg>';
}
$dayMax = $r['days'] ? max(array_map('abs', $r['days'])) : 0;
?>
<section class="section pnl-wrap">
  <div class="container">
    <header class="pnl-head reveal">
      <span class="pnl-badge"><?= icon('check-circle', 'icon icon-sm') ?> Verified by <?= e(setting('site_name', 'journzey.ai')) ?></span>
      <h1><?= e($l['title'] ?: (($first !== '' ? $first . '’s' : 'Trader’s') . ' P&L')) ?></h1>
      <p class="pnl-meta"><?= $first !== '' && $l['title'] ? e($first) . ' · ' : '' ?><?= $l['date_from'] <= '2000-01-01' ? 'All time' : e(fmt_date($l['date_from'], 'M j, Y')) ?> → <?= e(fmt_date($l['date_to'], 'M j, Y')) ?> · <?= e($kind) ?><?= (int) $l['has_demo_data'] ? ' <span class="pnl-demo">DEMO DATA</span>' : '' ?></p>
    </header>

    <div class="pnl-hero reveal">
      <div class="pnl-net">
        <small><?= $amt ? 'Net P&amp;L' : 'Net return' ?></small>
        <strong class="<?= $cls((float) $s['net']) ?>" data-count><?= e($fmt((float) $s['net'])) ?></strong>
        <?php if ($amt && $r['return'] !== null): ?><span class="<?= $cls((float) $s['net']) ?>"><?= ($r['return'] > 0 ? '+' : ($r['return'] < 0 ? '−' : '')) . number_format(abs($r['return']) * 100, 2) ?>% return on the period’s starting balance</span><?php endif; ?>
      </div>
      <?= $svg ?>
    </div>

    <div class="pnl-kpis">
      <?php foreach ([
          ['Trades', (string) $s['trades'], ''], ['Win rate', $s['win_rate'] === null ? '—' : round($s['win_rate'] * 100) . '%', $s['win_rate'] === null ? '' : ($s['win_rate'] >= 0.5 ? 'up' : 'down')],
          ['Profit factor', $s['profit_factor'] === null ? ($s['trades'] ? '∞' : '—') : number_format($s['profit_factor'], 2), $s['profit_factor'] === null ? '' : ($s['profit_factor'] >= 1 ? 'up' : 'down')],
          ['Total R', $r['total_r'] === null ? '—' : ($r['total_r'] > 0 ? '+' : '') . number_format($r['total_r'], 2) . 'R', $cls($r['total_r'])],
          ['Max drawdown', '−' . number_format((float) $r['eq']['max_dd_pct'], 2) . '%', $r['eq']['max_dd_pct'] > 0 ? 'down' : ''],
          ['Green / red days', $r['green_days'] . ' / ' . $r['red_days'], ''],
          ['Best day', $fmt($r['best_day']), $cls($r['best_day'])], ['Worst day', $fmt($r['worst_day']), $cls($r['worst_day'])],
      ] as $i => [$k, $v, $c]): ?>
      <div class="pnl-kpi reveal" style="--d:<?= $i % 4 ?>"><small><?= e($k) ?></small><b class="<?= $c ?>"><?= e($v) ?></b></div>
      <?php endforeach; ?>
    </div>

    <?php if ($r['days']): ?>
    <section class="pnl-card reveal" aria-label="Daily results">
      <h2 class="h4">Daily results</h2>
      <div class="pnl-days"><?php $i = 0; foreach ($r['days'] as $d => $v): ?><span class="<?= $v >= 0 ? 'up' : 'down' ?>" style="--h:<?= $dayMax ? max(6, round(abs($v) / $dayMax * 100)) : 6 ?>%;--i:<?= $i++ ?>" title="<?= e(fmt_date($d, 'M j') . ': ' . $fmt($v)) ?>"></span><?php endforeach; ?></div>
      <?php if (count($r['months']) > 1): ?>
      <div class="table-scroll"><table class="pnl-table"><thead><tr><th>Month</th><th>Trades</th><th>Win rate</th><th>Result</th></tr></thead><tbody>
        <?php foreach ($r['months'] as $mo => $x): ?><tr><td><?= e((new DateTimeImmutable($mo . '-01'))->format('F Y')) ?></td><td><?= (int) $x['trades'] ?></td><td><?= round($x['wins'] / max(1, $x['trades']) * 100) ?>%</td><td class="<?= $cls($x['net']) ?>"><?= e($fmt($x['net'])) ?></td></tr><?php endforeach; ?>
      </tbody></table></div>
      <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php if ($l['show_trades'] && $r['trades']): ?>
    <section class="pnl-card reveal" aria-label="Trade history">
      <h2 class="h4">Trade history <span class="muted">(<?= count($r['trades']) ?>)</span></h2>
      <div class="table-scroll"><table class="pnl-table"><thead><tr><th>Date</th><th>Instrument</th><th>Side</th><th>R</th><th>Result</th><th>Recorded</th></tr></thead><tbody>
        <?php foreach (array_slice(array_reverse($r['trades']), 0, 500) as $t): ?>
        <tr><td class="nowrap"><?= e((new DateTimeImmutable($t['executed_at'], new DateTimeZone('UTC')))->setTimezone(new DateTimeZone($l['timezone'] ?: 'UTC'))->format('M j, Y H:i')) ?></td><td><strong><?= e($t['symbol']) ?></strong></td>
        <td><span class="pnl-side <?= $t['side'] === 'LONG' ? 'up' : 'down' ?>"><?= $t['side'] === 'LONG' ? 'BUY' : 'SELL' ?></span></td>
        <td class="<?= $cls($t['rr'] === null ? null : (float) $t['rr']) ?>"><?= $t['rr'] === null ? '—' : (((float) $t['rr'] > 0 ? '+' : '') . number_format((float) $t['rr'], 2) . 'R') ?></td>
        <td class="<?= $cls((float) $t['pnl']) ?>"><?= e($fmt((float) $t['pnl'])) ?></td><td class="muted small"><?= e(PublicPnl::SOURCES[$t['source'] ?: 'MANUAL'] ?? ucfirst(strtolower((string) $t['source']))) ?></td></tr>
        <?php endforeach; ?>
      </tbody></table></div>
    </section>
    <?php elseif (!$r['trades']): ?>
    <p class="muted pnl-card">No closed trades in this period yet.</p>
    <?php endif; ?>

    <section class="pnl-card pnl-proof reveal" aria-label="How this is verified">
      <h2 class="h4"><?= icon('shield', 'icon icon-sm') ?> How this is verified</h2>
      <ul>
        <li>Every number on this page is calculated by <?= e(setting('site_name', 'journzey.ai')) ?> from the trades in the trader’s journal, live, each time the page opens — totals cannot be typed in.</li>
        <?php if ($r['trades']): ?>
        <li>How the trades were recorded: <?php $parts = []; foreach ($r['sources'] as $k => $n) { $parts[] = round($n / count($r['trades']) * 100) . '% ' . strtolower(PublicPnl::SOURCES[$k] ?? $k); } echo e(implode(', ', $parts)); ?>.</li>
        <li><?= round(($r['prompt_pct'] ?? 0) * 100) ?>% of trades were logged within 2 days of being taken (or imported from a broker statement).</li>
        <?php endif; ?>
        <li>Link created <?= e(fmt_date($l['created_at'], 'M j, Y')) ?>. The trader can pause or delete it at any time.</li>
      </ul>
      <p class="muted small">Trades are recorded by the trader and are not confirmed with the broker. Past results do not guarantee future performance. Not investment advice.</p>
    </section>

    <div class="pnl-cta reveal"><p>Track and prove your own trading.</p><a class="btn btn-primary" href="<?= e(url('/signup')) ?>">Start your free journal</a></div>
  </div>
</section>
