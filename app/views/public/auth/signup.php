<div class="auth-card">
  <h2>Create your free account</h2>
  <p class="muted">Start in demo mode — log test trades and explore every tool. Upgrade when you go live.</p>
  <?= App\Core\View::partial('public/partials/flash', ['flash' => $flash]) ?>
  <?php if (!$enabled): ?>
    <div class="alert alert-error"><p>New sign-ups are currently closed.</p></div>
  <?php else: ?>
  <?= App\Core\View::partial('public/auth/_google', ['google' => $google]) ?>
  <?php if ($emailEnabled): ?>
  <div class="auth-or"><span>or sign up with email</span></div>
  <form method="post" action="<?= e(url('/signup')) ?>" class="form" data-validate novalidate>
    <?= csrf_field() ?>
    <div class="hp" aria-hidden="true"><label for="s-website">Website</label><input id="s-website" type="text" name="website" tabindex="-1" autocomplete="off"></div>
    <input type="hidden" name="timezone" value="UTC" data-tz>
    <?= App\Core\View::partial('public/partials/field', ['name' => 'name', 'label' => 'Your name', 'required' => true, 'autocomplete' => 'name', 'max' => 120]) ?>
    <?= App\Core\View::partial('public/partials/field', ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true, 'autocomplete' => 'email', 'max' => 190]) ?>
    <div class="field<?= has_error('password') ? ' has-error' : '' ?>">
      <label for="f-password">Password <span class="req" aria-hidden="true">*</span></label>
      <input id="f-password" type="password" name="password" required minlength="10" maxlength="200" autocomplete="new-password" aria-describedby="pw-hint">
      <small class="hint" id="pw-hint">At least 10 characters with letters and numbers.</small>
      <?= field_error('password') ?>
    </div>
    <div class="field field-check<?= has_error('terms') ? ' has-error' : '' ?>">
      <label><input type="checkbox" name="terms" value="1" required<?= old('terms') ? ' checked' : '' ?>> <span>I agree to the <a href="<?= e(url('/terms-and-conditions')) ?>">Terms</a>, <a href="<?= e(url('/privacy-policy')) ?>">Privacy Policy</a> and <a href="<?= e(url('/disclaimer')) ?>">Risk Disclaimer</a>.</span></label>
      <?= field_error('terms') ?>
    </div>
    <button class="btn btn-primary btn-lg btn-block" type="submit">Create account</button>
  </form>
  <?php endif; ?>
  <?php endif; ?>
  <p class="auth-switch">Already have an account? <a href="<?= e(url('/login')) ?>">Sign in</a></p>
  <p class="auth-legal">By continuing with Google, you agree to the <a href="<?= e(url('/terms-and-conditions')) ?>">Terms of Service</a> and <a href="<?= e(url('/privacy-policy')) ?>">Privacy Policy</a>.</p>
</div>
