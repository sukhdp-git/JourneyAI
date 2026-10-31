<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow">
<title>Two-step sign-in · Control Panel</title>
<link rel="icon" href="<?= e(asset('images/favicon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('admin/admin.css')) ?>">
</head>
<body class="auth-body">
<main class="auth-wrap">
  <div class="auth-card">
    <div class="auth-brand"><span class="brand-mark">j</span><div><strong><?= e(setting('site_name', 'journzey.ai')) ?></strong><small>Control Panel</small></div></div>
    <h1>Two-step sign-in</h1>
    <p class="muted">Open your authenticator app and enter the 6-digit code for the Control Panel.</p>
    <?php foreach ($flash as $f): ?><div class="alert alert-<?= e($f['type']) ?>" role="<?= $f['type'] === 'error' ? 'alert' : 'status' ?>"><p><?= e($f['message']) ?></p></div><?php endforeach; ?>
    <form method="post" action="<?= e(admin_url('login/code')) ?>" class="stack" data-loading-form>
      <?= csrf_field() ?>
      <div class="f">
        <label for="code">6-digit code</label>
        <input id="code" name="code" inputmode="numeric" pattern="[0-9 ]{6,7}" maxlength="7" autocomplete="one-time-code" required autofocus>
      </div>
      <button type="submit" class="btn btn-primary btn-block btn-lg">Verify</button>
      <a class="btn btn-ghost btn-block" href="<?= e(admin_url('login')) ?>">Back to sign-in</a>
    </form>
    <p class="auth-foot"><?= icon('lock', 'icon icon-sm') ?> Protected area. Sign-in attempts are rate-limited and logged.</p>
  </div>
  <a class="auth-back" href="<?= e(url('/')) ?>"><?= icon('arrow-left', 'icon icon-sm') ?> Back to website</a>
</main>
<script src="<?= e(asset('admin/admin.js')) ?>" defer></script>
</body>
</html>
