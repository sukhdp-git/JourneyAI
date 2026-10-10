<?php
use App\Trading\Domain;
$ent = $m['ent'];
$sel = fn ($a, $b) => (string) $a === (string) $b ? ' selected' : '';
$limTxt = fn ($v, $t, $c) => $v === null ? '—' : ($t === 'percent' ? rtrim(rtrim(number_format((float) $v, 2), '0'), '.') . '%' : money($v, $c));
$limFields = function (?array $a) use ($sel): string {
    $h = '<fieldset class="lim-fields"><legend>Loss limits for this account</legend><div class="grid-form">';
    foreach (['daily' => 'Daily', 'weekly' => 'Weekly'] as $k => $l) {
        $t = $a[$k . '_limit_type'] ?? 'percent';
        $h .= '<div class="f"><label>' . $l . ' max loss</label><div class="lim-row"><input name="max_' . $k . '_loss" type="number" step="0.01" min="0" value="' . e((string) ($a['max_' . $k . '_loss'] ?? '')) . '" placeholder="' . ($k === 'daily' ? 'e.g. 2' : 'e.g. 5') . '">'
            . '<select name="' . $k . '_limit_type" aria-label="' . $l . ' limit type"><option value="percent"' . $sel('percent', $t) . '>% of capital</option><option value="amount"' . $sel('amount', $t) . '>amount</option></select></div></div>';
    }
    return $h . '</div><p class="f-hint">Leave empty for no limit. The terminal warns you when this account reaches it.</p></fieldset>';
};
?>


