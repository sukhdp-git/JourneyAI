<?php /** @var bool $paidOnly @var string $tab @var array $tabs @var array $posts @var array $comments @var array $blocked @var array $members @var array $counts */ $base = admin_url('confessions'); ?>
<div class="page-head"><div><h1>Confessions</h1><p class="muted">The anonymous feed for members with a plan. Posts with links, contact details or selling are blocked automatically; three reports hide a post or reply until you review it here. Members only ever see each other’s @handle.</p></div></div>
<div class="card" style="margin-bottom:16px;padding:16px">
  <form method="post" action="<?= e($base . '/access') ?>" class="inline-actions" style="justify-content:space-between;flex-wrap:wrap;gap:12px">
    <?= csrf_field() ?>
    <div><strong>Who can use Confessions</strong><p class="muted small" style="margin:4px 0 0"><?= $paidOnly ? 'Members with an active plan only. Free members see a locked preview with “Unlock with a plan”.' : 'Open to every signed-in member (testing mode). No plan is needed.' ?></p></div>
    <?php if ($paidOnly): ?><button class="btn btn-secondary btn-sm" name="paid_only" value="0">Open to all members</button><?php else: ?><button class="btn btn-primary btn-sm" name="paid_only" value="1">Lock to members with a plan</button><?php endif; ?>
  </form>
