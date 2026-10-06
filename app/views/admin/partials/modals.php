<div class="modal" id="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="confirm-title" hidden>
  <div class="modal-backdrop" data-modal-close></div>
  <div class="modal-card modal-sm">
    <div class="modal-icon danger"><?= icon('alert', 'icon') ?></div>
    <h2 id="confirm-title">Are you sure?</h2>
    <p data-confirm-text>This action cannot be undone.</p>
    <div class="modal-actions">
      <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
      <button type="button" class="btn btn-danger" data-confirm-ok>Delete</button>
    </div>
  </div>
</div>
<?php if (can('media')): ?>
<div class="modal" id="media-modal" role="dialog" aria-modal="true" aria-labelledby="media-title" hidden>
  <div class="modal-backdrop" data-modal-close></div>
  <div class="modal-card modal-lg">
    <div class="modal-head">
      <h2 id="media-title">Choose an image</h2>
      <button type="button" class="icon-btn" data-modal-close aria-label="Close"><?= icon('x', 'icon') ?></button>
    </div>
    <div class="modal-toolbar">
      <label class="search-input"><?= icon('search', 'icon icon-sm') ?><span class="sr-only">Search media</span><input type="search" placeholder="Search media…" data-media-search></label>
      <label class="btn btn-primary btn-sm upload-btn"><?= icon('upload', 'icon icon-sm') ?> Upload<input type="file" accept=".jpg,.jpeg,.png,.webp,.svg" multiple data-media-upload hidden></label>
    </div>
    <div class="media-picker-grid" data-media-grid aria-live="polite"></div>
    <div class="modal-foot"><button type="button" class="btn btn-secondary btn-sm" data-media-prev>Previous</button><span data-media-page></span><button type="button" class="btn btn-secondary btn-sm" data-media-next>Next</button></div>
  </div>
</div>
<?php endif; ?>
