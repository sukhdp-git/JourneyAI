<?php
declare(strict_types=1);

namespace App\Trading;

/**
 * The "Mind" features, all computed from the member's own journal on the active account:
 *  - roast():      next-day roast (or praise) about yesterday's broken rules, with a supportive mode after a rough day
 *  - deathClock(): when the account would reach zero at the current pace of the last 60 trades
 *  - ghost():      you vs a copy of you that followed your rules (Domain::LEAK_TREATMENT — never invents profits)
 *  - boss():       this month's costliest habit as a boss with HP: disciplined days hurt it, repeats heal it
 *  - art():        weekly counts that drive the generative psychology wallpaper
 */
final class Mind
{
    public const LEVELS = ['off' => 'Off', 'mild' => 'Mild', 'savage' => 'Savage 🔥'];

    /** Roast lines per broken rule. {n} = count, {cost} = money, {when} = "yesterday" / "on Fri". */
    private const ROASTS = [
        'REVENGE_TRADE' => [
            'savage' => ['{n} revenge trade{s} {when}. {cost}. You don\'t want funding, you want attention. Fix it today or I\'m locking your terminal. 🔒',
                'You tried to “win it back” {n} time{s} {when}. The market won it back instead: {cost}. 💀',
                '{n} revenge trade{s}, {cost}. The market isn\'t your ex. Stop texting it back.'],
            'mild' => ['{n} revenge trade{s} {when} cost {cost}. Today\'s deal: 2 losses = a 20-minute walk.', '{when}, emotions took {n} trade{s} ({cost}). Clean slate today.'],
        ],
        'FOMO_ENTRY' => [
            'savage' => ['FOMO ×{n} {when}. {cost}. The candle had already left. So had your discipline. 🚀💀', 'You chased the green candle ×{n}. It didn\'t chase you back. {cost}.', 'FOMO ×{n}: {cost}. Missing a move costs $0. Chasing it cost you that.'],
            'mild' => ['FOMO ×{n} {when} ({cost}). Missed moves are free; chased ones aren\'t.', '{n} FOMO entr{ies} {when}. Today, wait for your setup.'],
        ],
        'MOVED_STOP' => [
            'savage' => ['You moved your stop ×{n} {when}. {cost}. You weren\'t scared of losing money. You were scared of being wrong.', 'Stop moved ×{n}. A stop you move is just a suggestion. {cost}. 🛑', '×{n} stop moves, {cost}. The stop was fine. The finger wasn\'t.'],
            'mild' => ['Stop moved ×{n} {when} ({cost}). Today it only moves towards profit.', '{n} moved stop{s} {when}. Set it, then sit on your hands.'],
        ],
        'NO_STOP' => [
            'savage' => ['No stop ×{n} {when}. Bold. Also {cost}. Next time bring a seatbelt.', 'Trading without a stop ×{n} isn\'t trading. It\'s hoping with leverage. {cost}.'],
            'mild' => ['{n} trade{s} without a stop {when} ({cost}). Every trade gets one today.'],
        ],
        'OVERSIZED' => [
            'savage' => ['Oversized ×{n} {when}. {cost}. Your lot size had main-character energy. Your account didn\'t.', '×{n} oversized trade{s}: that\'s how one bad idea becomes a bad month. {cost}.'],
            'mild' => ['{n} oversized trade{s} {when} ({cost}). Size from the stop today.'],
        ],
        'EARLY_EXIT' => [
            'savage' => ['Closed early ×{n} {when}. Paper hands of the year 🧻. Your plan wanted more; you wanted out.', 'Early exit ×{n}. You cut your winners and kept your excuses.'],
            'mild' => ['{n} early exit{s} {when}. Let today\'s winners reach the plan.'],
        ],
        'LATE_ENTRY' => [
            'savage' => ['Late entry ×{n} {when}. You arrived after the party and still paid for the drinks. {cost}.'],
            'mild' => ['{n} late entr{ies} {when} ({cost}). If you missed it, let it go.'],
        ],
        'CHASING' => [
            'savage' => ['Chased price ×{n} {when}. It didn\'t want you back. {cost}.', '×{n} chases, {cost}. Price is not a bus. Another one comes.'],
            'mild' => ['Chased ×{n} {when} ({cost}). Wait for price to come to your level.'],
        ],
        'IGNORED_PLAN' => [
            'savage' => ['Rules broken ×{n} {when}. You wrote a plan and then ghosted it. {cost}. 👻', '×{n} trade{s} outside your plan. Your plan called. You declined. {cost}.', 'You broke your own rules ×{n}. Imagine losing an argument with a checklist. {cost}.'],
            'mild' => ['{n} trade{s} outside your plan {when} ({cost}). Read the plan before the first trade today.'],
        ],
        'OVERTRADING' => [
            'savage' => ['Overtrading ×{n} {when}. {cost}. Your broker says thanks for the commissions. 🙏', '{n} trade{s} you didn\'t need. Boredom is expensive: {cost}.'],
            'mild' => ['{n} extra trade{s} {when} ({cost}). Set a max trades number for today.'],
        ],
        'NEWS_GAMBLE' => [
            'savage' => ['Gambled on the news ×{n} {when}. {cost}. The economic calendar exists for a reason. 📅'],
            'mild' => ['{n} news gamble{s} {when} ({cost}). Check the calendar before the session.'],
        ],
        'IMPULSIVE_ENTRY' => [
            'savage' => ['Impulse entry ×{n} {when}. Your finger traded faster than your brain. {cost}.', '×{n} trades on impulse. The setup was in your head, not on the chart. {cost}.'],
            'mild' => ['{n} impulsive entr{ies} {when} ({cost}). One sentence “why this trade?” before each entry today.'],
        ],
        'TILT' => [
            'savage' => ['Traded within 30 minutes of 2 losses ×{n} {when}. That\'s not a strategy, that\'s a mood. {cost}.'],
            'mild' => ['{n} trade{s} right after 2 losses {when} ({cost}). After 2 losses today, walk away 20 minutes.'],
        ],
        'CLEAN' => [
            'savage' => ['Zero rules broken {when}. Who are you and what did you do with the old you? 👀', 'Clean day {when}. No revenge, no FOMO. Suspicious… but I\'ll allow it. ✅', 'Rules followed 100% {when}. Keep this up and I\'ll have nothing to roast. 😤'],
            'mild' => ['Clean day {when}: every rule followed. Do it again. ✅', 'No rules broken {when}. That\'s the job. 👏'],
        ],
    ];

