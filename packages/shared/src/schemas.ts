import { z } from 'zod';
import {
  ACCOUNT_TYPES,
  CAPITAL_TRANSACTION_TYPES,
  CHECKLIST_KEYS,
  CURRENCIES,
  EMOTIONS,
  LANGUAGES,
  MISTAKE_TAGS,
  PRIMARY_MARKETS,
  SIDES,
  THEMES,
  TRADE_STATUSES,
  TRADING_SESSIONS,
} from './constants.js';
import { d } from './calc.js';
import { getInstrument } from './instruments.js';
import { isValidTimeZone } from './sessions.js';

/** Accepts a number or numeric string and normalises it to a canonical decimal string. */
export const decimalString = (opts: { positive?: boolean; nonNegative?: boolean; max?: number } = {}) =>
  z
    .union([z.string().trim(), z.number()])
    .transform((v, ctx) => {
      const s = typeof v === 'number' ? String(v) : v;
      if (!/^-?\d{1,14}(\.\d{1,10})?$/.test(s)) {
        ctx.addIssue({ code: z.ZodIssueCode.custom, message: 'Must be a decimal number' });
        return z.NEVER;
      }
      const n = d(s);
      if (opts.positive && n.lte(0)) {
        ctx.addIssue({ code: z.ZodIssueCode.custom, message: 'Must be greater than zero' });
        return z.NEVER;
      }
      if (opts.nonNegative && n.lt(0)) {
        ctx.addIssue({ code: z.ZodIssueCode.custom, message: 'Must not be negative' });
        return z.NEVER;
      }
      if (opts.max !== undefined && n.gt(opts.max)) {
        ctx.addIssue({ code: z.ZodIssueCode.custom, message: `Must be at most ${opts.max}` });
        return z.NEVER;
      }
      return n.toString();
    });

const optionalDecimal = (opts?: Parameters<typeof decimalString>[0]) =>
  z.union([decimalString(opts), z.null(), z.literal('').transform(() => null)]).optional();

export const uuid = z.string().uuid();
const isoDate = z.string().regex(/^\d{4}-\d{2}-\d{2}$/, 'Expected YYYY-MM-DD');
const isoDateTime = z
  .string()
  .datetime({ offset: true })
  .or(z.string().regex(/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?$/));

export const symbolSchema = z
  .string()
  .trim()
  .toUpperCase()
  .refine((s) => !!getInstrument(s), 'Unsupported instrument');

export const timezoneSchema = z.string().max(64).refine(isValidTimeZone, 'Invalid IANA time zone');

const tradeCore = {
  tradingAccountId: uuid,
  executedAt: isoDateTime,
  closedAt: isoDateTime.nullable().optional(),
  symbol: symbolSchema,
  side: z.enum(SIDES),
  entryPrice: decimalString({ positive: true }),
  exitPrice: optionalDecimal({ positive: true }),
  stopLoss: optionalDecimal({ positive: true }),
  takeProfit: optionalDecimal({ positive: true }),
  lotSize: decimalString({ positive: true, max: 100000 }),
  /** Optional broker-reported net P&L; overrides the computed value when provided. */
  pnl: optionalDecimal(),
  fees: optionalDecimal({ nonNegative: true }),
  strategyId: uuid.nullable().optional(),
  setupTag: z.string().trim().max(120).nullable().optional(),
  session: z.enum(TRADING_SESSIONS).nullable().optional(),
  emotion: z.enum(EMOTIONS).nullable().optional(),
  mistakeTag: z.enum(MISTAKE_TAGS).nullable().optional(),
  rulesFollowed: z.boolean().default(true),
  notes: z.string().max(5000).nullable().optional(),
};

type StopCheck = { side: 'LONG' | 'SHORT'; entryPrice?: string; stopLoss?: string | null; takeProfit?: string | null };
const stopConsistency = (t: StopCheck, ctx: z.RefinementCtx) => {
  if (!t.entryPrice) return;
  const e = d(t.entryPrice);
  if (t.stopLoss) {
    const s = d(t.stopLoss);
    if (s.eq(e)) ctx.addIssue({ code: z.ZodIssueCode.custom, path: ['stopLoss'], message: 'Stop loss cannot equal entry' });
    else if (t.side === 'LONG' && s.gt(e))
      ctx.addIssue({ code: z.ZodIssueCode.custom, path: ['stopLoss'], message: 'Long stop loss must be below entry' });
    else if (t.side === 'SHORT' && s.lt(e))
      ctx.addIssue({ code: z.ZodIssueCode.custom, path: ['stopLoss'], message: 'Short stop loss must be above entry' });
  }
  if (t.takeProfit) {
    const tp = d(t.takeProfit);
    if (t.side === 'LONG' && tp.lte(e))
      ctx.addIssue({ code: z.ZodIssueCode.custom, path: ['takeProfit'], message: 'Long take profit must be above entry' });
    if (t.side === 'SHORT' && tp.gte(e))
      ctx.addIssue({ code: z.ZodIssueCode.custom, path: ['takeProfit'], message: 'Short take profit must be below entry' });
  }
};

