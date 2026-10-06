/**
 * Domain enumerations shared by the API and the web client.
 * Keep labels out of here — user-facing strings live in the web i18n resources.
 */

export const ASSET_CLASSES = ['METALS', 'FOREX', 'INDICES', 'CRYPTO', 'COMMODITIES'] as const;
export type AssetClass = (typeof ASSET_CLASSES)[number];

/** Markets a user can pick during onboarding (maps 1:1 to asset classes). */
export const PRIMARY_MARKETS = ASSET_CLASSES;

export const SIDES = ['LONG', 'SHORT'] as const;
export type Side = (typeof SIDES)[number];

export const TRADE_STATUSES = ['OPEN', 'CLOSED'] as const;
export type TradeStatus = (typeof TRADE_STATUSES)[number];

export const TRADING_SESSIONS = ['ASIA', 'LONDON', 'LONDON_NY_OVERLAP', 'NEW_YORK', 'OFF_HOURS'] as const;
export type TradingSession = (typeof TRADING_SESSIONS)[number];

export const EMOTIONS = [
  'CALM',
  'FOCUSED',
  'CONFIDENT',
  'NEUTRAL',
  'ANXIOUS',
  'FEARFUL',
  'GREEDY',
  'FOMO',
  'REVENGE',
  'FRUSTRATED',
  'BORED',
  'TIRED',
  'EUPHORIC',
] as const;
export type Emotion = (typeof EMOTIONS)[number];

/** Emotions considered "emotional trading" for disciplined-vs-emotional comparisons. */
export const NEGATIVE_EMOTIONS: readonly Emotion[] = [
  'ANXIOUS',
  'FEARFUL',
  'GREEDY',
  'FOMO',
  'REVENGE',
  'FRUSTRATED',
  'BORED',
  'TIRED',
  'EUPHORIC',
];

export const MISTAKE_TAGS = [
  'NONE',
  'FOMO_ENTRY',
  'REVENGE_TRADE',
  'MOVED_STOP',
  'NO_STOP',
  'OVERSIZED',
  'EARLY_EXIT',
  'LATE_ENTRY',
  'CHASING',
  'IGNORED_PLAN',
  'OVERTRADING',
  'NEWS_GAMBLE',
] as const;
export type MistakeTag = (typeof MISTAKE_TAGS)[number];

/**
 * How the Discipline Leak Mirror treats each mistake when building the
 * rule-compliant ("flawless execution") hypothetical:
 *  - SKIP:     a rule-compliant trader would not have taken the trade -> hypothetical P&L 0
 *  - CAP_LOSS: the trade is kept but a loss is capped at the planned 1R risk
 *  - ACTUAL:   no defensible hypothetical exists -> actual P&L is kept (no invented upside)
 */
export const MISTAKE_LEAK_TREATMENT: Record<MistakeTag, 'SKIP' | 'CAP_LOSS' | 'ACTUAL'> = {
  NONE: 'ACTUAL',
  FOMO_ENTRY: 'SKIP',
  REVENGE_TRADE: 'SKIP',
  CHASING: 'SKIP',
  IGNORED_PLAN: 'SKIP',
  OVERTRADING: 'SKIP',
  NEWS_GAMBLE: 'SKIP',
  MOVED_STOP: 'CAP_LOSS',
  NO_STOP: 'CAP_LOSS',
  OVERSIZED: 'CAP_LOSS',
  EARLY_EXIT: 'ACTUAL',
  LATE_ENTRY: 'ACTUAL',
};

export const TRADE_SOURCES = ['MANUAL', 'QUICK_COMMAND', 'CSV_IMPORT', 'WEBHOOK', 'BROKER_API', 'DEMO'] as const;
export type TradeSource = (typeof TRADE_SOURCES)[number];

export const ACCOUNT_TYPES = ['PERSONAL', 'PROP_CHALLENGE', 'PROP_FUNDED', 'OTHER'] as const;
export type AccountType = (typeof ACCOUNT_TYPES)[number];

