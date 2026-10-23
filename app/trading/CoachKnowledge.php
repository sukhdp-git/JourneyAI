<?php
declare(strict_types=1);

namespace App\Trading;

/**
 * Built-in knowledge base for the free Smart Coach: short, practical answers to common trading, risk, psychology and
 * journaling questions. No AI involved. Each entry: q (question shown to the member), k (keywords and phrases used for
 * matching), a (answer points, 2–5 short lines), link (optional terminal page with more).
 */
final class CoachKnowledge
{
    public const TOPICS = ['risk' => 'Risk & sizing', 'psych' => 'Psychology', 'journal' => 'Journaling', 'basics' => 'Trading basics', 'tools' => 'Tools & concepts', 'app' => 'Using journzey'];

    /** @return array<int, array{t:string,q:string,k:string[],a:string[],link?:string}> */
    public static function entries(): array
    {
        return [
            // ---------------- Risk & sizing ----------------
            ['t' => 'risk', 'q' => 'How much should I risk per trade?', 'k' => ['risk per trade', 'how much risk', 'percent risk', '1%', '2%', 'risk percentage', 'how much should i risk'],
             'a' => ['Most professional guidance keeps risk at **0.5–1% of the account per trade**, and rarely above 2%.', 'At 1%, ten losses in a row cost about **−9.6%**; at 2% about **−18.3%**; at 5% about **−40%**.', 'Small risk keeps you in the game long enough for your edge to show.'], 'link' => '/terminal/calculator'],
            ['t' => 'risk', 'q' => 'How do I calculate lot size?', 'k' => ['lot size', 'position size', 'how many lots', 'calculate lots', 'sizing', 'contract size'],
             'a' => ['**Lots = (account × risk %) ÷ (stop distance × value per point per lot)**.', 'Example: $10,000 × 1% = $100. Gold with a $10 stop: $100 ÷ ($10 × $100) = **0.10 lots**.', 'EURUSD with a 20-pip stop: $100 ÷ (20 × $10) = **0.50 lots**.', 'Let the stop decide the size, never your mood. The Risk Calculator does this for every instrument.'], 'link' => '/terminal/calculator'],
            ['t' => 'risk', 'q' => 'What is risk:reward and what win rate do I need?', 'k' => ['risk reward', 'risk:reward', 'r:r', 'rr', 'reward ratio', 'breakeven win rate', 'win rate needed'],
             'a' => ['Risk:reward compares what you risk with what you aim to make. 1:2 = risk $100 to make $200.', 'Breakeven win rate = **1 ÷ (1 + R)**: 1:1 → 50%, 1:2 → 33%, 1:3 → 25% (before costs).', 'A lower win rate is fine if your winners are bigger than your losers.']],
            ['t' => 'risk', 'q' => 'What is expectancy?', 'k' => ['expectancy', 'edge', 'expected value', 'is my strategy profitable', 'positive expectancy'],
             'a' => ['Expectancy = **(win% × average win) − (loss% × average loss)**. It is what one trade is worth on average.', 'Example: 40% wins, $200 average win, $100 average loss → 0.4×200 − 0.6×100 = **+$20 per trade**.', 'Judge a strategy on expectancy over 50–100 trades, not on the last few results. Ask me "what is my expectancy?" for yours.']],
            ['t' => 'risk', 'q' => 'What is an R multiple?', 'k' => ['r multiple', 'what is r', 'average r', '1r', '2r', 'r value'],
             'a' => ['**1R = the amount you risked** on the trade (entry to stop).', 'A trade that makes twice the risk is +2R; a full stop-out is −1R.', 'Measuring in R lets you compare trades of different sizes and instruments fairly.']],
            ['t' => 'risk', 'q' => 'How do I recover from a drawdown?', 'k' => ['drawdown', 'recover', 'recovery', 'lost a lot', 'down 20', 'down 50', 'blown', 'blew my account'],
             'a' => ['Losses need bigger gains to recover: **−10% needs +11%, −20% needs +25%, −30% needs +43%, −50% needs +100%**.', 'In a drawdown, cut your risk (for example to 0.5%) until you have a few disciplined trades back.', 'Review the journal for the cause: one habit, one session or one instrument is often most of the damage.']],
            ['t' => 'risk', 'q' => 'Should I use a daily loss limit?', 'k' => ['daily loss limit', 'max loss', 'stop trading for the day', 'daily limit', 'weekly limit'],
             'a' => ['Yes. A daily limit (for example **2–3% of the account**) stops one bad day from becoming a bad month.', 'Write it **before the session**, not in the middle of a loss.', 'Set it per account in Accounts; the terminal warns you when you reach it.'], 'link' => '/terminal/accounts'],
            ['t' => 'risk', 'q' => 'Is moving my stop loss bad?', 'k' => ['move stop', 'moving stop', 'widen stop', 'stop loss moved', 'remove stop', 'move my sl'],
             'a' => ['Moving a stop **away** from price turns a planned −1R into −2R or −3R, and quietly destroys expectancy.', 'Win 50% at +2R and lose −1R = +0.5R per trade. Let losses grow to −2R and it drops to **0**.', 'Rule: a stop may only move **toward** profit, never away.']],
            ['t' => 'risk', 'q' => 'Where should I place my stop loss?', 'k' => ['where to put stop', 'stop placement', 'place stop', 'stopped out then', 'stop hunted', 'stop too tight'],
             'a' => ['Place it where the trade idea is **proven wrong**: beyond the structure (swing high/low or level), plus the spread and a small buffer.', 'Obvious highs and lows attract stops; being 1 pip beyond them is often too close.', 'A tight stop is not lower risk: risk = lot size × stop distance. Wider stop, fewer lots, same dollars at risk.']],

            // ---------------- Psychology ----------------
            ['t' => 'psych', 'q' => 'How do I stop revenge trading?', 'k' => ['revenge', 'revenge trading', 'win it back', 'make it back', 'after a loss', 'angry trade', 'tilt'],
             'a' => ['Use a hard rule: **2 losses in a row = stand up and walk away for 20 minutes.**', 'Keep the same % risk on the trade after a loss. Never size up to "win it back".', 'Before each entry, write one line: "Why this trade?" If the honest answer is "to win it back", close the chart.', 'journzey flags tilt risk after a losing streak and starts a cooldown.']],
            ['t' => 'psych', 'q' => 'How do I deal with FOMO?', 'k' => ['fomo', 'fear of missing out', 'chasing', 'missed the move', 'chase', 'jumped in late'],
             'a' => ['A missed move costs **$0**. A chased move often costs a full loss at the worst price.', 'Rule: **no setup, no trade.** If it is not in your plan, it is not your trade.', 'Write down the trades you skipped and what happened. You will see most chases would not have paid.']],
            ['t' => 'psych', 'q' => 'How do I stop overtrading?', 'k' => ['overtrading', 'too many trades', 'trade too much', 'boredom', 'bored', 'max trades'],
             'a' => ['Set a **maximum number of trades per day** (for example 3) and a trading-hours window.', 'Overtrading usually comes from boredom or a losing streak. Check which one in your journal.', 'Fewer, better trades almost always beat more trades. Ask me "how many trades do I take per day?"']],
            ['t' => 'psych', 'q' => 'Why do I win on demo but lose live?', 'k' => ['demo', 'live account', 'demo vs live', 'fear', 'scared', 'hesitate', 'cut winners early'],
             'a' => ['On demo, a loss is a number. Live, it is real money, so fear joins the trade: you hesitate, cut winners and move stops.', 'Go live with the **smallest size possible** so the money feels small, then grow slowly.', 'Before each trade, log one word for how you feel. After 20 trades you will see which emotion costs you.']],
            ['t' => 'psych', 'q' => 'I lost several trades in a row. Is my strategy broken?', 'k' => ['losing streak', 'losses in a row', 'lost 5', 'lost five', 'strategy broken', 'keep losing', 'bad week'],
             'a' => ['Streaks are normal. With a 50% win rate there is about an **80% chance** of at least 5 losses in a row somewhere in 100 trades.', 'The danger is what you do during the streak: bigger size, faster entries, switching strategy.', 'Keep risk the same and judge the strategy over **100 trades**, not 5.']],
            ['t' => 'psych', 'q' => 'How do I stay disciplined?', 'k' => ['discipline', 'disciplined', 'follow my rules', 'follow rules', 'consistency', 'consistent'],
             'a' => ['Write your rules down and keep them short (5–8 rules).', 'Judge each day by **rules followed**, not P&L. A red day with every rule followed is a good day.', 'Review weekly: best trade, worst trade, one rule to fix.'], 'link' => '/terminal/notepad'],
            ['t' => 'psych', 'q' => 'How do I handle a big loss emotionally?', 'k' => ['big loss', 'lost a lot', 'upset', 'depressed', 'stress', 'stressed', 'anxious', 'anxiety'],
             'a' => ['Stop trading for the day. Decisions made in pain are rarely good ones.', 'Write what happened while it is fresh: setup, size, what you felt, which rule broke.', 'Come back the next day at **reduced size**. If trading is affecting your sleep or wellbeing, take a longer break and talk to someone you trust.']],
            ['t' => 'psych', 'q' => 'Should I trade when I am tired or emotional?', 'k' => ['tired', 'sleep', 'late night', 'emotional', 'mood', 'night trading', '2am'],
             'a' => ['A tired brain takes risks a rested one would not. Set trading hours like a job and keep the app closed outside them.', 'Log your mood before each session; your own data will show whether tired days cost you money.']],

            // ---------------- Journaling ----------------
            ['t' => 'journal', 'q' => 'Why should I journal my trades?', 'k' => ['why journal', 'journaling', 'why keep a journal', 'is journaling worth it', 'benefit of journal'],
             'a' => ['Most losses come from the same 2–3 mistakes repeated, and you cannot see them without writing them down.', 'A habit costing $40 a day is about **$10,400 a year**, and it never shows clearly on your P&L.', 'Journaling turns random results into patterns: your best hour, worst habit and real edge.']],
            ['t' => 'journal', 'q' => 'What should I write after every trade?', 'k' => ['what to write', 'what to journal', 'journal template', 'how to journal', 'journal questions'],
             'a' => ['Three questions: **Why did I enter? How did I feel? Did I follow my plan?**', 'Add the setup or strategy, a screenshot and one lesson.', 'Log within 5 minutes of closing the trade; memory gets kinder over time.']],
            ['t' => 'journal', 'q' => 'How do I do a weekly review?', 'k' => ['weekly review', 'review my week', 'sunday review', 'review trades'],
             'a' => ['Once a week, 10 minutes: **best trade, worst trade, most common mistake, one rule for next week.**', 'Compare rules followed with P&L; discipline usually leads the results.', 'journzey sends a short weekly brief with do\'s and don\'ts on Monday mornings.'], 'link' => '/terminal/notepad'],

            // ---------------- Trading basics ----------------
            ['t' => 'basics', 'q' => 'What are the forex trading sessions?', 'k' => ['session', 'sessions', 'london session', 'new york session', 'asian session', 'tokyo', 'best time to trade', 'market hours', 'overlap'],
             'a' => ['Winter times (UTC): **Sydney 22–07, Tokyo 00–09, London 08–17, New York 13–22**. London and New York open one hour earlier in summer.', 'The **London × New York overlap (13:00–17:00 UTC, 18:30–22:30 IST)** is usually the most liquid period.', 'Ask me "which session is best for me?" to see it from your own trades.']],
            ['t' => 'basics', 'q' => 'What is a pip?', 'k' => ['pip', 'pips', 'point', 'tick', 'what is a pip'],
             'a' => ['For most forex pairs a pip is **0.0001** (for JPY pairs **0.01**).', 'On a standard lot (100,000 units) of EURUSD, 1 pip ≈ **$10**.', 'For gold (XAUUSD, 100 oz per lot), a $1 move = **$100 per lot**. Check your broker\'s contract specs.']],
            ['t' => 'basics', 'q' => 'What is spread, commission and swap?', 'k' => ['spread', 'commission', 'swap', 'fees', 'costs', 'overnight fee', 'rollover'],
             'a' => ['**Spread**: the gap between buy and sell price, paid on every trade.', '**Commission**: a fee per lot charged by some accounts.', '**Swap**: interest paid or received for holding a position overnight.', 'Costs matter most for short-term trading; include them when you judge a strategy.']],
            ['t' => 'basics', 'q' => 'What is leverage and margin?', 'k' => ['leverage', 'margin', 'margin call', 'free margin', 'stop out'],
             'a' => ['Leverage lets you control a larger position with less money; **margin** is the deposit the broker holds for it.', 'Leverage does not change your risk per trade, your lot size and stop do. High leverage just makes oversizing easy.', 'A margin call or stop-out happens when equity falls too far below required margin.']],
            ['t' => 'basics', 'q' => 'How do prop firm challenges work?', 'k' => ['prop firm', 'funded', 'challenge', 'evaluation', 'trailing drawdown', 'ftmo', 'funded account'],
             'a' => ['You trade a firm\'s evaluation account and must hit a **profit target** without breaking rules: daily loss, max loss, minimum days, sometimes consistency.', 'Most failures come from a **rule**, not a bad trade. A trailing drawdown moves up with your peak balance, so your room shrinks while you win.', 'Add a prop account in journzey to track every rule live.'], 'link' => '/terminal/accounts'],
            ['t' => 'basics', 'q' => 'Should I trade the news?', 'k' => ['news', 'nfp', 'cpi', 'fomc', 'high impact', 'economic calendar'],
             'a' => ['High-impact news (CPI, NFP, rate decisions) can widen spreads and jump past stops.', 'Many traders avoid entering a few minutes before and after. Decide your rule and write it down.', 'Tag news trades in your journal to see whether they help or hurt you.']],

            // ---------------- Tools & concepts ----------------
            ['t' => 'tools', 'q' => 'What is VWAP?', 'k' => ['vwap', 'volume weighted', 'average price'],
             'a' => ['VWAP is the session\'s **average price weighted by volume**. Many institutions use it as an execution benchmark.', 'Above VWAP, buyers have had the upper hand that day; below, sellers.', 'After a big stretch away, price often returns to test it. Often, not always.']],
            ['t' => 'tools', 'q' => 'What is Volume Profile (POC, VAH, VAL)?', 'k' => ['volume profile', 'poc', 'vah', 'val', 'value area', 'point of control', 'lvn', 'hvn'],
             'a' => ['Volume Profile shows **how much traded at each price**.', '**POC** = the price with most volume. **Value area (VAH–VAL)** = where about 70% of volume traded.', 'Low-volume areas are often crossed quickly; high-volume areas tend to act as support or resistance.']],
            ['t' => 'tools', 'q' => 'What is support and resistance?', 'k' => ['support', 'resistance', 'levels', 'key level', 'supply', 'demand'],
             'a' => ['Support is a price area where buying has repeatedly appeared; resistance is where selling has.', 'Treat them as **zones**, not exact lines, and wait for a reaction instead of predicting one.', 'Tag trades by level type in your journal to see which levels work for you.']],
            ['t' => 'tools', 'q' => 'What is a liquidity sweep?', 'k' => ['liquidity', 'sweep', 'stop hunt', 'liquidity grab', 'smc', 'ict'],
             'a' => ['Price briefly runs beyond an obvious high or low (where many stops sit), then reverses.', 'It is why stops placed exactly at obvious levels often get hit before the move.', 'If it happens often in your journal, place stops beyond structure with a buffer.']],
            ['t' => 'tools', 'q' => 'How many trades do I need before judging a strategy?', 'k' => ['sample size', 'how many trades', 'backtest', 'backtesting', 'test strategy', 'judge a strategy'],
             'a' => ['At least **50–100 trades** with the same rules before deciding.', 'Backtest first, then forward-test at small size, logging every trade the same way.', 'Changing rules every few trades means you never learn what works.'], 'link' => '/terminal/strategies'],

            // ---------------- Using journzey ----------------
            ['t' => 'app', 'q' => 'What is the Edge Matrix?', 'k' => ['edge matrix', 'best window', 'anti window', 'anti-window', 'blow up radar', 'ruin', 'audit'],
             'a' => ['Edge Matrix finds patterns in your own trades: **best window, anti-window, last-month audit (keep doing / cut out)** and **blow-up risk**.', 'It needs a few weeks of logged trades to be meaningful.'], 'link' => '/terminal/edge'],
            ['t' => 'app', 'q' => 'How do I log a trade quickly?', 'k' => ['quick log', 'log a trade', 'add trade', 'new trade', 'how to log'],
             'a' => ['Use **Quick Log**: instrument → buy/sell → lot size → prices on the scroll wheel. Date, time and session fill in automatically.', 'Add setup, emotions and notes later; the Trade Log reminds you.'], 'link' => '/terminal'],
            ['t' => 'app', 'q' => 'How do I use the University?', 'k' => ['university', 'course', 'learn', 'lessons', 'strategies to learn', 'playbook'],
             'a' => ['The University has strategy lessons and a mini-course from beginner to intermediate.', 'Pick one strategy, learn its rules, and log every trade with that strategy tag to measure it.'], 'link' => '/terminal/university'],
        ];
    }
}
