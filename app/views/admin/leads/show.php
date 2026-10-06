<?php
use App\Controllers\Admin\LeadController;
/** @var array $lead @var array $notes @var array $emails */
$typeIcon = ['note' => 'message', 'status' => 'refresh', 'assignment' => 'user', 'email' => 'mail', 'edit' => 'edit'];
$g = fn (string $k) => old($k, $lead[$k] ?? '');
?>
<div class="page-head">
  <div><h1><?= e($lead['name']) ?></h1><p class="muted">Lead #<?= (int) $lead['id'] ?> · received <?= e(fmt_date($lead['created_at'], 'M j, Y g:i A T')) ?> via <?= e($lead['source']) ?></p></div>
  <div class="page-actions">
    <a class="btn btn-secondary" href="mailto:<?= e($lead['email']) ?>"><?= icon('mail', 'icon icon-sm') ?> Email</a>
    <?php if ($lead['whatsapp'] || $lead['phone']): ?><a class="btn btn-secondary" href="https://wa.me/<?= e(preg_replace('/\D/', '', $lead['whatsapp'] ?: $lead['phone'])) ?>" target="_blank" rel="noopener"><?= icon('whatsapp', 'icon icon-sm') ?> WhatsApp</a><?php endif; ?>
    <button type="button" class="btn btn-danger-ghost" data-confirm="Delete this lead and its history?" data-confirm-action="<?= e(admin_url('leads/' . $lead['id'] . '/delete')) ?>"><?= icon('trash', 'icon icon-sm') ?> Delete</button>
  </div>
