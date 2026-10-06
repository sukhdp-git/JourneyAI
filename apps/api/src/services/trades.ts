import { and, asc, desc, eq, ilike, or, sql, type SQL } from 'drizzle-orm';
import {
  classifySession,
  computeTradeMath,
  d,
  getInstrument,
  parseQuickTrade,
  tradeCreateSchema,
  type Paginated,
  type TradeCreate,
  type TradeDto,
  type TradeListQuery,
  type TradeSource,
  type TradeUpdate,
} from '@journzey/shared';
import type { Database, DbOrTx } from '../db/client.js';
import { capitalTransactions, strategies, tradingAccounts, trades } from '../db/schema.js';
import { AppError, badRequest, notFound, zodFields } from '../lib/errors.js';
import { dateRangeWhere, resolveScope, tradeScopeWhere } from './scope.js';

type TradeRow = typeof trades.$inferSelect;

const iso = (v: Date | null) => (v ? v.toISOString() : null);

export function toTradeDto(
  row: TradeRow,
  extra: { strategyName: string | null; demo: boolean; currency: string },
): TradeDto {
  return {
    id: row.id,
    tradingAccountId: row.tradingAccountId,
    executedAt: row.executedAt.toISOString(),
    closedAt: iso(row.closedAt),
    symbol: row.symbol,
    assetClass: row.assetClass,
    side: row.side as TradeDto['side'],
    status: row.status as TradeDto['status'],
    entryPrice: d(row.entryPrice).toString(),
    exitPrice: row.exitPrice ? d(row.exitPrice).toString() : null,
    stopLoss: row.stopLoss ? d(row.stopLoss).toString() : null,
    takeProfit: row.takeProfit ? d(row.takeProfit).toString() : null,
    lotSize: d(row.lotSize).toString(),
    pnl: row.pnl,
    fees: row.fees,
    rr: row.rr,
    riskAmount: row.riskAmount,
    strategyId: row.strategyId,
    strategyName: extra.strategyName,
    setupTag: row.setupTag,
    session: row.session as TradeDto['session'],
    emotion: row.emotion as TradeDto['emotion'],
    mistakeTag: row.mistakeTag as TradeDto['mistakeTag'],
    rulesFollowed: row.rulesFollowed,
    notes: row.notes,
    hasScreenshot: Boolean(row.screenshotUrl),
    source: row.source as TradeSource,
    brokerTradeId: row.brokerTradeId,
    demo: extra.demo,
    currency: extra.currency,
    createdAt: row.createdAt.toISOString(),
    updatedAt: row.updatedAt.toISOString(),
  };
}

async function ownedAccount(db: DbOrTx, userId: string, accountId: string) {
  const [acc] = await db
    .select()
    .from(tradingAccounts)
    .where(and(eq(tradingAccounts.id, accountId), eq(tradingAccounts.userId, userId)))
    .limit(1);
  if (!acc) throw notFound('Trading account not found');
  if (acc.archived) throw badRequest('Trading account is archived', { tradingAccountId: ['Account is archived'] });
  return acc;
}

async function resolveStrategy(db: DbOrTx, userId: string, strategyId: string | null | undefined, setupTag: string | null | undefined) {
  if (strategyId) {
    const [s] = await db
      .select({ id: strategies.id, name: strategies.name })
      .from(strategies)
      .where(and(eq(strategies.id, strategyId), eq(strategies.userId, userId)))
      .limit(1);
    if (!s) throw badRequest('Unknown strategy', { strategyId: ['Strategy not found'] });
    return s;
  }
  if (setupTag) {
    // Link quick-command setups ("val bounce", "silver bullet") to the user's own playbooks.
    const tag = setupTag.trim().toLowerCase();
    const rows = await db.select({ id: strategies.id, name: strategies.name }).from(strategies).where(eq(strategies.userId, userId));
    const exact = rows.find((r) => r.name.toLowerCase() === tag);
    if (exact) return exact;
    const partial = rows.find((r) => r.name.toLowerCase().includes(tag) || tag.includes(r.name.toLowerCase()));
    if (partial) return partial;
  }
  return null;
}

/** Recomputes current capital = starting + realised P&L + capital flows. Call inside the write transaction. */
export async function recalcAccountCapital(db: DbOrTx, accountId: string): Promise<void> {
  await db.execute(sql`
    UPDATE ${tradingAccounts} SET
      current_capital = starting_capital
        + COALESCE((SELECT SUM(pnl) FROM ${trades} WHERE trading_account_id = ${accountId} AND status = 'CLOSED' AND pnl IS NOT NULL), 0)
        + COALESCE((SELECT SUM(CASE type WHEN 'WITHDRAWAL' THEN -amount ELSE amount END) FROM ${capitalTransactions} WHERE trading_account_id = ${accountId}), 0),
      updated_at = now()
    WHERE id = ${accountId}`);
}

