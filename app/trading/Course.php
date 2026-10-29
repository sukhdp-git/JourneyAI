<?php
declare(strict_types=1);

namespace App\Trading;

use App\Core\Database;

/**
 * journzey.ai University course: "Trading Foundations" — beginner to confident trader in 13 modules and 4 levels.
 * Module numbers are permanent keys (progress is stored by number); ORDER is the learning order shown to members.
 * Content lives in views/terminal/course/m{n}.php.
 */
final class Course
{
    public const TITLE = 'Trading Foundations';

    /** n => [slug, title, short, minutes, icon, summary] */
    public const MODULES = [
        1 => ['harsh-reality', 'The harsh reality of trading', 'Harsh reality', 9, 'alert', 'Why most retail traders lose money — emotions, leverage and no journal — and what the numbers really say.'],
        2 => ['market-fundamentals', 'Market fundamentals', 'Fundamentals', 16, 'bars', 'Stocks, indices, forex, commodities and crypto; candlesticks, market structure, support & resistance and every order type.'],
        3 => ['risk-management', 'The golden rule: risk management', 'Risk management', 14, 'shield', 'Risk:reward, break-even win rates, expectancy and position sizing so one trade never costs more than 1–2%.'],
        4 => ['trading-psychology', 'Trading psychology & emotional traps', 'Psychology', 11, 'brain', 'Revenge trading, FOMO, cutting winners early, letting losers run — how each trap works and the rule that stops it.'],
        5 => ['habits-of-successful-traders', 'Habits of successful traders', 'Habits', 10, 'check-circle', 'Backtesting, a written trading plan and journaling every trade — the routine that turns practice into skill.'],
        6 => ['how-markets-move', 'How markets move: sessions, spreads & leverage', 'Sessions & leverage', 12, 'clock', 'When markets are busy, what a spread, swap and pip really cost you, and how leverage and margin calls work.'],
        7 => ['reading-charts', 'Reading charts like a pro', 'Chart reading', 14, 'trend-up', 'Top-down timeframes, trend structure, breakouts vs fakeouts and the chart patterns worth knowing.'],
        8 => ['indicators', 'Indicators: what they really tell you', 'Indicators', 12, 'activity', 'Moving averages, RSI, MACD, Bollinger Bands, ATR and VWAP — what each measures, and why less is more.'],
        9 => ['news-and-fundamentals', 'News, central banks & the economic calendar', 'News & macro', 11, 'calendar', 'Interest rates, inflation and jobs data, how news moves forex, gold and indices, and how to trade around it safely.'],
        10 => ['build-and-test-a-strategy', 'Build and test your own strategy', 'Strategy & backtest', 14, 'target', 'Turn an idea into written if-then rules, backtest 100 trades honestly, then forward-test before real money.'],
        11 => ['trade-management', 'Trade management: stops, targets & exits', 'Trade management', 11, 'scale', 'Where stops really go, break-even, trailing stops, partial profits and the one rule about moving a stop.'],
        12 => ['brokers-prop-firms-and-scams', 'Brokers, prop firms & staying safe', 'Brokers & safety', 10, 'shield', 'Choosing a regulated broker, how prop-firm challenges work, and the red flags of trading scams.'],
        13 => ['trading-as-a-business', 'Running your trading like a business', 'Your trading business', 10, 'layers', 'Daily routine, weekly review, when to size up, a drawdown plan, records and looking after your head.'],
    ];

    /** Levels in learning order: [title, one-liner, module numbers]. */
    public const LEVELS = [
        ['Start here', 'What trading really is, and the rules that keep you in the game.', [1, 2, 3]],
        ['Read the market', 'When it moves, how to read it, and what moves it.', [6, 7, 8, 9]],
        ['Build your edge', 'Your mind, your strategy and how you manage a trade.', [4, 10, 11]],
        ['Go pro', 'Habits, safety and running it like a business.', [5, 12, 13]],
    ];

    /** "In 30 seconds" — the three things to remember from each module. */
    public const TLDR = [
        1 => ['Most retail traders lose money; leverage magnifies every mistake.', 'Losses come mostly from behaviour, not from a lack of strategies.', 'A journal is how you find your own mistakes.'],
        2 => ['Every candle shows open, high, low and close.', 'Trends are higher highs and higher lows (or the reverse).', 'Market, limit and stop orders each have one job.'],
        3 => ['Risk 1% (max 2%) of the account per trade.', 'Let the stop distance decide the lot size.', 'Win rate only matters together with risk:reward.'],
        4 => ['After 2 losses, stop for 20 minutes.', 'FOMO says “chase”; your plan says “wait”.', 'Cutting winners and holding losers is the most common leak.'],
        5 => ['Backtest before you risk money.', 'Write your plan down; trade only what is in it.', 'Journal every trade and review every week.'],
        6 => ['Volume and spreads change with the session; the London–New York overlap is busiest.', 'Every trade costs spread, maybe commission, and swap if held overnight.', 'Leverage lowers margin, not risk — your stop decides the risk.'],
        7 => ['Start on a higher timeframe for direction, then zoom in for the entry.', 'Structure breaks tell you when a trend may be changing.', 'Wait for a close (or a retest) to avoid most fakeouts.'],
        8 => ['Indicators are maths on past prices — they lag.', 'Use 1–2 that answer different questions (trend, momentum, volatility).', 'ATR is the most practical one: it sizes stops to the market.'],
        9 => ['Interest rates and inflation drive currencies over time.', 'High-impact news widens spreads and causes slippage.', 'Know the calendar before the session; be flat or planned for big releases.'],
        10 => ['A strategy is a set of if-then rules anyone could follow.', 'Backtest at least 100 trades, including costs.', 'Forward-test on demo, then go live with small risk.'],
        11 => ['Put the stop where your idea is proven wrong, not at a round number.', 'Only ever move a stop towards profit.', 'Decide partials and trailing before you enter.'],
        12 => ['Use a broker regulated in a strong jurisdiction (and legal where you live).', 'Prop challenges are rules games: daily loss and max loss end them.', 'Guaranteed returns and “account managers” are red flags.'],
        13 => ['Pre-session plan, session, post-session journal — every day.', 'Size up slowly, only after a proven sample.', 'Have a written drawdown plan before you need it.'],
    ];

