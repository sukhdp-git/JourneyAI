import { createHmac } from 'node:crypto';
import request from 'supertest';
import { afterAll, beforeAll, describe, expect, it } from 'vitest';
import { pairFillsFifo, type BinanceFill } from '../src/services/brokers/binance.js';
import { parseTradeCsv } from '../src/services/brokers/csvImport.js';
import { createTestApp, login, onboard, resetData, type Agent } from './helpers.js';

const t = createTestApp();
let a: Agent;
let account: string;
let hookId: string;
let secret: string;

const sign = (body: string, ts = Math.floor(Date.now() / 1000), s = secret) => ({
  ts: String(ts),
  sig: `sha256=${createHmac('sha256', s).update(`${ts}.${body}`).digest('hex')}`,
});

beforeAll(async () => {
  await resetData(t.pool);
  a = await login(t.app, t.idp, { sub: 'hooks', email: 'hooks@example.com' });
  account = await onboard(a);
  const res = await a.post('/api/v1/broker-connections').set('x-csrf-token', a.csrf).send({ provider: 'webhook', label: 'MT5 EA', tradingAccountId: account }).expect(201);
  hookId = res.body.connection.id;
  secret = res.body.webhookSecret;
});
afterAll(() => t.pool.end());

const payload = JSON.stringify({
  brokerTradeId: 'mt5-1001',
  symbol: 'XAUUSD.r',
  side: 'LONG',
  executedAt: '2026-09-20T09:00:00Z',
  closedAt: '2026-09-20T10:00:00Z',
  entryPrice: '2862',
  exitPrice: '2870',
  stopLoss: '2858',
  lotSize: '0.5',
});

describe('broker connections', () => {
  it('never returns stored secrets after creation', async () => {
    expect(secret).toMatch(/^whsec_/);
    const list = await a.get('/api/v1/broker-connections').expect(200);
    expect(JSON.stringify(list.body)).not.toContain(secret);
    expect(list.body[0].webhookUrl).toBe(`http://localhost:3000/api/v1/webhooks/broker/${hookId}`);
    const { rows } = await t.pool.query('select encrypted_webhook_secret from broker_connections where id = $1', [hookId]);
    expect(rows[0].encrypted_webhook_secret).not.toContain(secret);
    expect(rows[0].encrypted_webhook_secret).toMatch(/^v1:/);
  });

  it('lists an honest provider catalogue', async () => {
    const res = await a.get('/api/v1/broker-connections/providers').expect(200);
    const statuses = new Set(res.body.map((p: { status: string }) => p.status));
    expect(statuses).toEqual(new Set(['AVAILABLE', 'BETA', 'COMING_SOON', 'UNSUPPORTED']));
  });
});

describe('signed webhooks', () => {
  it('rejects bad signatures, stale timestamps and unknown connections', async () => {
    const { ts } = sign(payload);
    await request(t.app).post(`/api/v1/webhooks/broker/${hookId}`).set('content-type', 'application/json').set('x-journzey-timestamp', ts).set('x-journzey-signature', 'sha256=deadbeef').send(payload).expect(401);
    const old = sign(payload, Math.floor(Date.now() / 1000) - 3600);
    await request(t.app).post(`/api/v1/webhooks/broker/${hookId}`).set('content-type', 'application/json').set('x-journzey-timestamp', old.ts).set('x-journzey-signature', old.sig).send(payload).expect(401);
    const wrong = sign(payload, undefined, 'whsec_wrong');
    await request(t.app).post(`/api/v1/webhooks/broker/${hookId}`).set('content-type', 'application/json').set('x-journzey-timestamp', wrong.ts).set('x-journzey-signature', wrong.sig).send(payload).expect(401);
    const good = sign(payload);
    await request(t.app).post('/api/v1/webhooks/broker/00000000-0000-0000-0000-000000000000').set('content-type', 'application/json').set('x-journzey-timestamp', good.ts).set('x-journzey-signature', good.sig).send(payload).expect(401);
  });

  it('imports a valid delivery once and is idempotent on replay', async () => {
    const { ts, sig } = sign(payload);
    const first = await request(t.app).post(`/api/v1/webhooks/broker/${hookId}`).set('content-type', 'application/json').set('x-journzey-timestamp', ts).set('x-journzey-signature', sig).set('x-journzey-event-id', 'evt-1').send(payload).expect(201);
    expect(first.body.status).toBe('imported');
    const replay = await request(t.app).post(`/api/v1/webhooks/broker/${hookId}`).set('content-type', 'application/json').set('x-journzey-timestamp', ts).set('x-journzey-signature', sig).set('x-journzey-event-id', 'evt-1').send(payload).expect(200);
    expect(replay.body.status).toBe('duplicate');
    const again = sign(payload);
    const newEvent = await request(t.app).post(`/api/v1/webhooks/broker/${hookId}`).set('content-type', 'application/json').set('x-journzey-timestamp', again.ts).set('x-journzey-signature', again.sig).set('x-journzey-event-id', 'evt-2').send(payload).expect(200);
    expect(newEvent.body.status).toBe('duplicate'); // same broker trade id → no duplicate trade
    const trades = await a.get('/api/v1/trades').expect(200);
    expect(trades.body.total).toBe(1);
    expect(trades.body.items[0]).toMatchObject({ symbol: 'XAUUSD', source: 'WEBHOOK', pnl: '400.00' });
  });

  it('rotating the secret invalidates the old one', async () => {
    const rotated = await a.post(`/api/v1/broker-connections/${hookId}/rotate-secret`).set('x-csrf-token', a.csrf).expect(200);
    const old = sign(payload);
    await request(t.app).post(`/api/v1/webhooks/broker/${hookId}`).set('content-type', 'application/json').set('x-journzey-timestamp', old.ts).set('x-journzey-signature', old.sig).send(payload).expect(401);
    secret = rotated.body.webhookSecret;
  });
});

