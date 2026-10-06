import type { BrokerProviderInfo, Side } from '@journzey/shared';

/** The single internal shape every broker/import source is normalised into. */
export interface NormalizedTrade {
  brokerTradeId: string;
  symbol: string;
  side: Side;
  executedAt: string;
  closedAt: string | null;
  entryPrice: string;
  exitPrice: string | null;
  stopLoss: string | null;
  takeProfit: string | null;
  lotSize: string;
  pnl: string | null;
  fees: string | null;
  setupTag?: string | null;
  notes?: string | null;
}

export interface ImportSummary {
  received: number;
  imported: number;
  duplicates: number;
  failed: number;
  errors: Array<{ ref: string; message: string }>;
}

/**
 * Adapter contract for broker integrations. Credentials are passed in already decrypted by the
 * BrokerService and must never be logged or returned to clients.
 */
export interface BrokerAdapter<Creds = unknown, Config = Record<string, unknown>> {
  readonly info: BrokerProviderInfo;
  /** Validate input and produce what should be stored (credentials are encrypted by the caller). */
  connect(input: unknown): Promise<{ credentials: Creds | null; config: Config; credentialHint: string | null }>;
  disconnect(creds: Creds | null): Promise<void>;
  verifyConnection(creds: Creds, config: Config): Promise<void>;
  fetchAccounts(creds: Creds): Promise<Array<{ id: string; label: string }>>;
  fetchTrades(creds: Creds, config: Config, since: Date): Promise<unknown[]>;
  normalizeTrade(raw: unknown): NormalizedTrade;
}

export class BrokerError extends Error {
  constructor(
    message: string,
    readonly retryable = false,
  ) {
    super(message);
  }
}