    /** Bosses for the costliest habit: code => [name, emoji, taunts]. */
    public const BOSSES = [
        'REVENGE_TRADE' => ['The Revenge Demon', '😈', ['Two losses and you\'ll come back to me. You always do.', 'Feed me another “win it back” trade.']],
        'FOMO_ENTRY' => ['The FOMO Phantom', '👻', ['That green candle? It\'s leaving without you…', 'Buy now. Think later. That\'s our deal.']],
        'MOVED_STOP' => ['The Stop-Loss Shifter', '🦎', ['Just a few more pips. It will come back.', 'A stop is only a suggestion, right?']],
        'NO_STOP' => ['The Naked Gambler', '🎲', ['Stops are for people who plan to be wrong.', 'Let it ride.']],
        'OVERSIZED' => ['The Size Monster', '🦍', ['Go big. What could happen?', 'One big win fixes everything.']],
        'EARLY_EXIT' => ['The Paper-Hands Goblin', '🧻', ['Take it now before it\'s gone!', 'Profit is profit… even 0.3R.']],
        'LATE_ENTRY' => ['The Late Ghoul', '⏰', ['It\'s still going, you\'re not too late…', 'Jump in, the move isn\'t over.']],
        'CHASING' => ['The Chaser', '🏃', ['Run! It\'s getting away!', 'Market order. Now.']],
        'IGNORED_PLAN' => ['The Plan Shredder', '📄', ['Rules are for other traders.', 'Your plan won\'t mind. Probably.']],
        'OVERTRADING' => ['The Overtrading Hydra', '🐉', ['Cut one trade off, two more grow back.', 'Bored? Click something.']],
        'NEWS_GAMBLE' => ['The News Kraken', '🦑', ['NFP in 2 minutes. Feeling lucky?', 'Spreads are just numbers.']],
        'IMPULSIVE_ENTRY' => ['The Impulse Imp', '⚡', ['Don\'t think. Click.', 'Setups are overrated.']],
    ];

