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
