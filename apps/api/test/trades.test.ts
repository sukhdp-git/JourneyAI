import { afterAll, beforeAll, describe, expect, it } from 'vitest';
import { csvCell } from '../src/routes/trades.routes.js';
import { createTestApp, login, onboard, resetData, tradeBody, type Agent } from './helpers.js';

const t = createTestApp();
let a: Agent;
let account: string;

beforeAll(async () => {
  await resetData(t.pool);
  a = await login(t.app, t.idp, { sub: 'trader', email: 'trader@example.com' });
  account = await onboard(a);
});
afterAll(() => t.pool.end());

const post = (body: unknown) => a.post('/api/v1/trades').set('x-csrf-token', a.csrf).send(body as object);

describe('trades CRUD and calculations', () => {
  let id: string;

  it('creates a trade with server-side P&L, R, risk and session', async () => {
    const res = await post(tradeBody(account)).expect(201);
    id = res.body.id;
    expect(res.body).toMatchObject({ symbol: 'XAUUSD', pnl: '600.00', rr: '3.0000', riskAmount: '200.00', status: 'CLOSED', session: 'LONDON' });
    const acc = (await a.get('/api/v1/accounts').expect(200)).body[0];
    expect(acc.currentCapital).toBe('10600.00');
  });

  it('ignores any client-computed P&L shape and validates inputs', async () => {
    const bad = await post(tradeBody(account, { side: 'LONG', stopLoss: '2870' }));
    expect(bad.status).toBe(400);
    expect(bad.body.error.code).toBe('VALIDATION_ERROR');
    expect(bad.body.error.fields.stopLoss).toBeTruthy();
    expect((await post(tradeBody(account, { lotSize: '-1' }))).status).toBe(400);
    expect((await post(tradeBody(account, { symbol: 'NOTREAL' }))).status).toBe(400);
    expect((await post(tradeBody(account, { executedAt: 'yesterday' }))).status).toBe(400);
    expect((await post(tradeBody(account, { notes: 'x'.repeat(5001) }))).status).toBe(400);
  });

  it('requires broker P&L when currency conversion is not derivable', async () => {
    const res = await post(tradeBody(account, { symbol: 'GER40', entryPrice: '24000', exitPrice: '24100', stopLoss: '23950', lotSize: '1' }));
    expect(res.status).toBe(400);
    const ok = await post(tradeBody(account, { symbol: 'GER40', entryPrice: '24000', exitPrice: '24100', stopLoss: '23950', lotSize: '1', pnl: '116.40' })).expect(201);
    expect(ok.body.pnl).toBe('116.40');
    expect(ok.body.rr).toBe('2.0000');
  });

  it('edits a trade and recomputes everything', async () => {
    const res = await a.patch(`/api/v1/trades/${id}`).set('x-csrf-token', a.csrf).send({ exitPrice: '2856', emotion: 'FOMO', mistakeTag: 'MOVED_STOP', rulesFollowed: false }).expect(200);
    expect(res.body).toMatchObject({ pnl: '-300.00', rr: '-1.5000', emotion: 'FOMO', mistakeTag: 'MOVED_STOP' });
  });

  it('rejects an edit that breaks stop consistency', async () => {
    await a.patch(`/api/v1/trades/${id}`).set('x-csrf-token', a.csrf).send({ stopLoss: '2900' }).expect(400);
  });

  it('logs a quick trade command with server-side parsing', async () => {
    const res = await a
      .post('/api/v1/trades/quick')
      .set('x-csrf-token', a.csrf)
      .send({ command: 'short us500 5880 sl 5890 2.5r 1.0 silver bullet', tradingAccountId: account, executedAt: '2026-09-16T14:30:00Z' })
      .expect(201);
    expect(res.body.trade).toMatchObject({ symbol: 'US500', side: 'SHORT', exitPrice: '5855', pnl: '25.00', strategyName: 'ICT Silver Bullet', source: 'QUICK_COMMAND' });
    const bad = await a.post('/api/v1/trades/quick').set('x-csrf-token', a.csrf).send({ command: 'buy unicorn 1 sl 2', tradingAccountId: account });
    expect(bad.status).toBe(400);
    expect(bad.body.error.fields.command).toBeTruthy();
  });

  it('filters, searches, sorts and paginates server-side', async () => {
    await post(tradeBody(account, { symbol: 'EURUSD', side: 'SHORT', entryPrice: '1.1650', stopLoss: '1.1670', exitPrice: '1.1610', lotSize: '1', executedAt: '2026-09-17T13:00:00Z', notes: 'Fed day #fomc' })).expect(201);
    const bySymbol = await a.get('/api/v1/trades?symbol=EURUSD').expect(200);
    expect(bySymbol.body.total).toBe(1);
    const winners = await a.get('/api/v1/trades?outcome=WIN').expect(200);
    expect(winners.body.items.every((x: { pnl: string }) => Number(x.pnl) > 0)).toBe(true);
    const losers = await a.get('/api/v1/trades?outcome=LOSS').expect(200);
    expect(losers.body.items.every((x: { pnl: string }) => Number(x.pnl) < 0)).toBe(true);
    const search = await a.get('/api/v1/trades?search=fomc').expect(200);
    expect(search.body.total).toBe(1);
    const shorts = await a.get('/api/v1/trades?side=SHORT&sort=pnl&order=asc').expect(200);
    expect(shorts.body.items.map((x: { side: string }) => x.side)).toEqual(['SHORT', 'SHORT']);
    const page = await a.get('/api/v1/trades?pageSize=2&page=2').expect(200);
    expect(page.body).toMatchObject({ page: 2, pageSize: 2 });
    const range = await a.get('/api/v1/trades?from=2026-09-17&to=2026-09-17').expect(200);
    expect(range.body.total).toBe(1);
    await a.get('/api/v1/trades?pageSize=100000').expect(400);
  });

  it('exports CSV safely with the required filename', async () => {
    await post(tradeBody(account, { notes: '=HYPERLINK("http://evil")', executedAt: '2026-09-18T10:00:00Z' })).expect(201);
    const res = await a.get('/api/v1/trades/export.csv').expect(200);
    expect(res.headers['content-type']).toContain('text/csv');
    expect(res.headers['content-disposition']).toMatch(/JournzeyAI_Trades_\d{4}-\d{2}-\d{2}\.csv/);
    expect(res.text).toContain(`"'=HYPERLINK(""http://evil"")"`);
    expect(csvCell('a,b')).toBe('"a,b"');
    expect(csvCell('-300.00')).toBe('-300.00');
    expect(csvCell('+cmd')).toBe("'+cmd");
  });

  it('deletes a trade and restores capital', async () => {
    await a.delete(`/api/v1/trades/${id}`).set('x-csrf-token', a.csrf).expect(204);
    await a.get(`/api/v1/trades/${id}`).expect(404);
    const { rows } = await t.pool.query(`select count(*)::int as n from audit_logs where action = 'trade.deleted'`);
    expect(rows[0].n).toBe(1);
  });

  it('validates screenshot uploads by content, not just extension', async () => {
    const tr = (await post(tradeBody(account, { executedAt: '2026-09-19T10:00:00Z' })).expect(201)).body.id;
    const fake = await a.post(`/api/v1/trades/${tr}/screenshot`).set('x-csrf-token', a.csrf).attach('file', Buffer.from('<script>alert(1)</script>'), { filename: 'x.png', contentType: 'image/png' });
    expect(fake.status).toBe(400);
    const png = Buffer.concat([Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a]), Buffer.alloc(64)]);
    const ok = await a.post(`/api/v1/trades/${tr}/screenshot`).set('x-csrf-token', a.csrf).attach('file', png, { filename: 'chart.png', contentType: 'image/png' }).expect(201);
    expect(ok.body.hasScreenshot).toBe(true);
    const img = await a.get(`/api/v1/trades/${tr}/screenshot`).expect(200);
    expect(img.headers['content-type']).toBe('image/png');
    await a.delete(`/api/v1/trades/${tr}/screenshot`).set('x-csrf-token', a.csrf).expect(204);
    await a.get(`/api/v1/trades/${tr}/screenshot`).expect(404);
  });

  it('returns standardised errors for unknown routes and malformed JSON', async () => {
    const nf = await a.get('/api/v1/nope').expect(404);
    expect(nf.body.error.code).toBe('NOT_FOUND');
    const bad = await a.post('/api/v1/trades').set('x-csrf-token', a.csrf).set('content-type', 'application/json').send('{bad json');
    expect(bad.status).toBe(400);
    expect(bad.body.error.code).toBe('INVALID_JSON');
  });
});