    public static function find(string $slug): ?int
    {
        foreach (self::MODULES as $n => $m) {
            if ($m[0] === $slug) {
                return $n;
            }
        }
        return null;
    }

    /** Module numbers in learning order. */
    public static function order(): array
    {
        return array_merge(...array_map(fn ($l) => $l[2], self::LEVELS));
    }

    /** 1-based position of a module in the learning order. */
    public static function position(int $n): int
    {
        return (int) array_search($n, self::order(), true) + 1;
    }

    /** [previous, next] module numbers in learning order (null at the ends). */
    public static function neighbours(int $n): array
    {
        $o = self::order();
        $i = (int) array_search($n, $o, true);
        return [$o[$i - 1] ?? null, $o[$i + 1] ?? null];
    }

    public static function minutes(): int
    {
        return array_sum(array_column(self::MODULES, 3));
    }

    // ------------------------------------------------------------------ progress & certificate

    /** A lesson counts as complete once its quiz is answered with at least this score (percent). */
    public const PASS = 60;
    public const XP_LESSON = 100;

    /** @return array<int,int> lesson number => best quiz score (percent) */
    public static function progress(int $uid): array
    {
        $out = [];
        foreach (Database::all('SELECT lesson, quiz_score FROM course_completions WHERE user_id = :u', ['u' => $uid]) as $r) {
            if (isset(self::MODULES[(int) $r['lesson']])) {
                $out[(int) $r['lesson']] = (int) $r['quiz_score'];
            }
        }
        return $out;
    }

    /** Records a passed lesson (keeps the best score). @return string|null error */
    public static function complete(int $uid, int $n, int $score): ?string
    {
        if (!isset(self::MODULES[$n])) {
            return 'Unknown lesson.';
        }
        $score = max(0, min(100, $score));
        if ($score < self::PASS) {
            return 'Score at least ' . self::PASS . '% in the quick check to complete this lesson.';
        }
        Database::query('INSERT INTO course_completions (user_id, lesson, quiz_score) VALUES (:u, :n, :s) ON DUPLICATE KEY UPDATE quiz_score = GREATEST(quiz_score, VALUES(quiz_score))',
            ['u' => $uid, 'n' => $n, 's' => $score]);
        return null;
    }

    public static function uncomplete(int $uid, int $n): void
    {
        Database::query('DELETE FROM course_completions WHERE user_id = :u AND lesson = :n', ['u' => $uid, 'n' => $n]);
    }

    /** XP, level badges and certificate eligibility for the progress map. */
    public static function standing(array $progress): array
    {
        $xp = 0;
        foreach ($progress as $score) {
            $xp += self::XP_LESSON + (int) round($score / 2);       // up to 150 XP a lesson
        }
        $badges = [];
        foreach (self::LEVELS as $i => [$title, , $nums]) {
            $badges[] = ['title' => $title, 'done' => !array_diff($nums, array_keys($progress)), 'n' => count(array_intersect($nums, array_keys($progress))), 'of' => count($nums)];
        }
        return ['xp' => $xp, 'max_xp' => count(self::MODULES) * (self::XP_LESSON + 50), 'done' => count($progress), 'total' => count(self::MODULES),
            'badges' => $badges, 'eligible' => count($progress) === count(self::MODULES)];
    }

    public static function certificate(int $uid): ?array
    {
        return Database::one("SELECT * FROM course_certificates WHERE user_id = :u AND course = 'foundations'", ['u' => $uid]);
    }

    public static function byCode(string $code): ?array
    {
        if (!preg_match('/^[A-Z0-9]{12}$/', $code)) {
            return null;
        }
        return Database::one("SELECT * FROM course_certificates WHERE code = :c AND course = 'foundations'", ['c' => $code]);
    }

    /** Issues the certificate once all lessons are passed. @return array{0:?string,1:?array} [error, certificate] */
    public static function issue(int $uid, string $name): array
    {
        if ($c = self::certificate($uid)) {
            return [null, $c];
        }
        if (!self::standing(self::progress($uid))['eligible']) {
            return ['Complete all ' . count(self::MODULES) . ' lessons first.', null];
        }
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');
        if (mb_strlen($name) < 2 || mb_strlen($name) > 60 || !preg_match('/^[\p{L}\p{M} .\'\-]+$/u', $name)) {
            return ['Enter the name to print: 2–60 letters (spaces, dots, apostrophes and hyphens are fine).', null];
        }
        $alpha = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $code = '';
            for ($i = 0; $i < 12; $i++) {
                $code .= $alpha[random_int(0, strlen($alpha) - 1)];
            }
        } while (Database::value('SELECT 1 FROM course_certificates WHERE code = :c', ['c' => $code]));
        Database::insert('course_certificates', ['code' => $code, 'user_id' => $uid, 'course' => 'foundations', 'name_on_cert' => $name]);
        return [null, self::certificate($uid)];
    }
}
