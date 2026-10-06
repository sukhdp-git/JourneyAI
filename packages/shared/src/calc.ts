import { Decimal } from 'decimal.js';
import type { Side } from './constants.js';
import { getInstrument, type Instrument } from './instruments.js';

Decimal.set({ precision: 40, rounding: Decimal.ROUND_HALF_EVEN });

export { Decimal };

export type DecimalInput = string | number | Decimal;

export const d = (v: DecimalInput): Decimal => new Decimal(v);

const dir = (side: Side) => (side === 'LONG' ? 1 : -1);

/** Risk distance: abs(entry - stopLoss). */
export function riskDistance(entry: DecimalInput, stopLoss: DecimalInput): Decimal {
  return d(entry).minus(stopLoss).abs();
}

/**
 * Target exit from an R multiple.
 * LONG:  exit = entry + riskDistance × targetRR
 * SHORT: exit = entry - riskDistance × targetRR
 */
export function exitFromR(side: Side, entry: DecimalInput, stopLoss: DecimalInput, targetRR: DecimalInput): Decimal {
  const risk = riskDistance(entry, stopLoss);
  return d(entry).plus(risk.times(targetRR).times(dir(side)));
}

/**
 * Realised R multiple, signed (positive = in the trade's favour).
 * Magnitude matches the spec's RR = abs(exit-entry) / abs(entry-stopLoss).
 */
export function realisedR(side: Side, entry: DecimalInput, stopLoss: DecimalInput, exit: DecimalInput): Decimal | null {
  const risk = riskDistance(entry, stopLoss);
  if (risk.isZero()) return null;
  return d(exit).minus(entry).times(dir(side)).dividedBy(risk);
}

/** Gross price-move P&L in the instrument's quote currency. */
export function grossPnlQuote(
  instrument: Instrument,
  side: Side,
  entry: DecimalInput,
  exit: DecimalInput,
  lotSize: DecimalInput,
): Decimal {
  return d(exit).minus(entry).times(dir(side)).times(lotSize).times(instrument.contractSize);
}

/**
 * Convert an amount from the instrument's quote currency into the account currency.
 * Returns null when no conversion is derivable from the trade itself — the caller must
 * then require a broker-reported P&L instead of inventing an FX rate.
 */
export function convertQuoteToAccount(
  instrument: Instrument,
  amountQuote: Decimal,
  referencePrice: DecimalInput,
  accountCurrency: string,
): Decimal | null {
  const acc = accountCurrency.toUpperCase();
  if (instrument.quoteCurrency === acc) return amountQuote;
  // e.g. USDJPY P&L is in JPY; a USD account converts at the pair's own rate.
  if (instrument.baseCurrency === acc) {
    const px = d(referencePrice);
    if (px.isZero()) return null;
    return amountQuote.dividedBy(px);
  }
  return null;
}

export interface TradeMathInput {
  symbol: string;
  side: Side;
  entryPrice: DecimalInput;
  exitPrice?: DecimalInput | null;
  stopLoss?: DecimalInput | null;
  lotSize: DecimalInput;
  fees?: DecimalInput | null;
  accountCurrency: string;
}

export interface TradeMathResult {
  /** Net P&L in account currency (gross - fees), 2dp string; null when not derivable. */
  pnl: string | null;
  /** Signed R multiple (4dp); null without a stop or exit. */
  rr: string | null;
  /** Planned monetary risk (1R) in account currency; null when not derivable. */
  riskAmount: string | null;
}

/** Server-authoritative trade calculations. */
export function computeTradeMath(input: TradeMathInput): TradeMathResult {
  const instrument = getInstrument(input.symbol);
  if (!instrument) return { pnl: null, rr: null, riskAmount: null };
  const fees = d(input.fees ?? 0);

  let pnl: string | null = null;
  if (input.exitPrice !== null && input.exitPrice !== undefined && input.exitPrice !== '') {
    const gross = grossPnlQuote(instrument, input.side, input.entryPrice, input.exitPrice, input.lotSize);
    const converted = convertQuoteToAccount(instrument, gross, input.exitPrice, input.accountCurrency);
    if (converted) pnl = converted.minus(fees).toDecimalPlaces(2).toFixed(2);
  }

  let rr: string | null = null;
  let riskAmount: string | null = null;
  if (input.stopLoss !== null && input.stopLoss !== undefined && input.stopLoss !== '') {
    if (input.exitPrice !== null && input.exitPrice !== undefined && input.exitPrice !== '') {
      const r = realisedR(input.side, input.entryPrice, input.stopLoss, input.exitPrice);
      rr = r ? r.toDecimalPlaces(4).toFixed(4) : null;
    }
    const riskQuote = riskDistance(input.entryPrice, input.stopLoss).times(input.lotSize).times(instrument.contractSize);
    const riskAcc = convertQuoteToAccount(instrument, riskQuote, input.stopLoss, input.accountCurrency);
    if (riskAcc && !riskAcc.isZero()) riskAmount = riskAcc.toDecimalPlaces(2).toFixed(2);
  }
  return { pnl, rr, riskAmount };
}

/**
 * Lot size calculator: lots = (equity × risk%) / (stopDistance × contractSize [converted]).
 */
export function lotSizeForRisk(args: {
  symbol: string;
  equity: DecimalInput;
  riskPercent: DecimalInput;
  entry: DecimalInput;
  stopLoss: DecimalInput;
  accountCurrency: string;
}): { lots: string; riskAmount: string; perLotRisk: string } | null {
  const instrument = getInstrument(args.symbol);
  if (!instrument) return null;
  const riskAmount = d(args.equity).times(args.riskPercent).dividedBy(100);
  const perLotQuote = riskDistance(args.entry, args.stopLoss).times(instrument.contractSize);
  const perLot = convertQuoteToAccount(instrument, perLotQuote, args.entry, args.accountCurrency);
  if (!perLot || perLot.isZero()) return null;
  const lots = riskAmount.dividedBy(perLot).toDecimalPlaces(2, Decimal.ROUND_DOWN);
  return { lots: lots.toFixed(2), riskAmount: riskAmount.toFixed(2), perLotRisk: perLot.toFixed(2) };
}

export function formatPrice(symbol: string, value: DecimalInput | null | undefined): string {
  if (value === null || value === undefined || value === '') return '—';
  const inst = getInstrument(symbol);
  return d(value).toFixed(inst?.decimals ?? 2);
}
