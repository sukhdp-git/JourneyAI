<div class="tm-modal" id="tm-quick" role="dialog" aria-modal="true" aria-labelledby="tm-quick-title" hidden>
  <div class="tm-modal-bg" data-close></div>
  <div class="tm-modal-card">
    <div class="tm-modal-head"><h2 id="tm-quick-title"><?= icon('zap', 'icon icon-sm') ?> Quick Log</h2><button type="button" class="tm-icon-btn" data-close aria-label="Close"><?= icon('x', 'icon') ?></button></div>
    <?php if (!$acc['writable']): ?>
      <p class="tm-alert tm-alert-error">This live account is read-only without an active plan. <a href="<?= e(url('/pricing')) ?>">Upgrade</a> or switch to a demo account.</p>
    <?php else: ?>
    <?= App\Core\View::partial('terminal/partials/quick-log', ['acc' => $acc, 'ctx' => 'qlm']) ?>
    <details class="ql-cmd">
      <summary>Prefer typing? Use a one-line command</summary>
      <?php $qSpecs = []; foreach (App\Trading\Instruments::all() as $sym => $ins) { $qSpecs[$sym] = ['a' => array_values($ins['aliases'])]; } ?>
      <form class="tm-quick" data-quick-form data-specs="<?= e(json_encode($qSpecs)) ?>">
        <div class="cmd-box quick-cmd-box"><label for="quick-cmd" class="sr-only">Trade command</label><input id="quick-cmd" name="command" class="tm-cmd" autocomplete="off" spellcheck="false" maxlength="300" placeholder="<?= e(t('quick.placeholder')) ?>" data-quick-input></div>
        <div class="tm-quick-preview" data-quick-preview aria-live="polite"><span class="muted">The parsed preview appears here. Press Enter to log it.</span></div>
        <p class="tm-hint">Syntax: <code>buy|sell &lt;asset&gt; &lt;entry&gt; sl &lt;stop&gt; [tp &lt;price&gt; | 3r] [0.5 lot] [win|loss|be] [#fomo #revenge] [setup words]</code></p>
        <div class="tm-modal-actions"><button class="tm-btn tm-btn-primary" type="submit">Log command ↵</button></div>
      </form>
    </details>
    <p class="small" style="margin:10px 0 0"><a href="<?= e(url('/terminal/trades/new')) ?>">Open the full trade form →</a></p>
    <?php endif; ?>
  </div>
</div>
