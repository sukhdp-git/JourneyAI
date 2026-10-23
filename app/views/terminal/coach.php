<?php
use App\Controllers\Terminal\CoachController;
use App\Trading\Domain;
use App\Trading\Edge;
$cur = $acc['currency'];
$rv = $review; $rs = $rv['summary'];
?>
<!-- Automatic home state: computed from your data, no AI call needed -->
<?php $topS = array_slice($home['strengths'], 0, 3); $topL = array_slice($home['leaks'], 0, 3); ?>
<?php if ($home['enough'] && $topL): ?><div class="coach-focus"><?= icon('target', 'icon icon-sm') ?> <span>Today’s focus:</span> <strong><?= e($topL[0]['short'] ?? $topL[0]['title']) ?></strong> <mark class="down"><?= e($topL[0]['value'] ?? '') ?></mark></div><?php endif; ?>
<div class="coach-home">
  <section class="ex-card c-green coach-col">
    <h2><?= icon('trend-up', 'icon icon-sm') ?> Your edge</h2>
    <?php if (!$home['enough']): ?><p class="muted small">Needs 10+ closed trades.</p>
    <?php elseif (!$topS): ?><p class="muted small">No clear strength yet.</p>
    <?php else: foreach ($topS as $x): ?><div class="ins"><span><?= e($x['short'] ?? $x['title']) ?></span><b class="up"><?= e($x['value'] ?? '') ?></b></div><?php endforeach; endif; ?>
  </section>
  <section class="ex-card c-red coach-col">
    <h2><?= icon('trend-down', 'icon icon-sm') ?> Your leaks</h2>
    <?php if (!$home['enough']): ?><p class="muted small">Needs 10+ closed trades.</p>
    <?php elseif (!$topL): ?><p class="muted small">No clear leak — keep it up.</p>
    <?php else: foreach ($topL as $x): ?><div class="ins"><span><?= e($x['short'] ?? $x['title']) ?></span><b class="down"><?= e($x['value'] ?? '') ?></b></div><?php endforeach; endif; ?>
  </section>
</div>
<p class="muted small" style="margin:-4px 0 12px"><?= e($acc['name']) ?><?= (int) $acc['has_demo_data'] ? ' · DEMO DATA' : '' ?> · from your recorded trades · not a forecast.</p>

