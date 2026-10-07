<?php /** Edit / adjust equity for the active account. @var array $acc @var array $balance @var array $history */
$return = (new App\Core\Request())->path; ?>
<div class="tm-modal" id="tm-equity" role="dialog" aria-modal="true" aria-labelledby="tm-equity-title" hidden>
  <div class="tm-modal-bg" data-close></div>
  <div class="tm-modal-card">
    <h2 id="tm-equity-title">Edit / adjust equity · <?= e($acc['name']) ?></h2>
    <p class="muted small">Current equity <strong class="num"><?= e(money($balance['equity'], $acc['currency'])) ?></strong> = starting capital <?= e(money($balance['start'], $acc['currency'])) ?> + deposits/withdrawals/adjustments <?= e(money($balance['flows'], $acc['currency'], true)) ?> + closed P&amp;L <?= e(money($balance['pnl'], $acc['currency'], true)) ?>. Changes are recorded as capital transactions — your trade history is never rewritten.</p>
    <div class="seg" role="tablist" aria-label="Capital action" style="margin:6px 0 10px">
      <button type="button" class="on" data-cap-tab="DEPOSIT">Deposit</button>
      <button type="button" data-cap-tab="WITHDRAWAL">Withdraw</button>
      <button type="button" data-cap-tab="SET">Set equity</button>
    </div>
    <form method="post" action="<?= e(url('/terminal/accounts/' . $acc['id'] . '/capital')) ?>" class="stack"><?= csrf_field() ?>
      <input type="hidden" name="type" value="DEPOSIT"><input type="hidden" name="return" value="<?= e($return) ?>">
      <div class="grid-form">
        <div class="f"><label for="eq-a"><span data-cap-label>Amount</span> (<?= e($acc['currency']) ?>)</label><input id="eq-a" name="amount" type="number" step="0.01" min="0" required inputmode="decimal"></div>
        <div class="f"><label for="eq-d">Date</label><input id="eq-d" name="occurred_at" type="datetime-local"></div>
      </div>
      <div class="f"><label for="eq-n">Note (optional)</label><input id="eq-n" type="text" name="note" maxlength="255" placeholder="e.g. Monthly top-up, payout, broker statement correction"></div>
      <p class="muted small" data-cap-help="DEPOSIT">Adds money to the account.</p>
      <p class="muted small" data-cap-help="WITHDRAWAL" hidden>Removes money from the account (e.g. a payout).</p>
      <p class="muted small" data-cap-help="SET" hidden>Enter what your broker shows as equity now. The difference is saved as an adjustment.</p>
      <div class="tm-modal-actions"><button type="button" class="tm-btn" data-close>Cancel</button><button class="tm-btn tm-btn-primary" type="submit">Save</button></div>
    </form>
    <?php if ($history): ?>
    <h3 style="margin-top:12px">Recent capital history</h3>
    <ul class="mini-hist"><?php foreach ($history as $h): $v = $h['type'] === 'WITHDRAWAL' ? -abs((float) $h['amount']) : (float) $h['amount']; ?><li><span><?= e(fmt_date($h['occurred_at'], 'M j, Y')) ?> · <?= e(ucfirst(strtolower($h['type']))) ?><?= $h['note'] ? ' · ' . e(mb_strimwidth($h['note'], 0, 40, '…')) : '' ?></span><strong class="num <?= $v >= 0 ? 'up' : 'down' ?>"><?= e(money($v, $acc['currency'], true)) ?></strong></li><?php endforeach; ?></ul>
    <a class="small" href="<?= e(url('/terminal/accounts')) ?>">Full history in Accounts →</a>
    <?php endif; ?>
  </div>
</div>
