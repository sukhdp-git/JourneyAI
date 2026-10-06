import { z } from 'zod';
import { d, resolveInstrument } from '@journzey/shared';
import { hmacSha256Hex } from '../../lib/crypto.js';
import { BrokerError, type BrokerAdapter, type NormalizedTrade } from './types.js';
import { BROKER_CATALOG } from './catalog.js';

export interface BinanceCreds {
  apiKey: string;
  apiSecret: string;
}
export interface BinanceConfig extends Record<string, unknown> {
  symbols: string[];
}

export interface BinanceFill {
  symbol: string;
  id: number;
  orderId: number;
  price: string;
  qty: string;
  commission: string;
  commissionAsset: string;
  time: number;
  isBuyer: boolean;
}

/** A round trip produced by FIFO-matching spot fills (spot = long-only). */
export interface BinanceRoundTrip {
  symbol: string;
  openFillIds: number[];
  closeFillId: number;
  openTime: number;
  closeTime: number;
  qty: string;
  avgEntry: string;
  exitPrice: string;
  quoteFees: string;
}

/**
 * Pairs buy fills (opens) with subsequent sell fills (closes) per symbol, FIFO.
 * Partial closes produce one round trip per closing fill. Only commissions paid in the
 * quote asset (USDT/USDC/BUSD) are converted into fees; others are left unconverted (noted).
 */
export function pairFillsFifo(fills: BinanceFill[]): BinanceRoundTrip[] {
  const out: BinanceRoundTrip[] = [];
  const bySymbol = new Map<string, BinanceFill[]>();
  for (const f of fills) bySymbol.set(f.symbol, [...(bySymbol.get(f.symbol) ?? []), f]);
  for (const [symbol, list] of bySymbol) {
    const lots: Array<{ id: number; qty: ReturnType<typeof d>; price: string; time: number; fee: ReturnType<typeof d> }> = [];
    const quote = symbol.replace(/^(BTC|ETH|SOL|BNB|XRP|ADA|DOGE)/, '');
    for (const f of [...list].sort((a, b) => a.time - b.time || a.id - b.id)) {
      const fee = f.commissionAsset === quote ? d(f.commission) : d(0);
      if (f.isBuyer) {
        lots.push({ id: f.id, qty: d(f.qty), price: f.price, time: f.time, fee });
        continue;
      }
      let remaining = d(f.qty);
      let cost = d(0);
      let matched = d(0);
      let openFee = d(0);
      const ids: number[] = [];
      let openTime = f.time;
      while (remaining.gt(0) && lots.length) {
        const lot = lots[0]!;
        const take = lot.qty.lt(remaining) ? lot.qty : remaining;
        cost = cost.plus(take.times(lot.price));
        matched = matched.plus(take);
        openFee = openFee.plus(lot.fee.times(take).dividedBy(lot.qty));
        openTime = Math.min(openTime, lot.time);
        ids.push(lot.id);
        lot.fee = lot.fee.minus(lot.fee.times(take).dividedBy(lot.qty));
        lot.qty = lot.qty.minus(take);
        remaining = remaining.minus(take);
        if (lot.qty.lte(0)) lots.shift();
      }
      if (matched.lte(0)) continue; // sell without a known opening buy (position predates sync window)
      out.push({
        symbol,
        openFillIds: ids,
        closeFillId: f.id,
        openTime,
        closeTime: f.time,
        qty: matched.toString(),
        avgEntry: cost.dividedBy(matched).toDecimalPlaces(8).toString(),
        exitPrice: f.price,
        quoteFees: openFee.plus(fee.times(matched).dividedBy(d(f.qty))).toDecimalPlaces(8).toString(),
      });
    }
  }
  return out.sort((a, b) => a.closeTime - b.closeTime);
}

const connectSchema = z.object({
  apiKey: z.string().min(16),
  apiSecret: z.string().min(16),
  symbols: z.array(z.string().regex(/^[A-Z0-9]{5,20}$/)).min(1),
});

