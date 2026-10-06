<?php
use App\Trading\Domain;
use App\Trading\Ingest;
$ent = $m['ent'];
$sel = fn ($a, $b) => (string) $a === (string) $b ? ' selected' : '';
?>
<?php if ($newSecret): ?>
<section class="panel stack" style="margin-bottom:12px;border-color:var(--warn)" id="webhook-secret">
  <div class="panel-head"><h2>New webhook — copy these now</h2><span class="badge warn">Shown once</span></div>
  <dl class="dl">
    <dt>Endpoint URL</dt><dd><code id="wh-url"><?= e($newSecret['url']) ?></code> <button type="button" class="tm-btn tm-btn-sm" data-copy-target="#wh-url">Copy</button></dd>
    <dt>Signing secret</dt><dd><code id="wh-secret" style="word-break:break-all"><?= e($newSecret['secret']) ?></code> <button type="button" class="tm-btn tm-btn-sm" data-copy-target="#wh-secret">Copy</button></dd>
  </dl>
  <p class="muted small">Store the secret in your EA or script. journzey.ai keeps it encrypted and cannot show it again; delete the connection and create a new one if you lose it.</p>
</section>
<?php endif; ?>

<section class="panel" style="margin-bottom:12px">
  <div class="panel-head"><h2>Your accounts</h2><span class="muted small"><?= (int) $liveCount ?> / <?= $ent['live'] ? (int) $ent['max_live'] : 0 ?> live accounts on <?= e($ent['plan_name']) ?></span></div>
  <div class="table-wrap"><table class="tbl cards">
    <thead><tr><th>Account</th><th>Mode</th><th>Broker</th><th>Type</th><th class="r">Start</th><th class="r">P&amp;L</th><th class="r">Equity</th><th class="r">Trades</th><th></th></tr></thead>
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
    <h2>New account</h2>
    <form method="post" action="<?= e(url('/terminal/accounts')) ?>" class="stack"><?= csrf_field() ?>
      <div class="seg" role="radiogroup" aria-label="Mode">
        <label style="padding:6px 11px"><input type="radio" name="mode" value="demo" checked> Demo (free)</label>
        <label style="padding:6px 11px"><input type="radio" name="mode" value="live"<?= $ent['live'] ? '' : ' disabled' ?>> Live<?= $ent['live'] ? '' : ' — paid plan' ?></label>
      </div>
      <div class="f"><label for="na-n">Name</label><input id="na-n" name="name" maxlength="80" required placeholder="e.g. FTMO 100k challenge"></div>
      <div class="f"><label for="na-b">Broker</label><input id="na-b" name="broker_name" maxlength="80"></div>
      <div class="grid-form">
        <div class="f"><label for="na-t">Type</label><select id="na-t" name="account_type"><?php foreach (Domain::ACCOUNT_TYPES as $k => $l): ?><option value="<?= e($k) ?>"><?= e($l) ?></option><?php endforeach; ?></select></div>
        <div class="f"><label for="na-c">Currency</label><select id="na-c" name="currency"><?php foreach (Domain::CURRENCIES as $c): ?><option<?= $sel($c, $m['base_currency'] ?: 'USD') ?>><?= e($c) ?></option><?php endforeach; ?></select></div>
      </div>
      <div class="f"><label for="na-s">Starting capital</label><input id="na-s" name="starting_capital" type="number" step="0.01" min="0" value="10000" required></div>
      <button class="tm-btn tm-btn-primary">Create account</button>
      <?php if (!$ent['live']): ?><p class="muted small">Demo accounts are free and unlimited in features. <a href="<?= e(url('/pricing')) ?>">Upgrade</a> to journal live accounts.</p><?php endif; ?>
    </form>
  </section>

  <section class="panel stack">
    <h2>Capital flows · <?= e($acc['name']) ?></h2>
    <?php if ($acc['writable']): ?>
    <form method="post" action="<?= e(url('/terminal/accounts/' . $acc['id'] . '/capital')) ?>" class="stack"><?= csrf_field() ?>
      <div class="grid-form">
        <div class="f"><label for="cf-t">Type</label><select id="cf-t" name="type"><option value="DEPOSIT">Deposit</option><option value="WITHDRAWAL">Withdrawal</option><option value="ADJUSTMENT">Adjustment (±)</option></select></div>
        <div class="f"><label for="cf-a">Amount (<?= e($acc['currency']) ?>)</label><input id="cf-a" name="amount" type="number" step="0.01" required></div>
      </div>
      <div class="f"><label for="cf-d">Date</label><input id="cf-d" name="occurred_at" type="datetime-local"></div>
      <div class="f"><label for="cf-n">Note</label><input id="cf-n" name="note" maxlength="255"></div>
      <button class="tm-btn">Record</button>
    </form>
    <?php else: ?><p class="muted small">This live account is read-only without an active plan.</p><?php endif; ?>
    <?php if ($flows): ?>
    <div class="table-wrap"><table class="tbl"><tbody>
      <?php foreach ($flows as $f): ?><tr><td><?= e(fmt_date($f['occurred_at'], 'M j, Y')) ?></td><td><?= e(ucfirst(strtolower($f['type']))) ?></td><td class="r num"><?= e(money($f['type'] === 'WITHDRAWAL' ? -abs((float) $f['amount']) : $f['amount'], $acc['currency'], true)) ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <?php endif; ?>
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

