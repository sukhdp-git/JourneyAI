<?php use App\Trading\Domain; ?>
<?php if (!$configured): ?>
<div class="tm-alert tm-alert-warn" role="status"><p><strong><?= e(t('ai.not_configured')) ?></strong> The site owner can add an Anthropic or Gemini API key in the Control Panel → Integrations. Your journal, analytics and Edge Matrix work without it.</p></div>
<?php endif; ?>
<div class="grid g-main">
  <section class="panel stack">
    <div class="panel-head">
      <h2><?= $conv ? e($conv['title']) : 'Ask your coach' ?></h2>
      <span class="muted small"><span data-ai-remaining><?= (int) $remaining ?></span> / <?= (int) $limit ?> messages left today<?= (int) $acc['has_demo_data'] ? ' · DEMO DATA' : '' ?></span>
    </div>
    <p class="muted small">The coach reads aggregated statistics from your active account (<?= e($acc['name']) ?>, last 90 days) and your recent journal entries. It never sees other traders' data. Answers are AI-generated and are not financial advice.</p>
    <div class="chat" data-chat-log aria-live="polite">
      <?php foreach ($messages as $msg): ?>
        <div class="msg <?= e($msg['role']) ?>"><?= e($msg['content']) ?><?php if ($msg['role'] === 'assistant'): ?><small>AI-generated · <?= e(fmt_date($msg['created_at'], 'M j, H:i')) ?></small><?php endif; ?></div>
      <?php endforeach; ?>
      <?php if (!$messages): ?><div class="msg assistant">Ask about your win rate, best setups, emotional patterns, sessions or risk. I will answer using your own numbers.</div><?php endif; ?>
    </div>
    <div class="prompt-chips" style="display:flex;flex-wrap:wrap;gap:6px">
      <?php foreach ($prompts as $p): ?><button type="button" class="tm-btn tm-btn-sm tm-btn-ghost" data-prompt="<?= e($p) ?>"<?= $configured ? '' : ' disabled' ?>><?= e($p) ?></button><?php endforeach; ?>
    </div>
    <form data-chat-form data-conversation="<?= $conv ? (int) $conv['id'] : '' ?>" class="stack">
      <label class="sr-only" for="chat-q">Your question</label>
      <textarea id="chat-q" rows="3" maxlength="4000" placeholder="e.g. Why do I lose money on Fridays?"<?= $configured ? '' : ' disabled' ?>></textarea>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <label class="sr-only" for="chat-lang">Reply language</label>
        <select id="chat-lang" name="lang" style="width:auto"><?php foreach (Domain::LANGUAGES as $k => $name): ?><option value="<?= e($k) ?>"<?= ($m['language'] ?: 'en') === $k ? ' selected' : '' ?>><?= e($name) ?></option><?php endforeach; ?></select>
        <button class="tm-btn tm-btn-primary" type="submit"<?= $configured ? '' : ' disabled' ?>>Send</button>
        <span class="muted small">Ctrl/⌘ + Enter to send</span>
        <?php if ($conv): ?><a class="tm-btn tm-btn-sm tm-btn-ghost" href="<?= e(url('/terminal/coach')) ?>" style="margin-left:auto">New conversation</a><?php endif; ?>
      </div>
    </form>
  </section>
  <div class="stack">
    <section class="panel stack">
      <div class="panel-head"><h2>Performance reviews</h2></div>
      <p class="muted small">A structured review of the previous calendar week or month for the active account.</p>
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <button type="button" class="tm-btn tm-btn-sm" data-review="weekly"<?= $configured ? '' : ' disabled' ?>>Weekly review</button>
        <button type="button" class="tm-btn tm-btn-sm" data-review="monthly"<?= $configured ? '' : ' disabled' ?>>Monthly review</button>
      </div>
      <div id="review-out" class="msg assistant print-area" data-review-out style="max-width:100%;min-height:48px">Choose a review to generate it.</div>
      <div data-review-actions hidden style="display:flex;gap:8px">
        <button type="button" class="tm-btn tm-btn-sm" data-copy-target="#review-out">Copy</button>
        <button type="button" class="tm-btn tm-btn-sm" data-print>Print / save as PDF</button>
      </div>
    </section>
    <section class="panel">
      <div class="panel-head"><h2>Conversations</h2><span class="muted small"><?= count($conversations) ?></span></div>
      <?php if ($conversations): ?>
      <ul style="list-style:none;margin:0;padding:0">
        <?php foreach ($conversations as $c): ?>
        <li style="display:flex;gap:8px;align-items:center;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--line)">
          <a href="<?= e(url('/terminal/coach/' . $c['id'])) ?>"<?= $conv && (int) $conv['id'] === (int) $c['id'] ? ' aria-current="page" class="strong"' : '' ?>><?= e($c['title']) ?><br><span class="muted small"><?= e(fmt_date($c['updated_at'], 'M j, H:i')) ?></span></a>
          <form method="post" action="<?= e(url('/terminal/coach/' . $c['id'] . '/delete')) ?>" data-confirm="Delete this conversation?"><?= csrf_field() ?><button class="tm-btn tm-btn-sm tm-btn-ghost" aria-label="Delete conversation"><?= icon('trash', 'icon icon-sm') ?></button></form>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php else: ?><p class="muted small">No conversations yet.</p><?php endif; ?>
    </section>
  </div>
</div>
