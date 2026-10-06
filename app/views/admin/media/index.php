<?php /** @var array $rows */ ?>
<div class="page-head">
  <div><h1>Media library</h1><p class="muted"><?= number_format($pager->total) ?> files · <?= e(human_size($usage)) ?> · JPG, PNG, WebP and SVG up to <?= e(human_size(App\Core\Media::maxBytes())) ?>. Images are optimised to WebP automatically.</p></div>
</div>
<form class="card dropzone" method="post" action="<?= e(admin_url('media/upload')) ?>" enctype="multipart/form-data" data-dropzone>
  <?= csrf_field() ?>
  <?= icon('upload', 'icon') ?>
  <p><strong>Drag & drop images here</strong> or</p>
  <label class="btn btn-primary">Choose files<input type="file" name="files[]" accept=".jpg,.jpeg,.png,.webp,.svg,image/jpeg,image/png,image/webp,image/svg+xml" multiple hidden data-dropzone-input></label>
  <noscript><button class="btn btn-secondary" type="submit">Upload</button></noscript>
  <div class="progress" hidden data-dropzone-progress><span></span></div>
</form>
<div class="card">
  <form class="toolbar" method="get" action="<?= e(admin_url('media')) ?>"><label class="search-input"><?= icon('search', 'icon icon-sm') ?><span class="sr-only">Search</span><input type="search" name="q" value="<?= e($q) ?>" placeholder="Search by file name or alt text…"></label><button class="btn btn-secondary btn-sm">Search</button><?php if ($q !== ''): ?><a class="link small" href="<?= e(admin_url('media')) ?>">Reset</a><?php endif; ?></form>
  <?php if ($rows): ?>
  <div class="media-grid">
    <?php foreach ($rows as $m): $u = media_url($m['path']); ?>
    <figure class="media-item">
      <a class="media-thumb" href="<?= e($u) ?>" target="_blank" rel="noopener"><img src="<?= e(media_medium($m['path'])) ?>" alt="<?= e($m['alt_text']) ?>" loading="lazy"></a>
      <figcaption>
        <strong title="<?= e($m['original_name']) ?>"><?= e(mb_strimwidth($m['original_name'], 0, 32, '…')) ?></strong>
        <small class="muted"><?= e(human_size((int) $m['size'])) ?><?= $m['width'] ? ' · ' . (int) $m['width'] . '×' . (int) $m['height'] : '' ?> · <?= e(fmt_date($m['created_at'])) ?><?= $m['uploader'] ? ' · ' . e($m['uploader']) : '' ?></small>
        <form method="post" action="<?= e(admin_url('media/' . $m['id'])) ?>" class="alt-form" data-ajax-form><?= csrf_field() ?><label class="sr-only" for="alt-<?= (int) $m['id'] ?>">Alt text</label><input id="alt-<?= (int) $m['id'] ?>" name="alt_text" value="<?= e($m['alt_text']) ?>" placeholder="Alt text" maxlength="255"><button class="icon-btn" type="submit" aria-label="Save alt text" title="Save alt text"><?= icon('check', 'icon icon-sm') ?></button></form>
        <div class="media-actions">
          <button type="button" class="btn btn-ghost btn-xs" data-copy="<?= e($u) ?>"><?= icon('copy', 'icon icon-xs') ?> Copy URL</button>
          <button type="button" class="btn btn-ghost btn-xs danger" data-confirm="Delete <?= e($m['original_name']) ?>? Pages using it will show no image." data-confirm-action="<?= e(admin_url('media/' . $m['id'] . '/delete')) ?>"><?= icon('trash', 'icon icon-xs') ?> Delete</button>
        </div>
      </figcaption>
    </figure>
    <?php endforeach; ?>
  </div>
  <?= App\Core\View::partial('admin/partials/pagination', ['pager' => $pager]) ?>
  <?php else: ?><div class="empty"><?= icon('image', 'icon') ?><h2><?= $q !== '' ? 'No files match' : 'No media yet' ?></h2><p>Upload logos, hero images and article images above.</p></div><?php endif; ?>
</div>
