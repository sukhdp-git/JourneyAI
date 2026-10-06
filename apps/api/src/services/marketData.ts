import { getInstrument, TICKER_SYMBOLS, type MarketQuote, type MarketQuotesResponse } from '@journzey/shared';
import type { Logger } from '../lib/logger.js';

export interface Candle {
  time: string;
  open: number;
  high: number;
  low: number;
  close: number;
}

export interface MarketDataProvider {
  readonly name: string;
  quotes(symbols: readonly string[]): Promise<MarketQuote[]>;
  candles(symbol: string, interval: '1min' | '5min' | '15min', startIso: string, endIso: string): Promise<Candle[]>;
}

/**
 * Twelve Data REST adapter (https://twelvedata.com/docs). Symbol coverage depends on the
 * subscription plan; symbols the plan cannot serve are reported per-quote as unavailable
 * instead of being filled with invented prices.
 */
export const TWELVE_DATA_SYMBOLS: Record<string, string> = {
  XAUUSD: 'XAU/USD',
  XAGUSD: 'XAG/USD',
  USOIL: 'WTI/USD',
  UKOIL: 'BRENT/USD',
  NATGAS: 'NG/USD',
  COPPER: 'XCU/USD',
  US500: 'SPX',
  NAS100: 'NDX',
  US30: 'DJI',
  GER40: 'DAX',
  UK100: 'FTSE',
  JPN225: 'N225',
  EURUSD: 'EUR/USD',
  GBPUSD: 'GBP/USD',
  USDJPY: 'USD/JPY',
  AUDUSD: 'AUD/USD',
  USDCAD: 'USD/CAD',
  USDCHF: 'USD/CHF',
  GBPJPY: 'GBP/JPY',
  EURJPY: 'EUR/JPY',
  BTCUSDT: 'BTC/USD',
  ETHUSDT: 'ETH/USD',
  SOLUSDT: 'SOL/USD',
};

export class TwelveDataProvider implements MarketDataProvider {
  readonly name = 'twelvedata';
  constructor(
    private readonly apiKey: string,
    private readonly log: Logger,
    private readonly base = 'https://api.twelvedata.com',
  ) {}

  async quotes(symbols: readonly string[]): Promise<MarketQuote[]> {
    const mapped = symbols.map((s) => ({ s, td: TWELVE_DATA_SYMBOLS[s] })).filter((x): x is { s: string; td: string } => !!x.td);
    const url = new URL('/quote', this.base);
    url.searchParams.set('symbol', mapped.map((m) => m.td).join(','));
    url.searchParams.set('apikey', this.apiKey);
    const res = await fetch(url, { signal: AbortSignal.timeout(8000) });
    if (!res.ok) throw new Error(`Market data HTTP ${res.status}`);
    const body = (await res.json()) as Record<string, unknown>;
    // Single-symbol responses are not keyed by symbol.
    const bySymbol = mapped.length === 1 ? { [mapped[0]!.td]: body } : body;
    return mapped.map(({ s, td }) => {
      const q = bySymbol[td] as { close?: string; change?: string; percent_change?: string; timestamp?: number; status?: string; message?: string } | undefined;
      const inst = getInstrument(s);
      if (!q || q.status === 'error' || !q.close) {
        if (q?.message) this.log.debug({ symbol: s }, 'market data symbol unavailable');
        return { symbol: s, displayName: inst?.displayName ?? s, price: null, change: null, changePercent: null, asOf: null, error: 'Unavailable on current market-data plan' };
      }
      return {
        symbol: s,
        displayName: inst?.displayName ?? s,
        price: q.close,
        change: q.change ?? null,
        changePercent: q.percent_change ?? null,
        asOf: q.timestamp ? new Date(q.timestamp * 1000).toISOString() : null,
      };
    });
  }

