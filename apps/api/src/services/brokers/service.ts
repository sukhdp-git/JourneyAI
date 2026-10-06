import { and, desc, eq, sql } from 'drizzle-orm';
import {
  resolveInstrument,
  brokerConnectionCreateSchema,
  tradeCreateSchema,
  webhookTradeSchema,
  type BrokerConnectionDto,
  type TradeSource,
} from '@journzey/shared';
import type { Database } from '../../db/client.js';
import { brokerConnections, tradingAccounts, trades, webhookEvents } from '../../db/schema.js';
import { AppError, badRequest, notFound, zodFields } from '../../lib/errors.js';
import { hmacSha256Hex, randomToken, safeEqual, type SecretBox } from '../../lib/crypto.js';
import type { Logger } from '../../lib/logger.js';
import { prepareTrade, recalcAccountCapital } from '../trades.js';
import { BinanceAdapter, pairFillsFifo, type BinanceConfig, type BinanceCreds, type BinanceFill } from './binance.js';
import { BROKER_CATALOG } from './catalog.js';
import { parseTradeCsv } from './csvImport.js';
import { BrokerError, type ImportSummary, type NormalizedTrade } from './types.js';

type ConnRow = typeof brokerConnections.$inferSelect;

export const WEBHOOK_TOLERANCE_SECONDS = 300;

export class BrokerService {
  private readonly binance: BinanceAdapter;
  constructor(
    private readonly db: Database,
    private readonly box: SecretBox,
    private readonly apiUrl: string,
    private readonly log: Logger,
    binanceBase: string,
  ) {
    this.binance = new BinanceAdapter(binanceBase);
  }

  providers() {
    return BROKER_CATALOG;
  }

  private toDto(c: ConnRow): BrokerConnectionDto {
    return {
      id: c.id,
      provider: c.provider,
      label: c.label,
      status: c.status,
      tradingAccountId: c.tradingAccountId,
      credentialHint: c.credentialHint,
      lastSyncedAt: c.lastSyncedAt?.toISOString() ?? null,
      lastError: c.lastError,
      webhookUrl: c.provider === 'webhook' ? `${this.apiUrl.replace(/\/$/, '')}/v1/webhooks/broker/${c.id}` : null,
      createdAt: c.createdAt.toISOString(),
    };
  }

  async list(userId: string): Promise<BrokerConnectionDto[]> {
    const rows = await this.db.select().from(brokerConnections).where(eq(brokerConnections.userId, userId)).orderBy(desc(brokerConnections.createdAt));
    return rows.map((r) => this.toDto(r));
  }

  private async owned(userId: string, id: string): Promise<ConnRow> {
    const [c] = await this.db
      .select()
      .from(brokerConnections)
      .where(and(eq(brokerConnections.id, id), eq(brokerConnections.userId, userId)))
      .limit(1);
    if (!c) throw notFound('Broker connection not found');
    return c;
  }

  private async ownedAccount(userId: string, accountId: string) {
    const [a] = await this.db
      .select()
      .from(tradingAccounts)
      .where(and(eq(tradingAccounts.id, accountId), eq(tradingAccounts.userId, userId)))
      .limit(1);
    if (!a) throw notFound('Trading account not found');
    if (a.sampleData) throw badRequest('Demo data accounts cannot be connected to a broker');
    return a;
  }