    public function __construct(private array $trades, private float $equity, private string $tz, private int $uid, private string $currency)
    {
    }

    // ------------------------------------------------------------------ helpers

    private function m(float $v): string
    {
        return money($v, $this->currency, true);
    }

    private function day(array $t): string
    {
        return Analytics::local($t['executed_at'], $this->tz)->format('Y-m-d');
    }

    /** Broken-rule code of a trade, or null when it followed the plan. */
    public static function violation(array $t): ?string
    {
        $mk = $t['mistake_tag'] ?: 'NONE';
        if ($mk !== 'NONE') {
            return $mk;
        }
        if ($t['emotion'] === 'REVENGE') {
            return 'REVENGE_TRADE';
        }
        if ($t['emotion'] === 'FOMO') {
            return 'FOMO_ENTRY';
        }
        return (int) $t['rules_followed'] ? null : 'IGNORED_PLAN';
    }

    private function last(int $n): array
    {
        return array_slice(Analytics::closed($this->trades), -$n);
    }

    // ------------------------------------------------------------------ roast

    public function roast(string $level): ?array
    {
        if ($level === 'off') {
            return null;
        }
        $closed = Analytics::closed($this->trades);
        $today = (new \DateTimeImmutable('now', new \DateTimeZone($this->tz)))->format('Y-m-d');
        $byDay = [];
        foreach ($closed as $t) {
            $byDay[$this->day($t)][] = $t;
        }
        $days = array_values(array_filter(array_keys($byDay), fn ($d) => $d < $today && $d >= (new \DateTimeImmutable($today))->modify('-4 days')->format('Y-m-d')));
        if (!$days) {
            return null;
        }
        $d = end($days);
        $list = $byDay[$d];
        $when = $d === (new \DateTimeImmutable($today))->modify('-1 day')->format('Y-m-d') ? 'yesterday' : 'on ' . (new \DateTimeImmutable($d))->format('D');
        $dayPnl = array_sum(array_map(fn ($t) => (float) $t['pnl'], $list));
        // a rough day gets support instead of a roast
        if ($dayPnl < 0 && $this->equity > 0 && -$dayPnl >= 0.04 * ($this->equity - $dayPnl)) {
            return ['kind' => 'support', 'date' => $d, 'text' => 'Rough day ' . $when . ' (' . $this->m($dayPnl) . '). No roast today. Take a break, review one trade, and trade small. You\'ve got this. 🤝', 'title' => 'Not today'];
        }
        $groups = [];
        foreach ($list as $t) {
            if ($v = self::violation($t)) {
                $groups[$v] ??= [0, 0.0];
                $groups[$v][0]++;
                $groups[$v][1] += (float) $t['pnl'];
            }
        }
        // tilt: trades entered within 30 minutes after two losses in a row
        $streak = 0;
        $lastLoss = null;
        foreach ($list as $t) {
            $ts = strtotime($t['executed_at'] . ' UTC');
            if ($streak >= 2 && $lastLoss !== null && $ts - $lastLoss <= 1800 && !self::violation($t)) {
                $groups['TILT'] ??= [0, 0.0];
                $groups['TILT'][0]++;
                $groups['TILT'][1] += (float) $t['pnl'];
            }
            if ((float) $t['pnl'] < 0) {
                $streak++;
                $lastLoss = strtotime(($t['closed_at'] ?? null ?: $t['executed_at']) . ' UTC');
            } else {
                $streak = 0;
            }
        }
        if (!$groups) {
            $code = 'CLEAN';
            $n = count($list);
            $cost = $this->m($dayPnl);
        } else {
            uasort($groups, fn ($a, $b) => [$b[0], $a[1]] <=> [$a[0], $b[1]]); // most often, then most costly
            $code = array_key_first($groups);
            [$n, $sum] = $groups[$code];
            $cost = $sum < 0 ? $this->m($sum) : $this->m($sum) . ' (luck, not skill)';
        }
        $bank = self::ROASTS[$code][$level] ?? self::ROASTS[$code]['savage'];
        $line = $bank[abs(crc32($this->uid . $d . $code)) % count($bank)];
        $text = strtr($line, ['{n}' => (string) $n, '{s}' => $n === 1 ? '' : 's', '{ies}' => $n === 1 ? 'y' : 'ies', '{cost}' => $cost, '{when}' => $when]);
        $text = ucfirst($text);
        return ['kind' => $code === 'CLEAN' ? 'praise' : 'roast', 'code' => $code, 'date' => $d, 'text' => $text,
            'title' => $code === 'CLEAN' ? 'Clean day' : Domain::label($code === 'TILT' ? 'REVENGE_TRADE' : $code)];
    }

