<?php
/** Plan countdown card (Settings + Plan & Billing). Needs $m (the signed-in member with 'ent'). */
use App\Trading\Subscriptions;
$ent = $m['ent'];
$sub = Subscriptions::current((int) $m['id']);
$auto = Subscriptions::renewing($sub);
$failed = $sub && in_array($sub['status'], ['pending', 'halted'], true);
if ($ent['paid']) {
    $end = strtotime($ent['expires_at'] . ' UTC');
    $secs = max(0, $end - time());
    $days = (int) floor($secs / 86400);
    $hours = (int) floor(($secs % 86400) / 3600);
    $period = max(1, (int) $ent['plan']['interval_days']) * 86400;
    $pct = max(0, min(100, round($secs / $period * 100)));
    $warn = $secs <= 3 * 86400 && !$auto;
}
$slug = $ent['plan']['slug'] ?? '';
?>
<section class="panel plan-cd<?= !empty($warn) || $failed ? ' is-warn' : '' ?>" aria-label="Subscription countdown">
  <?php if ($ent['paid']): ?>
    <div class="plan-cd-top">
      <div class="plan-cd-num"><b class="num"><?= $days ?></b><span><?= $days === 1 ? 'day' : 'days' ?><?= $days < 3 ? ' ' . $hours . ' h' : '' ?> left</span></div>
      <div class="plan-cd-meta">
        <strong><?= e($ent['plan_name']) ?></strong>
        <span class="muted small"><?= $auto ? 'Renews automatically on ' : 'Ends on ' ?><?= e(fmt_date($ent['expires_at'], 'M j, Y')) ?></span>
        <?php if ($auto): ?><span class="badge win">Autopay on · <?= e(money($sub['amount'], $sub['currency'])) ?></span>
        <?php elseif ($failed): ?><span class="badge loss">Renewal payment failed</span>
        <?php else: ?><span class="badge">Autopay off</span><?php endif; ?>
      </div>
    </div>
    <div class="plan-cd-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int) $pct ?>" aria-label="Time left in this period"><i style="width:<?= (int) $pct ?>%"></i></div>
    <div class="plan-cd-actions">
      <?php if ($auto): ?>
        <span class="muted small">You'll get an email 3 days before each renewal.</span>
        <form method="post" action="<?= e(url('/terminal/billing/autopay/cancel')) ?>" onsubmit="return confirm('Turn autopay off? You will not be charged again. Your access stays active until <?= e(fmt_date($ent['expires_at'], 'M j, Y')) ?>.')"><?= csrf_field() ?><button class="tm-btn tm-btn-sm" type="submit">Turn off autopay</button></form>
      <?php else: ?>
        <span class="muted small"><?= $failed ? 'Update your payment method or pay once to keep access.' : ($warn ? 'Renew now so your live accounts keep running.' : 'Turn on autopay so you never have to renew by hand.') ?></span>
        <?php if ($slug): ?><a class="tm-btn tm-btn-sm tm-btn-primary" href="<?= e(url('/checkout/' . $slug)) ?>"><?= $warn || $failed ? 'Renew now' : 'Renew / turn on autopay' ?></a><?php endif; ?>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="plan-cd-top">
      <div class="plan-cd-meta"><strong><?= e($ent['plan_name']) ?></strong><span class="muted small"><?= $m['plan_expires_at'] ? 'Your plan ended on ' . e(fmt_date($m['plan_expires_at'], 'M j, Y')) . '. Nothing was deleted.' : 'Demo accounts only. Upgrade to journal live accounts.' ?></span></div>
    </div>
    <div class="plan-cd-actions"><span></span><a class="tm-btn tm-btn-sm tm-btn-primary" href="<?= e(url('/terminal/billing')) ?>"><?= $m['plan_expires_at'] ? 'Renew plan' : 'See plans' ?></a></div>
  <?php endif; ?>
</section>
