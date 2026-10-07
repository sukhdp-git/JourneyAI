<?php
use App\Controllers\Terminal\CoachController;
use App\Trading\Domain;
use App\Trading\Edge;
$cur = $acc['currency'];
$rv = $review; $rs = $rv['summary'];
?>
<?php if (!$configured): ?>
<div class="tm-alert tm-alert-warn" role="status"><p><strong><?= e(t('ai.not_configured')) ?></strong> The site owner can add an Anthropic or Gemini API key in the Control Panel → Integrations. Your edge summary, reviews, journal and analytics below work without it.</p></div>
<?php endif; ?>

<!-- Automatic home state: computed from your data, no AI call needed -->
<div class="coach-home">
  <section class="panel good">
    <h2>Your mathematical edge &amp; strengths</h2>
    <?php if (!$home['enough']): ?><p class="muted small">More trading data is required to identify this pattern reliably (at least 10 closed trades).</p>
    <?php elseif (!$home['strengths']): ?><p class="muted small">More trading data is required to identify this pattern reliably.</p>
    <?php else: foreach ($home['strengths'] as $x): ?><div class="insight"><strong class="up">▲</strong> <strong><?= e($x['title']) ?></strong><p class="muted"><?= e($x['detail']) ?></p></div><?php endforeach; endif; ?>
  </section>
  <section class="panel bad">
    <h2>Your critical leaks</h2>
    <?php if (!$home['enough']): ?><p class="muted small">More trading data is required to identify this pattern reliably (at least 10 closed trades).</p>
    <?php elseif (!$home['leaks']): ?><p class="muted small">No clear leak in your data yet. More trading data is required to identify this pattern reliably.</p>
    <?php else: foreach ($home['leaks'] as $x): ?><div class="insight"><strong class="down">▼</strong> <strong><?= e($x['title']) ?></strong><p class="muted"><?= e($x['detail']) ?></p></div><?php endforeach; endif; ?>
  </section>
</div>
<p class="muted small" style="margin:-4px 0 12px">Calculated from <?= e($acc['name']) ?><?= (int) $acc['has_demo_data'] ? ' (DEMO DATA)' : '' ?> — only conclusions supported by your recorded trades are shown. Historical patterns do not guarantee future results.</p>

