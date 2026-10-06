import type {
  AccountType,
  CapitalTransactionType,
  Emotion,
  Language,
  MistakeTag,
  Side,
  Theme,
  TradeSource,
  TradeStatus,
  TradingSession,
} from './constants.js';
import type { DisciplineLeakResult, EquityPoint, MonteCarloResult, PerformanceSummary, TiltStatus } from './analytics.js';

/** Standardised API error envelope. */
export interface ApiErrorBody {
  error: {
    code: string;
    message: string;
    fields?: Record<string, string[]>;
    requestId?: string;
  };
}

export interface Paginated<T> {
  items: T[];
  page: number;
  pageSize: number;
  total: number;
  totalPages: number;
}

export interface UserDto {
  id: string;
  email: string;
  name: string | null;
  avatarUrl: string | null;
  emailVerified: boolean;
  onboarded: boolean;
  createdAt: string;
  lastLoginAt: string | null;
}

export interface SettingsDto {
  theme: Theme;
  language: Language;
  timezone: string;
  baseCurrency: string;
  defaultRiskPercentage: string;
  maxDailyLoss: string | null;
  maxWeeklyLoss: string | null;
  defaultTargetRr: string;
  primaryMarkets: string[];
  activeAccountScope: string;
  tiltLossCount: number;
  tiltWindowMinutes: number;
  tiltCooldownMinutes: number;
  terminalLockUntil: string | null;
}

export interface MeResponse {
  user: UserDto;
  settings: SettingsDto | null;
  features: FeatureFlags;
}

export interface FeatureFlags {
  googleAuth: boolean;
  devLogin: boolean;
  ai: boolean;
  marketData: boolean;
  storage: 'local' | 's3';
}

export interface TradingAccountDto {
  id: string;
  accountName: string;
  brokerName: string | null;
  accountType: AccountType;
  currency: string;
  startingCapital: string;
  currentCapital: string;
  demo: boolean;
  archived: boolean;
  createdAt: string;
  tradeCount?: number;
}

export interface TradeDto {
  id: string;
  tradingAccountId: string;
  executedAt: string;
  closedAt: string | null;
  symbol: string;
  assetClass: string;
  side: Side;
  status: TradeStatus;
  entryPrice: string;
  exitPrice: string | null;
  stopLoss: string | null;
  takeProfit: string | null;
  lotSize: string;
  pnl: string | null;
  fees: string;
  rr: string | null;
  riskAmount: string | null;
  strategyId: string | null;
  strategyName: string | null;
  setupTag: string | null;
  session: TradingSession | null;
  emotion: Emotion | null;
  mistakeTag: MistakeTag | null;
  rulesFollowed: boolean;
  notes: string | null;
  hasScreenshot: boolean;
  source: TradeSource;
  brokerTradeId: string | null;
  demo: boolean;
  currency: string;
  createdAt: string;
  updatedAt: string;
}

export interface StrategyDto {
  id: string;
  name: string;
  description: string | null;
  targetRr: string | null;
  checklist: string[];
  active: boolean;
  createdAt: string;
}

export interface JournalEntryDto {
  id: string;
  journalDate: string;
  compliance: number | null;
  emotionalState: Emotion | null;
  disciplineRating: number | null;
  reflection: string | null;
  keyLesson: string | null;
  voiceTranscript: string | null;
  voiceLanguage: Language | null;
  demo: boolean;
  createdAt: string;
  updatedAt: string;
}

export interface CapitalTransactionDto {
  id: string;
  tradingAccountId: string;
  type: CapitalTransactionType;
  amount: string;
  note: string | null;
  occurredAt: string;
}

export interface GroupStat {
  key: string;
  label?: string;
  summary: PerformanceSummary;
}

export interface DashboardResponse {
  scope: { account: string; from: string | null; to: string | null; currency: string; demo: boolean };
  summary: PerformanceSummary;
  equity: { points: EquityPoint[]; maxDrawdown: string; maxDrawdownPct: number; startingCapital: string };
  byWeekday: GroupStat[];
  bySession: GroupStat[];
  byInstrument: GroupStat[];
  byStrategy: GroupStat[];
  tilt: TiltStatus;
  today: { pnl: string; trades: number; remainingDailyBudget: string | null; maxDailyLoss: string | null };
}

export interface CalendarDay {
  date: string;
  pnl: string;
  trades: number;
  wins: number;
  losses: number;
}

export interface CalendarResponse {
  month: string;
  days: CalendarDay[];
  weeks: Array<{ weekStart: string; pnl: string; trades: number; wins: number; losses: number }>;
  monthTotal: { pnl: string; trades: number };
  currency: string;
}

export interface StrategyAnalyticsRow {
  strategyId: string | null;
  name: string;
  targetRr: string | null;
  summary: PerformanceSummary;
}

export interface EdgeMatrixResponse {
  period: { from: string; to: string; label: string };
  byStrategy: GroupStat[];
  bySession: GroupStat[];
  byInstrument: GroupStat[];
  byHour: GroupStat[];
  byMistake: GroupStat[];
  byEmotion: GroupStat[];
  bestWindow: {
    hour: GroupStat | null;
    session: GroupStat | null;
    instrument: GroupStat | null;
    setup: GroupStat | null;
    weekday: GroupStat | null;
    note: string;
  };
  disciplinedVsEmotional: { disciplined: PerformanceSummary; emotional: PerformanceSummary };
}

export type { DisciplineLeakResult, MonteCarloResult, PerformanceSummary, TiltStatus, EquityPoint };

export interface RiskOfRuinResponse {
  inputs: {
    winRate: number;
    avgWinR: number;
    avgLossR: number;
    riskPercent: number;
    tradesPerPath: number;
    sampleSize: number;
    tradesPerWeek: number;
  };
  result: MonteCarloResult | null;
  insufficientData: boolean;
}

export interface BrokerProviderInfo {
  id: string;
  name: string;
  status: 'AVAILABLE' | 'BETA' | 'CONFIGURATION_REQUIRED' | 'COMING_SOON' | 'UNSUPPORTED';
  method: string;
  description: string;
  connectable: boolean;
}

export interface BrokerConnectionDto {
  id: string;
  provider: string;
  label: string;
  status: string;
  tradingAccountId: string | null;
  credentialHint: string | null;
  lastSyncedAt: string | null;
  lastError: string | null;
  webhookUrl: string | null;
  createdAt: string;
}

export interface MarketQuote {
  symbol: string;
  displayName: string;
  price: string | null;
  change: string | null;
  changePercent: string | null;
  asOf: string | null;
  error?: string;
}

export interface MarketQuotesResponse {
  source: 'live' | 'demo';
  provider: string | null;
  quotes: MarketQuote[];
}

export interface AiMessageDto {
  id: string;
  role: 'user' | 'assistant';
  content: string;
  createdAt: string;
}

export interface AiConversationDto {
  id: string;
  title: string;
  language: Language;
  updatedAt: string;
}

export interface PerformanceReview {
  period: 'week' | 'month';
  label: string;
  from: string;
  to: string;
  currency: string;
  summary: PerformanceSummary;
  bestStrategy: GroupStat | null;
  worstStrategy: GroupStat | null;
  bestSession: GroupStat | null;
  disciplineRate: number | null;
  leak: DisciplineLeakResult;
  topMistakes: GroupStat[];
  journal: { entries: number; avgDiscipline: number | null; commonEmotions: Array<{ emotion: string; count: number }>; lessons: string[] };
  riskObservations: string[];
  actionPlan: string[];
  text: string;
}
