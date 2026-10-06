<div class="auth-card">
  <h2>Choose a new password</h2>
  <?= App\Core\View::partial('public/partials/flash', ['flash' => $flash]) ?>
  <form method="post" action="<?= e(url('/reset-password/' . $token)) ?>" class="form" novalidate>
    <?= csrf_field() ?>
    <div class="field<?= has_error('password') ? ' has-error' : '' ?>"><label for="p1">New password</label><input id="p1" type="password" name="password" required minlength="10" autocomplete="new-password"><?= field_error('password') ?></div>
    <div class="field"><label for="p2">Confirm new password</label><input id="p2" type="password" name="password_confirmation" required minlength="10" autocomplete="new-password"></div>
    <button class="btn btn-primary btn-lg btn-block" type="submit">Save password</button>
  </form>
</div>