<section class="panel stack" id="webhooks" style="margin-bottom:12px">
  <div class="panel-head"><h2>Signed webhooks</h2><span class="muted small">Push closed trades from an MT5 EA, TradingView relay or script</span></div>
  <?php if ($connections): ?>
  <div class="table-wrap"><table class="tbl cards">
    <thead><tr><th>Label</th><th>Account</th><th>Endpoint</th><th class="r">Events</th><th>Last event</th><th></th></tr></thead>
    <tbody><?php foreach ($connections as $c): ?>
      <tr><td data-label="Label" class="strong"><?= e($c['label']) ?></td><td data-label="Account"><?= e($c['account_name']) ?></td>
      <td data-label="Endpoint"><code class="small"><?= e(url('/api/webhooks/trades/' . $c['public_id'])) ?></code></td>
      <td data-label="Events" class="r num"><?= (int) $c['events_count'] ?></td><td data-label="Last event"><?= e($c['last_event_at'] ? fmt_date($c['last_event_at'], 'M j, H:i') : 'never') ?></td>
      <td class="r"><form method="post" action="<?= e(url('/terminal/connections/' . $c['id'] . '/delete')) ?>" data-confirm="Delete this webhook? Its URL will stop accepting trades."><?= csrf_field() ?><button class="tm-btn tm-btn-sm">Delete</button></form></td></tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?php endif; ?>
  <?php if ($acc['writable'] && !(int) $acc['has_demo_data']): ?>
  <form method="post" action="<?= e(url('/terminal/accounts/' . $acc['id'] . '/webhook')) ?>" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end"><?= csrf_field() ?>
    <div class="f" style="flex:1 1 220px"><label for="wh-l">Label</label><input id="wh-l" name="label" maxlength="80" placeholder="MT5 EA · <?= e($acc['name']) ?>"></div>
    <button class="tm-btn">Create webhook for <?= e($acc['name']) ?></button>
  </form>
  <?php endif; ?>
  <details><summary class="strong">Webhook format</summary>
<pre class="small" style="white-space:pre-wrap;overflow-x:auto">POST {endpoint}
Content-Type: application/json
X-Journzey-Timestamp: {unix seconds}
X-Journzey-Signature: sha256={hex HMAC-SHA256 of "{timestamp}.{raw body}" with your signing secret}

{"event_id": "unique-id-per-delivery",
 "trade": {"id": "123456", "symbol": "XAUUSD", "side": "buy", "open_time": "2026-10-06T08:14:00Z",
           "close_time": "2026-10-06T09:02:00Z", "entry_price": 2650.5, "exit_price": 2662.0,
           "stop_loss": 2645.0, "take_profit": 2665.0, "volume": 0.5, "profit": 575.0, "fees": 3.5,
           "setup": "London breakout", "comment": "optional"}}</pre>
    <p class="muted small">Requests older than 5 minutes are rejected (replay protection). Each <code>event_id</code> and each trade <code>id</code> is processed once (idempotent). Live accounts accept webhook trades only while a paid plan is active.</p>
  </details>
</section>

<section class="panel">
  <div class="panel-head"><h2>Broker connectors</h2><span class="muted small">Honest status — nothing pretends to sync</span></div>
  <div class="grid g-3">
    <?php foreach (Ingest::CATALOG as [$id, $name, $status, $method, $desc]): ?>
    <div class="plan-card">
      <div style="display:flex;justify-content:space-between;gap:8px;align-items:center"><strong><?= e($name) ?></strong><span class="badge<?= $status === 'AVAILABLE' ? ' win' : ($status === 'UNSUPPORTED' ? ' loss' : ($status === 'COMING SOON' ? ' warn' : '')) ?>"><?= e($status) ?></span></div>
      <p class="muted small" style="margin:6px 0 0"><?= e($method) ?></p>
      <p class="small" style="margin:6px 0 0"><?= e($desc) ?></p>
    </div>
    <?php endforeach; ?>
  </div>
</section>