interface PreparedTrade {
  values: typeof trades.$inferInsert;
  strategyName: string | null;
  account: typeof tradingAccounts.$inferSelect;
}

export async function prepareTrade(db: DbOrTx, userId: string, input: TradeCreate, source: TradeSource): Promise<PreparedTrade> {
  const account = await ownedAccount(db, userId, input.tradingAccountId);
  const instrument = getInstrument(input.symbol);
  if (!instrument) throw badRequest('Unsupported instrument', { symbol: ['Unsupported instrument'] });
  const strategy = await resolveStrategy(db, userId, input.strategyId, input.setupTag);
  const executedAt = new Date(input.executedAt);
  if (Number.isNaN(executedAt.getTime())) throw badRequest('Invalid execution time', { executedAt: ['Invalid date'] });
  if (executedAt.getTime() > Date.now() + 5 * 60_000) {
    throw badRequest('Execution time cannot be in the future', { executedAt: ['Cannot be in the future'] });
  }

  const math = computeTradeMath({
    symbol: instrument.symbol,
    side: input.side,
    entryPrice: input.entryPrice,
    exitPrice: input.exitPrice ?? null,
    stopLoss: input.stopLoss ?? null,
    lotSize: input.lotSize,
    fees: input.fees ?? '0',
    accountCurrency: account.currency,
  });
  const hasExit = Boolean(input.exitPrice);
  const overridden = input.pnl !== undefined && input.pnl !== null;
  let pnl = overridden ? d(input.pnl!).toFixed(2) : math.pnl;
  if (hasExit && pnl === null) {
    throw badRequest(
      `Cannot convert ${instrument.quoteCurrency} P&L into ${account.currency} automatically — enter the broker-reported P&L`,
      { pnl: ['Broker-reported P&L required for this instrument/account currency'] },
    );
  }
  if (!hasExit && !overridden) pnl = null;
  // R from P&L when no stop distance but a risk amount exists is not derivable; keep null.
  let rr = math.rr;
  if (overridden && math.riskAmount && pnl !== null) rr = d(pnl).dividedBy(math.riskAmount).toDecimalPlaces(4).toFixed(4);

  return {
    account,
    strategyName: strategy?.name ?? null,
    values: {
      userId,
      tradingAccountId: account.id,
      executedAt,
      closedAt: input.closedAt ? new Date(input.closedAt) : hasExit ? executedAt : null,
      symbol: instrument.symbol,
      assetClass: instrument.assetClass,
      side: input.side,
      status: hasExit || overridden ? 'CLOSED' : 'OPEN',
      entryPrice: input.entryPrice,
      exitPrice: input.exitPrice ?? null,
      stopLoss: input.stopLoss ?? null,
      takeProfit: input.takeProfit ?? null,
      lotSize: input.lotSize,
      pnl,
      pnlOverridden: overridden,
      fees: d(input.fees ?? '0').toFixed(2),
      rr,
      riskAmount: math.riskAmount,
      strategyId: strategy?.id ?? null,
      setupTag: input.setupTag ?? strategy?.name ?? null,
      session: input.session ?? classifySession(executedAt),
      emotion: input.emotion ?? null,
      mistakeTag: input.mistakeTag ?? null,
      rulesFollowed: input.rulesFollowed ?? true,
      notes: input.notes ?? null,
      source,
    },
  };
}

export class TradeService {
  constructor(private readonly db: Database) {}

  async create(userId: string, input: TradeCreate, source: TradeSource = 'MANUAL', extra: { brokerTradeId?: string } = {}): Promise<TradeDto> {
    return this.db.transaction(async (tx) => {
      const prepared = await prepareTrade(tx, userId, input, source);
      const [row] = await tx
        .insert(trades)
        .values({ ...prepared.values, brokerTradeId: extra.brokerTradeId ?? null })
        .returning();
      await recalcAccountCapital(tx, prepared.account.id);
      return toTradeDto(row!, { strategyName: prepared.strategyName, demo: prepared.account.demo, currency: prepared.account.currency });
    });
  }

