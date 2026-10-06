<?php
use App\Trading\Domain;
$cur = $acc['currency']; $v = fn ($k) => (string) ($entry[$k] ?? '');
$d = new DateTimeImmutable($date);
?>
<div class="toolbar">
  <a class="tm-btn tm-btn-sm" href="<?= e(url('/terminal/notepad?date=' . $d->modify('-1 day')->format('Y-m-d'))) ?>" aria-label="Previous day"><?= icon('chevron-left', 'icon icon-sm') ?></a>
  <form method="get" action="<?= e(url('/terminal/notepad')) ?>"><label class="sr-only" for="nd">Date</label><input id="nd" type="date" name="date" value="<?= e($date) ?>" data-autosubmit style="width:auto"></form>
  <a class="tm-btn tm-btn-sm" href="<?= e(url('/terminal/notepad?date=' . $d->modify('+1 day')->format('Y-m-d'))) ?>" aria-label="Next day"><?= icon('chevron-right', 'icon icon-sm') ?></a>
  <?php if ($date !== $today): ?><a class="tm-btn tm-btn-sm tm-btn-ghost" href="<?= e(url('/terminal/notepad')) ?>">Today</a><?php endif; ?>
  <span class="muted small" style="margin-left:auto">That day: <?= (int) $daySum['trades'] ?> trades · <strong class="num <?= $daySum['net'] >= 0 ? 'up' : 'down' ?>"><?= e(money($daySum['net'], $cur, true)) ?></strong></span>
</div>
<div class="grid g-main">
  <form method="post" action="<?= e(url('/terminal/notepad')) ?>" class="panel stack"><?= csrf_field() ?>
    <input type="hidden" name="journal_date" value="<?= e($date) ?>">
    <div class="panel-head"><h2><?= e($d->format('l j F Y')) ?></h2><?= (int) $acc['has_demo_data'] ? '<span class="mode demo-data">DEMO DATA</span>' : '' ?></div>
    <div class="grid-form">
      <div class="f"><label for="n-c">Plan compliance (1–5)</label><select id="n-c" name="compliance"><option value="">—</option><?php for ($i = 1; $i <= 5; $i++): ?><option<?= $v('compliance') === (string) $i ? ' selected' : '' ?>><?= $i ?></option><?php endfor; ?></select></div>
      <div class="f"><label for="n-e">Emotional state</label><select id="n-e" name="emotional_state"><option value="">—</option><?php foreach (Domain::EMOTIONS as $em): ?><option value="<?= e($em) ?>"<?= $v('emotional_state') === $em ? ' selected' : '' ?>><?= e(ucfirst(strtolower($em))) ?></option><?php endforeach; ?></select></div>
      <div class="f"><label for="n-d">Discipline rating (1–10)</label><select id="n-d" name="discipline_rating"><option value="">—</option><?php for ($i = 1; $i <= 10; $i++): ?><option<?= $v('discipline_rating') === (string) $i ? ' selected' : '' ?>><?= $i ?></option><?php endfor; ?></select></div>
      <div class="f"><label for="n-l">Voice language</label><select id="n-l" data-voice-lang><?php foreach (Domain::VOICE_LANGUAGES as $k => $code): ?><option value="<?= e($code) ?>"<?= ($m['language'] ?: 'en') === $k ? ' selected' : '' ?>><?= e(Domain::LANGUAGES[$k]) ?></option><?php endforeach; ?></select></div>
    </div>
    <div class="f"><label for="n-r">Reflection</label><textarea id="n-r" name="reflection" rows="10" data-draft-key="<?= e($date . '-' . $acc['id']) ?>" placeholder="How did you execute today? What did you feel before, during and after your trades?"><?= e($v('reflection')) ?></textarea></div>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap"><button type="button" class="tm-btn tm-btn-sm" data-dictate="#n-r">🎙 Dictate</button><span class="muted small" data-dictate-note hidden>Voice dictation needs a browser with speech recognition (Chrome, Edge, Safari).</span></div>
    <div class="f"><label for="n-k">Key lesson</label><input id="n-k" name="key_lesson" maxlength="500" value="<?= e($v('key_lesson')) ?>"></div>
    <div><button class="tm-btn tm-btn-primary" type="submit"><?= e(t('common.save')) ?> entry</button> <span class="muted small">Unsaved text is kept as a local draft in this browser.</span></div>
  </form>
  <section class="panel">
    <div class="panel-head"><h2>History</h2><span class="muted small"><?= count($history) ?> entries</span></div>
    <?php if ($history): ?>
    <div class="table-wrap"><table class="tbl"><thead><tr><th>Date</th><th class="r">Compl.</th><th class="r">Disc.</th><th>Emotion</th></tr></thead><tbody>
      <?php foreach ($history as $h): ?><tr><td><a href="<?= e(url('/terminal/notepad?date=' . $h['journal_date'])) ?>"><?= e($h['journal_date']) ?></a><?php if ($h['key_lesson']): ?><br><span class="muted small"><?= e(mb_strimwidth($h['key_lesson'], 0, 50, '…')) ?></span><?php endif; ?></td><td class="r num"><?= e((string) ($h['compliance'] ?? '—')) ?></td><td class="r num"><?= e((string) ($h['discipline_rating'] ?? '—')) ?></td><td><?= e($h['emotional_state'] ? ucfirst(strtolower($h['emotional_state'])) : '—') ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <?php else: ?><p class="muted">No entries yet. Journal entries feed the AI Coach and the weekly/monthly reviews.</p><?php endif; ?>
  </section>
</div>
