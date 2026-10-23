<?php
declare(strict_types=1);

namespace App\Trading;

use App\Models\Learn;

/**
 * Smart Coach: a free, instant, rule-based coach (no AI provider, no credits).
 *
 * A question is matched against two libraries:
 *   1. performance intents answered from the member's own closed trades and journal (win rate, P&L, best session,
 *      worst habit, streaks, drawdown, …) for the period named in the question (today, this week, last month…);
 *   2. a knowledge base: CoachKnowledge (trading, risk, psychology, journaling), the University strategy playbook
 *      and the Trading Foundations course modules.
 * Answers use the same short format as the AI Coach (bullets, **key number**, "Next:" line).
 */
final class SmartCoach
{
    private const STOP = ['the', 'a', 'an', 'is', 'are', 'was', 'were', 'what', 'which', 'how', 'do', 'does', 'did', 'i', 'my', 'me', 'to', 'of', 'in', 'on', 'for', 'and', 'or', 'it', 'its', 'can', 'should', 'would', 'be', 'with', 'at', 'from', 'about', 'this', 'that', 'you', 'your', 'am', 'have', 'has', 'much', 'many', 'tell', 'show', 'please', 'give'];

    /** Performance intents: id => trigger phrases. Longer phrases score higher. */
    private const INTENTS = [
        'summary' => ['how am i doing', 'my performance', 'overall', 'summary', 'stats', 'statistics', 'how is my trading', 'report', 'review my', 'how did i do', 'my results'],
        'winrate' => ['my win rate', 'win rate', 'winrate', 'win percentage', 'how often do i win', 'hit rate', 'accuracy'],
        'pnl' => ['how much did i make', 'how much did i lose', 'my profit', 'my p&l', 'pnl', 'p&l', 'net profit', 'am i profitable', 'money did i make', 'made money', 'lost money', 'profit', 'loss'],
        'instrument' => ['best instrument', 'worst instrument', 'which pair', 'which instrument', 'best pair', 'worst pair', 'which symbol', 'gold or', 'should i trade gold', 'best market', 'which asset'],
        'session' => ['best session', 'worst session', 'which session', 'london or new york', 'london vs new york', 'compare london', 'asian session', 'session should i avoid', 'session should i trade'],
        'hour' => ['best hour', 'worst hour', 'what time', 'which hour', 'danger hour', 'best time to trade', 'time of day', 'when do i lose', 'when do i win'],
        'weekday' => ['best day', 'worst day', 'which day', 'monday', 'friday', 'day of the week', 'weekday'],
        'strategy' => ['best strategy', 'strongest setup', 'best setup', 'which strategy', 'which setup', 'highest expectancy', 'a+ setup', 'strategy works', 'setup works', 'worst strategy', 'worst setup'],
        'expectancy' => ['my expectancy', 'expectancy', 'profit factor', 'average r', 'avg r', 'r multiple', 'payoff', 'average win', 'average loss', 'edge'],
        'drawdown' => ['drawdown', 'max drawdown', 'biggest drop', 'how far down', 'peak to'],
        'streak' => ['losing streak', 'winning streak', 'streak', 'in a row', 'consecutive'],
        'extremes' => ['biggest win', 'biggest loss', 'best trade', 'worst trade', 'largest loss', 'largest win'],
        'emotion' => ['emotion', 'emotional', 'feelings', 'how i feel', 'which emotion', 'mood', 'fear', 'greed', 'anxious'],
        'mistake' => ['mistake', 'mistakes', 'costs me the most', 'worst habit', 'bad habit', 'costliest', 'what am i doing wrong', 'doing wrong'],
        'discipline' => ['discipline', 'disciplined', 'rules followed', 'follow my rules', 'did i follow', 'compliance', 'how disciplined'],
        'revenge' => ['revenge', 'tilt', 'after a loss', 'after losses', 'win it back'],
        'side' => ['long or short', 'longs or shorts', 'buy or sell', 'buys vs sells', 'long vs short', 'better at buying', 'better at selling'],
        'activity' => ['trades per day', 'how many trades', 'overtrading', 'overtrade', 'trade too much', 'number of trades'],
        'window' => ['best window', 'anti window', 'anti-window', 'trading window'],
        'losing' => ['why am i losing', 'why do i lose', 'losing money', 'what is wrong', 'leaks', 'my leaks', 'where am i losing', 'improve', 'how can i improve', 'what should i fix', 'what to fix'],
        'strengths' => ['my strengths', 'what am i good at', 'what works', 'what is working', 'my edge'],
        'risk' => ['my risk', 'risk per trade am i', 'blow up', 'blow my account', 'ruin', 'chance of blowing', 'am i risking'],
        'lessons' => ['what did i learn', 'my lessons', 'key lessons', 'lessons learned', 'what have i learned'],
        'compare' => ['this month vs last month', 'compared to last month', 'better than last month', 'improving', 'am i getting better', 'progress'],
        'recent' => ['last trade', 'recent trades', 'my last', 'latest trade'],
    ];

    private array $all = [];
    private string $cur;

    public function __construct(private array $m, private array $acc, private string $tz, private array $journals, private float $equity, private ?\Closure $loadTrades = null)
    {
        $this->cur = $acc['currency'] ?? 'USD';
    }