/** Binance Spot read-only adapter (BETA). */
export class BinanceAdapter implements BrokerAdapter<BinanceCreds, BinanceConfig> {
  readonly info = BROKER_CATALOG.find((c) => c.id === 'binance')!;
  constructor(private readonly base: string) {}

  private async signedGet<T>(creds: BinanceCreds, path: string, params: Record<string, string>): Promise<T> {
    const qs = new URLSearchParams({ ...params, timestamp: String(Date.now()), recvWindow: '10000' });
    qs.set('signature', hmacSha256Hex(creds.apiSecret, qs.toString()));
    const res = await fetch(`${this.base}${path}?${qs.toString()}`, {
      headers: { 'X-MBX-APIKEY': creds.apiKey },
      signal: AbortSignal.timeout(10_000),
    });
    if (res.status === 401 || res.status === 403) throw new BrokerError('Binance rejected the API key (check key, secret and IP whitelist)');
    if (res.status === 429 || res.status === 418) throw new BrokerError('Binance rate limit reached; try again later', true);
    if (!res.ok) {
      const body = (await res.json().catch(() => ({}))) as { msg?: string };
      throw new BrokerError(`Binance error: ${body.msg ?? `HTTP ${res.status}`}`, res.status >= 500);
    }
    return (await res.json()) as T;
  }

  async connect(input: unknown) {
    const parsed = connectSchema.parse(input);
    const creds = { apiKey: parsed.apiKey, apiSecret: parsed.apiSecret };
    const config = { symbols: parsed.symbols };
    await this.verifyConnection(creds, config);
    return { credentials: creds, config, credentialHint: `…${parsed.apiKey.slice(-4)}` };
  }

  async disconnect(): Promise<void> {
    // Binance API keys are revoked by the user in their Binance account; we simply delete our encrypted copy.
  }

  async verifyConnection(creds: BinanceCreds, _config?: BinanceConfig): Promise<void> {
    const account = await this.signedGet<{ canTrade?: boolean; permissions?: string[] }>(creds, '/api/v3/account', {});
    if (!account || typeof account !== 'object') throw new BrokerError('Unexpected Binance response');
  }

  async fetchAccounts(): Promise<Array<{ id: string; label: string }>> {
    return [{ id: 'spot', label: 'Binance Spot' }];
  }

  async fetchTrades(creds: BinanceCreds, config: BinanceConfig, since: Date): Promise<BinanceFill[]> {
    const all: BinanceFill[] = [];
    for (const symbol of config.symbols) {
      const fills = await this.signedGet<BinanceFill[]>(creds, '/api/v3/myTrades', {
        symbol,
        startTime: String(since.getTime()),
        limit: '1000',
      });
      all.push(...fills);
    }
    return all;
  }

  normalizeTrade(raw: unknown): NormalizedTrade {
    const rt = raw as BinanceRoundTrip;
    const inst = resolveInstrument(rt.symbol);
    if (!inst) throw new BrokerError(`Unsupported Binance symbol ${rt.symbol}`);
    const pnl = d(rt.exitPrice).minus(rt.avgEntry).times(rt.qty).minus(rt.quoteFees);
    return {
      brokerTradeId: `binance-${rt.symbol}-${rt.closeFillId}`,
      symbol: inst.symbol,
      side: 'LONG',
      executedAt: new Date(rt.openTime).toISOString(),
      closedAt: new Date(rt.closeTime).toISOString(),
      entryPrice: rt.avgEntry,
      exitPrice: rt.exitPrice,
      stopLoss: null,
      takeProfit: null,
      lotSize: rt.qty,
      pnl: pnl.toFixed(2),
      fees: d(rt.quoteFees).toFixed(2),
      notes: `Imported from Binance Spot (fills ${rt.openFillIds.join(',')} → ${rt.closeFillId})`,
    };
  }
}
