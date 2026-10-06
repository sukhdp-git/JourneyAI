<?php /** @var array $cfg @var bool $hasPassword */ $o = fn ($k) => old($k, (string) ($cfg[$k] ?? '')); ?>
<div class="page-head"><div><h1>SMTP settings</h1><p class="muted">Outgoing email for lead and contact notifications. Credentials are stored on the server only; the password is encrypted and never shown again.</p></div>
  <span class="badge lg <?= (int) $cfg['is_enabled'] ? 'st-published' : 'st-draft' ?>"><?= (int) $cfg['is_enabled'] ? 'SMTP enabled' : 'SMTP disabled' ?></span></div>
<div class="detail-grid">
  <form method="post" action="<?= e(admin_url('email/smtp')) ?>" class="card form-card" autocomplete="off" data-dirty-check novalidate>
    <?= csrf_field() ?>
    <div class="form-grid">
      <?= App\Core\View::partial('admin/partials/field', ['f' => ['name' => 'is_enabled', 'label' => 'Enable SMTP', 'type' => 'checkbox'], 'value' => old('is_enabled', (string) $cfg['is_enabled'])]) ?>
      <?= App\Core\View::partial('admin/partials/field', ['f' => ['name' => 'host', 'label' => 'SMTP host', 'type' => 'text', 'width' => 'half', 'help' => 'e.g. mail.yourdomain.com (cPanel → Email Accounts → Connect Devices)'], 'value' => $o('host')]) ?>
      <?= App\Core\View::partial('admin/partials/field', ['f' => ['name' => 'port', 'label' => 'Port', 'type' => 'number', 'rules' => 'required', 'width' => 'third', 'help' => '465 for SSL, 587 for TLS'], 'value' => $o('port')]) ?>
      <?= App\Core\View::partial('admin/partials/field', ['f' => ['name' => 'encryption', 'label' => 'Encryption', 'type' => 'select', 'width' => 'third', 'options' => ['ssl' => 'SSL (port 465)', 'tls' => 'TLS / STARTTLS (port 587)', 'none' => 'None (not recommended)']], 'value' => $o('encryption')]) ?>
      <?= App\Core\View::partial('admin/partials/field', ['f' => ['name' => 'username', 'label' => 'SMTP username', 'type' => 'text', 'width' => 'half', 'help' => 'Usually the full email address.'], 'value' => $o('username')]) ?>
      <div class="f w-half<?= has_error('password') ? ' has-error' : '' ?>">
        <label for="smtp-pass">SMTP password</label>
        <div class="pw-wrap"><input type="password" id="smtp-pass" name="password" value="" autocomplete="new-password" placeholder="<?= $hasPassword ? '•••••••• (saved — leave empty to keep)' : 'Enter password' ?>"><button type="button" class="pw-toggle" data-pw-toggle aria-label="Show password"><?= icon('eye', 'icon icon-sm') ?></button></div>
        <?php if ($hasPassword): ?><label class="check small"><input type="checkbox" name="clear_password" value="1"> Remove the saved password</label><?php endif; ?>
      </div>
      <?= App\Core\View::partial('admin/partials/field', ['f' => ['name' => 'from_email', 'label' => 'From email', 'type' => 'email', 'width' => 'half', 'help' => 'Should be an address on your domain to avoid spam filtering.'], 'value' => $o('from_email')]) ?>
      <?= App\Core\View::partial('admin/partials/field', ['f' => ['name' => 'from_name', 'label' => 'From name', 'type' => 'text', 'width' => 'half'], 'value' => $o('from_name')]) ?>
      <?= App\Core\View::partial('admin/partials/field', ['f' => ['name' => 'reply_to', 'label' => 'Reply-to email', 'type' => 'email', 'width' => 'half', 'help' => 'Optional. Notification emails reply to the visitor automatically.'], 'value' => $o('reply_to')]) ?>
    </div>
    <div class="form-foot"><span class="spacer"></span><button class="btn btn-primary" type="submit"><?= icon('check', 'icon icon-sm') ?> Save settings</button></div>
  </form>
  <aside class="stack-lg">
    <form method="post" action="<?= e(admin_url('email/test')) ?>" class="card" data-loading-form>
      <?= csrf_field() ?><input type="hidden" name="from" value="smtp">
      <h2 class="card-title"><?= icon('send', 'icon icon-sm') ?> Send test email</h2>
      <p class="muted small">Save your settings first, then send a real test through the configured server.</p>
      <div class="f<?= has_error('test_to') ? ' has-error' : '' ?>"><label for="test_to">Recipient</label><input type="email" id="test_to" name="test_to" value="<?= e(old('test_to', $me['email'])) ?>" required><?= field_error('test_to') ?></div>
      <button class="btn btn-secondary btn-block" type="submit">Send test email</button>
    </form>
    <div class="card help-card"><h2 class="card-title">Typical cPanel settings</h2><ul class="small"><li>Host: <code>mail.yourdomain.com</code></li><li>SSL on port <code>465</code>, or TLS on <code>587</code></li><li>Username: the full mailbox address</li><li>Password: the mailbox password</li><li>From email: the same mailbox</li></ul></div>
  </aside>
</div>