export const CURRENCIES = ['USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'CHF', 'INR', 'SGD', 'AED', 'BRL', 'CNY', 'RUB'] as const;
export type Currency = (typeof CURRENCIES)[number];

export const CAPITAL_TRANSACTION_TYPES = ['DEPOSIT', 'WITHDRAWAL', 'ADJUSTMENT'] as const;
export type CapitalTransactionType = (typeof CAPITAL_TRANSACTION_TYPES)[number];

export const THEMES = ['clean-light', 'dark-terminal', 'cyberpunk-slate', 'midnight-navy'] as const;
export type Theme = (typeof THEMES)[number];

export const LANGUAGES = ['en', 'ru', 'zh', 'pt'] as const;
export type Language = (typeof LANGUAGES)[number];

export const VOICE_LANGUAGES: Record<Language, string> = {
  en: 'en-US',
  ru: 'ru-RU',
  zh: 'zh-CN',
  pt: 'pt-BR',
};

export const CHECKLIST_ITEMS = [
  { key: 'news_checked', phase: 'PRE_MARKET' },
  { key: 'levels_marked', phase: 'PRE_MARKET' },
  { key: 'loss_budget_defined', phase: 'PRE_MARKET' },
  { key: 'mental_state_verified', phase: 'PRE_MARKET' },
  { key: 'candle_confirmation', phase: 'IN_TRADE' },
  { key: 'hard_stop_placed', phase: 'IN_TRADE' },
  { key: 'stop_not_moved', phase: 'IN_TRADE' },
  { key: 'journal_completed', phase: 'POST_MARKET' },
  { key: 'capital_updated', phase: 'POST_MARKET' },
] as const;
export type ChecklistItemKey = (typeof CHECKLIST_ITEMS)[number]['key'];
export const CHECKLIST_KEYS = CHECKLIST_ITEMS.map((i) => i.key) as unknown as readonly [
  ChecklistItemKey,
  ...ChecklistItemKey[],
];

export const STRATEGY_TEMPLATES = [
  {
    name: 'Volume Profile',
    description: 'Trade reactions at high/low volume nodes of the session or composite profile.',
    targetRr: '2.00',
    checklist: ['Profile anchored to correct session', 'Price at HVN/LVN edge', 'Order-flow confirmation'],
  },
  {
    name: 'VAL Bounce',
    description: 'Long from the value area low after acceptance back inside value.',
    targetRr: '3.00',
    checklist: ['Price rejected below VAL', 'Re-entry into value confirmed', 'Target POC / VAH'],
  },
  {
    name: 'VAH Rejection',
    description: 'Short from the value area high after failed auction above value.',
    targetRr: '3.00',
    checklist: ['Failed auction above VAH', 'Re-entry into value confirmed', 'Target POC / VAL'],
  },
  {
    name: 'POC',
    description: 'Point-of-control magnet and rotation trades.',
    targetRr: '2.00',
    checklist: ['POC identified', 'Rotation context confirmed', 'Stop beyond value edge'],
  },
  {
    name: 'ICT Silver Bullet',
    description: 'Time-based FVG entry inside the 10:00–11:00 New York window.',
    targetRr: '2.50',
    checklist: ['Inside silver-bullet window', 'Liquidity draw identified', 'FVG entry with displacement'],
  },
  {
    name: 'Fair Value Gap',
    description: 'Entry on the retrace into an imbalance created by displacement.',
    targetRr: '2.00',
    checklist: ['Displacement candle present', 'Retrace into FVG', 'HTF bias aligned'],
  },
  {
    name: 'Order Block',
    description: 'Entry at the last opposing candle before a break of structure.',
    targetRr: '2.50',
    checklist: ['Break of structure', 'Unmitigated order block', 'Stop beyond block'],
  },
  {
    name: 'Liquidity Sweep',
    description: 'Fade the stop-run beyond obvious highs/lows after reclaim.',
    targetRr: '3.00',
    checklist: ['Clear liquidity pool', 'Sweep and reclaim', 'Market structure shift'],
  },
  {
    name: 'Trend Following',
    description: 'Pullback continuation entries in the direction of the higher-timeframe trend.',
    targetRr: '2.00',
    checklist: ['HTF trend defined', 'Pullback to dynamic support', 'Continuation trigger'],
  },
] as const;

export const DEFAULT_TILT_RULE = { lossCount: 3, windowMinutes: 20, cooldownMinutes: 30 } as const;

export const MAX_SCREENSHOT_BYTES = 5 * 1024 * 1024;
export const ALLOWED_SCREENSHOT_MIME = ['image/png', 'image/jpeg', 'image/webp'] as const;
export const ALLOWED_SCREENSHOT_EXT = ['png', 'jpg', 'jpeg', 'webp'] as const;
