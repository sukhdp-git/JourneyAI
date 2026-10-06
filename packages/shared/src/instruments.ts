import type { AssetClass } from './constants.js';

/**
 * Centralised instrument metadata. Every P&L / risk / formatting calculation
 * must go through this table — never hardcode contract values in components.
 *
 * Values follow the most common retail CFD conventions (1 standard lot):
 *  - FX: 100,000 units of base currency
 *  - XAUUSD: 100 troy ounces, XAGUSD: 5,000 troy ounces
 *  - Index CFDs: 1 unit of quote currency per index point
 *  - Crypto: 1 coin
 *  - Energy: WTI/Brent 1,000 barrels, Natural Gas 10,000 MMBtu, Copper 25,000 lbs
 * Brokers differ; users can always override P&L with the broker-reported figure.
 */
export interface Instrument {
  symbol: string;
  displayName: string;
  assetClass: AssetClass;
  baseCurrency: string;
  quoteCurrency: string;
  contractSize: string;
  tickSize: string;
  /** Value of one tick for one lot, in quote currency. */
  tickValue: string;
  pipSize: string;
  decimals: number;
  aliases: readonly string[];
  /** Symbol shown on the Home Hub market ticker. */
  tickerGroup?: 'METALS' | 'ENERGY' | 'INDICES' | 'FX' | 'CRYPTO';
}

const make = (i: Omit<Instrument, 'tickValue'>): Instrument => {
  // tickValue = tickSize * contractSize, computed with integer-safe string math via Number for metadata only.
  const tickValue = (Number(i.tickSize) * Number(i.contractSize)).toFixed(6).replace(/\.?0+$/, '');
  return { ...i, tickValue };
};