  /** Parses a natural-language command server-side (never trusting the client preview) and saves it. */
  async createFromCommand(userId: string, command: string, tradingAccountId: string, executedAt?: string) {
    const parsed = parseQuickTrade(command);
    if (parsed.errors.length) {
      throw new AppError(400, 'VALIDATION_ERROR', 'Invalid trade command', { command: parsed.errors });
    }
    const candidate = {
      tradingAccountId,
      executedAt: executedAt ?? new Date().toISOString(),
      symbol: parsed.symbol!,
      side: parsed.side!,
      entryPrice: parsed.entryPrice!,
      exitPrice: parsed.exitPrice,
      stopLoss: parsed.stopLoss,
      takeProfit: parsed.takeProfit,
      lotSize: parsed.lotSize!,
      setupTag: parsed.setup,
      mistakeTag: parsed.mistakeTag,
      emotion: parsed.emotion,
      rulesFollowed: !parsed.mistakeTag || parsed.mistakeTag === 'NONE',
    };
    const validated = tradeCreateSchema.safeParse(candidate);
    if (!validated.success) throw new AppError(400, 'VALIDATION_ERROR', 'Invalid trade parameters', zodFields(validated.error));
    const trade = await this.create(userId, validated.data, 'QUICK_COMMAND');
    return { trade, parsed };
  }

  async get(userId: string, id: string): Promise<TradeDto> {
    const [row] = await this.db
      .select({ t: trades, strategyName: strategies.name, demo: tradingAccounts.demo, currency: tradingAccounts.currency })
      .from(trades)
      .innerJoin(tradingAccounts, eq(trades.tradingAccountId, tradingAccounts.id))
      .leftJoin(strategies, eq(trades.strategyId, strategies.id))
      .where(and(eq(trades.id, id), eq(trades.userId, userId)))
      .limit(1);
    if (!row) throw notFound('Trade not found');
    return toTradeDto(row.t, { strategyName: row.strategyName, demo: row.demo, currency: row.currency });
  }

  /** Raw row for internal use (ownership enforced). */
  async getRow(userId: string, id: string): Promise<TradeRow> {
    const [row] = await this.db
      .select()
      .from(trades)
      .where(and(eq(trades.id, id), eq(trades.userId, userId)))
      .limit(1);
    if (!row) throw notFound('Trade not found');
    return row;
  }

  async update(userId: string, id: string, patch: TradeUpdate): Promise<TradeDto> {
    return this.db.transaction(async (tx) => {
      const [existing] = await tx
        .select()
        .from(trades)
        .where(and(eq(trades.id, id), eq(trades.userId, userId)))
        .for('update')
        .limit(1);
      if (!existing) throw notFound('Trade not found');
      const merged = {
        tradingAccountId: existing.tradingAccountId,
        executedAt: existing.executedAt.toISOString(),
        closedAt: existing.closedAt?.toISOString() ?? null,
        symbol: existing.symbol,
        side: existing.side,
        entryPrice: existing.entryPrice,
        exitPrice: existing.exitPrice,
        stopLoss: existing.stopLoss,
        takeProfit: existing.takeProfit,
        lotSize: existing.lotSize,
        pnl: existing.pnlOverridden ? existing.pnl : null,
        fees: existing.fees,
        strategyId: existing.strategyId,
        setupTag: existing.setupTag,
        session: existing.session,
        emotion: existing.emotion,
        mistakeTag: existing.mistakeTag,
        rulesFollowed: existing.rulesFollowed,
        notes: existing.notes,
        ...Object.fromEntries(Object.entries(patch).filter(([, v]) => v !== undefined)),
      };
      // If prices change, a stale manual P&L override must not silently persist.
      const priceFields = ['exitPrice', 'entryPrice', 'lotSize', 'symbol', 'side', 'fees', 'tradingAccountId'] as const;
      if (patch.pnl === undefined && priceFields.some((f) => patch[f] !== undefined)) merged.pnl = null;
      if (patch.executedAt !== undefined && patch.session === undefined) merged.session = null;
      const validated = tradeCreateSchema.safeParse(merged);
      if (!validated.success) throw new AppError(400, 'VALIDATION_ERROR', 'Invalid trade parameters', zodFields(validated.error));
      const prepared = await prepareTrade(tx, userId, validated.data, existing.source as TradeSource);
      const [row] = await tx
        .update(trades)
        .set({ ...prepared.values, userId: existing.userId, source: existing.source, updatedAt: new Date() })
        .where(and(eq(trades.id, id), eq(trades.userId, userId)))
        .returning();
      await recalcAccountCapital(tx, prepared.account.id);
      if (existing.tradingAccountId !== prepared.account.id) await recalcAccountCapital(tx, existing.tradingAccountId);
      return toTradeDto(row!, { strategyName: prepared.strategyName, demo: prepared.account.demo, currency: prepared.account.currency });
    });
  }

  /** Deletes a trade; returns its screenshot key so the caller can remove the stored object. */
  async delete(userId: string, id: string): Promise<{ screenshotKey: string | null }> {
    return this.db.transaction(async (tx) => {
      const [row] = await tx
        .delete(trades)
        .where(and(eq(trades.id, id), eq(trades.userId, userId)))
        .returning({ accountId: trades.tradingAccountId, screenshot: trades.screenshotUrl });
      if (!row) throw notFound('Trade not found');
      await recalcAccountCapital(tx, row.accountId);
      return { screenshotKey: row.screenshot };
    });
  }