  /** Creates a connection. For webhooks the plaintext signing secret is returned exactly once. */
  async create(userId: string, raw: unknown): Promise<{ connection: BrokerConnectionDto; webhookSecret?: string }> {
    const input = brokerConnectionCreateSchema.parse(raw);
    const account = await this.ownedAccount(userId, input.tradingAccountId);
    const base = { userId, tradingAccountId: account.id, provider: input.provider, label: input.label };
    if (input.provider === 'webhook') {
      const secret = `whsec_${randomToken(32)}`;
      const [row] = await this.db
        .insert(brokerConnections)
        .values({ ...base, encryptedWebhookSecret: this.box.encrypt(secret), credentialHint: `whsec_…${secret.slice(-4)}` })
        .returning();
      return { connection: this.toDto(row!), webhookSecret: secret };
    }
    if (input.provider === 'csv_import') {
      const [row] = await this.db.insert(brokerConnections).values(base).returning();
      return { connection: this.toDto(row!) };
    }
    if (account.currency !== 'USD') throw badRequest('Binance Spot import requires a USD-denominated trading account');
    let connected;
    try {
      connected = await this.binance.connect({ apiKey: input.apiKey, apiSecret: input.apiSecret, symbols: input.symbols });
    } catch (err) {
      if (err instanceof BrokerError) throw new AppError(400, 'BROKER_VERIFICATION_FAILED', err.message);
      throw new AppError(502, 'BROKER_UNAVAILABLE', 'Broker synchronization is temporarily unavailable. Your existing journal data is safe.');
    }
    const [row] = await this.db
      .insert(brokerConnections)
      .values({
        ...base,
        encryptedCredentials: this.box.encrypt(JSON.stringify(connected.credentials)),
        credentialHint: connected.credentialHint,
        config: connected.config,
      })
      .returning();
    return { connection: this.toDto(row!) };
  }

  async remove(userId: string, id: string): Promise<void> {
    const c = await this.owned(userId, id);
    await this.db.delete(brokerConnections).where(and(eq(brokerConnections.id, c.id), eq(brokerConnections.userId, userId)));
  }

  async rotateWebhookSecret(userId: string, id: string): Promise<{ webhookSecret: string }> {
    const c = await this.owned(userId, id);
    if (c.provider !== 'webhook') throw badRequest('Only webhook connections have signing secrets');
    const secret = `whsec_${randomToken(32)}`;
    await this.db
      .update(brokerConnections)
      .set({ encryptedWebhookSecret: this.box.encrypt(secret), credentialHint: `whsec_…${secret.slice(-4)}`, updatedAt: new Date() })
      .where(eq(brokerConnections.id, c.id));
    return { webhookSecret: secret };
  }

  /** Imports normalised trades atomically; duplicates (same broker trade id) are skipped. */
  async importNormalized(userId: string, accountId: string, items: NormalizedTrade[], source: TradeSource): Promise<ImportSummary> {
    const summary: ImportSummary = { received: items.length, imported: 0, duplicates: 0, failed: 0, errors: [] };
    await this.db.transaction(async (tx) => {
      for (const item of items) {
        const candidate = tradeCreateSchema.safeParse({
          tradingAccountId: accountId,
          executedAt: item.executedAt,
          closedAt: item.closedAt,
          symbol: item.symbol,
          side: item.side,
          entryPrice: item.entryPrice,
          exitPrice: item.exitPrice,
          stopLoss: item.stopLoss,
          takeProfit: item.takeProfit,
          lotSize: item.lotSize,
          pnl: item.pnl,
          fees: item.fees,
          setupTag: item.setupTag ?? null,
          notes: item.notes ?? null,
        });
        if (!candidate.success) {
          summary.failed++;
          summary.errors.push({ ref: item.brokerTradeId, message: Object.values(zodFields(candidate.error)).flat().join('; ') });
          continue;
        }
        try {
          // Savepoint per row so one bad row cannot abort the whole batch transaction.
          const inserted = await tx.transaction(async (sp) => {
            const prepared = await prepareTrade(sp, userId, candidate.data, source);
            return sp
              .insert(trades)
              .values({ ...prepared.values, brokerTradeId: item.brokerTradeId })
              .onConflictDoNothing()
              .returning({ id: trades.id });
          });
          if (inserted.length) summary.imported++;
          else summary.duplicates++;
        } catch (err) {
          summary.failed++;
          summary.errors.push({ ref: item.brokerTradeId, message: err instanceof AppError ? err.message : 'Could not import trade' });
        }
      }
      await recalcAccountCapital(tx, accountId);
    });
    summary.errors = summary.errors.slice(0, 50);
    return summary;
  }

