<div class="page-head"><div><h1>Send test email</h1><p class="muted">Sends a real message through your SMTP server and reports the result.</p></div></div>
<?php if (!$enabled): ?><div class="alert alert-warning"><?= icon('alert', 'icon icon-sm') ?><p>SMTP is currently disabled. <a href="<?= e(admin_url('email/smtp')) ?>">Configure and enable SMTP</a> first.</p></div><?php endif; ?>
<form method="post" action="<?= e(admin_url('email/test')) ?>" class="card form-card narrow-card" data-loading-form>
  <?= csrf_field() ?><input type="hidden" name="from" value="test">
  <div class="f<?= has_error('test_to') ? ' has-error' : '' ?>"><label for="test_to">Recipient email</label><input type="email" id="test_to" name="test_to" value="<?= e(old('test_to', $me['email'])) ?>" required><?= field_error('test_to') ?></div>
  <p class="muted small">Server: <?= e($host ?: 'not configured') ?></p>
  <div class="form-foot"><span class="spacer"></span><button class="btn btn-primary" type="submit"<?= $enabled ? '' : ' disabled' ?>><?= icon('send', 'icon icon-sm') ?> Send test email</button></div>
</form>