<div class="grid g-main">
  <section class="panel stack">
    <div class="panel-head">
      <h2><?= $conv ? e($conv['title']) : 'Ask your coach' ?></h2>
      <span class="muted small"><span data-ai-remaining><?= (int) $remaining ?></span> / <?= (int) $limit ?> messages left today</span>
    </div>
    <p class="muted small">The coach reads your trades, P&amp;L, strategies, sessions, hours, R, risk, journal entries, emotions, mistake tags, discipline answers, key lessons and loss limits — prepared on the server for this account only. Answers are AI-generated and are not financial advice.</p>
    <div class="chat" data-chat-log aria-live="polite">
      <?php foreach ($messages as $msg): ?>
        <div class="msg <?= e($msg['role']) ?>"><?= e($msg['content']) ?><?php if ($msg['role'] === 'assistant'): ?><small>AI-generated · <?= e(fmt_date($msg['created_at'], 'M j, H:i')) ?></small><?php endif; ?></div>
      <?php endforeach; ?>
      <?php if (!$messages): ?><div class="msg assistant">Ask about your strongest setup, why you are losing money, sessions, emotions, discipline or your key lessons. I answer from your own data.</div><?php endif; ?>
    </div>
    <div class="prompt-chips" style="display:flex;flex-wrap:wrap;gap:6px">
      <?php foreach ($prompts as $p): ?><button type="button" class="tm-btn tm-btn-sm tm-btn-ghost" data-prompt="<?= e($p) ?>"<?= $configured ? '' : ' disabled' ?>><?= e($p) ?></button><?php endforeach; ?>
    </div>
    <form data-chat-form data-conversation="<?= $conv ? (int) $conv['id'] : '' ?>" class="stack">
      <label class="sr-only" for="chat-q">Your question</label>
      <textarea id="chat-q" rows="3" maxlength="4000" placeholder="e.g. Which setup made me the most money this month?"<?= $configured ? '' : ' disabled' ?>></textarea>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <button type="button" class="tm-btn" data-voice-into="#chat-q" data-voice-note="#chat-voice-note"<?= $configured ? '' : ' disabled' ?>><?= icon('mic', 'icon icon-sm') ?> Speak</button>
        <label class="sr-only" for="chat-lang">Reply language</label>
        <select id="chat-lang" name="lang" style="width:auto"><?php foreach (Domain::LANGUAGES as $k => $name): ?><option value="<?= e($k) ?>"<?= ($m['language'] ?: 'en') === $k ? ' selected' : '' ?>><?= e($name) ?></option><?php endforeach; ?></select>
        <button class="tm-btn tm-btn-primary" type="submit"<?= $configured ? '' : ' disabled' ?>>Send</button>
        <span class="muted small">Speech becomes editable text — nothing is sent until you press Send.</span>
        <?php if ($conv): ?><a class="tm-btn tm-btn-sm tm-btn-ghost" href="<?= e(url('/terminal/coach')) ?>" style="margin-left:auto">New conversation</a><?php endif; ?>
      </div>
      <p class="muted small" id="chat-voice-note" hidden>Voice input needs Chrome, Edge or Safari with microphone access.</p>
    </form>
  </section>

  <div class="stack">
    <section class="panel stack review-doc print-area" id="review">
      <div class="panel-head"><h2>Performance review</h2>
        <form method="get" action="<?= e(url('/terminal/coach' . ($conv ? '/' . $conv['id'] : ''))) ?>#review"><label class="sr-only" for="rv-p">Period</label><select id="rv-p" name="period" data-autosubmit style="width:auto"><?php foreach (CoachController::PERIODS as $k => $l): ?><option value="<?= $k ?>"<?= $period === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></form></div>
      <div id="review-doc">
      <p class="small" style="margin:0"><strong><?= e($periodLabel) ?></strong> · <?= e($periodFrom) ?> to <?= e($periodTo) ?> · <?= (int) $rs['trades'] ?> trades · <strong class="<?= $rs['net'] >= 0 ? 'up' : 'down' ?>"><?= e(money($rs['net'], $cur, true)) ?></strong> · win rate <?= e(pct($rs['win_rate'], 0)) ?> · rule compliance <?= e(pct($rs['compliance'], 0)) ?></p>
      <?php if (!$rs['trades'] && !$rv['lessons']): ?><p class="muted small">No trades or journal lessons in this period yet.</p><?php else: ?>
      <p class="muted small" style="margin:6px 0 0">Based on your historical journal and trading data, these are the behaviours associated with your strongest and weakest performance in this period.</p>
      <?php if ($rv['best_trades']): ?><h3>Best trades</h3><ul><?php foreach ($rv['best_trades'] as $t): ?><li><?= e(fmt_date($t['executed_at'], 'M j')) ?> · <?= e($t['symbol']) ?> <?= $t['side'] === 'LONG' ? 'BUY' : 'SELL' ?> · <span class="up"><?= e(money($t['pnl'], $cur, true)) ?></span><?= $t['rr'] !== null ? ' · ' . e(number_format((float) $t['rr'], 2)) . 'R' : '' ?><?= $t['strategy_name'] ? ' · ' . e($t['strategy_name']) : '' ?></li><?php endforeach; ?></ul><?php endif; ?>
      <?php $dims = array_filter($rv['dims']); if ($dims): ?><h3>Strongest</h3><ul><?php foreach ($dims as $k => $g): ?><li><?= e(preg_replace('/ \(.*/', '', $k)) ?>: <strong><?= e($g['key']) ?></strong> — <?= e(money($g['s']['net'], $cur, true)) ?>, <?= (int) $g['s']['trades'] ?> trades</li><?php endforeach; ?></ul><?php endif; ?>
      <?php if ($rv['best_window']): ?><h3>Strongest trading window</h3><ul><li><?= e(Edge::windowName($rv['best_window'])) ?> · <?= e($rv['best_window']['best_hours']['label']) ?> — <?= e(money($rv['best_window']['s']['net'], $cur, true)) ?></li></ul><?php endif; ?>
      <?php if ($rv['worst_window']): ?><h3>Weakest trading window</h3><ul><li class="down"><?= e(Edge::windowName($rv['worst_window'])) ?> · <?= e($rv['worst_window']['worst_hours']['label']) ?> — <?= e(money($rv['worst_window']['s']['net'], $cur)) ?></li></ul><?php endif; ?>
      <?php if ($rv['leak']['by']): ?><h3>Key discipline leaks</h3><ul><?php foreach (array_slice($rv['leak']['by'], 0, 4) as $b): ?><li><?= e($b['label']) ?>: <?= (int) $b['trades'] ?> trades, leak <span class="down"><?= e(money($b['leak'], $cur)) ?></span></li><?php endforeach; ?></ul><?php endif; ?>
      <?php if ($rv['mistakes']): ?><h3>Repeated mistakes</h3><ul><?php foreach ($rv['mistakes'] as $k => $n): ?><li><?= e($k) ?> × <?= (int) $n ?></li><?php endforeach; ?></ul><?php endif; ?>
      <?php if ($rv['emotions'] || $rv['journal_emotions']): ?><h3>Emotional patterns</h3><ul><?php foreach ($rv['emotions'] as $x): ?><li><?= e($x) ?></li><?php endforeach; ?><?php if ($rv['journal_emotions']): ?><li>Journal mood: <?= e(implode(', ', array_map(fn ($k, $v) => $k . ' ×' . $v, array_keys($rv['journal_emotions']), $rv['journal_emotions']))) ?></li><?php endif; ?></ul><?php endif; ?>
      <?php if ($rv['answers']): ?><h3>Rule compliance (journal)</h3><ul><li><?= e(implode(' · ', array_map(fn ($k, $v) => ['yes' => 'Followed rules', 'partial' => 'Partially', 'no' => 'Did not'][$k] . ': ' . $v . ' day' . ($v === 1 ? '' : 's'), array_keys($rv['answers']), $rv['answers']))) ?></li></ul><?php endif; ?>
      <?php if ($rv['lessons']): ?><h3>Best lessons</h3><ul><?php foreach (array_slice($rv['lessons'], 0, 6) as $j): ?><li>“<?= e($j['key_lesson']) ?>” <span class="muted small"><?= e($j['journal_date']) ?></span></li><?php endforeach; ?></ul><?php endif; ?>
      <?php if ($rv['improve']): ?><h3>Improvement areas</h3><ul><?php foreach ($rv['improve'] as $x): ?><li><?= e($x) ?></li><?php endforeach; ?></ul><?php endif; ?>
      <?php endif; ?>
      <p class="muted small" style="margin-top:10px">Historical analysis only — not a forecast and not financial advice.</p>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <button type="button" class="tm-btn tm-btn-sm" data-copy-target="#review-doc">Copy</button>
        <button type="button" class="tm-btn tm-btn-sm" data-print>Print / save as PDF</button>
        <button type="button" class="tm-btn tm-btn-sm tm-btn-primary" data-review="<?= e($period) ?>"<?= $configured ? '' : ' disabled title="Requires an AI key"' ?>>Write AI narrative</button>
      </div>
      <div class="msg assistant" data-review-out style="max-width:100%;min-height:0" hidden></div>
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
