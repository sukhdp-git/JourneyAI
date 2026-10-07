<?php
declare(strict_types=1);

namespace App\Trading;

/** Domain enumerations and reference data shared by the terminal, analytics and imports. */
final class Domain
{
    public const ASSET_CLASSES = ['METALS' => 'Gold & metals', 'FOREX' => 'Forex', 'INDICES' => 'Indices', 'CRYPTO' => 'Crypto', 'COMMODITIES' => 'Commodities'];
    public const SIDES = ['LONG', 'SHORT'];
    public const SESSIONS = ['ASIA' => 'Asia', 'LONDON' => 'London', 'LONDON_NY_OVERLAP' => 'London/NY overlap', 'NEW_YORK' => 'New York', 'OFF_HOURS' => 'Off-hours'];
    public const EMOTIONS = ['CALM', 'FOCUSED', 'CONFIDENT', 'NEUTRAL', 'PATIENT', 'DISCIPLINED', 'ANXIOUS', 'FEARFUL', 'GREEDY', 'FOMO', 'REVENGE', 'FRUSTRATED', 'BORED', 'TIRED', 'EUPHORIC', 'OVERCONFIDENT', 'IMPULSIVE'];
    public const NEGATIVE_EMOTIONS = ['ANXIOUS', 'FEARFUL', 'GREEDY', 'FOMO', 'REVENGE', 'FRUSTRATED', 'BORED', 'TIRED', 'EUPHORIC', 'OVERCONFIDENT', 'IMPULSIVE'];
    public const MISTAKES = [
        'NONE' => 'None', 'FOMO_ENTRY' => 'FOMO entry', 'REVENGE_TRADE' => 'Revenge trade', 'MOVED_STOP' => 'Moved stop', 'NO_STOP' => 'No stop',
        'OVERSIZED' => 'Oversized', 'EARLY_EXIT' => 'Early exit', 'LATE_ENTRY' => 'Late entry', 'CHASING' => 'Chasing', 'IGNORED_PLAN' => 'Ignored plan',
        'OVERTRADING' => 'Overtrading', 'NEWS_GAMBLE' => 'News gamble', 'IMPULSIVE_ENTRY' => 'Impulsive entry',
    ];
    /** How the Discipline Leak Mirror re-scores each violation (never invents upside). */
    public const LEAK_TREATMENT = [
        'NONE' => 'ACTUAL', 'FOMO_ENTRY' => 'SKIP', 'REVENGE_TRADE' => 'SKIP', 'CHASING' => 'SKIP', 'IGNORED_PLAN' => 'SKIP', 'OVERTRADING' => 'SKIP',
        'NEWS_GAMBLE' => 'SKIP', 'IMPULSIVE_ENTRY' => 'SKIP', 'MOVED_STOP' => 'CAP_LOSS', 'NO_STOP' => 'CAP_LOSS', 'OVERSIZED' => 'CAP_LOSS', 'EARLY_EXIT' => 'ACTUAL', 'LATE_ENTRY' => 'ACTUAL',
    ];
    public const SOURCES = ['MANUAL', 'QUICK_COMMAND', 'CSV_IMPORT', 'WEBHOOK', 'DEMO'];
    public const ACCOUNT_TYPES = ['PERSONAL' => 'Live equity account', 'PROP_CHALLENGE' => 'Prop firm — challenge', 'PROP_FUNDED' => 'Prop firm — funded', 'OTHER' => 'Other / custom account'];
    /** Behaviour groups used by the Discipline Leak counter and negative-trap analysis. */
    public const BEHAVIOURS = [
        'FOMO' => ['FOMO', ['FOMO_ENTRY'], ['FOMO']],
        'REVENGE' => ['Revenge trading', ['REVENGE_TRADE'], ['REVENGE']],
        'OVERTRADING' => ['Overtrading', ['OVERTRADING'], []],
        'RULES' => ['Rule breaking', ['IGNORED_PLAN'], []],
        'CHASING' => ['Chasing', ['CHASING'], []],
        'MOVED_STOP' => ['Moving stop loss', ['MOVED_STOP', 'NO_STOP'], []],
        'IMPULSIVE' => ['Impulsive entry', ['IMPULSIVE_ENTRY', 'LATE_ENTRY', 'NEWS_GAMBLE'], ['IMPULSIVE']],
    ];
    /** Daily psychology journal emotional states. */
    public const JOURNAL_EMOTIONS = ['DISCIPLINED' => 'Disciplined', 'FOCUSED' => 'Focused', 'CALM' => 'Calm', 'PATIENT' => 'Patient', 'ANXIOUS' => 'Anxious',
        'FOMO' => 'FOMO', 'REVENGE' => 'Revenge', 'OVERCONFIDENT' => 'Overconfident', 'FRUSTRATED' => 'Frustrated', 'IMPULSIVE' => 'Impulsive'];
    public const NEGATIVE_JOURNAL_EMOTIONS = ['ANXIOUS', 'FOMO', 'REVENGE', 'OVERCONFIDENT', 'FRUSTRATED', 'IMPULSIVE'];
    public const STRATEGY_STYLES = ['SMC' => 'SMC', 'ICT' => 'ICT', 'ORDER_FLOW' => 'Order Flow', 'PRICE_ACTION' => 'Price Action', 'SUPPLY_DEMAND' => 'Supply & Demand',
        'VOLUME_PROFILE' => 'Volume Profile', 'SCALPING' => 'Scalping', 'BREAKOUT' => 'Breakout', 'TREND_FOLLOWING' => 'Trend Following', 'MEAN_REVERSION' => 'Mean Reversion', 'CUSTOM' => 'Custom'];
    /** Analytics session buckets (DST-aware, see Sessions::bucket). */
    public const SESSION_BUCKETS = ['ASIAN' => 'Asian', 'LONDON' => 'London', 'NEW_YORK' => 'New York'];
    public const CURRENCIES = ['USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'CHF', 'INR', 'SGD', 'AED', 'BRL', 'CNY', 'RUB'];
    public const THEMES = ['dark-terminal' => 'Dark Terminal', 'clean-light' => 'Clean Light', 'cyberpunk-slate' => 'Cyberpunk Slate', 'midnight-navy' => 'Midnight Navy'];
    public const LANGUAGES = ['en' => 'English', 'ru' => 'Русский', 'zh' => '简体中文', 'pt' => 'Português'];
    public const VOICE_LANGUAGES = ['en' => 'en-US', 'ru' => 'ru-RU', 'zh' => 'zh-CN', 'pt' => 'pt-BR'];
    public const CHECKLIST = [
        'PRE_MARKET' => ['news_checked' => 'Economic news checked', 'levels_marked' => 'Key levels marked', 'loss_budget_defined' => 'Daily loss budget defined', 'mental_state_verified' => 'Mental state verified'],
        'IN_TRADE' => ['candle_confirmation' => 'Candle confirmation', 'hard_stop_placed' => 'Hard stop placed', 'stop_not_moved' => 'Stop not emotionally moved'],
        'POST_MARKET' => ['journal_completed' => 'Journal completed', 'capital_updated' => 'Capital management updated'],
    ];
    public const STRATEGY_TEMPLATES = [
        ['Volume Profile', 'Trade reactions at high/low volume nodes of the session or composite profile.', '2.00', ['Profile anchored to correct session', 'Price at HVN/LVN edge', 'Order-flow confirmation']],
        ['VAL Bounce', 'Long from the value area low after acceptance back inside value.', '3.00', ['Price rejected below VAL', 'Re-entry into value confirmed', 'Target POC / VAH']],
        ['VAH Rejection', 'Short from the value area high after a failed auction above value.', '3.00', ['Failed auction above VAH', 'Re-entry into value confirmed', 'Target POC / VAL']],
        ['POC', 'Point-of-control magnet and rotation trades.', '2.00', ['POC identified', 'Rotation context confirmed', 'Stop beyond value edge']],
        ['ICT Silver Bullet', 'Time-based FVG entry inside the 10:00–11:00 New York window.', '2.50', ['Inside silver-bullet window', 'Liquidity draw identified', 'FVG entry with displacement']],
        ['Fair Value Gap', 'Entry on the retrace into an imbalance created by displacement.', '2.00', ['Displacement candle present', 'Retrace into FVG', 'HTF bias aligned']],
        ['Order Block', 'Entry at the last opposing candle before a break of structure.', '2.50', ['Break of structure', 'Unmitigated order block', 'Stop beyond block']],
        ['Liquidity Sweep', 'Fade the stop-run beyond obvious highs/lows after reclaim.', '3.00', ['Clear liquidity pool', 'Sweep and reclaim', 'Market structure shift']],
        ['Trend Following', 'Pullback continuation entries in the direction of the higher-timeframe trend.', '2.00', ['HTF trend defined', 'Pullback to dynamic support', 'Continuation trigger']],
    ];
    /** [quote, attribution, theme]. Unattributed sayings are labelled as trading proverbs. */
    public const QUOTES = [
        ['The goal of a successful trader is to make the best trades. Money is secondary.', 'Alexander Elder', 'Execution'],
        ['Risk comes from not knowing what you are doing.', 'Warren Buffett', 'Risk Management'],
        ['The market can stay irrational longer than you can stay solvent.', 'Attributed to J. M. Keynes', 'Risk Management'],
        ['Cut your losses short and let your winners run.', 'Trading proverb', 'Execution'],
        ['Plan the trade, trade the plan.', 'Trading proverb', 'Discipline'],
        ['Amateurs think about how much they can make. Professionals think about how much they can lose.', 'Trading proverb', 'Risk Management'],
        ['A losing trade that followed your rules is a good trade.', 'Trading proverb', 'Discipline'],
        ['No setup is also a position. Waiting is part of the job.', 'Trading proverb', 'Patience'],
        ['Protect your capital first. Opportunity returns; lost capital may not.', 'Trading proverb', 'Risk Management'],
        ['Your edge only shows up over many trades. Judge the process, not one result.', 'Trading proverb', 'Consistency'],
        ['The urge to win it back is the most expensive emotion in trading.', 'Trading proverb', 'Psychology'],
        ['Small, repeatable risk beats occasional heroics.', 'Trading proverb', 'Consistency'],
        ['Let price come to your level. Chasing is paying for impatience.', 'Trading proverb', 'Patience'],
        ['Write the stop before you enter. Decide the loss while you are calm.', 'Trading proverb', 'Execution'],
        ['Boredom is not a signal. Neither is fear of missing out.', 'Trading proverb', 'Psychology'],
        ['Consistency is a hundred ordinary days, not one extraordinary one.', 'Trading proverb', 'Consistency'],
    ];

    public static function label(string $code): string
    {
        return self::MISTAKES[$code] ?? self::SESSIONS[$code] ?? ucfirst(strtolower(str_replace('_', ' ', $code)));
    }
}