    /** @return array{text: string, links: array<int, array{0:string,1:string}>, source: string, suggest: string[]} */
    public function answer(string $question): array
    {
        $q = self::norm($question);
        if ($q === '') {
            return $this->reply(['Ask me about your performance, a trading concept or a University strategy.'], [], 'help');
        }
        [$intent, $iScore] = self::bestIntent($q);
        [$kb, $kScore] = $this->bestKnowledge($q);
        $personal = (bool) preg_match('/\b(my|me|mine|am i|do i|did i|have i|was i|i am)\b/', $q);
        $learning = (bool) preg_match('/^(how (do|can|to|should|does)|what (is|are|does)(?! my)|what\'?s(?! my)|why (should|do|does|is)|should i|explain|define|tell me about|when should|is it)\b/', $q) || (bool) preg_match('/\bneed\b|\bstop\b.*\btrading\b|\bhow to\b/', $q);
        $data = fn () => $intent && $iScore >= 2;
        $know = fn () => $kb && $kScore >= 2.4;
        if ($learning && $know() && !preg_match('/\b(my|mine)\b/', $q)) {
            return $this->knowledge($kb);
        }
        if ($personal && $data()) {
            return $this->performance($intent, $q);
        }
        if ($know() && (!$data() || $kScore >= $iScore)) {
            return $this->knowledge($kb);
        }
        if ($data()) {
            return $this->performance($intent, $q);
        }
        return $this->reply([
            'I could not match that question yet. Try one of these:',
            '• "What is my win rate this month?"  • "Which session should I avoid?"',
            '• "What mistake costs me the most?"  • "How do I stop revenge trading?"',
            '• "What is the Opening Range Breakout?"  • "What is VWAP?"',
        ], [], 'fallback');
    }

    private function knowledge(array $kb): array
    {
        return $this->reply($kb['a'], isset($kb['link']) ? [[$kb['link'], $kb['linkLabel'] ?? 'Open']] : [], 'knowledge');
    }

    // ------------------------------------------------------------------ matching
    public static function norm(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = str_replace(['’', '‘', '“', '”', '?', '!', ',', '.'], ["'", "'", '"', '"', ' ', ' ', ' ', ' '], $s);
        return trim(preg_replace('/\s+/', ' ', $s) ?? $s);
    }

    private static function tokens(string $s): array
    {
        $w = preg_split('/[^a-z0-9+&%:]+/', self::norm($s)) ?: [];
        return array_values(array_unique(array_filter($w, fn ($x) => $x !== '' && !in_array($x, self::STOP, true) && strlen($x) > 1)));
    }

    /** Phrase hits (substring, weighted by length) + shared content words. */
    private static function score(string $q, array $phrases, string $extra = ''): float
    {
        $s = 0.0;
        $padded = ' ' . $q . ' ';
        foreach ($phrases as $p) {
            $p = self::norm($p);
            if ($p !== '' && str_contains($padded, (str_contains($p, ' ') ? $p : ' ' . $p . ' '))) {
                $s = max($s, 2 + substr_count($p, ' ') * 1.5 + min(strlen($p), 20) / 10);
            }
        }
        if ($extra !== '') {
            $qt = self::tokens($q);
            $et = self::tokens($extra);
            $s += count(array_intersect($qt, $et)) * 0.8;
        }
        return $s;
    }

    private static function bestIntent(string $q): array
    {
        $best = [null, 0.0];
        foreach (self::INTENTS as $id => $ph) {
            $sc = self::score($q, $ph);
            if ($sc > $best[1]) {
                $best = [$id, $sc];
            }
        }
        return $best;
    }

    /** Knowledge base + University strategies + course modules, scored together. */
    private function bestKnowledge(string $q): array
    {
        $best = [null, 0.0];
        foreach (self::library() as $e) {
            $sc = self::score($q, $e['k'], $e['q']);
            if ($sc > 0 && $e['t'] === 'university' && preg_match('/\b(strategy|setup|playbook|lesson)\b/', $q)) {
                $sc += 2; // "... strategy" questions prefer the University lesson over the general concept
            }
            if ($sc > $best[1]) {
                $best = [$e, $sc];
            }
        }
        return $best;
    }

    /** Common trader names for the University strategies, so "ORB" or "judas swing" finds the lesson. */
    private static function aliases(string $title): array
    {
        $t = self::norm($title);
        $map = ['opening range' => ['orb', 'opening range breakout', 'opening range strategy'], 'judas' => ['judas swing', 'ny judas'],
            'order block' => ['order block', 'fvg', 'fair value gap', 'ob fvg'], 'breaker' => ['breaker block', 'breaker'],
            'asian' => ['asian range', 'asian high low', 'liquidity raid', 'london raid', 'london session strategy'],
            'footprint' => ['footprint', 'delta absorption', 'absorption'], 'supply' => ['supply and demand', 'supply demand', 'multi timeframe'],
            'poc' => ['poc rejection', 'volume profile strategy', 'point of control strategy'], 'mean reversion' => ['vwap mean reversion', 'vwap strategy'],
            'extreme' => ['vwap extreme', 'standard deviation', 'vwap bands']];
        $out = [];
        foreach ($map as $needle => $keys) {
            if (str_contains($t, $needle)) {
                $out = array_merge($out, $keys);
            }
        }
        return $out;
    }