<section class="panel" id="limits" style="margin-bottom:12px">
  <div class="panel-head"><h2>Your accounts</h2><span class="muted small"><?= (int) $liveCount ?> / <?= $ent['live'] ? (int) $ent['max_live'] : 0 ?> live accounts on <?= e($ent['plan_name']) ?></span></div>
  <div class="table-wrap"><table class="tbl cards">
    <thead><tr><th>Account</th><th>Mode</th><th>Broker</th><th>Type</th><th class="r">Start</th><th class="r">P&amp;L</th><th class="r">Equity</th><th class="r">Trades</th><th>Daily / weekly limit</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($list as $a): ?>
      <tr<?= (int) $a['is_archived'] ? ' class="muted"' : '' ?>>
        <td data-label="Account" class="strong"><?= e($a['name']) ?><?= (int) $a['id'] === (int) $acc['id'] ? ' <span class="badge">active</span>' : '' ?><?= (int) $a['is_archived'] ? ' <span class="badge">archived</span>' : '' ?></td>
        <td data-label="Mode"><?php if ((int) $a['has_demo_data']): ?><span class="mode demo-data">DEMO DATA</span><?php elseif ((int) $a['is_demo']): ?><span class="mode demo">DEMO</span><?php else: ?><span class="mode live">LIVE</span><?= $ent['live'] ? '' : ' <span class="badge warn">read-only</span>' ?><?php endif; ?></td>
        <td data-label="Broker"><?= e($a['broker_name'] ?? '—') ?></td>
        <td data-label="Type"><?= e(Domain::ACCOUNT_TYPES[$a['account_type']] ?? $a['account_type']) ?></td>
        <td data-label="Start" class="r num"><?= e(money($a['starting_capital'], $a['currency'])) ?></td>
        <td data-label="P&amp;L" class="r num <?= $a['bal']['pnl'] >= 0 ? 'up' : 'down' ?>"><?= e(money($a['bal']['pnl'], $a['currency'], true)) ?></td>
        <td data-label="Equity" class="r num"><?= e(money($a['bal']['equity'], $a['currency'])) ?></td>
        <td data-label="Trades" class="r num"><?= (int) $a['trades'] ?></td>
        <td data-label="Daily / weekly limit" class="num small"><?= e($limTxt($a['max_daily_loss'], $a['daily_limit_type'], $a['currency'])) ?> / <?= e($limTxt($a['max_weekly_loss'], $a['weekly_limit_type'], $a['currency'])) ?></td>
        <td data-label="" class="r">
          <details class="menu-details"><summary class="tm-btn tm-btn-sm">Manage</summary>
            <div class="panel stack" style="position:absolute;right:0;z-index:20;min-width:300px;text-align:left;white-space:normal">
              <?php if (!(int) $a['is_archived'] && (int) $a['id'] !== (int) $acc['id']): ?>
              <form method="post" action="<?= e(url('/terminal/account/switch')) ?>"><?= csrf_field() ?><input type="hidden" name="account_id" value="<?= (int) $a['id'] ?>"><button class="tm-btn tm-btn-sm">Make active</button></form>
              <?php endif; ?>
              <form method="post" action="<?= e(url('/terminal/accounts/' . $a['id'])) ?>" class="stack"><?= csrf_field() ?>
                <div class="f"><label>Name</label><input name="name" maxlength="80" value="<?= e($a['name']) ?>" required></div>
                <div class="f"><label>Broker</label><input name="broker_name" maxlength="80" value="<?= e($a['broker_name'] ?? '') ?>"></div>
                <div class="f"><label>Type</label><select name="account_type"><?php foreach (Domain::ACCOUNT_TYPES as $k => $l): ?><option value="<?= e($k) ?>"<?= $sel($k, $a['account_type']) ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
                <div class="f"><label>Currency</label><select name="currency"<?= (int) $a['has_demo_data'] ? ' disabled' : '' ?>><?php foreach (Domain::CURRENCIES as $c): ?><option<?= $sel($c, $a['currency']) ?>><?= e($c) ?></option><?php endforeach; ?></select></div>
                <div class="f"><label>Starting capital</label><input name="starting_capital" type="number" step="0.01" min="0" value="<?= e($a['starting_capital']) ?>" required></div>
                <?= $limFields($a) ?>
                <?php if (App\Trading\PropRules::isProp($a)): ?>
                <fieldset class="lim-fields"><legend>Prop firm rules</legend><input type="hidden" name="has_prop_rules" value="1"><input type="hidden" name="prop_preset" value="<?= e($a['prop_preset'] ?? '') ?>">
                  <div class="f"><label>Prop firm</label><input name="prop_firm" maxlength="80" value="<?= e($a['prop_firm'] ?? '') ?>"></div>
                  <div class="grid-form">
                    <div class="f"><label>Profit target %</label><input name="profit_target_pct" type="number" step="0.01" min="0" value="<?= e((string) ($a['profit_target_pct'] ?? '')) ?>"></div>
                    <div class="f"><label>Max overall loss %</label><input name="max_total_loss_pct" type="number" step="0.01" min="0" value="<?= e((string) ($a['max_total_loss_pct'] ?? '')) ?>"></div>
                    <div class="f"><label>Overall loss type</label><select name="total_loss_mode"><option value="static"<?= $sel('static', $a['total_loss_mode'] ?? 'static') ?>>Static</option><option value="trailing"<?= $sel('trailing', $a['total_loss_mode'] ?? '') ?>>Trailing</option></select></div>
                    <div class="f"><label>Min trading days</label><input name="min_trading_days" type="number" step="1" min="0" value="<?= e((string) ($a['min_trading_days'] ?? '')) ?>"></div>
                    <div class="f"><label>Consistency %</label><input name="consistency_pct" type="number" step="0.01" min="0" value="<?= e((string) ($a['consistency_pct'] ?? '')) ?>"></div>
                  </div>
                  <p class="f-hint">The daily loss rule is the “Daily max loss” above.</p>
                </fieldset>
                <?php endif; ?>
                <button class="tm-btn tm-btn-sm tm-btn-primary">Save</button>
              </form>
              <form method="post" action="<?= e(url('/terminal/accounts/' . $a['id'] . '/archive')) ?>"><?= csrf_field() ?><button class="tm-btn tm-btn-sm"><?= (int) $a['is_archived'] ? 'Restore' : 'Archive' ?></button></form>
              <form method="post" action="<?= e(url('/terminal/accounts/' . $a['id'] . '/archive')) ?>" class="stack" data-confirm="Delete this account and ALL of its trades permanently?"><?= csrf_field() ?><input type="hidden" name="action" value="delete">
                <div class="f"><label>Type “<?= e($a['name']) ?>” to delete</label><input name="confirm_name" autocomplete="off"></div>
                <button class="tm-btn tm-btn-sm">Delete permanently</button>
              </form>
            </div>
          </details>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</section>

