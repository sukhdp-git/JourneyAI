<?php
use App\Trading\Domain;
$cur = $acc['currency']; $v = fn ($k) => (string) ($entry[$k] ?? '');
$d = new DateTimeImmutable($date);
$answer = $v('rules_answer');
if ($answer === '' && $v('compliance') !== '') { $answer = (int) $v('compliance') >= 4 ? 'yes' : ((int) $v('compliance') >= 2 ? 'partial' : 'no'); }
$emoLabel = fn ($k) => Domain::JOURNAL_EMOTIONS[$k] ?? ucfirst(strtolower((string) $k));
?>
<div class="toolbar">
  <a class="tm-btn tm-btn-sm" href="<?= e(url('/terminal/notepad?date=' . $d->modify('-1 day')->format('Y-m-d'))) ?>" aria-label="Previous day"><?= icon('chevron-left', 'icon icon-sm') ?></a>
  <form method="get" action="<?= e(url('/terminal/notepad')) ?>"><label class="sr-only" for="nd">Date</label><input id="nd" type="date" name="date" value="<?= e($date) ?>" data-autosubmit style="width:auto"></form>
  <a class="tm-btn tm-btn-sm" href="<?= e(url('/terminal/notepad?date=' . $d->modify('+1 day')->format('Y-m-d'))) ?>" aria-label="Next day"><?= icon('chevron-right', 'icon icon-sm') ?></a>
  <?php if ($date !== $today): ?><a class="tm-btn tm-btn-sm tm-btn-ghost" href="<?= e(url('/terminal/notepad')) ?>">Today</a><?php else: ?><span class="badge">Today</span><?php endif; ?>
  <span style="margin-left:auto" class="small muted">That day: <?= (int) $daySum['trades'] ?> closed trades · <strong class="num <?= $daySum['net'] >= 0 ? 'up' : 'down' ?>"><?= e(money($daySum['net'], $cur, true)) ?></strong></span>
