<?php
use App\Trading\Confessions as C;
/** Confessions feed. @var array $prof @var array $stats @var array $rows @var bool $more @var string $tab @var ?string $tag @var array $trades */
[$em, $hue] = C::avatar((int) $prof['avatar_seed']);
$prompts = ['The trade I’m least proud of this week was…', 'I broke my rule today because…', 'One thing journaling showed me:', 'Beginner question:', 'The moment I knew I was tilting:', 'Small win nobody will clap for:'];
$q = fn (array $x) => url('/terminal/confessions') . '?' . http_build_query(array_filter(['tab' => $x['tab'] ?? $tab, 'tag' => array_key_exists('tag', $x) ? $x['tag'] : $tag], fn ($v) => $v !== null && $v !== '' && $v !== 'latest'));
?>
<div class="cf" data-cf-app data-base="<?= e(url('/terminal/confessions')) ?>" data-tab="<?= e($tab) ?>" data-tagf="<?= e((string) $tag) ?>">
  <header class="cf-hero">
    <div>
      <span class="cf-kicker"><?= icon('lock', 'icon icon-xs') ?> Members only · anonymous</span>
      <h2>The trading floor, <em>unfiltered.</em></h2>
      <p>Real trades, real feelings, zero names. No links, no DMs, no signals: the spam shield blocks them before they post.</p>
    </div>
    <div class="cf-me">
      <span class="cf-av lg" style="--h:<?= $hue ?>" aria-hidden="true"><?= $em ?></span>
      <div><small>Your respect</small><b class="cf-respect-total" data-countup><?= e(C::respect($stats['likes'])) ?></b><span class="muted small">@<?= e($prof['handle']) ?> · <?= (int) $stats['posts'] ?> post<?= $stats['posts'] === 1 ? '' : 's' ?> · <?= (int) $stats['likes'] ?> like<?= $stats['likes'] === 1 ? '' : 's' ?></span></div>
    </div>
  </header>

  <div class="cf-grid">
    <div class="cf-main">
      <form class="cf-compose" data-cf-compose>
        <div class="cf-compose-top">
          <span class="cf-av" style="--h:<?= $hue ?>" aria-hidden="true"><?= $em ?></span>
          <label class="sr-only" for="cf-text">Your confession</label>
          <textarea id="cf-text" name="body" rows="3" maxlength="<?= C::MAX_POST ?>" placeholder="<?= e($prompts[0]) ?>" data-cf-text data-prompts="<?= e(json_encode($prompts, JSON_UNESCAPED_UNICODE)) ?>"></textarea>
        </div>
        <div class="cf-shield" data-cf-shield hidden role="status"></div>
        <div class="cf-tags" role="radiogroup" aria-label="Topic">
          <?php $first = true; foreach (C::TAGS as $k => [$e, $l]): ?><label class="cf-chip"><input type="radio" name="tag" value="<?= e($k) ?>"<?= $first ? ' checked' : '' ?>><span><?= $e ?> <?= e($l) ?></span></label><?php $first = false; endforeach; ?>
        </div>
        <div class="cf-compose-row">
          <div class="cf-moods" role="radiogroup" aria-label="Mood (optional)">
            <?php foreach (C::MOODS as $mo): ?><label class="cf-mood-pick"><input type="radio" name="mood" value="<?= e($mo) ?>"><span><?= e($mo) ?></span></label><?php endforeach; ?>
          </div>
        </div>
        <div class="cf-compose-row">
          <label class="cf-attach"><?= icon('chart', 'icon icon-xs') ?><span class="sr-only">Attach a trade</span>
            <select name="trade_id" aria-label="Attach a trade from your journal (shows its real P&amp;L)">
              <option value="">Attach a trade from your journal (optional, shows its real P&amp;L)</option>
              <?php foreach ($trades as $t): ?><option value="<?= (int) $t['id'] ?>"><?= e(fmt_date($t['executed_at'], 'M j')) ?> · <?= e($t['symbol'] . ' ' . $t['side']) ?> · <?= e(money((float) $t['pnl'], $t['currency'], true)) ?><?= (int) $t['is_demo'] ? ' · demo' : '' ?></option><?php endforeach; ?>
            </select>
          </label>
          <span class="cf-count" data-cf-count>0 / <?= C::MAX_POST ?></span>
          <button type="submit" class="tm-btn tm-btn-primary cf-post" data-cf-post><?= icon('send', 'icon icon-sm') ?> Confess</button>
        </div>
      </form>

      <nav class="cf-tabs" aria-label="Feed">
        <?php foreach (['latest' => 'Latest', 'top' => 'Top this week', 'mine' => 'My posts'] as $k => $l): ?><a href="<?= e($q(['tab' => $k])) ?>" class="<?= $tab === $k ? 'on' : '' ?>"<?= $tab === $k ? ' aria-current="page"' : '' ?>><?= e($l) ?></a><?php endforeach; ?>
      </nav>
      <div class="cf-filter">
        <a href="<?= e($q(['tag' => null])) ?>" class="<?= $tag ? '' : 'on' ?>">All</a>
        <?php foreach (C::TAGS as $k => [$e, $l]): ?><a href="<?= e($q(['tag' => $k])) ?>" class="<?= $tag === $k ? 'on' : '' ?>"><?= $e ?> <?= e($l) ?></a><?php endforeach; ?>
      </div>

      <div class="cf-feed" data-cf-feed>
        <?php foreach ($rows as $i => $c): ?><?= App\Core\View::partial('terminal/confessions/card', ['c' => $c, 'me' => (int) $m['id'], 'i' => $i]) ?><?php endforeach; ?>
      </div>
      <?php if (!$rows): ?>
      <div class="cf-empty" data-cf-empty>
        <div class="cf-empty-ico" aria-hidden="true">🙊</div>
        <h3><?= $tab === 'mine' ? 'You haven’t confessed yet.' : 'Quiet on the floor… for now.' ?></h3>
        <p class="muted">Be the first. The best posts are honest and specific: the trade, the feeling, the lesson.</p>
        <div class="cf-card cf-example" aria-label="Example of how a post looks">
          <span class="cf-ribbon">Example</span>
          <header class="cf-head"><span class="cf-av" style="--h:262" aria-hidden="true">🦉</span><div class="cf-who"><b>@your_handle</b><span class="cf-meta"><span>now</span><span class="cf-tag cf-tag-confession">🙊 Confession</span></span></div></header>
          <div class="cf-body">Moved my stop three times on gold today. It hit the third one. Tomorrow the stop stays where I put it.</div>
          <footer class="cf-actions"><span class="cf-act cf-like"><svg class="cf-heart" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20.5s-7.5-4.6-9.3-9.2C1.4 7.9 3.6 4.5 7 4.5c2 0 3.6 1.1 5 3 1.4-1.9 3-3 5-3 3.4 0 5.6 3.4 4.3 6.8-1.8 4.6-9.3 9.2-9.3 9.2z"/></svg> $0</span></footer>
        </div>
      </div>
      <?php endif; ?>
      <div class="cf-more-wrap"><button type="button" class="tm-btn" data-cf-loadmore data-next="<?= $rows ? ($tab === 'top' ? count($rows) : (int) end($rows)['id']) : 0 ?>"<?= $more ? '' : ' hidden' ?>>Load more</button></div>
    </div>

    <aside class="cf-side">
      <section class="cf-box cf-howlike">
        <h3><svg class="cf-heart" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20.5s-7.5-4.6-9.3-9.2C1.4 7.9 3.6 4.5 7 4.5c2 0 3.6 1.1 5 3 1.4-1.9 3-3 5-3 3.4 0 5.6 3.4 4.3 6.8-1.8 4.6-9.3 9.2-9.3 9.2z"/></svg> 1 like = $15 of respect</h3>
        <p class="muted small">Likes are shown as dollars of respect, on posts and on helpful replies. It’s a scoreboard, not money: nothing is ever paid or charged.</p>
      </section>
      <section class="cf-box">
        <h3><?= icon('shield', 'icon icon-sm') ?> House rules</h3>
        <ul class="cf-rules">
          <li><b>Stay anonymous.</b> No names, emails, phone numbers.</li>
          <li><b>No selling.</b> Links, DMs, signals, groups and “account management” are blocked automatically.</li>
          <li><b>Help beginners.</b> The best replies teach one clear thing.</li>
          <li><b>Real P&amp;L only.</b> Numbers come from a trade in your journal.</li>
        </ul>
      </section>
      <section class="cf-box cf-prompts-box">
        <h3><?= icon('sparkles', 'icon icon-sm') ?> Need a prompt?</h3>
        <div class="cf-prompts"><?php foreach ($prompts as $p): ?><button type="button" class="cf-prompt" data-cf-prompt="<?= e($p) ?>"><?= e($p) ?></button><?php endforeach; ?></div>
      </section>
      <details class="cf-box cf-handle-edit">
        <summary><?= icon('user', 'icon icon-sm') ?> Your handle: @<?= e($prof['handle']) ?></summary>
        <form method="post" action="<?= e(url('/terminal/confessions/handle')) ?>" class="cf-handle-form">
          <?= csrf_field() ?>
          <label for="cf-h2" class="sr-only">New handle</label>
          <div class="cf-at"><span>@</span><input id="cf-h2" name="handle" value="<?= e($prof['handle']) ?>" maxlength="20" pattern="[a-z0-9_.]{3,20}" required></div>
          <button class="tm-btn tm-btn-sm" type="submit">Change</button>
          <p class="muted small">One change every 30 days. Your old posts move to the new handle.</p>
        </form>
      </details>
    </aside>
  </div>
</div>