    /** All knowledge entries (static KB + playbook strategies + course modules). */
    public static function library(): array
    {
        static $lib = null;
        if ($lib !== null) {
            return $lib;
        }
        $lib = [];
        foreach (CoachKnowledge::entries() as $e) {
            $e['linkLabel'] = 'Open';
            $lib[] = $e;
        }
        foreach (Learn::all() as $s) {
            $title = (string) $s['title'];
            $name = self::norm($s['short_title'] ?: $title);
            $words = array_values(array_filter(self::tokens($title), fn ($w) => !in_array($w, ['strategy', 'trading', 'setup', 'the'], true)));
            $a = [];
            if ($s['summary']) $a[] = '**' . $title . '**: ' . trim((string) $s['summary']);
            $rules = Learn::lines($s['setup_rules']);
            if ($rules) $a[] = 'Setup: ' . implode(' · ', array_slice($rules, 0, 3));
            if ($s['entry_trigger']) $a[] = 'Entry: ' . trim((string) $s['entry_trigger']);
            if ($s['stop_loss']) $a[] = 'Stop: ' . trim((string) $s['stop_loss']);
            $tp = Learn::lines($s['take_profit']);
            if ($tp) $a[] = 'Target: ' . $tp[0] . ($s['target_rr'] ? ' (' . $s['target_rr'] . ')' : '');
            if ($s['session_window'] || $s['timeframe']) $a[] = 'When: ' . trim($s['session_window'] . ' · ' . $s['timeframe'], ' ·');
            $lib[] = ['t' => 'university', 'q' => 'What is the ' . $title . '?', 'k' => array_merge([$name, self::norm($title), (string) $s['slug'], str_replace('-', ' ', (string) $s['slug'])], count($words) >= 2 ? [implode(' ', array_slice($words, 0, 2))] : $words, self::aliases($title)),
                'a' => $a ?: [$title], 'link' => '/terminal/university/' . $s['slug'], 'linkLabel' => 'Full lesson'];
        }
        foreach (Course::MODULES as $n => [$slug, $title, $short, , , $summary]) {
            $lib[] = ['t' => 'course', 'q' => $title, 'k' => [self::norm($title), self::norm($short)], 'a' => ['**' . $title . '**: ' . $summary, 'It is module ' . $n . ' of ' . Course::TITLE . ' in the University.'],
                'link' => '/terminal/university/course/' . $slug, 'linkLabel' => 'Open module'];
        }
        return $lib;
    }

    /** Suggested questions shown as chips. */
    public static function suggestions(): array
    {
        return [
            'mine' => ['How am I doing this month?', 'What is my win rate?', 'Which session should I avoid?', 'What mistake costs me the most?', 'What is my best strategy?', 'What is my worst hour?', 'Do I revenge trade?', 'What is my longest losing streak?', 'Am I getting better than last month?', 'How disciplined was I this week?'],
            'learn' => ['How much should I risk per trade?', 'How do I calculate lot size?', 'How do I stop revenge trading?', 'Why do I win on demo but lose live?', 'What is VWAP?', 'What are the trading sessions?', 'How do prop firm challenges work?', 'Why should I journal my trades?'],
        ];
    }

