<div class="page-head"><div><h1>My profile</h1><p class="muted"><?= e($user['email']) ?> · <?= e($user['role_name']) ?></p></div></div>
<form method="post" action="<?= e(admin_url('profile')) ?>" class="card form-card narrow-card" novalidate>
  <?= csrf_field() ?>
  <div class="form-grid">
    <?= App\Core\View::partial('admin/partials/field', ['f' => ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'rules' => 'required'], 'value' => old('name', $user['name'])]) ?>
    <div class="f"><label>Email</label><input type="email" value="<?= e($user['email']) ?>" disabled><small class="help">Ask a Super Admin to change your sign-in email.</small></div>
    <h2 class="card-title f">Change password</h2>
    <?= App\Core\View::partial('admin/partials/field', ['f' => ['name' => 'current_password', 'label' => 'Current password', 'type' => 'password']]) ?>
    <?= App\Core\View::partial('admin/partials/field', ['f' => ['name' => 'new_password', 'label' => 'New password', 'type' => 'password', 'width' => 'half', 'help' => 'At least 10 characters with letters and numbers.']]) ?>
    <?= App\Core\View::partial('admin/partials/field', ['f' => ['name' => 'new_password_confirmation', 'label' => 'Confirm new password', 'type' => 'password', 'width' => 'half']]) ?>
  </div>
  <div class="form-foot"><span class="spacer"></span><button class="btn btn-primary" type="submit">Save profile</button></div>
</form>

<section class="card form-card narrow-card" id="two-step" style="margin-top:18px">
  <h2 class="card-title">Two-step sign-in <?= $twoFa ? '<span class="badge badge-success">On</span>' : '<span class="badge">Off</span>' ?></h2>
  <?php if ($twoFa): ?>
  <p class="muted">Every sign-in asks for a 6-digit code from your authenticator app. To turn it off, enter a current code.</p>
  <form method="post" action="<?= e(admin_url('profile/two-step')) ?>" class="stack">
    <?= csrf_field() ?><input type="hidden" name="action" value="disable">
    <div class="f"><label for="tf-off">Current code</label><input id="tf-off" name="code" inputmode="numeric" maxlength="7" autocomplete="one-time-code" required></div>
    <button class="btn" type="submit">Turn off two-step sign-in</button>
  </form>
  <?php else: ?>
  <p class="muted">Protects the Control Panel even if your password leaks. Install Google Authenticator, Microsoft Authenticator or Authy, scan the code, then enter the 6 digits it shows.</p>
  <div style="display:flex;gap:18px;flex-wrap:wrap;align-items:center;margin:12px 0">
    <canvas width="180" height="180" data-otp-qr="<?= e($otpUri) ?>" style="background:#fff;border-radius:10px;padding:8px" aria-label="QR code for your authenticator app"></canvas>
    <div><small class="muted">Can't scan? Enter this key:</small><br><code style="font-size:15px;letter-spacing:.08em;word-break:break-all"><?= e(trim(chunk_split($secret, 4, ' '))) ?></code></div>
  </div>
  <form method="post" action="<?= e(admin_url('profile/two-step')) ?>" class="stack">
    <?= csrf_field() ?><input type="hidden" name="action" value="enable">
    <div class="f"><label for="tf-on">6-digit code from the app</label><input id="tf-on" name="code" inputmode="numeric" maxlength="7" autocomplete="one-time-code" required></div>
    <button class="btn btn-primary" type="submit">Turn on two-step sign-in</button>
  </form>
  <script src="<?= e(asset('js/vendor/qrcode.js')) ?>"></script>
  <script>(function(){var c=document.querySelector('[data-otp-qr]');if(!c||!window.qrcode)return;var q=qrcode(0,'M');q.addData(c.dataset.otpQr);q.make();var n=q.getModuleCount(),x=c.getContext('2d'),s=(c.width-16)/n;x.fillStyle='#fff';x.fillRect(0,0,c.width,c.height);x.fillStyle='#111';for(var r=0;r<n;r++)for(var k=0;k<n;k++)if(q.isDark(r,k))x.fillRect(8+k*s,8+r*s,Math.ceil(s),Math.ceil(s));})();</script>
  <?php endif; ?>
</section>