    // ------------------------------------------------------------------ death clock

    public function deathClock(): array
    {
        $tr = $this->last(60);
        if (count($tr) < 10) {
            return ['state' => 'need', 'trades' => count($tr)];
        }
        $days = [];
        $broken = 0;
        foreach ($tr as $t) {
            $days[$this->day($t)] = ($days[$this->day($t)] ?? 0) + (float) $t['pnl'];
            $broken += self::violation($t) ? 1 : 0;
        }
        $avg = array_sum($days) / count($days);
        $lastDay = array_key_last($days);
        $lastBroken = count(array_filter($tr, fn ($t) => $this->day($t) === $lastDay && self::violation($t)));
        $clean = array_filter($tr, fn ($t) => !self::violation($t));
        $cleanAvg = $clean ? array_sum(array_map(fn ($t) => (float) $t['pnl'], $clean)) / count($days) : 0.0;
        $base = ['trades' => count($tr), 'days' => count($days), 'avg' => round($avg, 2), 'avg_fmt' => $this->m($avg), 'broken_pct' => (int) round($broken / count($tr) * 100),
            'speed' => $lastBroken ? 'fast' : 'slow', 'clean_avg_fmt' => $this->m($cleanAvg), 'clean_positive' => $cleanAvg > 0];
        if ($avg >= 0 || $this->equity <= 0) {
            return $base + ['state' => $this->equity <= 0 ? 'dead' : 'alive'];
        }
        $tradingDays = $this->equity / -$avg;
        $seconds = (int) min($tradingDays * 7 / 5 * 86400, 3650 * 86400);
        return $base + ['state' => 'dying', 'death_at' => time() + $seconds, 'trading_days' => round($tradingDays, 1), 'capped' => $tradingDays * 7 / 5 >= 3650];
    }

    // ------------------------------------------------------------------ ghost

    public function ghost(): array
    {
        $tr = $this->last(60);
        $you = $ghost = 0.0;
        $pts = [[0.0, 0.0]];
        $skipped = $capped = 0;
        foreach ($tr as $t) {
            $p = (float) $t['pnl'];
            $h = $p;
            if ($v = self::violation($t)) {
                $treat = Domain::LEAK_TREATMENT[$v] ?? 'ACTUAL';
                if ($treat === 'SKIP') {
                    $h = 0.0;
                    $skipped++;
                } elseif ($treat === 'CAP_LOSS' && $p < 0 && $t['risk_amount']) {
                    $h = max($p, -abs((float) $t['risk_amount']));
                    $capped += $h !== $p ? 1 : 0;
                }
            }
            $you += $p;
            $ghost += $h;
            $pts[] = [round($you, 2), round($ghost, 2)];
        }
        return ['trades' => count($tr), 'you' => round($you, 2), 'ghost' => round($ghost, 2), 'gap' => round($ghost - $you, 2), 'points' => $pts,
            'you_fmt' => $this->m($you), 'ghost_fmt' => $this->m($ghost), 'gap_fmt' => money(abs($ghost - $you), $this->currency), 'skipped' => $skipped, 'capped' => $capped];
    }

    // ------------------------------------------------------------------ boss fight

