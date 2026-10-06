<?php
/** @var array $state @var array $errors */
$f = function (string $name, string $label, string $type = 'text', string $default = '', string $hint = '') use ($errors) {
    $v = old($name, $default);
    return '<div class="sf' . (isset($errors[$name]) ? ' err' : '') . '"><label for="s-' . e($name) . '">' . e($label) . '</label>'
        . '<input id="s-' . e($name) . '" type="' . e($type) . '" name="' . e($name) . '" value="' . ($type === 'password' ? '' : e($v)) . '" autocomplete="off"' . ($type !== 'password' || true ? '' : '') . '>'
        . ($hint ? '<small>' . e($hint) . '</small>' : '') . (isset($errors[$name]) ? '<p class="e">' . e($errors[$name]) . '</p>' : '') . '</div>';
};
$scheme = is_https() ? 'https' : 'http';
$guessUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow">
<title>Install journzey.ai</title>
<link rel="stylesheet" href="<?= e(asset('admin/admin.css')) ?>">
</head>
<body class="auth-body">
<main class="setup-wrap">
  <div class="setup-card">
    <div class="auth-brand"><span class="brand-mark">j</span><div><strong>journzey.ai</strong><small>Website installer</small></div></div>
    <?php if (!empty($state['done'])): ?>
      <div class="alert alert-success"><p><strong>Installation complete.</strong> The installer has been locked and can no longer be used.</p></div>
      <p>Sign in to the Control Panel to configure your website, SMTP email and content.</p>
      <a class="btn btn-primary btn-block" href="<?= e($adminUrl) ?>">Open the Control Panel</a>
    <?php else: ?>
      <h1>Install the website</h1>
      <p class="muted">This one-time setup connects the database, imports the starter content and creates your Super Admin account.</p>
      <ul class="req-list">
        <?php foreach ($state['requirements'] as $label => $ok): ?><li class="<?= $ok ? 'ok' : 'bad' ?>"><?= $ok ? '✓' : '✕' ?> <?= e($label) ?></li><?php endforeach; ?>
      </ul>
      <?php if (!empty($errors['db'])): ?><div class="alert alert-error"><p><?= e($errors['db']) ?></p></div><?php endif; ?>
      <?php if (!empty($state['db_error'])): ?><div class="alert alert-error"><p>config/config.php exists but the database connection failed. Check the credentials in config/config.php.</p></div><?php endif; ?>
      <form method="post" action="<?= e(url('/setup')) ?>" class="setup-form">
        <?= csrf_field() ?>
        <?php if ($state['needs_config']): ?>
        <fieldset><legend>1. Database (from cPanel → MySQL® Databases)</legend>
          <div class="grid2"><?= $f('db_host', 'Database host', 'text', 'localhost') ?><?= $f('db_port', 'Port', 'number', '3306') ?></div>
          <?= $f('db_name', 'Database name', 'text', '', 'e.g. cpaneluser_journzey') ?>
          <div class="grid2"><?= $f('db_user', 'Database user') ?><?= $f('db_pass', 'Database password', 'password') ?></div>
          <?= $f('base_url', 'Website URL', 'url', $guessUrl, 'Full address without trailing slash, e.g. https://journzey.ai') ?>
        </fieldset>
        <?php else: ?>
          <div class="alert alert-info"><p>config/config.php was found<?= $state['tables'] ? ' and the database tables exist' : '; the database tables will be imported' ?>. Create your Super Admin account to finish.</p></div>
        <?php endif; ?>
        <fieldset><legend><?= $state['needs_config'] ? '2.' : '' ?> Super Admin account</legend>
          <?= $f('admin_name', 'Your name') ?>
          <?= $f('admin_email', 'Email (used to sign in)', 'email') ?>
          <div class="grid2"><?= $f('admin_password', 'Password', 'password', '', 'At least 10 characters with letters and numbers') ?><?= $f('admin_password_confirmation', 'Confirm password', 'password') ?></div>
        </fieldset>
        <button class="btn btn-primary btn-block" type="submit">Install</button>
      </form>
    <?php endif; ?>
  </div>
</main>
</body>
</html>