<div class="grid g-3" style="margin-bottom:12px">
  <section class="panel stack">
    <h2>Add an account</h2>
    <p class="muted small">Add as many broker and prop-firm accounts as you trade. Each keeps its own trades, equity and limits.</p>
    <button type="button" class="aa-kind" data-open-add-account="broker"><?= icon('link', 'icon') ?><span><b>Broker account</b><small>Name, broker and starting equity</small></span></button>
    <button type="button" class="aa-kind" data-open-add-account="prop"><?= icon('shield', 'icon') ?><span><b>Prop firm account</b><small>Firm, account size and challenge rules</small></span></button>
    <?php if (!$ent['live']): ?><p class="muted small">On the free plan accounts are added in Practice (demo) mode. <a href="<?= e(url('/pricing')) ?>">Upgrade</a> to journal live accounts.</p><?php endif; ?>
  </section>

  <section class="panel stack">
    <div class="panel-head"><h2>Capital · <?= e($acc['name']) ?></h2><button type="button" class="tm-btn tm-btn-sm" data-open-modal="#tm-equity"<?= $acc['writable'] ? '' : ' disabled' ?>><?= icon('edit', 'icon icon-sm') ?> Edit / adjust equity</button></div>
    <?php $dep = $wd = $adj = 0.0; foreach ($flows as $f) { if ($f['type'] === 'DEPOSIT') $dep += abs((float) $f['amount']); elseif ($f['type'] === 'WITHDRAWAL') $wd += abs((float) $f['amount']); else $adj += (float) $f['amount']; } ?>
    <dl class="dl">
      <dt>Starting capital</dt><dd class="num"><?= e(money($balance['start'], $acc['currency'])) ?></dd>
      <dt>Deposits</dt><dd class="num up"><?= e(money($dep, $acc['currency'], true)) ?></dd>
      <dt>Withdrawals</dt><dd class="num down"><?= e(money(-$wd, $acc['currency'])) ?></dd>
      <?php if ($adj != 0.0): ?><dt>Adjustments</dt><dd class="num <?= $adj >= 0 ? 'up' : 'down' ?>"><?= e(money($adj, $acc['currency'], true)) ?></dd><?php endif; ?>
      <dt>Closed trade P&amp;L</dt><dd class="num <?= $balance['pnl'] >= 0 ? 'up' : 'down' ?>"><?= e(money($balance['pnl'], $acc['currency'], true)) ?></dd>
      <dt><strong>Current equity</strong></dt><dd class="num strong"><?= e(money($balance['equity'], $acc['currency'])) ?></dd>
    </dl>
    <?php if (!$acc['writable']): ?><p class="muted small">This live account is read-only without an active plan.</p><?php endif; ?>
    <h3 style="margin-top:6px">Capital history</h3>
    <?php if ($flows): ?>
    <div class="table-wrap"><table class="tbl"><tbody>
      <?php foreach ($flows as $f): $v = $f['type'] === 'WITHDRAWAL' ? -abs((float) $f['amount']) : (float) $f['amount']; ?><tr><td><?= e(fmt_date($f['occurred_at'], 'M j, Y')) ?></td><td><?= e(ucfirst(strtolower($f['type']))) ?><?php if ($f['note']): ?><br><span class="muted small"><?= e(mb_strimwidth($f['note'], 0, 60, '…')) ?></span><?php endif; ?></td><td class="r num <?= $v >= 0 ? 'up' : 'down' ?>"><?= e(money($v, $acc['currency'], true)) ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <?php else: ?><p class="muted small">No deposits, withdrawals or adjustments yet.</p><?php endif; ?>
  </section>

  <section class="panel stack">
    <h2>Import statement (CSV)</h2>
    <?php if ((int) $acc['has_demo_data']): ?>
      <p class="muted small">Switch to one of your own accounts to import — the DEMO DATA journal stays separate.</p>
    <?php elseif (!$acc['writable']): ?>
      <p class="muted small">Live accounts need an active plan to import. <a href="<?= e(url('/pricing')) ?>">See plans</a>.</p>
    <?php else: ?>
    <form method="post" action="<?= e(url('/terminal/accounts/' . $acc['id'] . '/import')) ?>" enctype="multipart/form-data" class="stack"><?= csrf_field() ?>
      <p class="muted small">Into <strong><?= e($acc['name']) ?></strong>. MT4/MT5, cTrader and NinjaTrader exports are auto-detected; duplicates are skipped by ticket.</p>
      <div class="f"><label for="im-f">CSV file (max 5 MB)</label><input id="im-f" type="file" name="file" accept=".csv,text/csv,.txt" required></div>
      <div class="f"><label for="im-o">Broker server time</label><select id="im-o" name="offset_minutes"><?php foreach ([0 => 'UTC (GMT+0)', 60 => 'GMT+1', 120 => 'GMT+2 (common MT servers)', 180 => 'GMT+3 (MT servers in summer)', -240 => 'GMT−4 (New York, summer)', -300 => 'GMT−5', 330 => 'GMT+5:30 (India)'] as $k => $l): ?><option value="<?= $k ?>"><?= e($l) ?></option><?php endforeach; ?></select></div>
      <button class="tm-btn">Import trades</button>
    </form>
    <?php endif; ?>
  </section>
</div>