  async setScreenshot(userId: string, id: string, key: string | null): Promise<string | null> {
    const existing = await this.getRow(userId, id);
    await this.db
      .update(trades)
      .set({ screenshotUrl: key, updatedAt: new Date() })
      .where(and(eq(trades.id, id), eq(trades.userId, userId)));
    return existing.screenshotUrl;
  }

  private async listWhere(userId: string, q: Omit<TradeListQuery, 'page' | 'pageSize' | 'sort' | 'order'>) {
    const scope = await resolveScope(this.db, userId, q.account);
    const conds: SQL[] = [tradeScopeWhere(userId, scope), ...dateRangeWhere(q.from, q.to, scope.timezone)];
    if (q.strategyId) conds.push(eq(trades.strategyId, q.strategyId));
    if (q.session) conds.push(eq(trades.session, q.session));
    if (q.symbol) conds.push(eq(trades.symbol, q.symbol));
    if (q.side) conds.push(eq(trades.side, q.side));
    if (q.status) conds.push(eq(trades.status, q.status));
    if (q.outcome === 'WIN') conds.push(sql`${trades.pnl} > 0`);
    if (q.outcome === 'LOSS') conds.push(sql`${trades.pnl} < 0`);
    if (q.outcome === 'BREAKEVEN') conds.push(sql`${trades.pnl} = 0`);
    if (q.search) {
      const term = `%${q.search.replace(/[\\%_]/g, (m) => `\\${m}`)}%`;
      conds.push(
        or(
          ilike(trades.symbol, term),
          ilike(trades.setupTag, term),
          ilike(trades.notes, term),
          ilike(strategies.name, term),
          ilike(trades.emotion, term),
          ilike(trades.mistakeTag, term),
        )!,
      );
    }
    return { scope, where: and(...conds)! };
  }

  async list(userId: string, q: TradeListQuery): Promise<Paginated<TradeDto> & { currency: string }> {
    const { scope, where } = await this.listWhere(userId, q);
    const sortCol = {
      executedAt: trades.executedAt,
      symbol: trades.symbol,
      pnl: trades.pnl,
      rr: trades.rr,
      lotSize: trades.lotSize,
      side: trades.side,
    }[q.sort];
    const dir = q.order === 'asc' ? asc : desc;
    const [countRow] = await this.db
      .select({ n: sql<number>`count(*)::int` })
      .from(trades)
      .leftJoin(strategies, eq(trades.strategyId, strategies.id))
      .where(where);
    const total = countRow?.n ?? 0;
    const rows = await this.db
      .select({ t: trades, strategyName: strategies.name, demo: tradingAccounts.demo, currency: tradingAccounts.currency })
      .from(trades)
      .innerJoin(tradingAccounts, eq(trades.tradingAccountId, tradingAccounts.id))
      .leftJoin(strategies, eq(trades.strategyId, strategies.id))
      .where(where)
      .orderBy(sql`${dir(sortCol)} NULLS LAST`, desc(trades.executedAt), desc(trades.id))
      .limit(q.pageSize)
      .offset((q.page - 1) * q.pageSize);
    return {
      items: rows.map((r) => toTradeDto(r.t, { strategyName: r.strategyName, demo: r.demo, currency: r.currency })),
      page: q.page,
      pageSize: q.pageSize,
      total,
      totalPages: Math.max(1, Math.ceil(total / q.pageSize)),
      currency: scope.currency,
    };
  }

  /** Streams all matching rows for CSV export in chunks (bounded memory). */
  async *iterateForExport(userId: string, q: TradeListQuery): AsyncGenerator<TradeDto[]> {
    const { where } = await this.listWhere(userId, q);
    const chunk = 500;
    for (let offset = 0; ; offset += chunk) {
      const rows = await this.db
        .select({ t: trades, strategyName: strategies.name, demo: tradingAccounts.demo, currency: tradingAccounts.currency })
        .from(trades)
        .innerJoin(tradingAccounts, eq(trades.tradingAccountId, tradingAccounts.id))
        .leftJoin(strategies, eq(trades.strategyId, strategies.id))
        .where(where)
        .orderBy(desc(trades.executedAt), desc(trades.id))
        .limit(chunk)
        .offset(offset);
      if (rows.length === 0) return;
      yield rows.map((r) => toTradeDto(r.t, { strategyName: r.strategyName, demo: r.demo, currency: r.currency }));
      if (rows.length < chunk) return;
    }
  }
}