</div>
<div class="card">
  <nav class="status-tabs" aria-label="Moderation views">
    <?php foreach ($tabs as $k => $l): ?><a href="<?= e($base . '?tab=' . $k) ?>"<?= $tab === $k ? ' class="is-active"' : '' ?>><?= e($l) ?><?php if (isset($counts[$k])): ?> <b><?= (int) $counts[$k] ?></b><?php endif; ?></a><?php endforeach; ?>
  </nav>
  <?php $act = function (string $kind, array $r) use ($base, $tab) { ?>
    <form method="post" action="<?= e($base . '/' . (int) $r['id']) ?>" class="inline-actions"><?= csrf_field() ?><input type="hidden" name="kind" value="<?= $kind ?>"><input type="hidden" name="tab" value="<?= e($tab) ?>">
      <?php if ($r['status'] !== 'visible'): ?><button class="btn btn-primary btn-sm" name="action" value="restore">Restore</button><?php endif; ?>
      <?php if ($r['status'] === 'visible'): ?><button class="btn btn-secondary btn-sm" name="action" value="hide">Hide</button><?php endif; ?>
      <?php if ($r['status'] !== 'removed'): ?><button class="btn btn-ghost btn-sm" name="action" value="remove" onclick="return confirm('Remove this permanently from the feed?')">Remove</button><?php endif; ?>
    </form>
  <?php }; ?>
  <?php if (in_array($tab, ['reported', 'hidden', 'recent'], true)): ?>
    <?php if (!$posts && !$comments): ?><p class="muted" style="padding:16px">Nothing to review.</p><?php endif; ?>
    <?php if ($posts): ?>
    <div class="table-wrap"><table class="table table-resp">
      <thead><tr><th>Post</th><th>From</th><th class="t-right">Likes · replies · reports</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody><?php foreach ($posts as $r): ?><tr>
        <td data-label="Post"><?= nl2br(e($r['body'])) ?><?php if ($r['trade_symbol']): ?><small class="block muted"><?= e($r['trade_symbol'] . ' ' . $r['trade_side'] . ' · ' . money((float) $r['trade_pnl'], $r['trade_currency'] ?: 'USD', true)) ?><?= (int) $r['trade_demo'] ? ' · demo' : '' ?></small><?php endif; ?></td>
        <td data-label="From">@<?= e($r['handle']) ?><small class="block muted"><?= e($r['email']) ?> · <?= e(fmt_date($r['created_at'], 'M j, H:i')) ?></small></td>
        <td data-label="Counts" class="t-right"><?= (int) $r['likes'] ?> · <?= (int) $r['comments'] ?> · <b><?= (int) $r['reports'] ?></b></td>
        <td data-label="Status"><?= e($r['status']) ?></td>
        <td data-label="Actions"><?php $act('post', $r); ?></td>
      </tr><?php endforeach; ?></tbody></table></div>
    <?php endif; ?>
    <?php if ($comments): ?>
    <h3 style="padding:16px 16px 0">Replies</h3>
    <div class="table-wrap"><table class="table table-resp">
      <thead><tr><th>Reply</th><th>On post</th><th>From</th><th class="t-right">Reports</th><th>Actions</th></tr></thead>
      <tbody><?php foreach ($comments as $r): ?><tr>
        <td data-label="Reply"><?= nl2br(e($r['body'])) ?></td>
        <td data-label="On post"><small class="muted"><?= e(mb_strimwidth($r['post_body'], 0, 90, '…')) ?></small></td>
        <td data-label="From">@<?= e($r['handle']) ?><small class="block muted"><?= e($r['email']) ?></small></td>
        <td data-label="Reports" class="t-right"><b><?= (int) $r['reports'] ?></b></td>
        <td data-label="Actions"><?php $act('comment', $r); ?></td>
      </tr><?php endforeach; ?></tbody></table></div>
    <?php endif; ?>
  <?php elseif ($tab === 'blocked'): ?>
    <?php if (!$blocked): ?><p class="muted" style="padding:16px">The shield has not blocked anything yet.</p><?php else: ?>
    <div class="table-wrap"><table class="table table-resp">
      <thead><tr><th>Text that was blocked</th><th>Why</th><th>Member</th><th>When</th></tr></thead>
      <tbody><?php foreach ($blocked as $r): ?><tr>
        <td data-label="Text"><?= e($r['body']) ?><small class="block muted"><?= e($r['kind']) ?></small></td>
        <td data-label="Why"><?= e($r['reason']) ?></td>
        <td data-label="Member"><?= $r['handle'] ? '@' . e($r['handle']) : '—' ?><small class="block muted"><?= e($r['email']) ?></small></td>
        <td data-label="When"><?= e(fmt_date($r['created_at'], 'M j, H:i')) ?></td>
      </tr><?php endforeach; ?></tbody></table></div>
    <?php endif; ?>
  <?php else: ?>
    <div class="table-wrap"><table class="table table-resp">
      <thead><tr><th>Handle</th><th>Member</th><th class="t-right">Posts · blocked</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody><?php foreach ($members as $r): $muted = $r['muted_until'] && strtotime($r['muted_until'] . ' UTC') > time(); ?><tr>
        <td data-label="Handle">@<?= e($r['handle']) ?></td>
        <td data-label="Member"><?= e($r['email']) ?><small class="block muted">since <?= e(fmt_date($r['created_at'], 'M j, Y')) ?></small></td>
        <td data-label="Counts" class="t-right"><?= (int) $r['posts'] ?> · <b><?= (int) $r['blocked'] ?></b></td>
        <td data-label="Status"><?= (int) $r['banned'] ? 'Suspended' : ($muted ? 'Muted until ' . e(fmt_date($r['muted_until'], 'M j')) : 'Active') ?></td>
        <td data-label="Actions"><form method="post" action="<?= e($base . '/' . (int) $r['user_id']) ?>" class="inline-actions"><?= csrf_field() ?><input type="hidden" name="kind" value="member"><input type="hidden" name="tab" value="members">
          <?php if ($muted): ?><button class="btn btn-secondary btn-sm" name="action" value="unmute">Unmute</button><?php else: ?><button class="btn btn-secondary btn-sm" name="action" value="mute">Mute 7 days</button><?php endif; ?>
          <?php if ((int) $r['banned']): ?><button class="btn btn-primary btn-sm" name="action" value="unban">Restore access</button><?php else: ?><button class="btn btn-ghost btn-sm" name="action" value="ban" onclick="return confirm('Suspend this member from Confessions?')">Suspend</button><?php endif; ?>
        </form></td>
      </tr><?php endforeach; ?></tbody></table></div>
  <?php endif; ?>
</div>