<div class="grid g-main">
  <section class="panel stack">
    <div class="panel-head">
      <h2><?= $conv ? e($conv['title']) : 'Ask your coach' ?></h2>
      <div class="seg coach-mode" role="radiogroup" aria-label="Coach mode">
        <label><input type="radio" name="coach-mode" value="smart" checked> <span><?= icon('zap', 'icon icon-xs') ?> Smart Coach · free</span></label>
        <label<?= $configured ? '' : ' title="AI chat is not enabled on this site"' ?>><input type="radio" name="coach-mode" value="ai"<?= $configured ? '' : ' disabled' ?>> <span><?= icon('sparkles', 'icon icon-xs') ?> AI Coach</span></label>
      </div>
    </div>
    <p class="muted small" data-mode-note="smart">Instant answers from <strong>your own trades and journal</strong> (<?= e($acc['name']) ?>), plus trading, psychology and University lessons. Free and unlimited.<?= $configured ? '' : ' AI chat is not enabled on this site.' ?></p>
    <p class="muted small" data-mode-note="ai" hidden>AI-written answers from your statistics · <span data-ai-remaining><?= (int) $remaining ?></span> / <?= (int) $limit ?> messages left today · not financial advice.</p>
    <div class="chat" data-chat-log aria-live="polite">
      <?php foreach ($messages as $msg): ?>
        <div class="msg <?= e($msg['role']) ?>"><?= $msg['role'] === 'assistant' ? coach_text($msg['content']) : e($msg['content']) ?><?php if ($msg['role'] === 'assistant'): ?><small>AI-generated · <?= e(fmt_date($msg['created_at'], 'M j, H:i')) ?></small><?php endif; ?></div>
      <?php endforeach; ?>
      <?php if (!$messages): ?><div class="msg assistant">Hi! Ask me about <strong>your trading</strong>: win rate, best session, worst habit, streaks, discipline, this month vs last. Or ask <strong>how to</strong> size a trade, stop revenge trading, or what a University strategy is. Tap a question below to start.</div><?php endif; ?>
    </div>
    <div class="coach-chips">
      <span class="muted small">Your trading</span>
      <div class="prompt-chips"><?php foreach ($smart['mine'] as $p): ?><button type="button" class="tm-btn tm-btn-sm tm-btn-ghost" data-prompt="<?= e($p) ?>" data-ask><?= e($p) ?></button><?php endforeach; ?></div>
      <span class="muted small">Learn</span>
      <div class="prompt-chips"><?php foreach ($smart['learn'] as $p): ?><button type="button" class="tm-btn tm-btn-sm tm-btn-ghost" data-prompt="<?= e($p) ?>" data-ask><?= e($p) ?></button><?php endforeach; ?></div>
    </div>
    <form data-chat-form data-conversation="<?= $conv ? (int) $conv['id'] : '' ?>" data-mode="smart" class="stack">
      <label class="sr-only" for="chat-q">Your question</label>
      <textarea id="chat-q" rows="2" maxlength="4000" placeholder="e.g. Which session should I avoid this month?"></textarea>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <button type="button" class="tm-btn" data-voice-into="#chat-q" data-voice-note="#chat-voice-note"><?= icon('mic', 'icon icon-sm') ?> Speak</button>
        <label class="sr-only" for="chat-lang">Reply language</label>
        <select id="chat-lang" name="lang" style="width:auto" data-ai-only hidden><?php foreach (Domain::LANGUAGES as $k => $name): ?><option value="<?= e($k) ?>"<?= ($m['language'] ?: 'en') === $k ? ' selected' : '' ?>><?= e($name) ?></option><?php endforeach; ?></select>
        <button class="tm-btn tm-btn-primary" type="submit">Ask</button>
        <span class="muted small">Ctrl/⌘ + Enter to send</span>
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
      <p class="muted small" style="margin:0"><?= e($periodLabel) ?> · <?= e($periodFrom) ?> → <?= e($periodTo) ?></p>
      <div class="rv-score">
        <div><small>Net</small><b class="<?= $rs['net'] >= 0 ? 'up' : 'down' ?>"><?= e(money($rs['net'], $cur, true)) ?></b></div>
        <div><small>Win rate</small><b class="<?= ($rs['win_rate'] ?? 0) >= 0.5 ? 'up' : 'down' ?>"><?= e(pct($rs['win_rate'], 0)) ?></b></div>
        <div><small>Rules kept</small><b class="<?= ($rs['compliance'] ?? 1) >= 0.8 ? 'up' : 'down' ?>"><?= e(pct($rs['compliance'], 0)) ?></b></div>
        <div><small>Trades</small><b><?= (int) $rs['trades'] ?></b></div>
      </div>
      <?php if (!$rs['trades'] && !$rv['lessons']): ?><p class="muted small">No trades or journal lessons in this period yet.</p><?php else:
        $win = []; $lose = []; $next = [];
        foreach (array_slice(array_filter($rv['dims']), 0, 2, true) as $k => $g) { $win[] = [preg_replace('/ \(.*/', '', $k) . ': ' . $g['key'], money($g['s']['net'], $cur, true)]; }
        if ($rv['best_window']) { $win[] = ['Best window: ' . Edge::windowName($rv['best_window']) . ' · ' . $rv['best_window']['best_hours']['label'], money($rv['best_window']['s']['net'], $cur, true)]; }
        foreach (array_slice($rv['leak']['by'], 0, 2) as $b) { $lose[] = [$b['label'] . ' (' . (int) $b['trades'] . ((int) $b['trades'] === 1 ? ' trade)' : ' trades)'), '−' . money($b['leak'], $cur)]; }
        if ($rv['worst_window']) { $lose[] = ['Weak window: ' . Edge::windowName($rv['worst_window']), money($rv['worst_window']['s']['net'], $cur)]; }
        if (!$lose && $rv['mistakes']) { foreach (array_slice($rv['mistakes'], 0, 2, true) as $k => $n) { $lose[] = [$k, '×' . (int) $n]; } }
        $next = array_slice($rv['improve'], 0, 3);
      ?>
      <?php if ($win): ?><div class="rv-block good"><h3><?= icon('trend-up', 'icon icon-xs') ?> What worked</h3><ul><?php foreach (array_slice($win, 0, 3) as [$t, $v]): ?><li><span><?= e($t) ?></span><mark class="up"><?= e($v) ?></mark></li><?php endforeach; ?></ul></div><?php endif; ?>
      <?php if ($lose): ?><div class="rv-block bad"><h3><?= icon('trend-down', 'icon icon-xs') ?> What cost you</h3><ul><?php foreach (array_slice($lose, 0, 3) as [$t, $v]): ?><li><span><?= e($t) ?></span><mark class="down"><?= e($v) ?></mark></li><?php endforeach; ?></ul></div><?php endif; ?>
      <?php if ($next): ?><div class="rv-block next"><h3><?= icon('target', 'icon icon-xs') ?> Focus next</h3><ol><?php foreach ($next as $x): ?><li><?= e($x) ?></li><?php endforeach; ?></ol></div><?php endif; ?>
      <?php if ($rv['lessons']): ?><p class="rv-lesson"><?= icon('star', 'icon icon-xs') ?> “<?= e($rv['lessons'][0]['key_lesson']) ?>”</p><?php endif; ?>
      <?php endif; ?>
      <p class="muted small" style="margin-top:8px">Historical analysis — not financial advice.</p>
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
