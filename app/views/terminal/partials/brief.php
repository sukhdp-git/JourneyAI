<?php /** Weekly do's & don'ts messenger. @var array $brief @var array $m */ $b = $brief; ?>
<div class="jzb" id="jz-brief" data-brief data-auto="<?= $b['auto'] ? '1' : '0' ?>" data-key="jz-brief-<?= (int) $m['id'] ?>-<?= e((new DateTimeImmutable('now', new DateTimeZone($tz)))->format('Y-m-d')) ?>" role="dialog" aria-modal="false" aria-labelledby="jzb-title" hidden>
  <div class="jzb-head">
    <span class="jzb-avatar" aria-hidden="true">j</span>
    <div><strong id="jzb-title">journzey Coach</strong><small><span class="jzb-online"></span> Week ahead · <?= e($b['week']) ?></small></div>
    <button type="button" class="tm-icon-btn" data-brief-close aria-label="Close"><?= icon('x', 'icon icon-sm') ?></button>
  </div>
  <div class="jzb-body" aria-live="polite">
    <div class="jzb-typing" aria-hidden="true"><i></i><i></i><i></i></div>
    <div class="jzb-msg"><p>Good morning<?= $b['name'] !== '' ? ', ' . e($b['name']) : '' ?> 👋 <?= $b['trades'] ? 'Last week: <b>' . (int) $b['trades'] . ' trades</b> · <b class="' . ($b['net'] >= 0 ? 'up' : 'down') . '">' . e($b['net_fmt']) . '</b>' . ($b['win_rate'] !== null ? ' · ' . round($b['win_rate'] * 100) . '% win' : '') . '.' : 'No trades logged last week — set your plan for this one.' ?></p></div>
    <?php if ($b['dont']): ?>
    <div class="jzb-msg jzb-bad"><h3>⛔ Don’t</h3><ul><?php foreach ($b['dont'] as $x): ?><li><span><?= e($x['text']) ?></span><?php if ($x['stat'] !== ''): ?><mark><?= e($x['stat']) ?></mark><?php endif; ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <div class="jzb-msg jzb-good"><h3>✅ Do</h3><ul><?php foreach ($b['do'] as $x): ?><li><span><?= e($x['text']) ?></span><?php if ($x['stat'] !== ''): ?><mark><?= e($x['stat']) ?></mark><?php endif; ?></li><?php endforeach; ?></ul></div>
  </div>
  <div class="jzb-foot"><a class="tm-btn tm-btn-sm" href="<?= e(url('/terminal/edge')) ?>"><?= icon('radar', 'icon icon-xs') ?> Open Edge Matrix</a><button type="button" class="tm-btn tm-btn-sm tm-btn-primary" data-brief-close>Got it 👍</button></div>
</div>