    public function boss(): ?array
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone($this->tz));
        $month = $now->format('Y-m');
        $tr = array_values(array_filter(Analytics::closed($this->trades), fn ($t) => substr($this->day($t), 0, 7) === $month));
        if (!$tr) {
            return null;
        }
        $per = [];
        foreach ($tr as $t) {
            if ($v = self::violation($t)) {
                $per[$v] ??= ['n' => 0, 'actual' => 0.0, 'flawless' => 0.0];
                $p = (float) $t['pnl'];
                $treat = Domain::LEAK_TREATMENT[$v] ?? 'ACTUAL';
                $per[$v]['n']++;
                $per[$v]['actual'] += $p;
                $per[$v]['flawless'] += $treat === 'SKIP' ? 0.0 : ($treat === 'CAP_LOSS' && $p < 0 && $t['risk_amount'] ? max($p, -abs((float) $t['risk_amount'])) : $p);
            }
        }
        if (!$per) {
            return ['state' => 'none', 'month' => $now->format('F')];
        }
        uasort($per, fn ($a, $b) => [$b['flawless'] - $b['actual'], $b['n']] <=> [$a['flawless'] - $a['actual'], $a['n']]);
        $code = array_key_first($per);
        $b = self::BOSSES[$code] ?? ['The Rule Breaker', '👹', ['Rules are made to be broken.']];
        $hp = 100;
        $log = [];
        $byDay = [];
        foreach ($tr as $t) {
            $byDay[$this->day($t)][] = $t;
        }
        foreach ($byDay as $d => $list) {
            $clean = !array_filter($list, fn ($t) => self::violation($t));
            if ($clean) {
                $hp = max(0, $hp - 15);
                $log[] = [$d, -15];
            }
            foreach ($list as $t) {
                if (self::violation($t) === $code) {
                    $hp = min(100, $hp + 10);
                    $log[] = [$d, 10];
                }
            }
        }
        $stolen = $per[$code]['flawless'] - $per[$code]['actual'];
        return ['state' => $hp <= 0 ? 'defeated' : 'alive', 'code' => $code, 'name' => $b[0], 'emoji' => $b[1], 'hp' => $hp, 'month' => $now->format('F'),
            'taunt' => $b[2][abs(crc32($this->uid . $now->format('Y-m-d'))) % count($b[2])], 'hits' => $per[$code]['n'],
            'stolen' => round(max(0, $stolen), 2), 'stolen_fmt' => money(max(0, $stolen), $this->currency), 'log' => array_slice($log, -8)];
    }

    // ------------------------------------------------------------------ psych art

    /** Counts for one ISO week ($offset 0 = this week, -1 = last week …). */
    public function art(int $offset = 0): array
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone($this->tz));
        $start = $now->modify('monday this week')->modify(($offset * 7) . ' days')->setTime(0, 0);
        $end = $start->modify('+7 days');
        $c = ['disciplined' => 0, 'revenge' => 0, 'fomo' => 0, 'stop' => 0, 'other' => 0, 'wins' => 0, 'trades' => 0];
        $pnl = 0.0;
        foreach (Analytics::closed($this->trades) as $t) {
            $l = Analytics::local($t['executed_at'], $this->tz);
            if ($l < $start || $l >= $end) {
                continue;
            }
            $c['trades']++;
            $pnl += (float) $t['pnl'];
            $c['wins'] += (float) $t['pnl'] > 0 ? 1 : 0;
            $v = self::violation($t);
            match (true) {
                $v === null && !in_array($t['emotion'], Domain::NEGATIVE_EMOTIONS, true) => $c['disciplined']++,
                $v === 'REVENGE_TRADE' => $c['revenge']++,
                $v === 'FOMO_ENTRY' || $v === 'CHASING' => $c['fomo']++,
                $v === 'MOVED_STOP' || $v === 'NO_STOP' => $c['stop']++,
                default => $c['other']++,
            };
        }
        $score = $c['trades'] ? (int) round($c['disciplined'] / $c['trades'] * 100) : 0;
        $type = match (true) {
            !$c['trades'] => 'Blank canvas',
            $score >= 80 => 'The Monk',
            $c['revenge'] >= max($c['fomo'], $c['stop'], 1) => 'The Avenger',
            $c['fomo'] >= max($c['stop'], 1) => 'The Chaser',
            $c['stop'] >= 1 => 'The Negotiator',
            default => 'The Wildcard',
        };
        return $c + ['week' => $start->format('M j') . ' – ' . $start->modify('+6 days')->format('M j, Y'), 'offset' => $offset, 'score' => $score, 'type' => $type,
            'seed' => abs(crc32($this->uid . '|' . $start->format('o-W'))), 'pnl_fmt' => $this->m($pnl)];
    }
}
