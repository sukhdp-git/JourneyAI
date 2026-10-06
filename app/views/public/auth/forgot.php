<div class="auth-card">
  <h2>Reset your password</h2>
  <p class="muted">Enter your account email and we will send a reset link.</p>
  <?= App\Core\View::partial('public/partials/flash', ['flash' => $flash]) ?>
  <form method="post" action="<?= e(url('/forgot-password')) ?>" class="form" data-validate novalidate>
    <?= csrf_field() ?>
    <?= App\Core\View::partial('public/partials/field', ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true, 'autocomplete' => 'email']) ?>
    <button class="btn btn-primary btn-lg btn-block" type="submit">Send reset link</button>
  </form>
  <p class="auth-switch"><a href="<?= e(url('/login')) ?>">Back to sign in</a></p>
  <p class="auth-legal">Signed up with Google? Use “Continue with Google” instead — no password needed.</p>
</div>
