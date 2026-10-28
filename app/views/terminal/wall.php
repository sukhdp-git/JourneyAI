<?php
/** Submit to the Wall of L's. @var array $pre @var array $mine */
use App\Trading\Domain;
$v = fn ($k) => (string) old($k, $pre[$k] ?? '');
$st = ['pending' => ['In review', ''], 'approved' => ['On the wall', 'win'], 'rejected' => ['Not published', 'loss']];
?>
<div class="grid g-main">
  <form method="post" action="<?= e(url('/terminal/wall')) ?>" class="panel stack wall-form"><?= csrf_field() ?>
    <div class="panel-head"><h2>Submit your L</h2><a class="small" href="<?= e(url('/wall')) ?>" target="_blank" rel="noopener">See the wall ↗</a></div>
    <p class="muted small">Every journal posts green screenshots. The Wall of L's posts the honest ones, anonymously, so other traders learn from them. Only your nickname is shown.</p>
    <div class="grid-form">
      <div class="f"><label for="w-h">Nickname <span class="muted">(optional)</span></label><input id="w-h" name="handle" maxlength="40" value="<?= e($v('handle')) ?>" placeholder="Anonymous trader"></div>
      <div class="f"><label for="w-i">Instrument</label><input id="w-i" name="instrument" maxlength="20" value="<?= e($v('instrument')) ?>" placeholder="XAUUSD"></div>
      <div class="f"><label for="w-m">What went wrong</label><select id="w-m" name="mistake"><?php foreach (Domain::MISTAKES as $k => $l): if ($k === 'NONE') continue; ?><option value="<?= e($k) ?>"<?= $v('mistake') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
      <div class="f"><label for="w-l">The loss</label><input id="w-l" name="loss_text" maxlength="40" required value="<?= e($v('loss_text')) ?>" placeholder="−$1,200 or −3.5R"></div>
    </div>
    <div class="f"><label for="w-s">What happened</label><textarea id="w-s" name="story" rows="4" maxlength="600" required placeholder="Moved my stop 4 times on gold because I was sure it would turn…"><?= e($v('story')) ?></textarea></div>
    <div class="f"><label for="w-ls">The lesson</label><input id="w-ls" name="lesson" maxlength="300" required value="<?= e($v('lesson')) ?>" placeholder="I wasn't scared of losing money. I was scared of being wrong."></div>
    <label class="check"><input type="checkbox" name="consent" value="1" required> I agree that journzey may publish this anonymously on its website and social media. I can ask for it to be removed at any time.</label>
    <div><button class="tm-btn tm-btn-primary" type="submit"><?= icon('send', 'icon icon-sm') ?> Submit for review</button></div>
  </form>
  <section class="panel stack">
    <div class="panel-head"><h2>Your submissions</h2></div>
    <?php if (!$mine): ?><p class="muted small">Nothing submitted yet. Tip: open a losing trade in the Trade Log and use “Post to Wall of L's” to prefill this form.</p>
    <?php else: ?><ul class="wall-mine"><?php foreach ($mine as $p): [$lbl, $cls] = $st[$p['status']] ?? ['—', '']; ?>
      <li><b><?= e($p['loss_text']) ?></b> <span class="muted small"><?= e(Domain::label($p['mistake'])) ?> · <?= e(fmt_date($p['created_at'], 'M j')) ?></span><span class="badge <?= $cls ?>"><?= e($lbl) ?></span></li>
    <?php endforeach; ?></ul><?php endif; ?>
    <p class="muted small">Want one removed? Email support and we'll take it down.</p>
  </section>
</div>
