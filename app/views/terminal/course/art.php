<?php
/** Animated hero illustration for each lesson (decorative; the lesson text carries all the content). @var int $n */
$V = '#8b6bff'; $P = '#feb2b4'; $G = '#34d399'; $R = '#f87171'; $A = '#fbbf24'; $M = '#3a3766';
$candles = function (array $c, float $x0, float $step, float $base, float $scale, string $cls = 'ca-grow') {
    $o = '';
    foreach ($c as $i => [$open, $close, $hi, $lo]) {
        $up = $close >= $open; $x = $x0 + $i * $step; $col = $up ? '#34d399' : '#f87171';
        $y1 = $base - max($open, $close) * $scale; $h = max(3, abs($close - $open) * $scale);
        $o .= '<g class="' . $cls . '" style="--d:' . ($i * 0.12) . 's"><line x1="' . $x . '" x2="' . $x . '" y1="' . ($base - $hi * $scale) . '" y2="' . ($base - $lo * $scale) . '" stroke="' . $col . '" stroke-width="2"/>'
            . '<rect x="' . ($x - 6) . '" y="' . $y1 . '" width="12" height="' . $h . '" rx="2" fill="' . $col . '"/></g>';
    }
    return $o;
};
?>
<svg class="cx-art" viewBox="0 0 320 200" aria-hidden="true" focusable="false">
  <defs>
    <linearGradient id="ca-g<?= $n ?>" x1="0" x2="1"><stop offset="0" stop-color="<?= $V ?>"/><stop offset="1" stop-color="<?= $P ?>"/></linearGradient>
    <radialGradient id="ca-r<?= $n ?>"><stop offset="0" stop-color="<?= $V ?>" stop-opacity=".45"/><stop offset="1" stop-color="<?= $V ?>" stop-opacity="0"/></radialGradient>
  </defs>
  <circle cx="160" cy="100" r="120" fill="url(#ca-r<?= $n ?>)" class="ca-breathe"/>
  <?php switch ($n): case 1: // harsh reality: the balance bars shrink, the curve falls ?>
    <?php foreach ([150, 128, 104, 86, 60, 38] as $i => $h): ?><rect class="ca-grow" style="--d:<?= $i * .15 ?>s" x="<?= 52 + $i * 36 ?>" y="<?= 170 - $h ?>" width="22" height="<?= $h ?>" rx="5" fill="<?= $i < 2 ? $V : ($i < 4 ? $A : $R) ?>" opacity=".85"/><?php endforeach; ?>
    <path class="ca-draw" d="M40 40 C90 42 110 70 150 78 S220 120 290 160" fill="none" stroke="<?= $R ?>" stroke-width="4" stroke-linecap="round"/>
    <text class="ca-blink" x="236" y="44" fill="<?= $R ?>" font-size="22" font-weight="800">−72%</text>
  <?php break; case 2: // fundamentals: candles build one by one ?>
    <line x1="30" x2="290" y1="170" y2="170" stroke="<?= $M ?>" stroke-width="2"/>
    <?= $candles([[2, 5, 6, 1], [5, 4, 6, 3], [4, 7, 8, 3], [7, 9, 10, 6], [9, 8, 10, 7], [8, 11, 12, 7], [11, 13, 14, 10], [13, 12, 14, 11], [12, 15, 16, 11]], 50, 28, 175, 10) ?>
  <?php break; case 3: // risk: the 1% shield and 1:3 reward-to-risk bars ?>
    <path class="ca-pulse" d="M90 34 L140 50 V98 C140 130 116 150 90 160 C64 150 40 130 40 98 V50 Z" fill="url(#ca-g<?= $n ?>)" opacity=".92"/>
    <text x="90" y="110" text-anchor="middle" fill="#fff" font-size="28" font-weight="800">1%</text>
    <rect class="ca-grow" x="186" y="130" width="30" height="40" rx="6" fill="<?= $R ?>"/><text x="201" y="188" text-anchor="middle" fill="#b9b5d6" font-size="12">risk 1R</text>
    <rect class="ca-grow" style="--d:.35s" x="236" y="50" width="30" height="120" rx="6" fill="<?= $G ?>"/><text x="251" y="188" text-anchor="middle" fill="#b9b5d6" font-size="12">reward 3R</text>
  <?php break; case 4: // psychology: the heartbeat spikes after a loss ?>
    <path class="ca-draw" d="M20 110 H90 L104 110 L114 70 L126 150 L138 40 L150 130 L160 110 H200 L212 96 L224 110 H300" fill="none" stroke="url(#ca-g<?= $n ?>)" stroke-width="4" stroke-linejoin="round" stroke-linecap="round"/>
    <circle class="ca-pulse" cx="138" cy="40" r="9" fill="<?= $R ?>"/>
    <text x="150" y="30" fill="<?= $R ?>" font-size="13" font-weight="700">revenge?</text>
    <text x="214" y="82" fill="<?= $G ?>" font-size="13" font-weight="700">breathe</text>
  <?php break; case 5: // habits: the checklist ticks itself, the streak grows ?>
    <?php foreach (['Plan written', 'Stop placed', 'Journal done'] as $i => $t): $y = 46 + $i * 40; ?>
    <rect x="44" y="<?= $y - 16 ?>" width="150" height="30" rx="9" fill="#ffffff10" stroke="#ffffff22"/>
    <circle cx="62" cy="<?= $y - 1 ?>" r="9" fill="none" stroke="<?= $G ?>" stroke-width="2"/>
    <path class="ca-tick" style="--d:<?= $i * .5 ?>s" d="M57 <?= $y - 1 ?> l4 4 l7 -8" fill="none" stroke="<?= $G ?>" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
    <text x="80" y="<?= $y + 4 ?>" fill="#dedcec" font-size="13" font-weight="600"><?= $t ?></text>
    <?php endforeach; ?>
    <?php for ($i = 0; $i < 7; $i++): ?><circle class="ca-pop" style="--d:<?= .9 + $i * .15 ?>s" cx="<?= 222 + ($i % 3) * 22 ?>" cy="<?= 70 + intdiv($i, 3) * 26 ?>" r="8" fill="<?= $i < 6 ? $G : $A ?>"/><?php endfor; ?>
    <text x="244" y="164" text-anchor="middle" fill="#b9b5d6" font-size="12">7-day streak</text>
  <?php break; case 6: // sessions: the world turns, sessions light up ?>
    <circle cx="160" cy="100" r="62" fill="none" stroke="<?= $M ?>" stroke-width="2"/>
    <g class="ca-spin"><ellipse cx="160" cy="100" rx="62" ry="22" fill="none" stroke="<?= $V ?>" stroke-width="2" opacity=".7"/><ellipse cx="160" cy="100" rx="22" ry="62" fill="none" stroke="<?= $P ?>" stroke-width="2" opacity=".6"/></g>
    <?php foreach ([['Tokyo', 80, 50, $P], ['London', 230, 56, $V], ['New York', 236, 160, $G]] as $i => [$t, $x, $y, $c]): ?><g class="ca-blink" style="--d:<?= $i * .6 ?>s"><circle cx="<?= $x ?>" cy="<?= $y ?>" r="6" fill="<?= $c ?>"/><text x="<?= $x + ($x < 160 ? -10 : 10) ?>" y="<?= $y + 4 ?>" fill="#dedcec" font-size="12" font-weight="600" text-anchor="<?= $x < 160 ? 'end' : 'start' ?>"><?= $t ?></text></g><?php endforeach; ?>
  <?php break; case 7: // chart reading: higher highs, higher lows, breakout ?>
    <line x1="30" x2="300" y1="84" y2="84" stroke="<?= $A ?>" stroke-width="2" stroke-dasharray="6 5"/>
    <path class="ca-draw" d="M30 170 L80 120 L110 145 L160 96 L190 122 L236 86 L260 102 L300 40" fill="none" stroke="url(#ca-g<?= $n ?>)" stroke-width="4" stroke-linejoin="round" stroke-linecap="round"/>
    <?php foreach ([[80, 120, 'HH'], [110, 145, 'HL'], [160, 96, 'HH'], [190, 122, 'HL']] as [$x, $y, $t]): ?><text x="<?= $x ?>" y="<?= $t === 'HH' ? $y - 10 : $y + 20 ?>" text-anchor="middle" fill="#b9b5d6" font-size="11" font-weight="700"><?= $t ?></text><?php endforeach; ?>
    <text class="ca-blink" x="296" y="30" text-anchor="end" fill="<?= $G ?>" font-size="13" font-weight="800">breakout ↑</text>
  <?php break; case 8: // indicators: price, a moving average and a breathing RSI ?>
    <path class="ca-draw" d="M20 110 C50 70 70 130 100 96 S150 50 180 80 S230 120 300 60" fill="none" stroke="#dedcec" stroke-width="2.5"/>
    <path class="ca-draw" style="--d:.4s" d="M20 115 C70 100 120 92 170 86 S250 80 300 72" fill="none" stroke="<?= $V ?>" stroke-width="3.5"/>
    <rect x="20" y="146" width="280" height="40" rx="8" fill="#ffffff0d"/><line x1="20" x2="300" y1="154" y2="154" stroke="<?= $R ?>" stroke-dasharray="4 4" opacity=".6"/><line x1="20" x2="300" y1="178" y2="178" stroke="<?= $G ?>" stroke-dasharray="4 4" opacity=".6"/>
    <path class="ca-wave" d="M20 168 Q50 150 80 166 T140 166 T200 160 T260 172 T320 160" fill="none" stroke="<?= $P ?>" stroke-width="2.5"/>
    <text x="300" y="142" text-anchor="end" fill="#b9b5d6" font-size="11">RSI</text>
  <?php break; case 9: // news: the calendar card blinks, price spikes ?>
    <rect x="34" y="40" width="96" height="104" rx="14" fill="#ffffff10" stroke="#ffffff26"/><rect x="34" y="40" width="96" height="28" rx="14" fill="url(#ca-g<?= $n ?>)"/>
    <text x="82" y="59" text-anchor="middle" fill="#fff" font-size="12" font-weight="800">CPI · 13:30</text>
    <text class="ca-blink" x="82" y="112" text-anchor="middle" fill="<?= $A ?>" font-size="30" font-weight="800">!</text>
    <path class="ca-draw" d="M150 120 H200 L208 116 L214 40 L222 168 L230 70 L238 128 L250 112 H300" fill="none" stroke="<?= $R ?>" stroke-width="3" stroke-linejoin="round"/>
    <text x="226" y="186" text-anchor="middle" fill="#b9b5d6" font-size="11">spread widens · slippage</text>
  <?php break; case 10: // backtest: an equity curve draws itself over 100 trades ?>
    <?php for ($i = 0; $i < 20; $i++): $h = 10 + (($i * 37) % 30); ?><rect class="ca-grow" style="--d:<?= $i * .05 ?>s" x="<?= 30 + $i * 13 ?>" y="<?= 182 - $h ?>" width="8" height="<?= $h ?>" rx="2" fill="<?= $i % 3 === 1 ? $R : $G ?>" opacity=".55"/><?php endfor; ?>
    <path class="ca-draw" d="M30 140 L60 132 L80 138 L110 116 L130 122 L160 98 L180 104 L210 80 L230 88 L260 62 L290 48" fill="none" stroke="url(#ca-g<?= $n ?>)" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
    <circle class="ca-pulse" cx="290" cy="48" r="7" fill="<?= $P ?>"/><text x="282" y="32" text-anchor="end" fill="#dedcec" font-size="12" font-weight="700">100 trades</text>
  <?php break; case 11: // trade management: the stop slides up to break-even, then trails ?>
    <path d="M30 150 L80 128 L120 136 L160 100 L200 108 L250 70 L296 54" fill="none" stroke="#dedcec" stroke-width="2.5"/>
    <line x1="30" x2="300" y1="128" y2="128" stroke="<?= $V ?>" stroke-width="2"/><text x="34" y="122" fill="<?= $V ?>" font-size="11" font-weight="700">entry</text>
    <g class="ca-trail"><line x1="30" x2="300" y1="160" y2="160" stroke="<?= $R ?>" stroke-width="2.5" stroke-dasharray="7 5"/><text x="296" y="154" text-anchor="end" fill="<?= $R ?>" font-size="11" font-weight="700">stop → break-even → trail</text></g>
    <line x1="30" x2="300" y1="40" y2="40" stroke="<?= $G ?>" stroke-width="2" stroke-dasharray="4 4"/><text x="34" y="34" fill="<?= $G ?>" font-size="11" font-weight="700">target 3R</text>
  <?php break; case 12: // brokers & safety: lock, shield, red flags ?>
    <path class="ca-pulse" d="M110 40 L160 56 V100 C160 132 136 150 110 160 C84 150 60 132 60 100 V56 Z" fill="url(#ca-g<?= $n ?>)" opacity=".9"/>
    <rect x="94" y="94" width="32" height="26" rx="5" fill="#fff"/><path d="M100 94 V86 a10 10 0 0 1 20 0 V94" fill="none" stroke="#fff" stroke-width="4"/>
    <?php foreach (['“Guaranteed 10% a week”', '“DM me for signals”', '“Send USDT to unlock”'] as $i => $t): $y = 62 + $i * 40; ?><g class="ca-shake" style="--d:<?= $i * .4 ?>s"><path d="M184 <?= $y ?> l9 -16 l9 16 z" fill="<?= $R ?>"/><text x="193" y="<?= $y - 3 ?>" text-anchor="middle" fill="#fff" font-size="11" font-weight="900">!</text><text x="208" y="<?= $y - 2 ?>" fill="#fca5a5" font-size="11" font-weight="600"><?= $t ?></text></g><?php endforeach; ?>
  <?php break; default: // trading as a business: monthly P&L bars and a review ritual ?>
    <?php foreach ([40, -18, 56, 30, -10, 72] as $i => $v): $h = abs($v); ?><rect class="ca-grow" style="--d:<?= $i * .14 ?>s" x="<?= 46 + $i * 38 ?>" y="<?= $v > 0 ? 120 - $h : 120 ?>" width="24" height="<?= $h ?>" rx="5" fill="<?= $v > 0 ? $G : $R ?>" opacity=".85"/><?php endforeach; ?>
    <line x1="34" x2="290" y1="120" y2="120" stroke="<?= $M ?>" stroke-width="2"/>
    <?php foreach (['J', 'F', 'M', 'A', 'M', 'J'] as $i => $t): ?><text x="<?= 58 + $i * 38 ?>" y="186" text-anchor="middle" fill="#b9b5d6" font-size="11"><?= $t ?></text><?php endforeach; ?>
    <text class="ca-blink" x="160" y="30" text-anchor="middle" fill="#dedcec" font-size="13" font-weight="700">Sunday review ✓</text>
  <?php endswitch; ?>
</svg>