</div>
<div class="grid g-main">
  <form method="post" action="<?= e(url('/terminal/notepad')) ?>" class="panel stack"><?= csrf_field() ?>
    <input type="hidden" name="journal_date" value="<?= e($date) ?>">
    <div class="panel-head"><h2><?= e($d->format('l j F Y')) ?></h2><span class="head-actions"><?= (int) $acc['has_demo_data'] ? '<span class="mode demo-data">DEMO DATA</span>' : '' ?><?php if ($daySum['trades']): ?><button type="button" class="tm-btn tm-btn-sm flex-btn" data-flex-day="<?= e($date) ?>"><?= icon('share', 'icon icon-sm') ?> Flex card</button><?php endif; ?></span></div>

    <div class="f big-q"><label for="n-r">How was your trading day?</label>
      <textarea id="n-r" name="reflection" rows="7" data-draft-key="<?= e($date . '-' . $acc['id']) ?>" placeholder="e.g. High-conviction day. I waited patiently for my setup and only entered after confirmation. The trade had clean invalidation and strong risk-to-reward."><?= e($v('reflection')) ?></textarea>
      <div class="chat-voice" style="margin-top:6px;align-items:center;flex-wrap:wrap">
        <button type="button" class="tm-btn tm-btn-sm" data-voice-into="#n-r" data-voice-append data-voice-note="#voice-note"><?= icon('mic', 'icon icon-sm') ?> Dictate</button>
        <label class="sr-only" for="n-l">Voice language</label><select id="n-l" data-voice-lang style="width:auto"><?php foreach (Domain::VOICE_LANGUAGES as $k => $code): ?><option value="<?= e($code) ?>"<?= ($m['language'] ?: 'en') === $k ? ' selected' : '' ?>><?= e(Domain::LANGUAGES[$k]) ?></option><?php endforeach; ?></select>
        <span class="muted small" id="voice-note" hidden>Voice dictation needs Chrome, Edge or Safari with microphone access.</span>
      </div>
    </div>

    <fieldset class="f" style="border:0;padding:0;margin:0"><legend class="lbl">Did you follow your trading rules?</legend>
      <div class="choice-row">
        <?php foreach (['yes' => 'Yes', 'partial' => 'Partially', 'no' => 'No'] as $k => $l): ?><label class="<?= $k ?>"><input type="radio" name="rules_answer" value="<?= $k ?>"<?= $answer === $k ? ' checked' : '' ?>><span><?= $l ?></span></label><?php endforeach; ?>
      </div>
    </fieldset>

    <fieldset class="f" style="border:0;padding:0;margin:0"><legend class="lbl">Emotional state</legend>
      <div class="choice-row">
        <?php foreach (Domain::JOURNAL_EMOTIONS as $k => $l): ?><label class="<?= in_array($k, Domain::NEGATIVE_JOURNAL_EMOTIONS, true) ? 'no' : 'yes' ?>"><input type="radio" name="emotional_state" value="<?= e($k) ?>"<?= $v('emotional_state') === $k ? ' checked' : '' ?>><span><?= e($l) ?></span></label><?php endforeach; ?>
        <?php if ($v('emotional_state') !== '' && !isset(Domain::JOURNAL_EMOTIONS[$v('emotional_state')])): ?><label><input type="radio" name="emotional_state" value="<?= e($v('emotional_state')) ?>" checked><span><?= e($emoLabel($v('emotional_state'))) ?></span></label><?php endif; ?>
      </div>
    </fieldset>

    <div class="grid-form">
      <div class="f"><label for="n-d">Your discipline rating (1–10)</label><select id="n-d" name="discipline_rating"><option value="">—</option><?php for ($i = 1; $i <= 10; $i++): ?><option<?= $v('discipline_rating') === (string) $i ? ' selected' : '' ?>><?= $i ?></option><?php endfor; ?></select></div>
      <div class="f"><span class="lbl">System discipline score</span>
        <?php if ($score): ?><div class="score-box"><span class="score <?= $score['score'] >= 7 ? 'up' : ($score['score'] >= 4 ? 'warn' : 'down') ?>"><?= e(number_format($score['score'], 1)) ?></span><span class="muted small">/ 10 · calculated from your answers, the day's trades, mistake tags and daily loss limit. Your own rating is never changed.</span></div>
        <?php else: ?><span class="muted small">Answer the rules question or log trades to calculate it.</span><?php endif; ?>
      </div>
    </div>
    <?php if ($score): ?><details><summary class="small">How the score was calculated</summary><ul class="small" style="margin:6px 0 0;padding-left:18px"><?php foreach ($score['parts'] as $l => [$got, $max]): ?><li><?= e($l) ?>: <?= e((string) $got) ?> / <?= $max ?></li><?php endforeach; ?></ul></details><?php endif; ?>

    <div class="f big-q"><label for="n-k">Key lesson / breakthrough</label>
      <input id="n-k" type="text" name="key_lesson" maxlength="500" value="<?= e($v('key_lesson')) ?>" placeholder="e.g. Patience during the first 15 minutes of New York gives me cleaner confirmation.">
      <div style="margin-top:6px"><button type="button" class="tm-btn tm-btn-sm" data-voice-into="#n-k" data-voice-note="#voice-note"><?= icon('mic', 'icon icon-sm') ?> Dictate lesson</button></div>
    </div>
    <div><button class="tm-btn tm-btn-primary" type="submit"><?= e(t('common.save')) ?> journal</button> <span class="muted small">Saved entries feed the weekly/monthly reviews and the AI Coach. Unsaved text is kept as a local draft.</span></div>
  </form>

  <div class="stack">
    <?php if ($dayTrades): ?>
    <section class="panel">
      <div class="panel-head"><h2>That day's trades</h2></div>
      <ul class="mini-hist"><?php foreach ($dayTrades as $t): ?><li><span><a href="<?= e(url('/terminal/trades/' . $t['id'])) ?>"><?= e(fmt_date($t['executed_at'], 'H:i')) ?> · <?= e($t['symbol']) ?> <?= $t['side'] === 'LONG' ? 'BUY' : 'SELL' ?></a><?= ($t['mistake_tag'] ?? 'NONE') !== 'NONE' ? ' <span class="badge warn">' . e(Domain::MISTAKES[$t['mistake_tag']] ?? $t['mistake_tag']) . '</span>' : '' ?><?= (int) $t['rules_followed'] ? '' : ' <span class="badge loss">rules broken</span>' ?></span><strong class="num <?= (float) $t['pnl'] >= 0 ? 'up' : 'down' ?>"><?= $t['pnl'] === null ? 'OPEN' : e(money($t['pnl'], $cur, true)) ?></strong></li><?php endforeach; ?></ul>
    </section>
    <?php endif; ?>
    <section class="panel">
      <div class="panel-head"><h2><?= icon('star', 'icon icon-sm') ?> Key lessons library</h2><span class="muted small"><?= count($lessons) ?></span></div>
      <?php if ($lessons): ?><ul class="lesson-list"><?php foreach (array_slice($lessons, 0, 12) as $l): ?><li><?= e($l['key_lesson']) ?><small><a href="<?= e(url('/terminal/notepad?date=' . $l['journal_date'])) ?>"><?= e($l['journal_date']) ?></a></small></li><?php endforeach; ?></ul>
      <?php else: ?><p class="muted small">Your key lessons are saved here permanently and used by the reviews and AI Coach.</p><?php endif; ?>
    </section>
    <section class="panel">
      <div class="panel-head"><h2>History</h2><span class="muted small"><?= count($history) ?> entries</span></div>
      <?php if ($history): ?>
      <div class="table-wrap"><table class="tbl"><thead><tr><th>Date</th><th>Rules</th><th class="r">Rating</th><th>Emotion</th></tr></thead><tbody>
        <?php foreach ($history as $h): $ra = $h['rules_answer'] ?? null; if (!$ra && $h['compliance'] !== null) { $ra = (int) $h['compliance'] >= 4 ? 'yes' : ((int) $h['compliance'] >= 2 ? 'partial' : 'no'); } ?><tr><td><a href="<?= e(url('/terminal/notepad?date=' . $h['journal_date'])) ?>"><?= e($h['journal_date']) ?></a></td><td class="<?= $ra === 'yes' ? 'up' : ($ra === 'no' ? 'down' : '') ?>"><?= e(['yes' => 'Yes', 'partial' => 'Partially', 'no' => 'No'][$ra] ?? '—') ?></td><td class="r num"><?= e((string) ($h['discipline_rating'] ?? '—')) ?></td><td><?= e($h['emotional_state'] ? $emoLabel($h['emotional_state']) : '—') ?></td></tr><?php endforeach; ?>
      </tbody></table></div>
      <?php else: ?><p class="muted">No entries yet.</p><?php endif; ?>
    </section>
  </div>
</div>