export const INSTRUMENTS: readonly Instrument[] = [
  make({ symbol: 'XAUUSD', displayName: 'Gold', assetClass: 'METALS', baseCurrency: 'XAU', quoteCurrency: 'USD', contractSize: '100', tickSize: '0.01', pipSize: '0.1', decimals: 2, aliases: ['gold', 'xau', 'xauusd', 'gc'], tickerGroup: 'METALS' }),
  make({ symbol: 'XAGUSD', displayName: 'Silver', assetClass: 'METALS', baseCurrency: 'XAG', quoteCurrency: 'USD', contractSize: '5000', tickSize: '0.001', pipSize: '0.01', decimals: 3, aliases: ['silver', 'xag', 'xagusd', 'si'], tickerGroup: 'METALS' }),
  make({ symbol: 'USOIL', displayName: 'WTI Crude', assetClass: 'COMMODITIES', baseCurrency: 'WTI', quoteCurrency: 'USD', contractSize: '1000', tickSize: '0.01', pipSize: '0.01', decimals: 2, aliases: ['usoil', 'wti', 'oil', 'cl', 'crude'], tickerGroup: 'ENERGY' }),
  make({ symbol: 'UKOIL', displayName: 'Brent Crude', assetClass: 'COMMODITIES', baseCurrency: 'BRENT', quoteCurrency: 'USD', contractSize: '1000', tickSize: '0.01', pipSize: '0.01', decimals: 2, aliases: ['ukoil', 'brent', 'bz'], tickerGroup: 'ENERGY' }),
  make({ symbol: 'NATGAS', displayName: 'Natural Gas', assetClass: 'COMMODITIES', baseCurrency: 'NG', quoteCurrency: 'USD', contractSize: '10000', tickSize: '0.001', pipSize: '0.001', decimals: 3, aliases: ['natgas', 'ng', 'gas', 'xngusd'], tickerGroup: 'ENERGY' }),
  make({ symbol: 'COPPER', displayName: 'Copper', assetClass: 'COMMODITIES', baseCurrency: 'HG', quoteCurrency: 'USD', contractSize: '25000', tickSize: '0.0005', pipSize: '0.0005', decimals: 4, aliases: ['copper', 'hg', 'xcuusd'], tickerGroup: 'METALS' }),
  make({ symbol: 'US500', displayName: 'S&P 500', assetClass: 'INDICES', baseCurrency: 'SPX', quoteCurrency: 'USD', contractSize: '1', tickSize: '0.01', pipSize: '0.1', decimals: 2, aliases: ['us500', 'spx', 'sp500', 'es', 'spx500'], tickerGroup: 'INDICES' }),
  make({ symbol: 'NAS100', displayName: 'Nasdaq 100', assetClass: 'INDICES', baseCurrency: 'NDX', quoteCurrency: 'USD', contractSize: '1', tickSize: '0.01', pipSize: '0.1', decimals: 2, aliases: ['nas100', 'nas', 'nq', 'ustec', 'us100', 'ndx'], tickerGroup: 'INDICES' }),
  make({ symbol: 'US30', displayName: 'Dow Jones 30', assetClass: 'INDICES', baseCurrency: 'DJI', quoteCurrency: 'USD', contractSize: '1', tickSize: '0.01', pipSize: '1', decimals: 2, aliases: ['us30', 'dow', 'dji', 'ym', 'dj30'], tickerGroup: 'INDICES' }),
  make({ symbol: 'GER40', displayName: 'DAX 40', assetClass: 'INDICES', baseCurrency: 'DAX', quoteCurrency: 'EUR', contractSize: '1', tickSize: '0.01', pipSize: '1', decimals: 2, aliases: ['ger40', 'dax', 'de40', 'ger30'], tickerGroup: 'INDICES' }),
  make({ symbol: 'UK100', displayName: 'FTSE 100', assetClass: 'INDICES', baseCurrency: 'FTSE', quoteCurrency: 'GBP', contractSize: '1', tickSize: '0.01', pipSize: '1', decimals: 2, aliases: ['uk100', 'ftse', 'ftse100'], tickerGroup: 'INDICES' }),
  make({ symbol: 'JPN225', displayName: 'Nikkei 225', assetClass: 'INDICES', baseCurrency: 'NKY', quoteCurrency: 'JPY', contractSize: '1', tickSize: '1', pipSize: '1', decimals: 0, aliases: ['jpn225', 'nikkei', 'nk225', 'jp225'], tickerGroup: 'INDICES' }),
  make({ symbol: 'EURUSD', displayName: 'EUR/USD', assetClass: 'FOREX', baseCurrency: 'EUR', quoteCurrency: 'USD', contractSize: '100000', tickSize: '0.00001', pipSize: '0.0001', decimals: 5, aliases: ['eurusd', 'eu', 'fiber'], tickerGroup: 'FX' }),
  make({ symbol: 'GBPUSD', displayName: 'GBP/USD', assetClass: 'FOREX', baseCurrency: 'GBP', quoteCurrency: 'USD', contractSize: '100000', tickSize: '0.00001', pipSize: '0.0001', decimals: 5, aliases: ['gbpusd', 'gu', 'cable'], tickerGroup: 'FX' }),
  make({ symbol: 'USDJPY', displayName: 'USD/JPY', assetClass: 'FOREX', baseCurrency: 'USD', quoteCurrency: 'JPY', contractSize: '100000', tickSize: '0.001', pipSize: '0.01', decimals: 3, aliases: ['usdjpy', 'uj', 'yen'], tickerGroup: 'FX' }),
  make({ symbol: 'AUDUSD', displayName: 'AUD/USD', assetClass: 'FOREX', baseCurrency: 'AUD', quoteCurrency: 'USD', contractSize: '100000', tickSize: '0.00001', pipSize: '0.0001', decimals: 5, aliases: ['audusd', 'au', 'aussie'] }),
  make({ symbol: 'USDCAD', displayName: 'USD/CAD', assetClass: 'FOREX', baseCurrency: 'USD', quoteCurrency: 'CAD', contractSize: '100000', tickSize: '0.00001', pipSize: '0.0001', decimals: 5, aliases: ['usdcad', 'uc', 'loonie'] }),
  make({ symbol: 'USDCHF', displayName: 'USD/CHF', assetClass: 'FOREX', baseCurrency: 'USD', quoteCurrency: 'CHF', contractSize: '100000', tickSize: '0.00001', pipSize: '0.0001', decimals: 5, aliases: ['usdchf', 'swissy'] }),
  make({ symbol: 'GBPJPY', displayName: 'GBP/JPY', assetClass: 'FOREX', baseCurrency: 'GBP', quoteCurrency: 'JPY', contractSize: '100000', tickSize: '0.001', pipSize: '0.01', decimals: 3, aliases: ['gbpjpy', 'gj', 'guppy'] }),
  make({ symbol: 'EURJPY', displayName: 'EUR/JPY', assetClass: 'FOREX', baseCurrency: 'EUR', quoteCurrency: 'JPY', contractSize: '100000', tickSize: '0.001', pipSize: '0.01', decimals: 3, aliases: ['eurjpy', 'ej'] }),
  make({ symbol: 'BTCUSDT', displayName: 'Bitcoin', assetClass: 'CRYPTO', baseCurrency: 'BTC', quoteCurrency: 'USD', contractSize: '1', tickSize: '0.01', pipSize: '1', decimals: 2, aliases: ['btcusdt', 'btc', 'bitcoin', 'btcusd', 'xbt'], tickerGroup: 'CRYPTO' }),
  make({ symbol: 'ETHUSDT', displayName: 'Ethereum', assetClass: 'CRYPTO', baseCurrency: 'ETH', quoteCurrency: 'USD', contractSize: '1', tickSize: '0.01', pipSize: '0.1', decimals: 2, aliases: ['ethusdt', 'eth', 'ethereum', 'ethusd'], tickerGroup: 'CRYPTO' }),
  make({ symbol: 'SOLUSDT', displayName: 'Solana', assetClass: 'CRYPTO', baseCurrency: 'SOL', quoteCurrency: 'USD', contractSize: '1', tickSize: '0.001', pipSize: '0.01', decimals: 3, aliases: ['solusdt', 'sol', 'solana', 'solusd'] }),
];

