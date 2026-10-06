<?php use App\Controllers\Admin\MessageController; ?>
<div class="page-head">
  <div><h1><?= e($m['subject'] ?: 'Message from ' . $m['name']) ?></h1><p class="muted">From <?= e($m['name']) ?> · <?= e(fmt_date($m['created_at'], 'M j, Y g:i A T')) ?></p></div>
  <div class="page-actions">
    <a class="btn btn-primary" href="mailto:<?= e($m['email']) ?>?subject=<?= rawurlencode('Re: ' . ($m['subject'] ?: 'Your message')) ?>"><?= icon('mail', 'icon icon-sm') ?> Reply by email</a>
    <button type="button" class="btn btn-danger-ghost" data-confirm="Delete this message?" data-confirm-action="<?= e(admin_url('messages/' . $m['id'] . '/delete')) ?>"><?= icon('trash', 'icon icon-sm') ?> Delete</button>
  </div>
</div>
<div class="detail-grid">
  <section class="card">
    <dl class="kv"><div><dt>Email</dt><dd><a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a></dd></div><div><dt>Phone</dt><dd><?= e($m['phone'] ?: '—') ?></dd></div></dl>
    <h3 class="sub-title">Message</h3>
    <div class="message-box"><?= nl2br(e($m['message'])) ?></div>
    <p class="muted small">IP <?= e($m['ip'] ?? '—') ?></p>
  </section>
  <aside class="stack-lg">
    <section class="card"><h2 class="card-title">Status</h2>
      <form method="post" action="<?= e(admin_url('messages/' . $m['id'] . '/status')) ?>" class="stack"><?= csrf_field() ?>
        <div class="status-pick"><?php foreach (MessageController::STATUSES as $k => $l): ?><label><input type="radio" name="status" value="<?= e($k) ?>"<?= $m['status'] === $k ? ' checked' : '' ?>><span class="badge st-<?= e($k) ?>"><?= e($l) ?></span></label><?php endforeach; ?></div>
        <button class="btn btn-secondary btn-sm">Update status</button>
      </form>
    </section>
    <section class="card"><h2 class="card-title">Email delivery</h2>
      <?php if ($emails): ?><ul class="mini-list"><?php foreach ($emails as $em): ?><li><div><strong><?= e($em['subject']) ?></strong><small>To <?= e($em['recipient']) ?></small><span class="badge st-<?= $em['status'] === 'sent' ? 'published' : ($em['status'] === 'failed' ? 'warn' : 'draft') ?>"><?= e(ucfirst($em['status'])) ?></span><?php if ($em['error']): ?><small class="err-text"><?= e($em['error']) ?></small><?php endif; ?></div></li><?php endforeach; ?></ul><?php else: ?><p class="muted small">No emails recorded.</p><?php endif; ?>
    </section>
  </aside>
</div>
