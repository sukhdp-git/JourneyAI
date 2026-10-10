<?php $style = '<div class="seg card-style" role="radiogroup" aria-label="Card style"><label><input type="radio" name="card_style_d" value="dark" checked><span>Dark</span></label><label><input type="radio" name="card_style_d" value="light"><span>Light</span></label></div>'; ?>
<div class="tm-modal" id="tm-flex" role="dialog" aria-modal="true" aria-labelledby="tm-flex-title" hidden>
  <div class="tm-modal-bg" data-close></div>
  <div class="tm-modal-card flex-modal">
    <div class="tm-modal-head"><h2 id="tm-flex-title">Day flex card</h2><?= $style ?></div>
    <p class="muted small">Post it on X, Instagram or anywhere. The QR code opens a page confirming the card came from your journzey.ai journal. No balances or account names are shown.</p>
    <canvas width="1080" height="1350" class="flex-preview" aria-label="Flex card preview"></canvas>
    <div class="tm-modal-actions"><button type="button" class="tm-btn" data-close>Close</button><button type="button" class="tm-btn" data-card-copy><?= icon('copy', 'icon icon-sm') ?> Copy</button><button type="button" class="tm-btn" data-card-download><?= icon('download', 'icon icon-sm') ?> Download</button><button type="button" class="tm-btn tm-btn-primary" data-card-share><?= icon('share', 'icon icon-sm') ?> Share / post on X</button></div>
  </div>
</div>