  async importCsv(userId: string, connectionId: string, content: string, serverUtcOffsetMinutes: number) {
    const c = await this.owned(userId, connectionId);
    if (c.provider !== 'csv_import') throw badRequest('This connection does not accept CSV imports');
    if (!c.tradingAccountId) throw badRequest('Connection is not linked to a trading account');
    const parsed = parseTradeCsv(content, serverUtcOffsetMinutes);
    if (parsed.trades.length === 0 && parsed.errors.length) {
      throw new AppError(400, 'CSV_INVALID', parsed.errors[0]!.message, { file: parsed.errors.slice(0, 10).map((e) => `${e.ref}: ${e.message}`) });
    }
    const summary = await this.importNormalized(userId, c.tradingAccountId, parsed.trades, 'CSV_IMPORT');
    summary.failed += parsed.errors.length;
    summary.errors = [...parsed.errors, ...summary.errors].slice(0, 50);
    await this.db
      .update(brokerConnections)
      .set({ lastSyncedAt: new Date(), lastError: null, status: 'ACTIVE', updatedAt: new Date() })
      .where(eq(brokerConnections.id, c.id));
    return { ...summary, skippedRows: parsed.skipped };
  }

  async sync(userId: string, connectionId: string) {
    const c = await this.owned(userId, connectionId);
    if (c.provider !== 'binance') throw badRequest('This connection type does not support pull synchronisation');
    if (!c.encryptedCredentials || !c.tradingAccountId) throw badRequest('Connection is missing credentials or a linked account');
    const creds = JSON.parse(this.box.decrypt(c.encryptedCredentials)) as BinanceCreds;
    const config = c.config as BinanceConfig;
    const since = c.lastSyncedAt ? new Date(c.lastSyncedAt.getTime() - 7 * 24 * 3600_000) : new Date(Date.now() - 90 * 24 * 3600_000);
    try {
      const fills = (await this.binance.fetchTrades(creds, config, since)) as BinanceFill[];
      const normalized = pairFillsFifo(fills).map((rt) => this.binance.normalizeTrade(rt));
      const summary = await this.importNormalized(userId, c.tradingAccountId, normalized, 'BROKER_API');
      await this.db
        .update(brokerConnections)
        .set({ lastSyncedAt: new Date(), lastError: null, status: 'ACTIVE', updatedAt: new Date() })
        .where(eq(brokerConnections.id, c.id));
      return summary;
    } catch (err) {
      const message = err instanceof BrokerError ? err.message : 'Broker synchronization is temporarily unavailable. Your existing journal data is safe.';
      await this.db
        .update(brokerConnections)
        .set({ lastError: message, status: 'ERROR', updatedAt: new Date() })
        .where(eq(brokerConnections.id, c.id));
      this.log.warn({ connectionId: c.id, err: (err as Error).message }, 'broker sync failed');
      throw new AppError(502, 'BROKER_SYNC_FAILED', message);
    }
  }