  async candles(symbol: string, interval: '1min' | '5min' | '15min', startIso: string, endIso: string): Promise<Candle[]> {
    const td = TWELVE_DATA_SYMBOLS[symbol];
    if (!td) throw new Error(`No market-data mapping for ${symbol}`);
    const url = new URL('/time_series', this.base);
    url.searchParams.set('symbol', td);
    url.searchParams.set('interval', interval);
    url.searchParams.set('start_date', startIso.replace('T', ' ').slice(0, 19));
    url.searchParams.set('end_date', endIso.replace('T', ' ').slice(0, 19));
    url.searchParams.set('timezone', 'UTC');
    url.searchParams.set('order', 'ASC');
    url.searchParams.set('outputsize', '5000');
    url.searchParams.set('apikey', this.apiKey);
    const res = await fetch(url, { signal: AbortSignal.timeout(10_000) });
    if (!res.ok) throw new Error(`Market data HTTP ${res.status}`);
    const body = (await res.json()) as { status?: string; message?: string; values?: Array<Record<string, string>> };
    if (body.status === 'error' || !body.values) throw new Error(body.message ?? 'Market data unavailable');
    return body.values.map((v) => ({
      time: new Date(`${v.datetime!.replace(' ', 'T')}Z`).toISOString(),
      open: Number(v.open),
      high: Number(v.high),
      low: Number(v.low),
      close: Number(v.close),
    }));
  }
}

/**
 * Static reference levels shown ONLY when no market-data provider is configured.
 * The API marks the response source as "demo" and the UI labels it DEMO DATA.
 */
const DEMO_LEVELS: Record<string, [string, string]> = {
  XAUUSD: ['3352.40', '0.42'],
  XAGUSD: ['38.215', '-0.31'],
  USOIL: ['66.12', '0.85'],
  UKOIL: ['69.48', '0.71'],
  NATGAS: ['3.214', '-1.12'],
  COPPER: ['4.6120', '0.18'],
  US500: ['6412.50', '0.24'],
  NAS100: ['23410.75', '0.38'],
  US30: ['44890.00', '0.11'],
  GER40: ['24120.00', '-0.15'],
  UK100: ['9180.00', '0.05'],
  JPN225: ['41250', '0.62'],
  EURUSD: ['1.16420', '-0.08'],
  GBPUSD: ['1.34510', '0.04'],
  USDJPY: ['147.820', '0.19'],
  BTCUSDT: ['112450.00', '1.24'],
  ETHUSDT: ['4310.50', '1.87'],
};

export class MarketDataService {
  private cache: { at: number; value: MarketQuotesResponse } | null = null;
  constructor(
    readonly provider: MarketDataProvider | null,
    private readonly log: Logger,
    private readonly ttlMs = 60_000,
  ) {}

  get configured() {
    return this.provider !== null;
  }

  async quotes(): Promise<MarketQuotesResponse> {
    if (!this.provider) {
      return {
        source: 'demo',
        provider: null,
        quotes: TICKER_SYMBOLS.map((s) => {
          const [price, pct] = DEMO_LEVELS[s] ?? [null, null];
          return { symbol: s, displayName: getInstrument(s)?.displayName ?? s, price, change: null, changePercent: pct, asOf: null };
        }),
      };
    }
    if (this.cache && Date.now() - this.cache.at < this.ttlMs) return this.cache.value;
    try {
      const quotes = await this.provider.quotes(TICKER_SYMBOLS);
      const value: MarketQuotesResponse = { source: 'live', provider: this.provider.name, quotes };
      this.cache = { at: Date.now(), value };
      return value;
    } catch (err) {
      this.log.warn({ err: (err as Error).message }, 'market data fetch failed');
      return {
        source: 'live',
        provider: this.provider.name,
        quotes: TICKER_SYMBOLS.map((s) => ({ symbol: s, displayName: getInstrument(s)?.displayName ?? s, price: null, change: null, changePercent: null, asOf: null, error: 'Market data temporarily unavailable' })),
      };
    }
  }
}
