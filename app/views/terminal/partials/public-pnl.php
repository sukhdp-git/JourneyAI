<?php
/** Public verified P&L links — shown inside Settings. @var array $links @var array $accountsAll @var string $today @var string $monthStart @var array $acc */
$new = $_SESSION['_new_pnl'] ?? null; unset($_SESSION['_new_pnl']);
$t = new DateTimeImmutable($today);
$presets = [
    'This month' => [$monthStart, $today], 'Last month' => [$t->modify('first day of last month')->format('Y-m-d'), $t->modify('last day of last month')->format('Y-m-d')],
    'Last 3 months' => [$t->modify('-3 months')->format('Y-m-d'), $today], 'This year' => [$t->format('Y-01-01'), $today], 'All time' => ['2000-01-01', $today],
];
$check = fn (string $n, string $label, string $hint, bool $on) => '<label class="pp-toggle"><input type="checkbox" name="' . $n . '" value="1"' . ($on ? ' checked' : '') . '><span><b>' . e($label) . '</b><small>' . e($hint) . '</small></span></label>';
$form = function (?array $l) use ($accountsAll, $acc, $presets, $monthStart, $today, $check): string {
    $id = $l ? 'e' . $l['id'] : 'n';
    $h = '<div class="grid-form"><div class="f"><label for="pp-a-' . $id . '">Account</label><select id="pp-a-' . $id . '" name="account_id">';
    foreach ($accountsAll as $a) {
        $h .= '<option value="' . (int) $a['id'] . '"' . ((int) $a['id'] === (int) ($l['account_id'] ?? $acc['id']) ? ' selected' : '') . '>' . e($a['name']) . ((int) $a['has_demo_data'] ? ' (demo data)' : '') . '</option>';
    }
    $h .= '</select></div><div class="f"><label for="pp-t-' . $id . '">Title <span class="muted">(optional)</span></label><input id="pp-t-' . $id . '" name="title" maxlength="80" value="' . e($l['title'] ?? '') . '" placeholder="e.g. My October gold scalping"></div></div>';
    $h .= '<div class="pp-presets" role="group" aria-label="Date presets">';
    foreach ($presets as $lbl => [$f, $t2]) {
        $h .= '<button type="button" class="lt-chip" data-pp-preset data-from="' . e($f) . '" data-to="' . e($t2) . '">' . e($lbl) . '</button>';
    }
    $h .= '</div><div class="grid-form"><div class="f"><label for="pp-f-' . $id . '">From</label><input id="pp-f-' . $id . '" type="date" name="date_from" value="' . e($l['date_from'] ?? $monthStart) . '" required data-pp-from></div>'
        . '<div class="f"><label for="pp-to-' . $id . '">To</label><input id="pp-to-' . $id . '" type="date" name="date_to" value="' . e($l['date_to'] ?? $today) . '" required data-pp-to></div></div>';
    $h .= '<div class="pp-toggles">' . $check('show_amounts', 'Show money amounts', 'Off = only % return and R multiples', (bool) ($l['show_amounts'] ?? 1))
        . $check('show_trades', 'Show trade list', 'Date, instrument, side, R and result of every trade', (bool) ($l['show_trades'] ?? 1))
        . $check('show_equity', 'Show equity curve', 'Animated growth chart for the period', (bool) ($l['show_equity'] ?? 1))
        . $check('show_name', 'Show my first name', 'Off = anonymous trader', (bool) ($l['show_name'] ?? 1)) . '</div>';
    return $h;
};
?>
<div class="pp-section" id="public-pnl">
<div class="grid g-main">
  <section class="panel stack pp-create">
    <div class="panel-head"><h2><?= icon('share', 'icon icon-sm') ?> Create a public verified P&amp;L link</h2></div>
    <p class="muted small">Anyone in the world can open the link — no account needed. It shows your results for the dates you choose, calculated live from your journal. Notes, screenshots, balances and account names are never shown.</p>
    <form method="post" action="<?= e(url('/terminal/public-pnl')) ?>" class="stack"><?= csrf_field() ?>
      <?= $form(null) ?>
      <button class="tm-btn tm-btn-primary"><?= icon('link', 'icon icon-sm') ?> Create public link</button>
    </form>
  </section>
  <aside class="panel stack small">
    <h2>How “verified” works</h2>
    <ul class="aff-list">
      <li>Figures are computed by journzey.ai from the trades in your journal — you cannot type a total.</li>
      <li>The page shows how each trade was recorded (imported from a broker statement, voice, manual) and how many were logged within 2 days.</li>
      <li>Demo-data accounts are clearly labelled.</li>
      <li>Pause or delete a link anytime; it stops working immediately.</li>
    </ul>
  </aside>
</div>

<section class="panel" style="margin-top:12px">
  <div class="panel-head"><h2>Your links</h2><span class="muted small"><?= count($links) ?> / 30</span></div>
  <?php if ($links): ?>
  <div class="pp-list">
    <?php foreach ($links as $l): $url = url('/p/' . $l['code']); ?>
    <article class="pp-item<?= (int) $l['is_active'] ? '' : ' is-paused' ?><?= $new === $l['code'] ? ' is-new' : '' ?>">
      <div class="pp-main">
        <strong><?= e($l['title'] ?: 'P&L ' . ($l['date_from'] <= '2000-01-01' ? 'all time' : fmt_date($l['date_from'], 'M j')) . ' – ' . fmt_date($l['date_to'], 'M j, Y')) ?></strong>
        <span class="muted small"><?= e($l['account_name']) ?> · <?= $l['date_from'] <= '2000-01-01' ? 'All time' : e(fmt_date($l['date_from'], 'M j, Y')) ?> → <?= e(fmt_date($l['date_to'], 'M j, Y')) ?> · <?= (int) $l['views'] ?> view<?= (int) $l['views'] === 1 ? '' : 's' ?><?= (int) $l['is_active'] ? '' : ' · <b class="down">paused</b>' ?></span>
        <div class="aff-link-row"><code id="pp-u-<?= (int) $l['id'] ?>"><?= e($url) ?></code><button type="button" class="tm-btn tm-btn-sm" data-copy-target="#pp-u-<?= (int) $l['id'] ?>"><?= icon('copy', 'icon icon-xs') ?> Copy</button><a class="tm-btn tm-btn-sm" href="<?= e($url) ?>" target="_blank" rel="noopener"><?= icon('external', 'icon icon-xs') ?> Open</a></div>
      </div>
      <div class="pp-actions">
        <details class="menu-details"><summary class="tm-btn tm-btn-sm">Edit</summary>
          <form method="post" action="<?= e(url('/terminal/public-pnl/' . $l['id'])) ?>" class="panel stack pp-edit"><?= csrf_field() ?><?= $form($l) ?><button class="tm-btn tm-btn-sm tm-btn-primary">Save</button></form>
        </details>
        <form method="post" action="<?= e(url('/terminal/public-pnl/' . $l['id'])) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><button class="tm-btn tm-btn-sm"><?= (int) $l['is_active'] ? 'Pause' : 'Resume' ?></button></form>
        <form method="post" action="<?= e(url('/terminal/public-pnl/' . $l['id'])) ?>" data-confirm="Delete this public link? Anyone who has it will no longer be able to open it."><?= csrf_field() ?><input type="hidden" name="action" value="delete"><button class="tm-btn tm-btn-sm">Delete</button></form>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
  <?php else: ?><p class="muted">No links yet. Create one above, then share it on X, Instagram, Discord or with a prop firm.</p><?php endif; ?>
</section>
</div>
