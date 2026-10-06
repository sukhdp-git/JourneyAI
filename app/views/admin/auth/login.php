<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow">
<title>Sign in · Control Panel</title>
<link rel="icon" href="<?= e(asset('images/favicon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('admin/admin.css')) ?>">
</head>
<body class="auth-body">
<main class="auth-wrap">
  <div class="auth-card">
    <div class="auth-brand"><span class="brand-mark">j</span><div><strong><?= e(setting('site_name', 'journzey.ai')) ?></strong><small>Control Panel</small></div></div>
    <h1>Sign in</h1>
    <p class="muted">Use your administrator email and password.</p>
    <?php if ($timeout): ?><div class="alert alert-info"><p>Your session timed out after inactivity. Please sign in again.</p></div><?php endif; ?>
    <?php foreach ($flash as $f): ?><div class="alert alert-<?= e($f['type']) ?>" role="<?= $f['type'] === 'error' ? 'alert' : 'status' ?>"><p><?= e($f['message']) ?></p></div><?php endforeach; ?>
    <form method="post" action="<?= e(admin_url('login')) ?>" class="stack" data-loading-form>
      <?= csrf_field() ?>
      <div class="f">
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="<?= e($email) ?>" autocomplete="username" required autofocus maxlength="190">
      </div>
      <div class="f">
        <label for="password">Password</label>
        <div class="pw-wrap"><input id="password" type="password" name="password" autocomplete="current-password" required maxlength="1024"><button type="button" class="pw-toggle" data-pw-toggle aria-label="Show password"><?= icon('eye', 'icon icon-sm') ?></button></div>
      </div>
      <button type="submit" class="btn btn-primary btn-block btn-lg">Sign in</button>
    </form>
    <p class="auth-foot"><?= icon('lock', 'icon icon-sm') ?> Protected area. Sign-in attempts are rate-limited and logged.</p>
  </div>
  <a class="auth-back" href="<?= e(url('/')) ?>"><?= icon('arrow-left', 'icon icon-sm') ?> Back to website</a>
</main>
<script src="<?= e(asset('admin/admin.js')) ?>" defer></script>
</body>
</html>
