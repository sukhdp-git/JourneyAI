<?php /** Weekly recap card (Trade Log only). @var array $weeks Monday => label */ ?>
<div class="tm-modal" id="tm-week" role="dialog" aria-modal="true" aria-labelledby="tm-week-title" hidden>
  <div class="tm-modal-bg" data-close></div>
  <div class="tm-modal-card flex-modal wk-modal">
    <div class="tm-modal-head"><h2 id="tm-week-title">Weekly flex card</h2><div class="seg card-style" role="radiogroup" aria-label="Card style"><label><input type="radio" name="card_style_w" value="dark" checked><span>Dark</span></label><label><input type="radio" name="card_style_w" value="light"><span>Light</span></label></div></div>
    <div class="wk-pick">
      <label for="wk-sel" class="sr-only">Week</label>
      <select id="wk-sel" data-week-select><?php foreach ($weeks as $mon => $label): ?><option value="<?= e($mon) ?>"><?= e($label) ?></option><?php endforeach; ?></select>
    </div>
    <p class="muted small">Your week in one image — share it on Instagram, X or WhatsApp. The QR code opens a page confirming the numbers came from your journzey.ai journal. No balances or account names are shown.</p>
    <canvas width="1080" height="1350" class="flex-preview" aria-label="Weekly card preview"></canvas>
    <div class="tm-modal-actions"><button type="button" class="tm-btn" data-close>Close</button><button type="button" class="tm-btn" data-card-copy><?= icon('copy', 'icon icon-sm') ?> Copy</button><button type="button" class="tm-btn" data-card-download><?= icon('download', 'icon icon-sm') ?> Download</button><button type="button" class="tm-btn tm-btn-primary" data-card-share><?= icon('share', 'icon icon-sm') ?> Share</button></div>
  </div>
</div>
