<div class="page-head"><div><h1>System information</h1><p class="muted">Server environment and health checks. Credentials and secrets are never displayed.</p></div></div>
<div class="dash-grid">
  <section class="card span-2"><h2 class="card-title">Environment</h2><dl class="kv"><?php foreach ($info as $k => $v): ?><div><dt><?= e($k) ?></dt><dd><?= e($v) ?></dd></div><?php endforeach; ?></dl></section>
  <section class="card"><h2 class="card-title">Writable folders</h2><ul class="check-rows"><?php foreach ($checks as $k => $ok): ?><li class="<?= $ok ? 'ok' : 'bad' ?>"><?= icon($ok ? 'check-circle' : 'alert', 'icon icon-sm') ?><code><?= e($k) ?></code><span><?= $ok ? 'Writable' : 'Not writable' ?></span></li><?php endforeach; ?></ul>
    <h2 class="card-title mt">PHP extensions</h2><ul class="check-rows"><?php foreach ($ext as $k => $ok): ?><li class="<?= $ok ? 'ok' : 'bad' ?>"><?= icon($ok ? 'check-circle' : 'alert', 'icon icon-sm') ?><code><?= e($k) ?></code><span><?= $ok ? 'Loaded' : 'Missing' ?></span></li><?php endforeach; ?></ul></section>
  <section class="card span-2"><h2 class="card-title">Database records</h2><dl class="kv kv-3"><?php foreach ($counts as $k => $v): ?><div><dt><?= e(str_replace('_', ' ', $k)) ?></dt><dd><?= number_format($v) ?></dd></div><?php endforeach; ?></dl></section>
</div>
