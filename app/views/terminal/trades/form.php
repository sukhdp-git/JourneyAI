<?php
use App\Trading\AiCoach;
use App\Trading\Domain;
use App\Trading\Instruments;
/** Log Trade modal (create + edit). @var ?array $t @var array $acc @var string $tz @var array $strategies */
$v = function (string $k, string $d = '') use ($t) {
    $old = old($k, null);
    if ($old !== null) return (string) $old;
    if (!$t) return $d;
    if ($k === 'pnl') return $t['pnl_override'] ? (string) $t['pnl'] : '';
    $x = $t[$k] ?? '';
    return is_string($x) && preg_match('/^-?\d+\.\d+$/', $x) ? rtrim(rtrim($x, '0'), '.') : (string) $x;
};
$err = fn ($k) => field_error($k);
$fc = fn ($k, $extra = '') => 'f' . ($extra ? ' ' . $extra : '') . (has_error($k) ? ' has-error' : '');
$editing = (bool) $t;
$action = $editing ? url('/terminal/trades/' . $t['id']) : url('/terminal/trades');
$back = $editing ? url('/terminal/trades/' . $t['id']) : url('/terminal/trades');
$zone = new DateTimeZone($tz);
$when = $editing ? (new DateTimeImmutable($t['executed_at'], new DateTimeZone('UTC')))->setTimezone($zone) : new DateTimeImmutable('now', $zone);
$side = $v('side', 'LONG') === 'SHORT' ? 'SHORT' : 'LONG';
$ai = AiCoach::configured();
$specs = [];
foreach (Instruments::all() as $s => $i) {
    $specs[$s] = ['n' => $i['name'], 'c' => $i['contract'], 'q' => $i['quote'], 'b' => $i['base'], 'p' => $i['pip'], 'd' => $i['decimals'], 'a' => array_values($i['aliases'])];
}
$stratData = array_map(fn ($s) => [
    'id' => (int) $s['id'], 'name' => $s['name'], 'style' => $s['style'] ?? null, 'styleLabel' => Domain::STRATEGY_STYLES[$s['style'] ?? ''] ?? null,
    'rr' => $s['target_rr'] !== null ? (float) $s['target_rr'] : null,
    'rules' => array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $s['checklist'])), 'strlen')),
    'setups' => array_values(array_filter(array_map('trim', preg_split('/\R/', (string) ($s['setups'] ?? ''))), 'strlen')),
], $strategies);
$emotions = ['DISCIPLINED' => 'Disciplined (100% textbook)', 'CALM' => 'Calm', 'PATIENT' => 'Patient', 'FOMO' => 'FOMO (Jumped early)', 'HESITANT' => 'Hesitant', 'REVENGE' => 'Revenge (After loss)', 'GREEDY' => 'Greedy (Oversized lot)'];
$mistakes = ['NONE' => 'None', 'FOMO_ENTRY' => 'FOMO Entry', 'REVENGE_TRADE' => 'Revenge Trade', 'MOVED_STOP' => 'Moved Stop Loss', 'OVERSIZED' => 'Overleveraged', 'IGNORED_PLAN' => 'Broke Trading Rules'];
$curEmo = $v('emotion');
$curMis = $v('mistake_tag', 'NONE');
if ($curEmo !== '' && !isset($emotions[$curEmo])) $emotions[$curEmo] = ucfirst(strtolower($curEmo));
if (!isset($mistakes[$curMis])) $mistakes[$curMis] = Domain::MISTAKES[$curMis] ?? $curMis;
$sessions = ['ASIA' => 'Asian', 'LONDON' => 'London', 'NEW_YORK' => 'New York', 'NY_PM' => 'NY PM'];
$curSession = $v('session');
$mini = fn (string $field, string $label) => '<button type="button" class="lt-mic-mini" data-lt-mic="' . $field . '" aria-label="Speak ' . e($label) . '" title="Speak ' . e($label) . '">' . icon('mic', 'icon icon-xs') . '</button>';
if ($editing && $t['pnl'] !== null) {
    $fmt = fn ($x) => Instruments::format($t['symbol'], $x);
    $share = ['symbol' => $t['symbol'], 'side' => $t['side'], 'pnl' => money($t['pnl'], $acc['currency'], true), 'positive' => (float) $t['pnl'] >= 0, 'r' => $t['rr'] === null ? '' : ((float) $t['rr'] > 0 ? '+' : '') . number_format((float) $t['rr'], 2) . 'R',
        'entry' => $fmt($t['entry_price']), 'exit' => $fmt($t['exit_price']), 'stop' => $fmt($t['stop_loss']), 'lots' => rtrim(rtrim((string) $t['lot_size'], '0'), '.'), 'strategy' => $t['strategy_name'] ?: ($t['setup_tag'] ?: '—'),
        'session' => Domain::SESSIONS[$t['session']] ?? '—', 'brand' => setting('site_name', 'journzey.ai'), 'demo' => (bool) $acc['has_demo_data']];
}
?>
<div class="lt-stage">
<form method="post" action="<?= e($action) ?>" class="lt-modal" id="trade-form" enctype="multipart/form-data" novalidate
  data-lt data-currency="<?= e($acc['currency']) ?>" data-specs="<?= e(json_encode($specs)) ?>" data-strategies="<?= e(json_encode($stratData)) ?>" data-base="<?= e(url('/terminal')) ?>">
  <?= csrf_field() ?>

  <!-- A. Header -->
  <header class="lt-head">
    <span class="lt-dir-badge <?= $side === 'LONG' ? 'is-long' : 'is-short' ?>" data-lt-badge aria-hidden="true"><?= icon($side === 'LONG' ? 'trend-up' : 'trend-down', 'icon') ?></span>
    <div class="lt-head-text">
      <h2><?= $editing ? 'Edit Logged Trade' : 'Log New Trade' ?></h2>
      <p>Automatic R:R calculation, Gold lot sizing &amp; strategy metrics · <?= e($acc['name']) ?> (<?= e($acc['currency']) ?>)</p>
    </div>
    <a class="lt-x" href="<?= e($back) ?>" aria-label="Close"><?= icon('x', 'icon') ?></a>
  </header>

  <!-- B. Voice console -->
  <section class="lt-voice" data-lt-voice aria-label="Voice input">
    <div class="lt-voice-row">
      <button type="button" class="lt-mic" data-lt-mic="all" aria-pressed="false"><span class="lt-mic-on"><?= icon('mic', 'icon') ?></span><span class="lt-mic-label">Speak trade</span></button>
      <span class="lt-listen" data-lt-listen hidden><i></i> Listening… Speak your trade details</span>
      <div class="lt-locales" role="group" aria-label="Voice language">
        <?php foreach (['en-US' => '🇺🇸 EN', 'ru-RU' => '🇷🇺 RU', 'zh-CN' => '🇨🇳 中文', 'pt-BR' => '🇧🇷 PT'] as $code => $lbl): ?><button type="button" class="lt-chip" data-lt-locale="<?= $code ?>"><?= $lbl ?></button><?php endforeach; ?>
      </div>
      <button type="button" class="lt-audio" data-lt-audio aria-pressed="true" title="Spoken confirmation"><span data-lt-audio-on><?= icon('volume', 'icon icon-sm') ?> Audio on</span><span data-lt-audio-off hidden><?= icon('volume-off', 'icon icon-sm') ?> Audio off</span></button>
    </div>
    <div class="lt-heard" aria-live="polite"><span class="muted">Heard:</span> <q data-lt-heard>—</q></div>
    <ul class="lt-changes" data-lt-changes aria-live="polite"></ul>
    <div class="lt-sims"><span class="muted small">Try it:</span>
      <button type="button" class="lt-chip" data-lt-sim="en-US" data-text="Bought gold at two thousand eight hundred sixty point five, stop loss two thousand eight hundred fifty five, exit two thousand eight hundred seventy two, zero point five lots, London session">🇺🇸 Gold long</button>
      <button type="button" class="lt-chip" data-lt-sim="ru-RU" data-text="Продал золото вход две тысячи восемьсот шестьдесят точка пять стоп две тысячи восемьсот шестьдесят восемь выход две тысячи восемьсот сорок лот ноль точка два Нью-Йорк">🇷🇺 Золото шорт</button>
      <button type="button" class="lt-chip" data-lt-sim="zh-CN" data-text="买入黄金 入场两千八百六十点五 止损两千八百五十五 出场两千八百八十 零点二手 伦敦">🇨🇳 黄金做多</button>
      <button type="button" class="lt-chip" data-lt-sim="pt-BR" data-text="Comprei ouro entrada dois mil oitocentos e sessenta vírgula cinco stop dois mil oitocentos e cinquenta e cinco saída dois mil oitocentos e setenta lote zero vírgula três sessão Londres">🇧🇷 Ouro compra</button>
    </div>
    <p class="muted small" data-lt-unsupported hidden>This browser has no speech recognition — the “Try it” examples and typing still work. Use Chrome, Edge or Safari for voice.</p>
  </section>

  <!-- C. Primary execution parameters -->
  <section class="lt-sec">
    <div class="lt-grid lt-grid-c">
      <div class="<?= $fc('symbol') ?>">
        <label for="t-symbol">Asset / symbol <?= $mini('symbol', 'asset') ?></label>
        <input id="t-symbol" name="symbol" type="text" list="lt-symbols" autocomplete="off" spellcheck="false" placeholder="XAUUSD" value="<?= e($v('symbol', 'XAUUSD')) ?>" required data-lt-symbol>
        <datalist id="lt-symbols"><?php foreach (Instruments::all() as $s => $i): ?><option value="<?= e($s) ?>"><?= e($i['name']) ?></option><?php endforeach; ?></datalist>
        <div class="lt-quick"><?php foreach (['XAUUSD', 'EURUSD', 'GBPUSD', 'US30'] as $q): ?><button type="button" class="lt-chip" data-lt-quick="<?= $q ?>"><?= $q ?></button><?php endforeach; ?></div>
        <?= $err('symbol') ?>
      </div>
      <div class="<?= $fc('side') ?>">
        <span class="lbl">Direction <?= $mini('side', 'direction') ?></span>
        <div class="lt-dir" role="radiogroup" aria-label="Direction">
          <label class="lt-dir-long"><input type="radio" name="side" value="LONG"<?= $side === 'LONG' ? ' checked' : '' ?>><span><?= icon('trend-up', 'icon icon-sm') ?> LONG</span></label>
          <label class="lt-dir-short"><input type="radio" name="side" value="SHORT"<?= $side === 'SHORT' ? ' checked' : '' ?>><span><?= icon('trend-down', 'icon icon-sm') ?> SHORT</span></label>
        </div>
        <?= $err('side') ?>
      </div>
      <div class="<?= $fc('executed_at') ?>"><label for="t-date">Date</label><input id="t-date" type="date" name="trade_date" value="<?= e((string) old('trade_date', $when->format('Y-m-d'))) ?>"><?= $err('executed_at') ?></div>
      <div class="f"><label for="t-time">Execution time</label><input id="t-time" type="time" name="trade_time" value="<?= e((string) old('trade_time', $when->format('H:i'))) ?>"></div>
    </div>
  </section>

  <!-- D. Execution & sizing calculator -->
  <section class="lt-sec">
    <div class="lt-gold" data-lt-gold hidden><?= icon('sparkles', 'icon icon-sm') ?> <b>Gold formula active</b> — $1 move = $1 on 0.01 lot, $10 on 0.1 lot, $100 on 1.0 lot</div>
    <div class="lt-grid lt-grid-d">
      <div class="<?= $fc('entry_price') ?>"><label for="t-entry">Entry price <?= $mini('entry', 'entry price') ?></label><input id="t-entry" type="number" step="any" name="entry_price" inputmode="decimal" value="<?= e($v('entry_price')) ?>" required><?= $err('entry_price') ?></div>
      <div class="<?= $fc('exit_price') ?>"><label for="t-exit">Exit price <?= $mini('exit', 'exit price') ?></label><input id="t-exit" type="number" step="any" name="exit_price" inputmode="decimal" value="<?= e($v('exit_price')) ?>" placeholder="empty = open"><?= $err('exit_price') ?></div>
      <div class="<?= $fc('stop_loss') ?>"><label for="t-stop">Stop loss (SL) <?= $mini('stop', 'stop loss') ?></label><input id="t-stop" type="number" step="any" name="stop_loss" inputmode="decimal" value="<?= e($v('stop_loss')) ?>"><?= $err('stop_loss') ?></div>
      <div class="<?= $fc('lot_size') ?>"><label for="t-lots">Lot size <?= $mini('lots', 'lot size') ?></label><input id="t-lots" type="number" step="0.01" min="0.01" name="lot_size" inputmode="decimal" value="<?= e($v('lot_size', '0.10')) ?>" required><?= $err('lot_size') ?></div>
      <div class="<?= $fc('take_profit') ?>"><label for="t-tp">Take profit <span class="muted">(optional)</span></label><input id="t-tp" type="number" step="any" name="take_profit" inputmode="decimal" value="<?= e($v('take_profit')) ?>"><?= $err('take_profit') ?></div>
    </div>
    <div class="<?= $fc('rate') ?>" data-rate-wrap<?= has_error('rate') ? '' : ' hidden' ?>><label for="t-rate">Conversion rate <span data-rate-pair></span></label><input id="t-rate" type="number" step="any" min="0" name="rate" inputmode="decimal" value="<?= e((string) old('rate', '')) ?>"><?= $err('rate') ?></div>
    <div class="lt-metrics" aria-live="polite">
      <div class="lt-metric"><small>Risk / max loss</small><b class="lt-risk" data-lt-o="risk">—</b></div>
      <div class="lt-metric"><small>Risk : Reward</small><b class="lt-rr" data-lt-o="rr">—</b></div>
      <div class="lt-metric lt-metric-pnl"><small data-lt-o="pnl-label">Calculated net P&amp;L</small><b data-lt-o="pnl">—</b></div>
      <button type="button" class="lt-override-btn" data-lt-override aria-pressed="<?= $v('pnl') !== '' ? 'true' : 'false' ?>"><?= icon('edit', 'icon icon-xs') ?> <span>Manual P&amp;L</span></button>
    </div>
    <div class="<?= $fc('pnl') ?>" data-lt-override-box<?= $v('pnl') !== '' || has_error('pnl') ? '' : ' hidden' ?>>
      <label for="t-pnl">Manual P&amp;L (<?= e($acc['currency']) ?>)</label>
      <input id="t-pnl" type="number" step="any" name="pnl" inputmode="decimal" value="<?= e($v('pnl')) ?>"<?= $v('pnl') !== '' || has_error('pnl') ? '' : ' disabled' ?>>
      <span class="f-hint">For broker commissions, swaps or partial scale-outs. Replaces the calculated P&amp;L.</span><?= $err('pnl') ?>
    </div>
  </section>

  <!-- E. Session, strategy & catalyst -->
  <section class="lt-sec">
    <div class="f">
      <span class="lbl">Session <?= $mini('session', 'session') ?></span>
      <input type="hidden" name="session" value="<?= e(isset($sessions[$curSession]) ? $curSession : '') ?>" data-lt-session>
      <div class="lt-pills" role="group" aria-label="Session">
        <?php foreach ($sessions as $k => $l): ?><button type="button" class="lt-pill<?= $curSession === $k ? ' on' : '' ?>" data-lt-session-btn="<?= $k ?>" aria-pressed="<?= $curSession === $k ? 'true' : 'false' ?>"><?= e($l) ?></button><?php endforeach; ?>
      </div>
      <span class="f-hint">None selected = detected from the execution time.</span>
    </div>
    <div class="<?= $fc('strategy_id') ?>">
      <span class="lbl">Strategy <?= $mini('strategy', 'strategy') ?> <a class="lt-add-strat" href="<?= e(url('/terminal/strategies/new')) ?>" target="_blank" rel="noopener">+ Add Strategy</a></span>
      <input type="hidden" name="strategy_id" value="<?= e($v('strategy_id')) ?>" data-lt-strategy>
      <div class="lt-strats" role="group" aria-label="Strategy">
        <?php foreach ($stratData as $s): ?><button type="button" class="lt-strat<?= (string) $v('strategy_id') === (string) $s['id'] ? ' on' : '' ?>" data-lt-strat="<?= $s['id'] ?>"><b><?= e($s['name']) ?></b><?php if ($s['styleLabel']): ?><span class="lt-cat"><?= e($s['styleLabel']) ?></span><?php endif; ?></button><?php endforeach; ?>
        <?php if (!$stratData): ?><p class="muted small">No strategies yet — <a href="<?= e(url('/terminal/strategies/new')) ?>">create one</a> or copy one from the <a href="<?= e(url('/terminal/university')) ?>">University</a>.</p><?php endif; ?>
      </div>
      <?= $err('strategy_id') ?>
    </div>
    <div class="lt-context" data-lt-context hidden>
      <div class="lt-vp" data-lt-vp hidden>
        <div class="f"><label for="t-vp-level">Level trigger</label><select id="t-vp-level" data-lt-vp-level><option value="">—</option><?php foreach (['VAL Bounce', 'VAL Breakdown', 'VAH Rejection', 'VAH Breakout', 'POC Test', 'HVN Accumulation', 'LVN Swift Pass'] as $o): ?><option><?= $o ?></option><?php endforeach; ?></select></div>
        <div class="f"><label for="t-vp-ref">Reference timeframe</label><select id="t-vp-ref" data-lt-vp-ref><?php foreach (['Previous Day', 'Previous Session', 'Previous Week'] as $o): ?><option><?= $o ?></option><?php endforeach; ?></select></div>
      </div>
      <div class="lt-catalysts" data-lt-catalysts></div>
      <div class="lt-rules" data-lt-rules></div>
    </div>
    <div class="f"><label for="t-setup">Setup trigger / tags</label><input id="t-setup" type="text" name="setup_tag" maxlength="120" value="<?= e($v('setup_tag')) ?>" placeholder="e.g. VAL Bounce · Previous Day" data-lt-setup></div>
  </section>

  <!-- F. Chart screenshot -->
  <section class="lt-sec">
    <span class="lbl"><?= icon('camera', 'icon icon-xs') ?> Chart screenshot</span>
    <div class="lt-tabs" role="tablist" aria-label="Screenshot source">
      <button type="button" role="tab" class="on" data-lt-shot-tab="paste" aria-selected="true">Paste (Ctrl/⌘+V)</button>
      <button type="button" role="tab" data-lt-shot-tab="upload" aria-selected="false">Upload</button>
      <button type="button" role="tab" data-lt-shot-tab="url" aria-selected="false">Image link</button>
    </div>
    <div class="lt-shot" data-lt-shot-pane="paste"><p class="lt-paste-zone" tabindex="0" data-lt-paste>Copy a chart (e.g. TradingView: Ctrl/⌘+Alt+S or the camera menu) and press <kbd>Ctrl</kbd>/<kbd>⌘</kbd>+<kbd>V</kbd> anywhere on this form.</p></div>
    <div class="lt-shot" data-lt-shot-pane="upload" hidden><input type="file" name="screenshot" accept="image/png,image/jpeg,image/webp" data-lt-file><span class="f-hint">PNG, JPG or WEBP up to 10 MB. Stored privately with the trade.</span></div>
    <div class="<?= $fc('screenshot_url', 'lt-shot') ?>" data-lt-shot-pane="url" hidden><input type="url" name="screenshot_url" maxlength="500" placeholder="https://www.tradingview.com/x/…" value="<?= e($v('screenshot_url')) ?>" data-lt-url><?= $err('screenshot_url') ?></div>
    <div class="lt-preview" data-lt-preview hidden><img alt="Screenshot preview" data-lt-preview-img><button type="button" class="lt-preview-x" data-lt-shot-clear aria-label="Remove screenshot"><?= icon('x', 'icon icon-sm') ?></button></div>
    <?php if ($editing && $t['screenshot_path']): ?><p class="muted small">A screenshot is already attached — adding a new one replaces it.</p><?php endif; ?>
    <?php if ($ai): ?><button type="button" class="tm-btn tm-btn-sm" data-shot-read="<?= e(url('/terminal/trades/read-chart')) ?>" disabled><?= icon('sparkles', 'icon icon-sm') ?> Read chart (fill from position tool)</button><?php endif; ?>
    <p class="small" data-shot-msg aria-live="polite"><?= $err('screenshot') ?></p>
  </section>

  <!-- G. Psychology & discipline -->
  <section class="lt-sec">
    <div class="lt-grid lt-grid-g">
      <div class="f"><label for="t-emo">Trader mindset / emotion</label><select id="t-emo" name="emotion" data-lt-emotion><option value="">—</option><?php foreach ($emotions as $k => $l): ?><option value="<?= e($k) ?>"<?= $curEmo === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
      <div class="f"><label for="t-mis">Discipline leak / mistake</label><select id="t-mis" name="mistake_tag" data-lt-mistake><?php foreach ($mistakes as $k => $l): ?><option value="<?= e($k) ?>"<?= $curMis === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
    </div>
    <input type="hidden" name="rules_followed" value="0">
    <label class="lt-check"><input type="checkbox" name="rules_followed" value="1" data-lt-rules-box<?= $v('rules_followed', '1') === '1' ? ' checked' : '' ?>> Followed all trading rules &amp; risk limits</label>
    <div class="f"><label for="t-notes">Notes</label><textarea id="t-notes" name="notes" rows="3" maxlength="5000" placeholder="Reflection, what went well, what to improve"><?= e($v('notes')) ?></textarea></div>
  </section>

  <?php if ($editing): // values imported from a broker statement are kept unchanged ?><input type="hidden" name="fees" value="<?= e((string) (float) $t['fees']) ?>"><?php endif; ?>
  <footer class="lt-foot">
    <a class="tm-btn" href="<?= e($back) ?>">Cancel</a>
    <?php if (!empty($share)): ?><button type="button" class="tm-btn" data-share-trade="<?= e(json_encode($share)) ?>"><?= icon('share', 'icon icon-sm') ?> Generate Share Card</button><?php endif; ?>
    <button class="tm-btn tm-btn-primary" type="submit"><?= icon('check', 'icon icon-sm') ?> <?= $editing ? 'Update Trade' : 'Save Logged Trade' ?></button>
  </footer>
</form>
</div>
<script src="<?= e(asset('js/logtrade.js')) ?>" defer></script>