const BY_SYMBOL = new Map(INSTRUMENTS.map((i) => [i.symbol, i]));
const BY_ALIAS = new Map<string, Instrument>();
for (const inst of INSTRUMENTS) {
  BY_ALIAS.set(inst.symbol.toLowerCase(), inst);
  for (const a of inst.aliases) BY_ALIAS.set(a.toLowerCase(), inst);
}

export function getInstrument(symbol: string): Instrument | undefined {
  return BY_SYMBOL.get(symbol.toUpperCase());
}

/** Resolve user/broker text (e.g. "gold", "XAUUSD.r", "US500m") to an instrument. */
export function resolveInstrument(text: string): Instrument | undefined {
  const raw = text.trim().toLowerCase();
  if (!raw) return undefined;
  const direct = BY_ALIAS.get(raw) ?? BY_ALIAS.get(raw.replace(/[/_\-\s]/g, ''));
  if (direct) return direct;
  // Strip common broker suffixes: XAUUSD.r, XAUUSDm, EURUSD+, EURUSD.pro, US500.cash
  const stripped = raw
    .replace(/[/_\-\s]/g, '')
    .replace(/\.(r|m|pro|raw|ecn|cash|std|i|a|b|c)$/i, '')
    .replace(/[+#!.]$/g, '');
  const viaStripped = BY_ALIAS.get(stripped);
  if (viaStripped) return viaStripped;
  const trailingLetter = stripped.replace(/[a-z]$/, '');
  return BY_ALIAS.get(trailingLetter);
}

export const TICKER_SYMBOLS: readonly string[] = [
  'XAUUSD',
  'XAGUSD',
  'USOIL',
  'UKOIL',
  'NATGAS',
  'COPPER',
  'US500',
  'NAS100',
  'US30',
  'GER40',
  'UK100',
  'JPN225',
  'EURUSD',
  'GBPUSD',
  'USDJPY',
  'BTCUSDT',
  'ETHUSDT',
];