  /**
   * Verifies and ingests a signed webhook delivery.
   * Signature: hex HMAC-SHA256(secret, `${timestamp}.${rawBody}`) in `X-Journzey-Signature: sha256=<hex>`.
   * Replay protection: timestamp tolerance + unique (connection, event id). Idempotent on retries.
   */
  async handleWebhook(
    connectionId: string,
    headers: { timestamp?: string; signature?: string; eventId?: string },
    rawBody: Buffer | undefined,
    body: unknown,
  ): Promise<{ status: 'imported' | 'duplicate'; tradeId?: string }> {
    const invalid = () => new AppError(401, 'WEBHOOK_SIGNATURE_INVALID', 'Invalid webhook signature');
    if (!/^[0-9a-f-]{36}$/i.test(connectionId)) throw invalid();
    const [c] = await this.db.select().from(brokerConnections).where(eq(brokerConnections.id, connectionId)).limit(1);
    if (!c || c.provider !== 'webhook' || !c.encryptedWebhookSecret || c.status === 'DISCONNECTED' || !c.tradingAccountId) throw invalid();
    if (!headers.timestamp || !headers.signature || !rawBody) throw invalid();
    const ts = Number(headers.timestamp);
    if (!Number.isInteger(ts) || Math.abs(Date.now() / 1000 - ts) > WEBHOOK_TOLERANCE_SECONDS) {
      throw new AppError(401, 'WEBHOOK_TIMESTAMP_INVALID', 'Webhook timestamp outside the allowed window');
    }
    const secret = this.box.decrypt(c.encryptedWebhookSecret);
    const expected = `sha256=${hmacSha256Hex(secret, Buffer.concat([Buffer.from(`${headers.timestamp}.`), rawBody]))}`;
    if (!safeEqual(expected, headers.signature)) throw invalid();

    const parsed = webhookTradeSchema.safeParse(body);
    if (!parsed.success) throw new AppError(400, 'VALIDATION_ERROR', 'Invalid trade payload', zodFields(parsed.error));
    const eventId = (headers.eventId ?? parsed.data.brokerTradeId).slice(0, 120);

    const claimed = await this.db
      .insert(webhookEvents)
      .values({ connectionId: c.id, eventId, status: 'PROCESSING' })
      .onConflictDoNothing()
      .returning({ id: webhookEvents.id });
    if (claimed.length === 0) return { status: 'duplicate' };

    const p = parsed.data;
    const instrument = resolveInstrument(p.symbol);
    if (!instrument) {
      await this.db.delete(webhookEvents).where(eq(webhookEvents.id, claimed[0]!.id));
      throw new AppError(422, 'WEBHOOK_TRADE_REJECTED', `Unsupported instrument "${p.symbol}"`);
    }
    const summary = await this.importNormalized(
      c.userId,
      c.tradingAccountId,
      [
        {
          brokerTradeId: p.brokerTradeId,
          symbol: instrument.symbol,
          side: p.side,
          executedAt: p.executedAt,
          closedAt: p.closedAt ?? null,
          entryPrice: p.entryPrice,
          exitPrice: p.exitPrice ?? null,
          stopLoss: p.stopLoss ?? null,
          takeProfit: p.takeProfit ?? null,
          lotSize: p.lotSize,
          pnl: p.pnl ?? null,
          fees: p.fees ?? null,
          setupTag: p.setupTag ?? null,
          notes: p.notes ?? null,
        },
      ],
      'WEBHOOK',
    );
    const [t] = await this.db
      .select({ id: trades.id })
      .from(trades)
      .where(and(eq(trades.userId, c.userId), eq(trades.brokerTradeId, p.brokerTradeId), eq(trades.source, 'WEBHOOK')))
      .limit(1);
    const failed = summary.failed > 0;
    if (failed) {
      // Release the idempotency claim so a corrected retry with the same event id can be processed.
      await this.db.delete(webhookEvents).where(eq(webhookEvents.id, claimed[0]!.id));
    }
    await this.db
      .update(webhookEvents)
      .set({ status: failed ? 'FAILED' : summary.duplicates ? 'DUPLICATE' : 'IMPORTED', tradeId: t?.id ?? null, error: failed ? summary.errors[0]?.message ?? 'failed' : null })
      .where(eq(webhookEvents.id, claimed[0]!.id));
    await this.db
      .update(brokerConnections)
      .set({ lastSyncedAt: new Date(), lastError: failed ? summary.errors[0]?.message ?? null : null, updatedAt: sql`now()` })
      .where(eq(brokerConnections.id, c.id));
    if (failed) throw new AppError(422, 'WEBHOOK_TRADE_REJECTED', summary.errors[0]?.message ?? 'Trade rejected');
    return { status: summary.duplicates ? 'duplicate' : 'imported', ...(t ? { tradeId: t.id } : {}) };
  }
}
