<div class="tm-modal" id="tm-share" role="dialog" aria-modal="true" aria-labelledby="tm-share-title" hidden>
  <div class="tm-modal-bg" data-close></div>
  <div class="tm-modal-card share-card-modal">
    <h2 id="tm-share-title">Share trade</h2>
    <p class="muted small">Ready for Instagram, X, Telegram, Discord or WhatsApp. No account names, balances or IDs are included.</p>
    <div class="seg" role="radiogroup" aria-label="Card style"><label><input type="radio" name="card_style" value="dark" checked><span>Dark</span></label><label><input type="radio" name="card_style" value="light"><span>Light</span></label></div>
    <canvas width="1080" height="1080" class="share-preview" aria-label="Share card preview"></canvas>
    <div class="tm-modal-actions"><button type="button" class="tm-btn" data-close>Close</button><button type="button" class="tm-btn" data-card-copy><?= icon('copy', 'icon icon-sm') ?> Copy image</button><button type="button" class="tm-btn tm-btn-primary" data-card-download><?= icon('download', 'icon icon-sm') ?> Download image</button></div>
  </div>
</div>
