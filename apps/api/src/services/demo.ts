import { and, eq, inArray } from 'drizzle-orm';
import { computeTradeMath, DEMO_ACCOUNT, DEMO_JOURNALS, generateDemoTrades, getInstrument } from '@journzey/shared';
import type { Database, DbOrTx } from '../db/client.js';
import { journalEntries, strategies, tradingAccounts, trades, userSettings } from '../db/schema.js';
import { copyStrategyTemplates } from './users.js';
import { recalcAccountCapital } from './trades.js';

/**
 * DEMO DATA lifecycle. Demo trades live in a dedicated account flagged demo=true and
 * sample_data=true; demo journals carry demo=true. Real analytics ("real" scope) never include them.
 */
export class DemoService {
  constructor(private readonly db: Database) {}

  async createDemoData(tx: DbOrTx, userId: string): Promise<{ accountId: string; trades: number; journals: number }> {
    await copyStrategyTemplates(tx, userId);
    const strategyRows = await tx.select({ id: strategies.id, name: strategies.name }).from(strategies).where(eq(strategies.userId, userId));
    const byName = new Map(strategyRows.map((s) => [s.name.toLowerCase(), s.id]));
    const [account] = await tx
      .insert(tradingAccounts)
      .values({
        userId,
        accountName: DEMO_ACCOUNT.accountName,
        brokerName: DEMO_ACCOUNT.brokerName,
        currency: DEMO_ACCOUNT.currency,
        startingCapital: DEMO_ACCOUNT.startingCapital,
        currentCapital: DEMO_ACCOUNT.startingCapital,
        demo: true,
        sampleData: true,
      })
      .returning();
    const demoTrades = generateDemoTrades();
    await tx.insert(trades).values(
      demoTrades.map((t) => {
        const math = computeTradeMath({ ...t, accountCurrency: DEMO_ACCOUNT.currency });
        return {
          userId,
          tradingAccountId: account!.id,
          executedAt: new Date(t.executedAt),
          closedAt: new Date(t.closedAt),
          symbol: t.symbol,
          assetClass: getInstrument(t.symbol)!.assetClass,
          side: t.side,
          status: 'CLOSED',
          entryPrice: t.entryPrice,
          exitPrice: t.exitPrice,
          stopLoss: t.stopLoss,
          takeProfit: t.takeProfit,
          lotSize: t.lotSize,
          pnl: math.pnl,
          rr: math.rr,
          riskAmount: math.riskAmount,
          strategyId: byName.get(t.strategy.toLowerCase()) ?? null,
          setupTag: t.setupTag,
          session: t.session,
          emotion: t.emotion,
          mistakeTag: t.mistakeTag,
          rulesFollowed: t.rulesFollowed,
          notes: t.notes,
          source: 'DEMO',
        };
      }),
    );
    await tx
      .insert(journalEntries)
      .values(DEMO_JOURNALS.map((j) => ({ userId, ...j, reflection: `[DEMO DATA] ${j.reflection}`, demo: true })))
      .onConflictDoNothing();
    await recalcAccountCapital(tx, account!.id);
    return { accountId: account!.id, trades: demoTrades.length, journals: DEMO_JOURNALS.length };
  }

  async removeDemoData(tx: DbOrTx, userId: string): Promise<void> {
    const demoAccounts = await tx
      .select({ id: tradingAccounts.id })
      .from(tradingAccounts)
      .where(and(eq(tradingAccounts.userId, userId), eq(tradingAccounts.sampleData, true)));
    if (demoAccounts.length) {
      await tx.delete(tradingAccounts).where(
        and(
          eq(tradingAccounts.userId, userId),
          inArray(
            tradingAccounts.id,
            demoAccounts.map((a) => a.id),
          ),
        ),
      );
    }
    await tx.delete(journalEntries).where(and(eq(journalEntries.userId, userId), eq(journalEntries.demo, true)));
    const [settings] = await tx.select().from(userSettings).where(eq(userSettings.userId, userId)).limit(1);
    if (settings && demoAccounts.some((a) => a.id === settings.activeAccountScope)) {
      await tx.update(userSettings).set({ activeAccountScope: 'real' }).where(eq(userSettings.userId, userId));
    }
  }

  /** Reset Demo Data: atomically remove and regenerate. */
  async reset(userId: string) {
    return this.db.transaction(async (tx) => {
      await this.removeDemoData(tx, userId);
      return this.createDemoData(tx, userId);
    });
  }

  /** Start With Empty Account: remove all demo data and switch to real scope. */
  async clear(userId: string) {
    return this.db.transaction(async (tx) => {
      await this.removeDemoData(tx, userId);
      await tx.update(userSettings).set({ activeAccountScope: 'real' }).where(eq(userSettings.userId, userId));
    });
  }
}
