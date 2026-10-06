<?php foreach ($flash ?? [] as $f): ?>
<div class="alert alert-<?= e($f['type']) ?>" role="<?= $f['type'] === 'error' ? 'alert' : 'status' ?>"><?= icon($f['type'] === 'error' ? 'alert' : 'check-circle', 'icon') ?><p><?= e($f['message']) ?></p></div>
<?php endforeach; ?>
