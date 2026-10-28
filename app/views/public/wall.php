<?php
/** Public Wall of L's. @var array $posts */
use App\Core\View;
use App\Trading\Domain;
use App\Trading\Wall;
?>
<?= View::partial('public/partials/page-hero', ['eyebrow' => 'Wall of L\'s', 'title' => 'Everyone posts green. We post the L\'s.', 'subtitle' => 'Real trades from real traders, anonymised, with the autopsy and the lesson. Because almost everyone loses sometimes, and almost nobody shows it.', 'crumbs' => [['Home', '/'], ['Wall of L\'s', '/wall']]]) ?>
<section class="section section-tight">
  <div class="container wall" data-wall data-csrf="<?= e(csrf_token()) ?>">
    <div class="wall-cta reveal">
      <p><b>Took an L worth learning from?</b> Submit it anonymously from your journzey terminal. Only your chosen nickname is shown — never your name or email.</p>
      <a class="btn btn-primary" href="<?= e(url('/terminal/wall')) ?>">Submit your L</a>
    </div>
    <?php if (!$posts): ?>
      <p class="muted center">The wall is being built. The first L's will appear here soon.</p>
    <?php else: ?>
    <div class="wall-grid">
      <?php foreach ($posts as $i => $p): ?>
      <article class="wall-card reveal" style="--d:<?= $i % 3 ?>" data-tilt3d>
        <header><span class="wall-mk"><?= e(Domain::label($p['mistake'])) ?></span><?php if ($p['instrument']): ?><span class="wall-sym"><?= e($p['instrument']) ?></span><?php endif; ?></header>
        <p class="wall-loss"><?= e($p['loss_text']) ?></p>
        <p class="wall-story"><?= e($p['story']) ?></p>
        <p class="wall-lesson"><b>Lesson:</b> <?= e($p['lesson']) ?></p>
        <footer>
          <span class="wall-by"><?= e($p['handle'] ?: 'Anonymous trader') ?></span>
          <span class="wall-react">
            <?php foreach (Wall::REACTIONS as $k => [$em, $lbl]): ?><button type="button" data-react="<?= e($k) ?>" data-id="<?= (int) $p['id'] ?>" title="<?= e($lbl) ?>" aria-label="<?= e($em . ' ' . $lbl) ?>"><?= $em ?> <b><?= (int) $p['react_' . $k] ?></b></button><?php endforeach; ?>
          </span>
        </footer>
      </article>
      <?php endforeach; ?>
    </div>
    <p class="wall-ask center">Drop a 🫠 if you've ever done this.</p>
    <?php endif; ?>
    <p class="form-note center">Posts are shared by traders with their permission and reviewed before publishing. Nothing here is financial advice.</p>
  </div>
</section>
<script>
(function () {
  var w = document.querySelector('[data-wall]'); if (!w) return;
  w.addEventListener('click', function (e) {
    var b = e.target.closest('[data-react]'); if (!b || b.disabled) return;
    var fd = new FormData(); fd.append('_csrf', w.dataset.csrf); fd.append('r', b.dataset.react);
    b.disabled = true;
    fetch('<?= e(url('/wall')) ?>/' + b.dataset.id + '/react', { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } })
      .then(function (r) { return r.json(); }).then(function (j) { if (j.ok) { b.querySelector('b').textContent = j.count; b.classList.add('on'); } })
      .catch(function () { b.disabled = false; });
  });
})();
</script>