export const tradeCreateSchema = z.object(tradeCore).strict().superRefine(stopConsistency);
export type TradeCreateInput = z.input<typeof tradeCreateSchema>;
export type TradeCreate = z.output<typeof tradeCreateSchema>;

export const tradeUpdateSchema = z
  .object(Object.fromEntries(Object.entries(tradeCore).map(([k, v]) => [k, v.optional()])) as {
    [K in keyof typeof tradeCore]: z.ZodOptional<(typeof tradeCore)[K]>;
  })
  .strict();
export type TradeUpdate = z.output<typeof tradeUpdateSchema>;

export const quickTradeSchema = z
  .object({
    command: z.string().trim().min(3).max(300),
    tradingAccountId: uuid,
    executedAt: isoDateTime.optional(),
  })
  .strict();

export const SORTABLE_TRADE_FIELDS = ['executedAt', 'symbol', 'pnl', 'rr', 'lotSize', 'side'] as const;

export const tradeListQuerySchema = z.object({
  page: z.coerce.number().int().min(1).default(1),
  pageSize: z.coerce.number().int().min(1).max(200).default(25),
  sort: z.enum(SORTABLE_TRADE_FIELDS).default('executedAt'),
  order: z.enum(['asc', 'desc']).default('desc'),
  search: z.string().trim().max(100).optional(),
  account: z.string().max(40).optional(),
  strategyId: uuid.optional(),
  session: z.enum(TRADING_SESSIONS).optional(),
  symbol: z.string().trim().toUpperCase().max(20).optional(),
  side: z.enum(SIDES).optional(),
  outcome: z.enum(['WIN', 'LOSS', 'BREAKEVEN']).optional(),
  status: z.enum(TRADE_STATUSES).optional(),
  from: isoDate.optional(),
  to: isoDate.optional(),
});
export type TradeListQuery = z.infer<typeof tradeListQuerySchema>;

/** Shared analytics filter: account scope + date range (dates in the user's time zone). */
export const analyticsQuerySchema = z.object({
  account: z.string().max(40).optional(),
  from: isoDate.optional(),
  to: isoDate.optional(),
});

export const accountScopeSchema = z.union([z.literal('real'), z.literal('demo'), z.literal('all'), uuid]);

export const tradingAccountCreateSchema = z
  .object({
    accountName: z.string().trim().min(1).max(80),
    brokerName: z.string().trim().max(80).nullable().optional(),
    accountType: z.enum(ACCOUNT_TYPES).default('PERSONAL'),
    currency: z.enum(CURRENCIES).default('USD'),
    startingCapital: decimalString({ nonNegative: true, max: 1e12 }),
    demo: z.boolean().default(false),
  })
  .strict();

export const tradingAccountUpdateSchema = z
  .object({
    accountName: z.string().trim().min(1).max(80).optional(),
    brokerName: z.string().trim().max(80).nullable().optional(),
    accountType: z.enum(ACCOUNT_TYPES).optional(),
    startingCapital: decimalString({ nonNegative: true, max: 1e12 }).optional(),
    archived: z.boolean().optional(),
  })
  .strict();

export const capitalTransactionSchema = z
  .object({
    type: z.enum(CAPITAL_TRANSACTION_TYPES),
    amount: decimalString({ max: 1e12 }),
    note: z.string().trim().max(500).nullable().optional(),
    occurredAt: isoDateTime.optional(),
  })
  .strict()
  .superRefine((v, ctx) => {
    if (v.type !== 'ADJUSTMENT' && d(v.amount).lte(0))
      ctx.addIssue({ code: z.ZodIssueCode.custom, path: ['amount'], message: 'Amount must be positive' });
    if (v.type === 'ADJUSTMENT' && d(v.amount).isZero())
      ctx.addIssue({ code: z.ZodIssueCode.custom, path: ['amount'], message: 'Adjustment cannot be zero' });
  });

export const strategyCreateSchema = z
  .object({
    name: z.string().trim().min(1).max(80),
    description: z.string().trim().max(1000).nullable().optional(),
    targetRr: optionalDecimal({ positive: true, max: 100 }),
    checklist: z.array(z.string().trim().min(1).max(200)).max(20).default([]),
    active: z.boolean().default(true),
  })
  .strict();
export const strategyUpdateSchema = strategyCreateSchema.partial().strict();

export const journalUpsertSchema = z
  .object({
    journalDate: isoDate,
    compliance: z.number().int().min(1).max(5).nullable().optional(),
    emotionalState: z.enum(EMOTIONS).nullable().optional(),
    disciplineRating: z.number().int().min(1).max(10).nullable().optional(),
    reflection: z.string().max(10000).nullable().optional(),
    keyLesson: z.string().max(1000).nullable().optional(),
    voiceTranscript: z.string().max(20000).nullable().optional(),
    voiceLanguage: z.enum(LANGUAGES).nullable().optional(),
  })
  .strict();