</div>
<div class="detail-grid">
  <div class="stack-lg">
    <section class="card">
      <h2 class="card-title">Submission</h2>
      <dl class="kv">
        <div><dt>Email</dt><dd><a href="mailto:<?= e($lead['email']) ?>"><?= e($lead['email']) ?></a></dd></div>
        <div><dt>Phone</dt><dd><?= $lead['phone'] ? '<a href="tel:' . e(preg_replace('/[^0-9+]/', '', $lead['phone'])) . '">' . e($lead['phone']) . '</a>' : '—' ?></dd></div>
        <div><dt>WhatsApp</dt><dd><?= e($lead['whatsapp'] ?: '—') ?></dd></div>
        <div><dt>Company</dt><dd><?= e($lead['company'] ?: '—') ?></dd></div>
        <div><dt>Interested in</dt><dd><?= e($lead['service_name'] ?: '—') ?></dd></div>
        <div><dt>Preferred time</dt><dd><?= e(trim(($lead['preferred_date'] ? date('D, M j, Y', strtotime($lead['preferred_date'])) : 'Any day') . ' ' . ($lead['preferred_time'] ?? ''))) ?></dd></div>
        <div><dt>Privacy consent</dt><dd><?= $lead['consent'] ? 'Given' : 'Not recorded' ?></dd></div>
        <div><dt>Notification email</dt><dd><span class="badge st-<?= $lead['email_status'] === 'sent' ? 'published' : ($lead['email_status'] === 'failed' ? 'warn' : 'draft') ?>"><?= e(ucfirst($lead['email_status'])) ?></span><?= $lead['email_error'] ? '<small class="block muted">' . e($lead['email_error']) . '</small>' : '' ?></dd></div>
      </dl>
      <h3 class="sub-title">Message</h3>
      <div class="message-box"><?= $lead['message'] ? nl2br(e($lead['message'])) : '<span class="muted">No message.</span>' ?></div>
      <p class="muted small">IP <?= e($lead['ip'] ?? '—') ?> · <?= e(mb_strimwidth((string) $lead['user_agent'], 0, 90, '…')) ?></p>
    </section>
    <section class="card">
      <h2 class="card-title">Notes & history</h2>
      <form method="post" action="<?= e(admin_url('leads/' . $lead['id'] . '/notes')) ?>" class="note-form"><?= csrf_field() ?>
        <div class="f<?= has_error('note') ? ' has-error' : '' ?>"><label for="note">Add an internal note</label><textarea id="note" name="note" rows="3" maxlength="5000" placeholder="Call summary, next steps…"><?= e(old('note')) ?></textarea><?= field_error('note') ?></div>
        <button class="btn btn-primary btn-sm" type="submit"><?= icon('plus', 'icon icon-sm') ?> Add note</button>
      </form>
      <?php if ($notes): ?>
      <ol class="timeline">
        <?php foreach ($notes as $n): ?>
        <li class="tl-<?= e($n['type']) ?>"><span class="tl-icon"><?= icon($typeIcon[$n['type']] ?? 'dot', 'icon icon-sm') ?></span><div><p><?= nl2br(e($n['body'])) ?></p><small class="muted"><?= e($n['admin_name'] ?: 'System') ?> · <?= e(fmt_date($n['created_at'], 'M j, Y g:i A')) ?></small></div></li>
        <?php endforeach; ?>
      </ol>
      <?php else: ?><p class="muted">No notes yet.</p><?php endif; ?>
    </section>
    <section class="card" id="edit">
      <details<?= ($GLOBALS['__errors'] ?? []) && !has_error('note') ? ' open' : '' ?>><summary class="card-title">Edit lead details</summary>
      <form method="post" action="<?= e(admin_url('leads/' . $lead['id'])) ?>" class="form-grid mt"><?= csrf_field() ?>
        <?php foreach ([['name', 'Name', 'text', 'half'], ['email', 'Email', 'email', 'half'], ['phone', 'Phone', 'text', 'half'], ['whatsapp', 'WhatsApp', 'text', 'half'], ['company', 'Company', 'text', 'half'], ['preferred_date', 'Preferred date', 'date', 'third'], ['preferred_time', 'Preferred time', 'text', 'third']] as [$k, $l, $t, $w]): ?>
          <?= App\Core\View::partial('admin/partials/field', ['f' => ['name' => $k, 'label' => $l, 'type' => $t, 'width' => $w], 'value' => $g($k)]) ?>
        <?php endforeach; ?>
        <?= App\Core\View::partial('admin/partials/field', ['f' => ['name' => 'service_id', 'label' => 'Service', 'type' => 'select', 'width' => 'half', 'options' => ['' => $lead['service_name'] && !$lead['service_id'] ? $lead['service_name'] : '— Not specified —'] + array_column($services, 'title', 'id')], 'value' => $g('service_id')]) ?>
        <?= App\Core\View::partial('admin/partials/field', ['f' => ['name' => 'message', 'label' => 'Message', 'type' => 'textarea'], 'value' => $g('message')]) ?>
        <div class="f"><button class="btn btn-primary" type="submit">Save details</button></div>
      </form>
      </details>
    </section>
  </div>
  <aside class="stack-lg">
    <section class="card">
      <h2 class="card-title">Status</h2>
      <form method="post" action="<?= e(admin_url('leads/' . $lead['id'] . '/status')) ?>" class="stack"><?= csrf_field() ?>
        <div class="status-pick"><?php foreach (LeadController::STATUSES as $k => $l): ?><label><input type="radio" name="status" value="<?= e($k) ?>"<?= $lead['status'] === $k ? ' checked' : '' ?>><span class="badge st-<?= e($k) ?>"><?= e($l) ?></span></label><?php endforeach; ?></div>
        <button class="btn btn-secondary btn-sm" type="submit">Update status</button>
      </form>
    </section>
    <section class="card">
      <h2 class="card-title">Assigned to</h2>
      <form method="post" action="<?= e(admin_url('leads/' . $lead['id'] . '/assign')) ?>" class="stack"><?= csrf_field() ?>
        <label class="sr-only" for="assigned_to">Assign to</label>
        <select id="assigned_to" name="assigned_to"><option value="">— Unassigned —</option><?php foreach ($admins as $a): ?><option value="<?= (int) $a['id'] ?>"<?= (int) $lead['assigned_to'] === (int) $a['id'] ? ' selected' : '' ?>><?= e($a['name']) ?></option><?php endforeach; ?></select>
        <button class="btn btn-secondary btn-sm" type="submit">Save assignment</button>
      </form>
    </section>
    <section class="card">
      <h2 class="card-title">Email delivery</h2>
      <?php if ($emails): ?><ul class="mini-list"><?php foreach ($emails as $em): ?><li><div><strong><?= e($em['subject']) ?></strong><small>To <?= e($em['recipient']) ?> · <?= e(fmt_date($em['created_at'], 'M j, g:i A')) ?></small><span class="badge st-<?= $em['status'] === 'sent' ? 'published' : ($em['status'] === 'failed' ? 'warn' : 'draft') ?>"><?= e(ucfirst($em['status'])) ?></span><?php if ($em['error']): ?><small class="err-text"><?= e($em['error']) ?></small><?php endif; ?></div></li><?php endforeach; ?></ul>
      <?php else: ?><p class="muted small">No emails recorded.</p><?php endif; ?>
    </section>
  </aside>
</div>
