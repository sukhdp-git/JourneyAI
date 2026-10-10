<?php use App\Core\View; ?>
<?= View::partial('public/partials/page-hero', ['eyebrow' => setting('pricing_eyebrow', 'Pricing'), 'title' => setting('pricing_heading', 'Pricing'), 'subtitle' => setting('pricing_intro'), 'crumbs' => [['Home', '/'], ['Pricing', '/pricing']]]) ?>
<section class="section section-tight">
  <div class="container">
    <?= View::partial('public/partials/flash', ['flash' => $flash]) ?>
    <?= View::partial('public/partials/pricing-cards') ?>
  </div>
</section>
<?= View::partial('public/partials/product-band', ['title' => 'What you are paying for', 'text' => 'The same terminal on every plan — voice logging, edge analytics, prop-firm rules and verified flex cards.']) ?>
<section class="section section-tight">
  <div class="container">
    <div class="pricing-compare reveal">
      <h2 class="h3">What is included</h2>
      <div class="table-scroll"><table class="compare">
        <thead><tr><th>Feature</th><th>Free (Demo)</th><th>Paid plans</th></tr></thead>
        <tbody>
          <tr><td>Demo accounts &amp; test trades</td><td>Unlimited</td><td>Unlimited</td></tr>
          <tr><td>40-trade demo journal (marked DEMO DATA)</td><td>✓</td><td>✓</td></tr>
          <tr><td>Dashboard, calendar, strategy analysis, Edge Matrix</td><td>✓ on demo accounts</td><td>✓ on demo and live accounts</td></tr>
          <tr><td>Live trading accounts</td><td>—</td><td>✓ (limit per plan)</td></tr>
          <tr><td>CSV statement import into live accounts</td><td>—</td><td>✓</td></tr>
          <tr><td>AI Coach messages per day</td><td><?= (int) (setting('free_ai_daily_limit') ?: 3) ?></td><td>Per plan</td></tr>
          <tr><td>Export my data (JSON/CSV)</td><td>✓</td><td>✓</td></tr>
        </tbody>
      </table></div>
      <p class="muted small">If a paid plan ends, your live accounts and their trades stay in your journal as read-only until you renew. Nothing is deleted.</p>
    </div>
  </div>
</section>
