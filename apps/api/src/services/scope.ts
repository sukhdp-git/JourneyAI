import { and, eq, sql, type SQL } from 'drizzle-orm';
import { accountScopeSchema, d } from '@journzey/shared';
import type { DbOrTx } from '../db/client.js';
import { tradingAccounts, trades, userSettings } from '../db/schema.js';
import { badRequest, notFound } from '../lib/errors.js';

export interface ResolvedScope {
  key: string;
  accountIds: string[];
  currency: string;
  mixedCurrency: boolean;
  demo: boolean;
  sampleData: boolean;
  startingCapital: string;
  timezone: string;
}

/**
 * Resolves which trading accounts an analytics/list request covers.
 *  - "real": non-demo accounts (default) — demo data is never mixed in implicitly
 *  - "demo": demo/practice accounts incl. generated DEMO DATA
 *  - "all":  explicit opt-in to everything
 *  - <uuid>: a single account, which must belong to the user
 */
export async function resolveScope(db: DbOrTx, userId: string, requested?: string): Promise<ResolvedScope> {
  const [settings] = await db
    .select({ timezone: userSettings.timezone, activeAccountScope: userSettings.activeAccountScope, baseCurrency: userSettings.baseCurrency })
    .from(userSettings)
    .where(eq(userSettings.userId, userId))
    .limit(1);
  const key = requested ?? settings?.activeAccountScope ?? 'real';
  const parsed = accountScopeSchema.safeParse(key);
  if (!parsed.success) throw badRequest('Invalid account scope', { account: ['Expected real, demo, all or an account id'] });

  const conds: SQL[] = [eq(tradingAccounts.userId, userId)];
  if (key === 'real') conds.push(eq(tradingAccounts.demo, false));
  else if (key === 'demo') conds.push(eq(tradingAccounts.demo, true));
  else if (key !== 'all') conds.push(eq(tradingAccounts.id, key));

  const accounts = await db
    .select({
      id: tradingAccounts.id,
      currency: tradingAccounts.currency,
      startingCapital: tradingAccounts.startingCapital,
      demo: tradingAccounts.demo,
      sampleData: tradingAccounts.sampleData,
    })
    .from(tradingAccounts)
    .where(and(...conds));
  if (key !== 'real' && key !== 'demo' && key !== 'all' && accounts.length === 0) throw notFound('Trading account not found');

  const currencies = [...new Set(accounts.map((a) => a.currency))];
  return {
    key,
    accountIds: accounts.map((a) => a.id),
    currency: currencies[0] ?? settings?.baseCurrency ?? 'USD',
    mixedCurrency: currencies.length > 1,
    demo: accounts.length > 0 && accounts.every((a) => a.demo),
    sampleData: accounts.some((a) => a.sampleData),
    startingCapital: accounts.reduce((acc, a) => acc.plus(a.startingCapital), d(0)).toFixed(2),
    timezone: settings?.timezone ?? 'UTC',
  };
}

/** SQL predicate: trade belongs to user AND to an account in scope. */
export function tradeScopeWhere(userId: string, scope: ResolvedScope): SQL {
  if (scope.accountIds.length === 0) return sql`false`;
  return and(
    eq(trades.userId, userId),
    sql`${trades.tradingAccountId} IN (${sql.join(
      scope.accountIds.map((id) => sql`${id}::uuid`),
      sql`, `,
    )})`,
  )!;
}

/** SQL predicates for a [from, to] calendar-date range interpreted in the user's time zone. */
export function dateRangeWhere(from: string | undefined, to: string | undefined, timezone: string): SQL[] {
  const out: SQL[] = [];
  if (from) out.push(sql`${trades.executedAt} >= ((${from}::date)::timestamp AT TIME ZONE ${timezone})`);
  if (to) out.push(sql`${trades.executedAt} < (((${to}::date) + 1)::timestamp AT TIME ZONE ${timezone})`);
  return out;
}