describe('CSV statement import', () => {
  const csv = [
    'Ticket,Open Time,Type,Volume,Symbol,Open Price,S / L,T / P,Close Time,Close Price,Commission,Swap,Profit',
    '5001,2026.09.21 10:00:00,buy,0.50,XAUUSD.r,2862.00,2858.00,2874.00,2026.09.21 11:00:00,2874.00,-3.50,0.00,600.00',
    '5002,2026.09.21 12:00:00,sell,1.00,EURUSD,1.16500,1.16700,1.16100,2026.09.21 13:00:00,1.16700,-7.00,-0.50,-200.00',
    '5003,2026.09.21 12:30:00,balance,,,,,,,,,,1000.00',
    '5004,2026.09.21 13:00:00,buy,1.00,UNKNOWNX,1.0,,,2026.09.21 14:00:00,1.1,0,0,10',
  ].join('\n');

  it('parses MT4/MT5 statements with server-time offset', () => {
    const r = parseTradeCsv(csv, 180);
    expect(r.trades).toHaveLength(2);
    expect(r.trades[0]).toMatchObject({ brokerTradeId: '5001', symbol: 'XAUUSD', side: 'LONG', executedAt: '2026-09-21T07:00:00.000Z', pnl: '596.50', fees: '3.50' });
    expect(r.skipped).toBe(1);
    expect(r.errors[0]!.message).toContain('UNKNOWNX');
  });

  it('imports through the API and skips duplicates on re-import', async () => {
    const conn = await a.post('/api/v1/broker-connections').set('x-csrf-token', a.csrf).send({ provider: 'csv_import', label: 'Vantage MT5', tradingAccountId: account }).expect(201);
    const id = conn.body.connection.id;
    const first = await a.post(`/api/v1/broker-connections/${id}/import`).set('x-csrf-token', a.csrf).field('serverUtcOffsetMinutes', '180').attach('file', Buffer.from(csv), 'statement.csv').expect(200);
    expect(first.body).toMatchObject({ imported: 2, duplicates: 0 });
    const second = await a.post(`/api/v1/broker-connections/${id}/import`).set('x-csrf-token', a.csrf).field('serverUtcOffsetMinutes', '180').attach('file', Buffer.from(csv), 'statement.csv').expect(200);
    expect(second.body).toMatchObject({ imported: 0, duplicates: 2 });
    await a.post(`/api/v1/broker-connections/${id}/import`).set('x-csrf-token', a.csrf).attach('file', Buffer.from('MZ\u0000\u0000'), 'evil.exe').expect(400);
  });
});

describe('Binance FIFO normalisation', () => {
  it('pairs buys and sells into round trips', () => {
    const f = (id: number, isBuyer: boolean, price: string, qty: string, time: number): BinanceFill => ({
      symbol: 'BTCUSDT', id, orderId: id, price, qty, commission: '0.1', commissionAsset: 'USDT', time, isBuyer,
    });
    const trips = pairFillsFifo([f(1, true, '100000', '0.5', 1), f(2, true, '102000', '0.5', 2), f(3, false, '104000', '0.75', 3), f(4, false, '99000', '0.5', 4)]);
    expect(trips).toHaveLength(2);
    expect(trips[0]).toMatchObject({ qty: '0.75', exitPrice: '104000' });
    expect(Number(trips[0]!.avgEntry)).toBeCloseTo((100000 * 0.5 + 102000 * 0.25) / 0.75, 4);
    expect(trips[1]!.qty).toBe('0.25');
  });
});
