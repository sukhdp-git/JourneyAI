import { computeTradeMath, d, getInstrument, grossPnlQuote, convertQuoteToAccount, riskDistance } from '@journzey/shared';
import { eq } from 'drizzle-orm';
import type { Database } from '../db/client.js';
import { tradingAccounts } from '../db/schema.js';
import { AppError, badRequest, notConfigured } from '../lib/errors.js';
import type { MarketDataService } from './marketData.js';
import type { TradeService } from './trades.js';

/**
 * Post-Trade Runner Auditor.
 * Hypothetical: instead of closing 100% at the actual exit, keep a 20% runner with its stop moved
 * to breakeven (entry) and close it at the end of the review window. Uses real historical candles
 * from the configured market-data provider; never fabricates prices.
 */
export class RunnerAuditService {
  constructor(
    private readonly db: Database,
    private readonly trades: TradeService,
    private readonly market: MarketDataService,
  ) {}

  async audit(userId: string, tradeId: string, windowHours: number, runnerFraction = 0.2) {
    const provider = this.market.provider;
    if (!provider) {
      throw notConfigured('MARKET_DATA_NOT_CONFIGURED', 'Runner audit requires a market-data provider (MARKET_DATA_API_KEY). No prices are simulated.');
    }
    const t = await this.trades.getRow(userId, tradeId);
    if (t.status !== 'CLOSED' || !t.exitPrice) throw badRequest('Runner audit requires a closed trade with an exit price');
    if (!t.stopLoss) throw badRequest('Runner audit requires a stop loss to measure R');
    const inst = getInstrument(t.symbol);
    if (!inst) throw badRequest('Unsupported instrument');
    const [account] = await this.db.select().from(tradingAccounts).where(eq(tradingAccounts.id, t.tradingAccountId)).limit(1);
    const start = (t.closedAt ?? t.executedAt).toISOString();
    const end = new Date(Math.min(Date.now(), new Date(start).getTime() + windowHours * 3600_000)).toISOString();
    let candles;
    try {
      candles = await provider.candles(t.symbol, '5min', start, end);
    } catch (err) {
      throw new AppError(502, 'MARKET_DATA_UNAVAILABLE', `Historical prices unavailable: ${(err as Error).message}`);
    }
    if (candles.length === 0) throw new AppError(404, 'NO_HISTORICAL_DATA', 'No historical candles available for this window');

    const side = t.side as 'LONG' | 'SHORT';
    const entry = d(t.entryPrice);
    let runnerExit = d(candles.at(-1)!.close);
    let exitReason: 'BREAKEVEN_STOP' | 'WINDOW_END' = 'WINDOW_END';
    let mfe = d(t.exitPrice);
    for (const c of candles) {
      const fav = side === 'LONG' ? d(c.high) : d(c.low);
      if (side === 'LONG' ? fav.gt(mfe) : fav.lt(mfe)) mfe = fav;
      const stopped = side === 'LONG' ? d(c.low).lte(entry) : d(c.high).gte(entry);
      if (stopped) {
        runnerExit = entry;
        exitReason = 'BREAKEVEN_STOP';
        break;
      }
    }
    const runnerLots = d(t.lotSize).times(runnerFraction);
    const deltaQuote = grossPnlQuote(inst, side, t.exitPrice, runnerExit, runnerLots);
    const deltaAcc = convertQuoteToAccount(inst, deltaQuote, runnerExit, account!.currency);
    const risk = riskDistance(t.entryPrice, t.stopLoss);
    const dir = side === 'LONG' ? 1 : -1;
    const additionalR = risk.isZero() ? null : runnerExit.minus(t.exitPrice).times(dir).times(runnerFraction).dividedBy(risk);
    const actual = computeTradeMath({ symbol: t.symbol, side, entryPrice: t.entryPrice, exitPrice: t.exitPrice, stopLoss: t.stopLoss, lotSize: t.lotSize, accountCurrency: account!.currency });
    return {
      hypothetical: true,
      label: 'Hypothetical runner analysis based on historical prices — not a recommendation.',
      tradeId: t.id,
      runnerFraction,
      windowStart: start,
      windowEnd: end,
      candles: candles.length,
      provider: provider.name,
      runnerExit: runnerExit.toString(),
      exitReason,
      maxFavourablePrice: mfe.toString(),
      additionalR: additionalR ? additionalR.toDecimalPlaces(2).toNumber() : null,
      additionalPnl: deltaAcc ? deltaAcc.toFixed(2) : null,
      actualR: actual.rr,
      currency: account!.currency,
    };
  }
}