export const journalUpdateSchema = journalUpsertSchema.omit({ journalDate: true }).partial().strict();

export const checklistUpdateSchema = z
  .object({
    date: isoDate,
    itemKey: z.enum(CHECKLIST_KEYS),
    completed: z.boolean(),
  })
  .strict();

export const settingsUpdateSchema = z
  .object({
    theme: z.enum(THEMES).optional(),
    language: z.enum(LANGUAGES).optional(),
    timezone: timezoneSchema.optional(),
    baseCurrency: z.enum(CURRENCIES).optional(),
    defaultRiskPercentage: decimalString({ positive: true, max: 100 }).optional(),
    maxDailyLoss: optionalDecimal({ nonNegative: true }),
    maxWeeklyLoss: optionalDecimal({ nonNegative: true }),
    defaultTargetRr: decimalString({ positive: true, max: 100 }).optional(),
    primaryMarkets: z.array(z.enum(PRIMARY_MARKETS)).max(5).optional(),
    activeAccountScope: accountScopeSchema.optional(),
    tiltLossCount: z.number().int().min(2).max(20).optional(),
    tiltWindowMinutes: z.number().int().min(1).max(1440).optional(),
    tiltCooldownMinutes: z.number().int().min(1).max(1440).optional(),
  })
  .strict();

export const profileUpdateSchema = z
  .object({
    name: z.string().trim().min(1).max(120),
  })
  .strict();

export const onboardingSchema = z
  .object({
    primaryMarkets: z.array(z.enum(PRIMARY_MARKETS)).min(1).max(5),
    accountName: z.string().trim().min(1).max(80),
    startingCapital: decimalString({ positive: true, max: 1e12 }),
    currency: z.enum(CURRENCIES),
    demo: z.boolean(),
    defaultRiskPercentage: decimalString({ positive: true, max: 100 }),
    maxDailyLoss: optionalDecimal({ nonNegative: true }),
    maxWeeklyLoss: optionalDecimal({ nonNegative: true }),
    defaultTargetRr: decimalString({ positive: true, max: 100 }),
    timezone: timezoneSchema,
    loadDemoData: z.boolean().default(false),
  })
  .strict();
export type OnboardingInput = z.input<typeof onboardingSchema>;

export const aiChatSchema = z
  .object({
    conversationId: uuid.optional(),
    message: z.string().trim().min(1).max(4000),
    language: z.enum(LANGUAGES).default('en'),
    account: z.string().max(40).optional(),
  })
  .strict();

export const reviewRequestSchema = z
  .object({
    period: z.enum(['week', 'month']).default('month'),
    /** Any date inside the period to review (YYYY-MM-DD, user time zone). */
    anchorDate: isoDate.optional(),
    account: z.string().max(40).optional(),
    language: z.enum(LANGUAGES).default('en'),
  })
  .strict();

export const riskOfRuinQuerySchema = analyticsQuerySchema.extend({
  riskPercent: z.coerce.number().min(0.25).max(5).default(1),
  paths: z.coerce.number().int().min(1000).max(20000).default(5000),
  trades: z.coerce.number().int().min(10).max(2000).optional(),
});

export const brokerConnectionCreateSchema = z.discriminatedUnion('provider', [
  z
    .object({
      provider: z.literal('webhook'),
      label: z.string().trim().min(1).max(80),
      tradingAccountId: uuid,
    })
    .strict(),
  z
    .object({
      provider: z.literal('csv_import'),
      label: z.string().trim().min(1).max(80),
      tradingAccountId: uuid,
    })
    .strict(),
  z
    .object({
      provider: z.literal('binance'),
      label: z.string().trim().min(1).max(80),
      tradingAccountId: uuid,
      apiKey: z.string().trim().min(16).max(256),
      apiSecret: z.string().trim().min(16).max(256),
      symbols: z.array(z.string().trim().toUpperCase().regex(/^[A-Z0-9]{5,20}$/)).min(1).max(20),
    })
    .strict(),
]);
export type BrokerConnectionCreate = z.infer<typeof brokerConnectionCreateSchema>;

/** Normalised trade payload accepted by the signed webhook endpoint. */
export const webhookTradeSchema = z
  .object({
    brokerTradeId: z.string().trim().min(1).max(120),
    symbol: z.string().trim().min(1).max(30),
    side: z.enum(SIDES),
    executedAt: isoDateTime,
    closedAt: isoDateTime.nullable().optional(),
    entryPrice: decimalString({ positive: true }),
    exitPrice: optionalDecimal({ positive: true }),
    stopLoss: optionalDecimal({ positive: true }),
    takeProfit: optionalDecimal({ positive: true }),
    lotSize: decimalString({ positive: true }),
    pnl: optionalDecimal(),
    fees: optionalDecimal({ nonNegative: true }),
    setupTag: z.string().trim().max(120).nullable().optional(),
    notes: z.string().max(2000).nullable().optional(),
  })
  .strict();

export const deleteAccountSchema = z
  .object({
    confirmation: z.string().trim().min(1).max(320),
  })
  .strict();