    // ------------------------------------------------------------------ period + data
    /** [from, to, label] in the member's timezone, or [null, null, 'all time']. */
    private function period(string $q): array
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone($this->tz));
        if (preg_match('/\btoday\b/', $q)) return [$now->format('Y-m-d'), $now->format('Y-m-d'), 'today'];
        if (preg_match('/\byesterday\b/', $q)) { $y = $now->modify('-1 day')->format('Y-m-d'); return [$y, $y, 'yesterday']; }
        if (preg_match('/last week|previous week/', $q)) { [$f, $t] = Edge::periodRange('last_week', $this->tz); return [$f, $t, 'last week']; }
        if (preg_match('/this week|the week|weekly/', $q)) { [$f, $t] = Edge::periodRange('week', $this->tz); return [$f, $t, 'this week']; }
        if (preg_match('/last month|previous month/', $q) && !preg_match('/vs|versus|compared|than/', $q)) { [$f, $t] = Edge::periodRange('last_month', $this->tz); return [$f, $t, 'last month']; }
        if (preg_match('/this month|the month|monthly/', $q) && !preg_match('/vs|versus|compared|than/', $q)) { [$f, $t] = Edge::periodRange('month', $this->tz); return [$f, $t, 'this month']; }
        if (preg_match('/this year|ytd/', $q)) return [$now->format('Y-01-01'), $now->format('Y-m-d'), 'this year'];
        if (preg_match('/last (\d{1,3}) days/', $q, $m)) return [$now->modify('-' . ((int) $m[1] - 1) . ' days')->format('Y-m-d'), $now->format('Y-m-d'), 'last ' . (int) $m[1] . ' days'];
        return [null, null, 'all time'];
    }

    private function all(): array
    {
        if (!$this->all && $this->loadTrades) {
            $this->all = Analytics::closed(($this->loadTrades)());
        }
        return $this->all;
    }

    private function inPeriod(array $trades, ?string $from, ?string $to): array
    {
        if (!$from) return $trades;
        return array_values(array_filter($trades, function ($t) use ($from, $to) {
            $d = Analytics::local($t['executed_at'], $this->tz)->format('Y-m-d');
            return $d >= $from && $d <= $to;
        }));
    }

    // ------------------------------------------------------------------ answers from data
    private function performance(string $intent, string $q): array
    {
        [$from, $to, $label] = $this->period($q);
        $all = $this->all();
        $trades = $this->inPeriod($all, $from, $to);
        $c = $this->cur;
        $m = fn ($v, $sign = true) => money($v, $c, $sign);
        $n = count($trades);
        $none = function () use ($label, $all) {
            return $this->reply([$all ? 'No closed trades ' . ($label === 'all time' ? 'yet' : $label) . '. Ask about a longer period, e.g. "all time".' : 'You have no closed trades in this account yet. Log a few trades and I can answer from your own data.'], [['/terminal', 'Log a trade']], 'data');
        };
        if ($intent !== 'lessons' && $intent !== 'compare' && $n === 0) return $none();
        $S = Analytics::summarize($trades);
        $L = ucfirst($label);
        $groupLine = function (array $g, string $what) use ($m) {
            return sprintf('%s: **%s** over %d trade%s · win rate %s', $what, $m($g['s']['net']), $g['s']['trades'], $g['s']['trades'] === 1 ? '' : 's', pct($g['s']['win_rate'], 0));
        };
        $bestWorst = function (array $groups, string $noun, string $link) use ($groupLine, $L, $n) {
            $g = array_values(array_filter($groups, fn ($x) => $x['s']['trades'] >= ($n >= 15 ? 3 : 1)));
            if (!$g) return $this->reply(['Not enough trades per ' . $noun . ' yet (' . $L . ').'], [], 'data');
            usort($g, fn ($a, $b) => $b['s']['net'] <=> $a['s']['net']);
            $best = $g[0]; $worst = $g[count($g) - 1];
            $lines = [$L . ' · by ' . $noun . ':', '• Best · ' . $groupLine($best, $best['key'])];
            if (count($g) > 1) $lines[] = '• ' . ($worst['s']['net'] < 0 ? 'Worst' : 'Weakest (still profitable)') . ' · ' . $groupLine($worst, $worst['key']);
            $next = $worst['s']['net'] < 0 ? 'Next: cut size or skip **' . $worst['key'] . '** for 2 weeks and compare.' : 'Next: focus more of your trades on **' . $best['key'] . '**.';
            $lines[] = $next;
            return $this->reply($lines, [[$link, 'See details']], 'data');
        };

        switch ($intent) {
            case 'summary':
                $eq = Analytics::equity($trades, 0);
                return $this->reply([
                    $L . ' · ' . $S['trades'] . ' closed trades',
                    '• Net P&L **' . $m($S['net']) . '** · win rate **' . pct($S['win_rate'], 0) . '**',
                    '• Profit factor ' . ($S['profit_factor'] ?? '—') . ' · expectancy ' . $m($S['expectancy']) . '/trade' . ($S['avg_r'] !== null ? ' · avg ' . number_format($S['avg_r'], 2) . 'R' : ''),
                    '• Max drawdown ' . $m(-$eq['max_dd'], false) . ' · rules followed ' . pct($S['compliance'], 0),
                    $this->nextFromLeaks($trades),
                ], [['/terminal/dashboard', 'Dashboard']], 'data');
            case 'winrate':
                return $this->reply([
                    $L . ': win rate **' . pct($S['win_rate'], 0) . '** (' . $S['wins'] . ' wins, ' . $S['losses'] . ' losses' . ($S['breakeven'] ? ', ' . $S['breakeven'] . ' breakeven' : '') . ').',
                    '• Average win ' . $m($S['avg_win']) . ' vs average loss ' . ($S['avg_loss'] !== null ? $m(-$S['avg_loss'], false) : '—') . ($S['payoff'] ? ' (payoff ' . $S['payoff'] . '×)' : '') . '.',
                    $S['payoff'] ? '• Breakeven win rate at your payoff: about **' . pct(1 / (1 + $S['payoff']), 0) . '**.' : '',
                    'Next: judge win rate together with payoff, not alone.',
                ], [], 'data');
            case 'pnl':
                $bySym = Analytics::groupBy($trades, fn ($t) => $t['symbol']);
                return $this->reply([
                    $L . ': net P&L **' . $m($S['net']) . '** from ' . $S['trades'] . ' trades.',
                    '• Gross profit ' . $m($S['gross_profit']) . ' · gross loss ' . $m(-$S['gross_loss'], false) . '.',
                    $bySym ? '• Biggest contributor: **' . $bySym[0]['key'] . '** ' . $m($bySym[0]['s']['net']) . (count($bySym) > 1 ? ' · biggest drag: **' . end($bySym)['key'] . '** ' . $m(end($bySym)['s']['net']) : '') . '.' : '',
                    $S['net'] >= 0 ? 'Next: protect it, keep risk the same.' : 'Next: ask me "what mistake costs me the most?"',
                ], [['/terminal/calendar', 'Calendar']], 'data');
            case 'instrument':
                return $bestWorst(Analytics::groupBy($trades, fn ($t) => $t['symbol']), 'instrument', '/terminal/dashboard');
            case 'session':
                $g = []; foreach (Edge::sessionStats($trades) as $k => $x) { if ($x['s']['trades']) $g[] = ['key' => $x['label'], 's' => $x['s']]; }
                return $bestWorst($g, 'session', '/terminal/edge');
            case 'hour':
                return $bestWorst(Analytics::byHour($trades, $this->tz), 'entry hour (' . $this->tz . ')', '/terminal/edge');
            case 'weekday':
                return $bestWorst(Analytics::byWeekday($trades, $this->tz), 'weekday', '/terminal/dashboard');
            case 'strategy':
                $g = Analytics::groupBy($trades, [Edge::class, 'strategyOf']);
                $best = Analytics::bestByExpectancy($g, $n >= 15 ? 3 : 1);
                if (!$best) return $bestWorst($g, 'strategy', '/terminal/strategies');
                $w = Edge::bestWindow($trades);
                $ap = Edge::aPlus($w);
                return $this->reply([
                    $L . ': highest expectancy is **' . $best['key'] . '**, ' . $m($best['s']['expectancy']) . ' per trade over ' . $best['s']['trades'] . ' trades (win rate ' . pct($best['s']['win_rate'], 0) . ').',
                    $w ? '• Best window: ' . Edge::windowName($w) . ' · ' . $w['best_hours']['label'] . ' (' . $m($w['s']['net']) . ').' : '',
                    $ap ? '• It meets the A+ bar (20+ trades, 60%+ win rate, 1R+ average).' : '• No strategy meets the A+ bar yet (20+ trades, 60%+ win rate, 1R+ average).',
                    'Next: size your best setup normally and reduce size on the rest.',
                ], [['/terminal/strategies', 'Strategy analysis']], 'data');
            case 'expectancy':
                return $this->reply([
                    $L . ': expectancy **' . $m($S['expectancy']) . ' per trade**' . ($S['avg_r'] !== null ? ' · average **' . number_format($S['avg_r'], 2) . 'R**' : '') . '.',
                    '• Profit factor ' . ($S['profit_factor'] ?? '—') . ' · average win ' . $m($S['avg_win']) . ' · average loss ' . ($S['avg_loss'] !== null ? $m(-$S['avg_loss'], false) : '—') . '.',
                    $S['trades'] < 50 ? '• Only ' . $S['trades'] . ' trades: treat this as early evidence; 50–100 trades is more reliable.' : '',
                    $S['expectancy'] !== null && $S['expectancy'] < 0 ? 'Next: find the leak first. Ask "what mistake costs me the most?"' : 'Next: keep the rules that produced this; don\'t change strategy mid-sample.',
                ], [], 'data');
            case 'drawdown':
                $eq = Analytics::equity($trades, max(1.0, (float) ($this->acc['starting_capital'] ?? 0)));
                return $this->reply([
                    $L . ': max drawdown **' . $m(-$eq['max_dd'], false) . '**' . ($eq['max_dd_pct'] ? ' (' . number_format($eq['max_dd_pct'], 1) . '% of peak equity)' : '') . '.',
                    '• Recovering −20% needs +25%; −50% needs +100%. Keeping drawdowns small matters more than big wins.',
                    'Next: if you are in a drawdown now, halve your risk until you have 5 disciplined trades.',
                ], [['/terminal/dashboard', 'Equity curve']], 'data');
            case 'streak':
                [$lw, $ll, $cur] = self::streaks($trades);
                return $this->reply([
                    $L . ': longest losing streak **' . $ll . '**, longest winning streak **' . $lw . '**.',
                    '• Current streak: ' . ($cur > 0 ? $cur . ' win' . ($cur > 1 ? 's' : '') : ($cur < 0 ? (-$cur) . ' loss' . ($cur < -1 ? 'es' : '') : 'none')) . '.',
                    '• Streaks are normal: with a 50% win rate, about 80% of 100-trade samples contain 5+ losses in a row.',
                    'Next: keep the same risk during a streak; the damage comes from sizing up.',
                ], [], 'data');
            case 'extremes':
                $sorted = $trades; usort($sorted, fn ($a, $b) => (float) $b['pnl'] <=> (float) $a['pnl']);
                $b = $sorted[0]; $w = end($sorted);
                $d = fn ($t) => $t['symbol'] . ' ' . ($t['side'] === 'LONG' ? 'buy' : 'sell') . ' on ' . Analytics::local($t['executed_at'], $this->tz)->format('M j');
                return $this->reply([
                    $L . ':',
                    '• Biggest win **' . $m((float) $b['pnl']) . '**: ' . $d($b) . '.',
                    '• Biggest loss **' . $m((float) $w['pnl']) . '**: ' . $d($w) . ($w['mistake_tag'] && $w['mistake_tag'] !== 'NONE' ? ' (tagged: ' . (Domain::MISTAKES[$w['mistake_tag']] ?? $w['mistake_tag']) . ')' : '') . '.',
                    abs((float) $w['pnl']) > 2 * max(1, abs((float) $S['avg_loss'])) ? 'Next: your biggest loss is over 2× your average loss. Check whether the stop was respected.' : 'Next: your losses are contained. Keep it that way.',
                ], [['/terminal/trades', 'Trade log']], 'data');
            case 'emotion':
                $g = array_values(array_filter(Analytics::groupBy($trades, fn ($t) => $t['emotion'] ?: null), fn ($x) => $x['s']['trades'] >= 1));
                if (!$g) return $this->reply(['You have not tagged emotions on your trades ' . $label . '. Add how you felt on each trade and I can show which emotion costs you.'], [['/terminal/trades', 'Trade log']], 'data');
                usort($g, fn ($a, $b) => $a['s']['net'] <=> $b['s']['net']);
                $ev = Analytics::emotionalVsDisciplined($trades);
                $lines = [$L . ' · by emotion:', '• Costliest: **' . ucfirst(strtolower($g[0]['key'])) . '** ' . $m($g[0]['s']['net']) . ' over ' . $g[0]['s']['trades'] . ' trades.'];
                if (count($g) > 1) $lines[] = '• Best: **' . ucfirst(strtolower(end($g)['key'])) . '** ' . $m(end($g)['s']['net']) . '.';
                if ($ev['emotional']['trades'] && $ev['disciplined']['trades']) $lines[] = '• Emotional trades ' . $m($ev['emotional']['net']) . ' vs disciplined ' . $m($ev['disciplined']['net']) . '.';
                $lines[] = 'Next: when you feel **' . strtolower($g[0]['key']) . '**, skip the next trade.';
                return $this->reply($lines, [], 'data');
            case 'mistake':
                $dl = Analytics::disciplineLeak($trades);
                $bm = array_values(array_filter($dl['by_mistake'], fn ($x) => $x['trades'] > 0));
                if (!$bm) return $this->reply([$L . ': no mistake tags on your trades. Tag mistakes (FOMO, moved stop, revenge…) when you log, and I can price each one.', 'Rules followed: **' . pct($S['compliance'], 0) . '**.'], [['/terminal/trades', 'Trade log']], 'data');
                usort($bm, fn ($a, $b) => $a['actual'] <=> $b['actual']);
                foreach ($bm as &$x) { $x['mistake'] = Domain::MISTAKES[$x['mistake']] ?? ucfirst(strtolower(str_replace('_', ' ', (string) $x['mistake']))); } unset($x);
                $lines = [$L . ': your costliest mistake is **' . $bm[0]['mistake'] . '**: ' . $bm[0]['trades'] . ' trade' . ($bm[0]['trades'] === 1 ? '' : 's') . ', ' . $m($bm[0]['actual']) . '.'];
                foreach (array_slice($bm, 1, 2) as $x) $lines[] = '• ' . $x['mistake'] . ': ' . $x['trades'] . ' trade' . ($x['trades'] === 1 ? '' : 's') . ', ' . $m($x['actual']) . '.';
                if ($dl['leak'] > 0) $lines[] = '• Following your rules would have changed results by about **' . $m($dl['leak']) . '**.';
                $lines[] = 'Next: write one rule that stops **' . strtolower($bm[0]['mistake']) . '** and read it before each session.';
                return $this->reply($lines, [['/terminal/edge', 'Edge Matrix']], 'data');
            case 'discipline':
                $ev = Analytics::emotionalVsDisciplined($trades);
                $ans = array_count_values(array_filter(array_map(fn ($j) => $j['rules_answer'] ?? null, $this->journalsIn($from, $to))));
                return $this->reply([
                    $L . ': **' . pct($S['compliance'], 0) . '** of trades followed your plan.',
                    $ev['disciplined']['trades'] ? '• Plan-following trades: ' . $m($ev['disciplined']['net']) . ' (' . $ev['disciplined']['trades'] . ') vs emotional/rule-breaking: ' . $m($ev['emotional']['net']) . ' (' . $ev['emotional']['trades'] . ').' : '',
                    $ans ? '• Journal days: ' . ($ans['yes'] ?? 0) . ' yes, ' . ($ans['partial'] ?? 0) . ' partial, ' . ($ans['no'] ?? 0) . ' no on "followed my rules".' : '',
                    ($S['compliance'] ?? 1) >= .8 ? 'Next: strong discipline. Keep judging days by rules, not P&L.' : 'Next: aim for 80%+. Pick the one rule you break most and fix only that this week.',
                ], [['/terminal/notepad', 'Daily notepad']], 'data');
            case 'revenge':
                $rev = array_values(array_filter($trades, fn ($t) => Edge::inBehaviour($t, 'REVENGE')));
                $after = self::afterLosses($trades);
                $lines = [$L . ':'];
                $lines[] = $rev ? '• Tagged revenge trades: **' . count($rev) . '**, net ' . $m(array_sum(array_map(fn ($t) => (float) $t['pnl'], $rev))) . '.' : '• No trades tagged as revenge.';
                if ($after['n']) $lines[] = '• Trades taken within 30 min after 2+ losses: **' . $after['n'] . '**, net ' . $m($after['net']) . ', win rate ' . pct($after['wr'], 0) . '.';
                $lines[] = ($after['n'] && $after['net'] < 0) || $rev ? 'Next: 2 losses in a row = walk away 20 minutes. journzey\'s tilt alert enforces it.' : 'Next: no sign of revenge trading. Keep the 2-loss rule anyway.';
                return $this->reply($lines, [['/terminal/settings', 'Tilt settings']], 'data');
            case 'side':
                return $bestWorst(Analytics::groupBy($trades, fn ($t) => $t['side'] === 'LONG' ? 'Longs (buys)' : 'Shorts (sells)'), 'direction', '/terminal/dashboard');
            case 'activity':
                $days = []; foreach ($trades as $t) { $days[Analytics::local($t['executed_at'], $this->tz)->format('Y-m-d')][] = $t; }
                $per = count($days) ? $n / count($days) : 0;
                $heavy = array_filter($days, fn ($d) => count($d) > max(3, $per * 1.5));
                $hs = Analytics::summarize(array_merge(...array_values($heavy ?: [[]])));
                return $this->reply([
                    $L . ': **' . number_format($per, 1) . ' trades per trading day** (' . $n . ' trades over ' . count($days) . ' days).',
                    $heavy ? '• On your ' . count($heavy) . ' busiest days (more than ' . max(3, (int) round($per * 1.5)) . ' trades) you made ' . $m($hs['net']) . '.' : '• No unusually busy days.',
                    $heavy && $hs['net'] < 0 ? 'Next: set a daily max of ' . max(2, (int) round($per)) . ' trades.' : 'Next: keep a daily trade cap so boredom never sets your size.',
                ], [], 'data');
            case 'window':
                $w = Edge::bestWindow($trades); $a = Edge::antiWindow($trades);
                if (!$w && !$a) return $this->reply(['Not enough trades per session and hour yet (' . $L . '). It needs about 5 trades in the same window.'], [['/terminal/edge', 'Edge Matrix']], 'data');
                return $this->reply(array_filter([
                    $w ? '• Best window: **' . Edge::windowName($w) . '**, ' . $w['best_hours']['label'] . ' · ' . $m($w['s']['net']) . ' · win rate ' . pct($w['s']['win_rate'], 0) . '.' : '',
                    $a ? '• Anti-window: **' . Edge::windowName($a) . '**' . (!empty($a['worst_hours']['label']) ? ', ' . $a['worst_hours']['label'] : '') . ' · ' . $m($a['s']['net']) . '.' : '',
                    'Next: trade more of the best window; cut size in the anti-window.',
                ]), [['/terminal/edge', 'Edge Matrix']], 'data');
            case 'losing':
                $sl = Edge::strengthsAndLeaks($trades, $this->tz, $this->m, $this->equity, $c);
                if (!$sl['enough']) return $this->reply(['I need at least 10 closed trades ' . ($label === 'all time' ? '' : $label . ' ') . 'to find reliable leaks. You have ' . $n . '.', 'Meanwhile: ask "what mistake costs me the most?"'], [], 'data');
                $lines = [$L . ': your biggest leaks'];
                foreach (array_slice($sl['leaks'], 0, 3) as $x) $lines[] = '• ' . ($x['title'] ?? $x['short']) . ($x['detail'] ? ' (' . rtrim($x['detail'], '.') . ')' : '') . '.';
                if (!$sl['leaks']) $lines[] = '• No clear leak in this period. Net ' . $m($S['net']) . '.';
                $lines[] = $sl['leaks'] ? 'Next: fix only the first one this week.' : 'Next: keep doing what works.';
                return $this->reply($lines, [['/terminal/edge', 'Edge Matrix']], 'data');
            case 'strengths':
                $sl = Edge::strengthsAndLeaks($trades, $this->tz, $this->m, $this->equity, $c);
                if (!$sl['enough']) return $this->reply(['I need at least 10 closed trades to find reliable strengths. You have ' . $n . '.'], [], 'data');
                $lines = [$L . ': what works for you'];
                foreach (array_slice($sl['strengths'], 0, 3) as $x) $lines[] = '• ' . $x['title'] . ' (' . rtrim($x['detail'], '.') . ').';
                if (!$sl['strengths']) $lines[] = '• No clear strength yet. Keep logging consistently.';
                $lines[] = 'Next: put more of your risk where your edge is.';
                return $this->reply($lines, [['/terminal/edge', 'Edge Matrix']], 'data');
            case 'risk':
                $r = Edge::ruinRadar($this->all(), $this->equity, (float) ($this->m['default_risk_pct'] ?? 1));
                if (!$r['ok']) return $this->reply(['I need at least ' . $r['needed'] . ' closed trades with a stop to estimate your blow-up risk. You have ' . $r['have'] . '.', 'General rule: 0.5–1% risk per trade.'], [['/terminal/calculator', 'Risk calculator']], 'data');
                $in = $r['inputs'];
                return $this->reply([
                    $in['risk_avg'] !== null ? 'Your recent risk per trade averages **' . number_format((float) $in['risk_avg'], 2) . '%** (max ' . number_format((float) $in['risk_max'], 2) . '%).' : 'Add stop losses to your trades so I can measure your risk per trade.',
                    '• Estimated chance of a ' . (int) round($r['threshold'] * 100) . '% drawdown over your next ' . $r['horizon'] . ' trades (about 14 days): **' . number_format($r['current_p'] * 100, 1) . '%** (' . strip_tags((string) $r['level'][0]) . ').',
                    '• Based on your recent trades: win rate ' . pct($in['win_rate'], 0) . ', longest losing streak ' . (int) $in['max_loss_streak'] . '.',
                    'Next: if that number worries you, cut risk per trade; the Edge Matrix shows the scenarios.',
                ], [['/terminal/edge', 'Blow-up radar']], 'data');
            case 'lessons':
                $j = array_values(array_filter($this->journalsIn($from, $to), fn ($x) => trim((string) $x['key_lesson']) !== ''));
                if (!$j) return $this->reply(['No key lessons in your journal ' . ($label === 'all time' ? 'yet' : $label) . '. Add one line to the Daily Notepad after each session.'], [['/terminal/notepad', 'Daily notepad']], 'data');
                $lines = ['Your recent key lessons (' . $label . '):'];
                foreach (array_slice($j, 0, 5) as $x) $lines[] = '• ' . fmt_date($x['journal_date'] . ' 12:00:00', 'M j') . ': "' . mb_strimwidth(trim((string) $x['key_lesson']), 0, 140, '…') . '"';
                $lines[] = 'Next: pick the lesson that repeats and turn it into a written rule.';
                return $this->reply($lines, [['/terminal/notepad', 'Daily notepad']], 'data');
            case 'compare':
                [$f1, $t1] = Edge::periodRange('month', $this->tz); [$f0, $t0] = Edge::periodRange('last_month', $this->tz);
                $a = Analytics::summarize($this->inPeriod($all, $f1, $t1)); $b = Analytics::summarize($this->inPeriod($all, $f0, $t0));
                if (!$a['trades'] && !$b['trades']) return $none();
                $arrow = fn ($x, $y) => $x === null || $y === null ? '' : ($x > $y ? ' ↑' : ($x < $y ? ' ↓' : ' ='));
                return $this->reply([
                    'This month vs last month:',
                    '• Net **' . $m($a['net']) . '** vs ' . $m($b['net']) . $arrow($a['net'], $b['net']),
                    '• Win rate ' . pct($a['win_rate'], 0) . ' vs ' . pct($b['win_rate'], 0) . $arrow($a['win_rate'], $b['win_rate']),
                    '• Rules followed ' . pct($a['compliance'], 0) . ' vs ' . pct($b['compliance'], 0) . $arrow($a['compliance'], $b['compliance']),
                    '• Trades ' . $a['trades'] . ' vs ' . $b['trades'],
                    ($a['compliance'] ?? 0) >= ($b['compliance'] ?? 0) ? 'Next: discipline is holding. Keep the same process.' : 'Next: discipline slipped. Reread your rules before each session.',
                ], [['/terminal/dashboard', 'Dashboard']], 'data');
            case 'recent':
                $last = array_slice(array_reverse($trades), 0, 3);
                $lines = ['Your last ' . count($last) . ' closed trade' . (count($last) > 1 ? 's' : '') . ':'];
                foreach ($last as $t) $lines[] = '• ' . Analytics::local($t['executed_at'], $this->tz)->format('M j H:i') . ' · ' . $t['symbol'] . ' ' . ($t['side'] === 'LONG' ? 'buy' : 'sell') . ' · **' . $m((float) $t['pnl']) . '**' . ($t['emotion'] ? ' · ' . strtolower($t['emotion']) : '');
                $lines[] = 'Next: add setup and emotion to any trade missing them.';
                return $this->reply($lines, [['/terminal/trades', 'Trade log']], 'data');
        }
        return $this->reply(['I could not answer that from your data yet.'], [], 'fallback');
    }

    private function nextFromLeaks(array $trades): string
    {
        $sl = Edge::strengthsAndLeaks($trades, $this->tz, $this->m, $this->equity, $this->cur);
        if ($sl['enough'] && $sl['leaks']) return 'Next: biggest leak is **' . ($sl['leaks'][0]['short'] ?? $sl['leaks'][0]['title']) . '** (' . $sl['leaks'][0]['value'] . ').';
        return 'Next: ask "why am I losing?" or "what is my best session?" for detail.';
    }

    private function journalsIn(?string $from, ?string $to): array
    {
        if (!$from) return $this->journals;
        return array_values(array_filter($this->journals, fn ($j) => $j['journal_date'] >= $from && $j['journal_date'] <= $to));
    }

    /** [longest win streak, longest loss streak, current (+wins / −losses)] in time order. */
    public static function streaks(array $trades): array
    {
        $lw = $ll = $cw = $cl = 0;
        foreach ($trades as $t) {
            $p = (float) $t['pnl'];
            if ($p > 0) { $cw++; $cl = 0; } elseif ($p < 0) { $cl++; $cw = 0; } else { $cw = $cl = 0; }
            $lw = max($lw, $cw); $ll = max($ll, $cl);
        }
        return [$lw, $ll, $cw ? $cw : -$cl];
    }

    /** Trades opened within 30 minutes after 2+ consecutive losses (a behavioural revenge signal). */
    public static function afterLosses(array $trades): array
    {
        $out = []; $run = 0; $lastClose = null;
        foreach ($trades as $t) {
            $ts = strtotime($t['executed_at'] . ' UTC');
            if ($run >= 2 && $lastClose !== null && $ts - $lastClose <= 1800) $out[] = $t;
            $p = (float) $t['pnl'];
            $run = $p < 0 ? $run + 1 : 0;
            $lastClose = strtotime(($t['closed_at'] ?: $t['executed_at']) . ' UTC');
        }
        $s = Analytics::summarize($out);
        return ['n' => count($out), 'net' => $s['net'], 'wr' => $s['win_rate']];
    }

    private function reply(array $lines, array $links, string $source): array
    {
        $lines = array_values(array_filter($lines, fn ($l) => trim((string) $l) !== ''));
        return ['text' => implode("\n", $lines), 'links' => $links, 'source' => $source];
    }
}
