<?php
use App\Trading\Confessions as C;
/** First visit: pick an anonymous handle. @var string $suggest */
[$em, $hue] = C::avatar(abs(crc32($suggest)));
?>
<div class="cf cf-onboard">
  <section class="cf-handle-card">
    <span class="cf-av xl" style="--h:<?= $hue ?>" aria-hidden="true"><?= $em ?></span>
    <span class="cf-kicker"><?= icon('lock', 'icon icon-xs') ?> Step 1 of 1</span>
    <h2>Pick your <em>anonymous</em> handle</h2>
    <p class="muted">This is the only name anyone in Confessions will ever see. Your real name and email stay private. Don’t use anything that identifies you.</p>
    <form method="post" action="<?= e(url('/terminal/confessions/handle')) ?>" class="cf-handle-form" data-cf-handle-form>
      <?= csrf_field() ?>
      <label for="cf-h" class="sr-only">Handle</label>
      <div class="cf-at big"><span>@</span><input id="cf-h" name="handle" value="<?= e($suggest) ?>" maxlength="20" pattern="[a-z0-9_.]{3,20}" autocomplete="off" spellcheck="false" required data-cf-hinput></div>
      <p class="cf-hhint muted small" data-cf-hhint>3–20 characters: lowercase letters, numbers, dot or underscore.</p>
      <button class="tm-btn tm-btn-primary cf-post" type="submit">Enter Confessions</button>
    </form>
    <ul class="cf-rules compact">
      <li>🙊 Anonymous by default</li><li>🛡️ Spam, links and selling are blocked automatically</li><li>💵 Every like = $15 of respect (just for fun)</li>
    </ul>
  </section>
</div>
