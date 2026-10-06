import { afterAll, beforeAll, describe, expect, it } from 'vitest';
import { createTestApp, login, onboard, resetData, tradeBody, type Agent } from './helpers.js';

const t = createTestApp();
let alice: Agent;
let bob: Agent;
let aliceAccount: string;
let bobAccount: string;
let aliceTrade: string;
let aliceJournal: string;
let aliceStrategy: string;

beforeAll(async () => {
  await resetData(t.pool);
  alice = await login(t.app, t.idp, { sub: 'alice', email: 'alice@example.com' });
  bob = await login(t.app, t.idp, { sub: 'bob', email: 'bob@example.com' });
  aliceAccount = await onboard(alice);
  bobAccount = await onboard(bob);
  aliceTrade = (await alice.post('/api/v1/trades').set('x-csrf-token', alice.csrf).send(tradeBody(aliceAccount)).expect(201)).body.id;
  aliceJournal = (
    await alice.post('/api/v1/journal').set('x-csrf-token', alice.csrf).send({ journalDate: '2026-09-15', reflection: 'Alice private note' }).expect(201)
  ).body.id;
  aliceStrategy = (await alice.post('/api/v1/strategies').set('x-csrf-token', alice.csrf).send({ name: 'Alice Secret Edge' }).expect(201)).body.id;
});
afterAll(() => t.pool.end());

describe('multi-tenant isolation (user B cannot reach user A data)', () => {
  it('lists only own trades, journals, strategies and accounts', async () => {
    const trades = await bob.get('/api/v1/trades?account=all').expect(200);
    expect(trades.body.total).toBe(0);
    const journal = await bob.get('/api/v1/journal').expect(200);
    expect(journal.body).toEqual([]);
    const strategies = await bob.get('/api/v1/strategies').expect(200);
    expect(strategies.body.map((s: { name: string }) => s.name)).not.toContain('Alice Secret Edge');
    const accounts = await bob.get('/api/v1/accounts').expect(200);
    expect(accounts.body.map((a: { id: string }) => a.id)).toEqual([bobAccount]);
  });

  it('cannot read, edit or delete another user’s trade', async () => {
    await bob.get(`/api/v1/trades/${aliceTrade}`).expect(404);
    await bob.patch(`/api/v1/trades/${aliceTrade}`).set('x-csrf-token', bob.csrf).send({ notes: 'pwned' }).expect(404);
    await bob.delete(`/api/v1/trades/${aliceTrade}`).set('x-csrf-token', bob.csrf).expect(404);
    await bob.get(`/api/v1/trades/${aliceTrade}/screenshot`).expect(404);
    const still = await alice.get(`/api/v1/trades/${aliceTrade}`).expect(200);
    expect(still.body.notes).toBeNull();
  });

  it('cannot log a trade into another user’s account', async () => {
    await bob.post('/api/v1/trades').set('x-csrf-token', bob.csrf).send(tradeBody(aliceAccount)).expect(404);
    await bob
      .post('/api/v1/trades/quick')
      .set('x-csrf-token', bob.csrf)
      .send({ command: 'buy gold 2862 sl 2858 2r', tradingAccountId: aliceAccount })
      .expect(404);
  });

  it('cannot use another user’s strategy id', async () => {
    const res = await bob.post('/api/v1/trades').set('x-csrf-token', bob.csrf).send(tradeBody(bobAccount, { strategyId: aliceStrategy }));
    expect(res.status).toBe(400);
  });

  it('cannot touch another user’s journal, strategy or account', async () => {
    await bob.patch(`/api/v1/journal/${aliceJournal}`).set('x-csrf-token', bob.csrf).send({ reflection: 'x' }).expect(404);
    await bob.delete(`/api/v1/journal/${aliceJournal}`).set('x-csrf-token', bob.csrf).expect(404);
    await bob.patch(`/api/v1/strategies/${aliceStrategy}`).set('x-csrf-token', bob.csrf).send({ name: 'mine' }).expect(404);
    await bob.delete(`/api/v1/strategies/${aliceStrategy}`).set('x-csrf-token', bob.csrf).expect(404);
    await bob.patch(`/api/v1/accounts/${aliceAccount}`).set('x-csrf-token', bob.csrf).send({ accountName: 'x' }).expect(404);
    await bob.delete(`/api/v1/accounts/${aliceAccount}`).set('x-csrf-token', bob.csrf).expect(404);
    await bob.get(`/api/v1/accounts/${aliceAccount}/transactions`).expect(404);
  });

  it('cannot scope analytics to another user’s account', async () => {
    await bob.get(`/api/v1/dashboard?account=${aliceAccount}`).expect(404);
    await bob.get(`/api/v1/calendar?month=2026-09&account=${aliceAccount}`).expect(404);
    await bob.get(`/api/v1/analytics/edge-matrix?account=${aliceAccount}`).expect(404);
    const dash = await bob.get('/api/v1/dashboard?account=all').expect(200);
    expect(dash.body.summary.trades).toBe(0);
  });

  it('cannot set another user’s account as active scope', async () => {
    await bob.patch('/api/v1/settings').set('x-csrf-token', bob.csrf).send({ activeAccountScope: aliceAccount }).expect(404);
  });

  it('exports contain only own data', async () => {
    const csv = await bob.get('/api/v1/trades/export.csv?account=all').expect(200);
    expect(csv.text.trim().split('\n')).toHaveLength(1);
    const exp = await bob.get('/api/v1/account/export').expect(200);
    expect(JSON.stringify(exp.body)).not.toContain('Alice private note');
    expect(exp.body.trades).toHaveLength(0);
  });

  it('ignores client-supplied userId fields', async () => {
    const res = await bob.post('/api/v1/trades').set('x-csrf-token', bob.csrf).send({ ...tradeBody(bobAccount), userId: 'whatever' });
    expect(res.status).toBe(400); // strict schema rejects unknown fields outright
  });
});
