import { and, asc, desc, eq, sql } from 'drizzle-orm';
import type { CapitalTransactionDto, TradingAccountDto } from '@journzey/shared';
import type { Database } from '../db/client.js';
import { brokerConnections, capitalTransactions, tradingAccounts, trades, userSettings } from '../db/schema.js';
import { badRequest, notFound } from '../lib/errors.js';
import { recalcAccountCapital } from './trades.js';

type AccountRow = typeof tradingAccounts.$inferSelect;

export const toAccountDto = (a: AccountRow, tradeCount?: number): TradingAccountDto => ({
  id: a.id,
  accountName: a.accountName,
  brokerName: a.brokerName,
  accountType: a.accountType as TradingAccountDto['accountType'],
  currency: a.currency,
  startingCapital: a.startingCapital,
  currentCapital: a.currentCapital,
  demo: a.demo,
  archived: a.archived,
  createdAt: a.createdAt.toISOString(),
  ...(tradeCount !== undefined ? { tradeCount } : {}),
});

export class AccountService {
  constructor(private readonly db: Database) {}

  async list(userId: string): Promise<Array<TradingAccountDto & { sampleData: boolean }>> {
    const rows = await this.db
      .select({ a: tradingAccounts, n: sql<number>`(SELECT count(*)::int FROM ${trades} t WHERE t.trading_account_id = "trading_accounts"."id")` })
      .from(tradingAccounts)
      .where(eq(tradingAccounts.userId, userId))
      .orderBy(asc(tradingAccounts.demo), asc(tradingAccounts.createdAt));
    return rows.map((r) => ({ ...toAccountDto(r.a, r.n), sampleData: r.a.sampleData }));
  }

  async owned(userId: string, id: string): Promise<AccountRow> {
    const [a] = await this.db
      .select()
      .from(tradingAccounts)
      .where(and(eq(tradingAccounts.id, id), eq(tradingAccounts.userId, userId)))
      .limit(1);
    if (!a) throw notFound('Trading account not found');
    return a;
  }

  async create(
    userId: string,
    input: { accountName: string; brokerName?: string | null; accountType: string; currency: string; startingCapital: string; demo: boolean },
  ): Promise<TradingAccountDto> {
    const [a] = await this.db
      .insert(tradingAccounts)
      .values({ userId, ...input, brokerName: input.brokerName ?? null, currentCapital: input.startingCapital })
      .returning();
    return toAccountDto(a!);
  }

  async update(
    userId: string,
    id: string,
    patch: { accountName?: string; brokerName?: string | null; accountType?: string; startingCapital?: string; archived?: boolean },
  ): Promise<TradingAccountDto> {
    const existing = await this.owned(userId, id);
    if (existing.sampleData && patch.startingCapital) throw badRequest('Demo data accounts cannot be edited; use Reset Demo Data');
    return this.db.transaction(async (tx) => {
      await tx
        .update(tradingAccounts)
        .set({ ...patch, updatedAt: new Date() })
        .where(and(eq(tradingAccounts.id, id), eq(tradingAccounts.userId, userId)));
      await recalcAccountCapital(tx, id);
      const [a] = await tx.select().from(tradingAccounts).where(eq(tradingAccounts.id, id));
      return toAccountDto(a!);
    });
  }

  /** Deletes an account and everything attached to it atomically. */
  async delete(userId: string, id: string): Promise<{ screenshotKeys: string[]; trades: number }> {
    await this.owned(userId, id);
    return this.db.transaction(async (tx) => {
      const shots = await tx
        .select({ key: trades.screenshotUrl })
        .from(trades)
        .where(and(eq(trades.tradingAccountId, id), eq(trades.userId, userId)));
      await tx
        .update(brokerConnections)
        .set({ status: 'DISCONNECTED', tradingAccountId: null, updatedAt: new Date() })
        .where(and(eq(brokerConnections.tradingAccountId, id), eq(brokerConnections.userId, userId)));
      await tx.delete(tradingAccounts).where(and(eq(tradingAccounts.id, id), eq(tradingAccounts.userId, userId)));
      await tx
        .update(userSettings)
        .set({ activeAccountScope: 'real' })
        .where(and(eq(userSettings.userId, userId), eq(userSettings.activeAccountScope, id)));
      return { screenshotKeys: shots.map((s) => s.key).filter((k): k is string => !!k), trades: shots.length };
    });
  }

  async listTransactions(userId: string, accountId: string): Promise<CapitalTransactionDto[]> {
    await this.owned(userId, accountId);
    const rows = await this.db
      .select()
      .from(capitalTransactions)
      .where(and(eq(capitalTransactions.tradingAccountId, accountId), eq(capitalTransactions.userId, userId)))
      .orderBy(desc(capitalTransactions.occurredAt));
    return rows.map((r) => ({
      id: r.id,
      tradingAccountId: r.tradingAccountId,
      type: r.type as CapitalTransactionDto['type'],
      amount: r.amount,
      note: r.note,
      occurredAt: r.occurredAt.toISOString(),
    }));
  }

  async addTransaction(
    userId: string,
    accountId: string,
    input: { type: 'DEPOSIT' | 'WITHDRAWAL' | 'ADJUSTMENT'; amount: string; note?: string | null; occurredAt?: string },
  ): Promise<TradingAccountDto> {
    const acc = await this.owned(userId, accountId);
    if (acc.sampleData) throw badRequest('Demo data accounts cannot receive capital transactions');
    return this.db.transaction(async (tx) => {
      await tx.insert(capitalTransactions).values({
        userId,
        tradingAccountId: accountId,
        type: input.type,
        amount: input.amount,
        note: input.note ?? null,
        occurredAt: input.occurredAt ? new Date(input.occurredAt) : new Date(),
      });
      await recalcAccountCapital(tx, accountId);
      const [a] = await tx.select().from(tradingAccounts).where(eq(tradingAccounts.id, accountId));
      return toAccountDto(a!);
    });
  }

  async deleteTransaction(userId: string, accountId: string, txId: string): Promise<void> {
    await this.owned(userId, accountId);
    await this.db.transaction(async (tx) => {
      const deleted = await tx
        .delete(capitalTransactions)
        .where(and(eq(capitalTransactions.id, txId), eq(capitalTransactions.userId, userId), eq(capitalTransactions.tradingAccountId, accountId)))
        .returning({ id: capitalTransactions.id });
      if (deleted.length === 0) throw notFound('Capital transaction not found');
      await recalcAccountCapital(tx, accountId);
    });
  }
}
