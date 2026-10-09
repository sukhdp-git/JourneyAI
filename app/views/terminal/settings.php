<?php
use App\Trading\Domain;
$v = fn ($k) => (string) old($k, $m[$k] ?? '');
$sel = fn ($a, $b) => (string) $a === (string) $b ? ' selected' : '';
$err = fn ($k) => field_error($k);
?>
<div class="grid g-main">
  <form method="post" action="<?= e(url('/terminal/settings')) ?>" class="panel stack"><?= csrf_field() ?>
    <h2>Profile &amp; preferences</h2>
    <div class="grid-form">
      <div class="f"><label for="s-n">Name</label><input id="s-n" name="name" maxlength="120" value="<?= e($v('name')) ?>" required><?= $err('name') ?></div>
      <div class="f"><label>Email</label><input value="<?= e($m['email']) ?>" disabled><span class="muted small">Signed up with <?= $m['signup_method'] === 'google' ? 'Google' : 'email' ?><?= $m['google_sub'] ? ' · Google linked' : '' ?></span></div>
      <div class="f"><label for="s-tz">Timezone</label><select id="s-tz" name="timezone"><?php foreach (DateTimeZone::listIdentifiers() as $z): ?><option<?= $sel($z, $v('timezone')) ?>><?= e($z) ?></option><?php endforeach; ?></select><?= $err('timezone') ?></div>
      <div class="f"><label for="s-l">Language</label><select id="s-l" name="language"><?php foreach (Domain::LANGUAGES as $k => $l): ?><option value="<?= e($k) ?>"<?= $sel($k, $v('language')) ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
      <div class="f"><label for="s-th">Theme</label><select id="s-th" name="theme"><?php foreach (Domain::THEMES as $k => $l): ?><option value="<?= e($k) ?>"<?= $sel($k, $v('theme')) ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
      <div class="f"><label for="s-c">Base currency</label><select id="s-c" name="base_currency"><?php foreach (Domain::CURRENCIES as $c): ?><option<?= $sel($c, $v('base_currency')) ?>><?= e($c) ?></option><?php endforeach; ?></select></div>
    </div>
    <h2 style="margin-top:8px" id="risk">Risk rules</h2>
    <div class="grid-form">
      <div class="f"><label for="s-r">Default risk per trade (%)</label><input id="s-r" name="default_risk_pct" type="number" step="0.01" min="0.01" max="100" value="<?= e($v('default_risk_pct')) ?>"><?= $err('default_risk_pct') ?></div>
      <div class="f"><label for="s-rr">Default target (R)</label><input id="s-rr" name="default_target_rr" type="number" step="0.1" min="0.1" max="50" value="<?= e($v('default_target_rr')) ?>"><?= $err('default_target_rr') ?></div>
      <div class="f"><label for="s-ap">Higher-risk tier for A+ setups (%)</label><input id="s-ap" name="a_plus_risk_pct" type="number" step="0.01" min="0.01" max="100" value="<?= e($v('a_plus_risk_pct')) ?>" placeholder="optional, e.g. 1.5"><span class="f-hint">Only mentioned by the Edge Matrix when your history strongly supports a setup.</span><?= $err('a_plus_risk_pct') ?></div>
    </div>
    <div class="tm-alert tm-alert-info" style="margin-top:6px"><?= icon('info', 'icon icon-sm') ?><p><strong>Daily &amp; weekly loss limits are now set per account</strong> — each prop, live or demo account can have its own rules. <a href="<?= e(url('/terminal/accounts')) ?>">Set them in Accounts →</a></p></div>
    <h2 style="margin-top:8px">Tilt Circuit Breaker</h2>
    <p class="muted small">After this many losses inside the window, the journzey terminal locks new trade entries for the cooldown (TERMINAL LOCK). It cannot block orders at your broker.</p>
    <div class="grid-form">
      <div class="f"><label for="s-tc">Losses</label><input id="s-tc" name="tilt_loss_count" type="number" min="2" max="20" value="<?= e($v('tilt_loss_count')) ?>"><?= $err('tilt_loss_count') ?></div>
      <div class="f"><label for="s-tw">Window (minutes)</label><input id="s-tw" name="tilt_window_minutes" type="number" min="5" max="1440" value="<?= e($v('tilt_window_minutes')) ?>"></div>
      <div class="f"><label for="s-td">Cooldown (minutes)</label><input id="s-td" name="tilt_cooldown_minutes" type="number" min="5" max="1440" value="<?= e($v('tilt_cooldown_minutes')) ?>"></div>
    </div>
    <div><button class="tm-btn tm-btn-primary"><?= e(t('common.save')) ?> settings</button></div>
  </form>

  <div class="stack">
    <section class="panel stack">
      <div class="panel-head"><h2>Plan</h2><a class="tm-btn tm-btn-sm" href="<?= e(url('/terminal/billing')) ?>">Plan &amp; billing</a></div>
      <p style="margin:0"><strong><?= e($m['ent']['plan_name']) ?></strong><?= $m['ent']['paid'] ? ' · active until ' . e(fmt_date($m['plan_expires_at'], 'M j, Y')) : ' · demo accounts only' ?></p>
    </section>

    <section class="panel stack" id="security">
      <h2><?= $hasPassword ? 'Change password' : 'Set a password' ?></h2>
      <?php if (!$hasPassword): ?><p class="muted small">You sign in with Google. Optionally set a password to also sign in with your email.</p><?php endif; ?>
      <form method="post" action="<?= e(url('/terminal/settings/password')) ?>" class="stack"><?= csrf_field() ?>
        <?php if ($hasPassword): ?><div class="f"><label for="p-c">Current password</label><input id="p-c" type="password" name="current_password" autocomplete="current-password" required></div><?php endif; ?>
        <div class="f"><label for="p-n">New password</label><input id="p-n" type="password" name="new_password" autocomplete="new-password" minlength="10" required><span class="muted small">At least 10 characters with a letter and a number.</span></div>
        <div class="f"><label for="p-r">Repeat new password</label><input id="p-r" type="password" name="new_password_confirmation" autocomplete="new-password" required></div>
        <button class="tm-btn"><?= $hasPassword ? 'Change password' : 'Set password' ?></button>
      </form>
    </section>

    <section class="panel stack">
      <h2>Export my data</h2>
      <p class="muted small">Download everything journzey.ai stores about you.</p>
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a class="tm-btn tm-btn-sm" href="<?= e(url('/terminal/settings/export')) ?>">All data (JSON)</a>
        <a class="tm-btn tm-btn-sm" href="<?= e(url('/terminal/settings/export?format=csv')) ?>">All trades (CSV)</a>
      </div>
    </section>

    <section class="panel">
      <h2>Recent sign-ins</h2>
      <div class="table-wrap"><table class="tbl"><tbody>
        <?php foreach ($logins as $l): ?><tr><td><?= e(fmt_date($l['created_at'], 'M j, H:i')) ?></td><td><?= e(str_replace('_', ' ', $l['method'])) ?></td><td><?= $l['success'] ? '<span class="badge win">ok</span>' : '<span class="badge loss">failed</span>' ?></td><td class="muted small"><?= e($l['ip'] ?? '') ?></td></tr><?php endforeach; ?>
        <?php if (!$logins): ?><tr><td class="muted">No sign-ins recorded yet.</td></tr><?php endif; ?>
      </tbody></table></div>
    </section>

    <section class="panel stack" id="danger" style="border-color:color-mix(in srgb, var(--neg) 45%, var(--line))">
      <h2>Delete account</h2>
      <p class="muted small">Permanently deletes your profile, accounts, trades, journal, screenshots, AI conversations and capital history. Payment records are kept anonymised for accounting. This cannot be undone.</p>
      <form method="post" action="<?= e(url('/terminal/settings/delete')) ?>" class="stack" data-confirm="Delete your journzey.ai account and all data permanently?"><?= csrf_field() ?>
        <div class="f"><label for="d-c">Type DELETE to confirm</label><input id="d-c" name="confirm" autocomplete="off" required pattern="DELETE"></div>
        <?php if ($hasPassword): ?><div class="f"><label for="d-p">Password</label><input id="d-p" type="password" name="password" autocomplete="current-password" required></div><?php endif; ?>
        <button class="tm-btn">Delete my account</button>
      </form>
    </section>
  </div>
</div>
