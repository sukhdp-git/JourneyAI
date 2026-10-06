<div class="auth-card">
  <h2>Sign in</h2>
  <p class="muted">Welcome back. Sign in to open your terminal.</p>
  <?= App\Core\View::partial('public/partials/flash', ['flash' => $flash]) ?>
  <?= App\Core\View::partial('public/auth/_google', ['google' => $google]) ?>
  <div class="auth-or"><span>or sign in with email</span></div>
  <form method="post" action="<?= e(url('/login')) ?>" class="form" data-validate novalidate>
    <?= csrf_field() ?>
    <?= App\Core\View::partial('public/partials/field', ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true, 'autocomplete' => 'email', 'max' => 190]) ?>
    <div class="field">
      <label for="f-password">Password <a class="auth-link-right" href="<?= e(url('/forgot-password')) ?>">Forgot password?</a></label>
      <input id="f-password" type="password" name="password" required autocomplete="current-password" maxlength="200">
    </div>
    <button class="btn btn-primary btn-lg btn-block" type="submit">Sign in</button>
  </form>
  <p class="auth-switch">New to <?= e(setting('site_name', 'journzey.ai')) ?>? <a href="<?= e(url('/signup')) ?>">Create a free account</a></p>
  <p class="auth-legal">By continuing, you agree to the <a href="<?= e(url('/terms-and-conditions')) ?>">Terms of Service</a> and <a href="<?= e(url('/privacy-policy')) ?>">Privacy Policy</a>. <a href="<?= e(url('/security')) ?>">Security</a></p>
</div>
