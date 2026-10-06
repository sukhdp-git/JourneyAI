import { afterAll, beforeAll, describe, expect, it } from 'vitest';
import { createTestApp, FakeAiClient, login, onboard, resetData, tradeBody, type Agent } from './helpers.js';

const ai = new FakeAiClient();
const t = createTestApp({ ai });
let a: Agent;
let account: string;

beforeAll(async () => {
  await resetData(t.pool);
  a = await login(t.app, t.idp, { sub: 'analyst', email: 'analyst@example.com' });
  account = await onboard(a, { loadDemoData: true });
  const mk = (exitPrice: string, executedAt: string, extra: Record<string, unknown> = {}) =>
    a.post('/api/v1/trades').set('x-csrf-token', a.csrf).send(tradeBody(account, { exitPrice, executedAt, ...extra })).expect(201);
  // +600, -200, +400, -500 (moved stop: -500 actual, capped to the planned -200 (1R) in the flawless hypothetical)
  await mk('2874', '2026-09-01T09:00:00Z');
  await mk('2858', '2026-09-02T09:00:00Z');
  await mk('2870', '2026-09-03T09:00:00Z');
  await mk('2852', '2026-09-04T09:00:00Z', { rulesFollowed: false, mistakeTag: 'MOVED_STOP', emotion: 'REVENGE' });
});
afterAll(() => t.pool.end());

describe('analytics', () => {
  it('computes dashboard metrics from stored real trades only', async () => {
    const res = await a.get('/api/v1/dashboard?account=real').expect(200);
    const s = res.body.summary;
    expect(s.trades).toBe(4);
    expect(s.winRate).toBe(0.5);
    expect(s.netPnl).toBe('300.00');
    expect(s.grossProfit).toBe('1000.00');
    expect(s.grossLoss).toBe('700.00');
    expect(s.profitFactor).toBe(1.4286);
    expect(s.avgWin).toBe('500.00');
    expect(s.avgLoss).toBe('350.00');
    expect(s.payoffRatio).toBe(1.4286);
    expect(res.body.equity.points.map((p: { equity: string }) => p.equity)).toEqual(['10600.00', '10400.00', '10800.00', '10300.00']);
    expect(res.body.equity.maxDrawdown).toBe('500.00');
    expect(res.body.scope.demo).toBe(false);
  });

  it('never mixes DEMO DATA into real analytics', async () => {
    const real = await a.get('/api/v1/dashboard?account=real').expect(200);
    const demo = await a.get('/api/v1/dashboard?account=demo').expect(200);
    expect(real.body.summary.trades).toBe(4);
    expect(demo.body.summary.trades).toBeGreaterThanOrEqual(40);
    expect(demo.body.scope.demo).toBe(true);
    const all = await a.get('/api/v1/dashboard?account=all').expect(200);
    expect(all.body.summary.trades).toBe(real.body.summary.trades + demo.body.summary.trades);
  });

  it('applies date filters in the user time zone', async () => {
    const res = await a.get('/api/v1/dashboard?account=real&from=2026-09-02&to=2026-09-03').expect(200);
    expect(res.body.summary.trades).toBe(2);
    expect(res.body.equity.startingCapital).toBe('10600.00');
  });

  it('computes the discipline leak conservatively', async () => {
    const res = await a.get('/api/v1/analytics/discipline?account=real').expect(200);
    expect(res.body.leak).toMatchObject({ actualPnl: '300.00', flawlessPnl: '600.00', leak: '300.00', violations: 1 });
  });

  it('builds the calendar with weekly summaries', async () => {
    const res = await a.get('/api/v1/calendar?month=2026-09&account=real').expect(200);
    expect(res.body.days).toHaveLength(4);
    expect(res.body.monthTotal).toEqual({ pnl: '300.00', trades: 4 });
    expect(res.body.weeks[0].weekStart).toBe('2026-08-31');
  });

  it('runs Monte Carlo on demo history with ≥1000 paths', async () => {
    const res = await a.get('/api/v1/analytics/risk-of-ruin?account=demo&riskPercent=1&paths=1000').expect(200);
    expect(res.body.insufficientData).toBe(false);
    expect(res.body.result.paths).toBe(1000);
    expect(res.body.result.probabilities.map((p: { drawdown: number }) => p.drawdown)).toEqual([0.1, 0.25, 0.5]);
    expect(res.body.result.disclaimer).toMatch(/not a prediction/);
    await a.get('/api/v1/analytics/risk-of-ruin?riskPercent=9').expect(400);
    const small = await a.get('/api/v1/analytics/risk-of-ruin?account=real').expect(200);
    expect(small.body.insufficientData).toBe(true);
  });

  it('produces strategy, edge matrix and review analytics', async () => {
    const strat = await a.get('/api/v1/analytics/strategies?account=demo').expect(200);
    expect(strat.body.rows.length).toBeGreaterThan(5);
    const edge = await a.get('/api/v1/analytics/edge-matrix?account=demo&from=2026-08-01&to=2026-10-31').expect(200);
    expect(edge.body.bestWindow.note).toMatch(/does not guarantee/);
    expect(edge.body.byHour.length).toBeGreaterThan(0);
    const review = await a.get('/api/v1/analytics/review?period=month&anchorDate=2026-09-10&account=demo').expect(200);
    expect(review.body.text).toContain('Monthly Review');
    expect(review.body.text).toContain('[DEMO DATA]');
    expect(review.body.actionPlan.length).toBeGreaterThan(0);
  });

  it('demo data can be reset and cleared', async () => {
    await a.post('/api/v1/demo/reset').set('x-csrf-token', a.csrf).expect(200);
    const accounts = (await a.get('/api/v1/accounts').expect(200)).body as Array<{ sampleData: boolean }>;
    expect(accounts.filter((x) => x.sampleData)).toHaveLength(1);
    await a.delete('/api/v1/demo').set('x-csrf-token', a.csrf).expect(204);
    const after = (await a.get('/api/v1/accounts').expect(200)).body as Array<{ sampleData: boolean }>;
    expect(after.filter((x) => x.sampleData)).toHaveLength(0);
    const journals = await a.get('/api/v1/journal?demo=true').expect(200);
    expect(journals.body).toHaveLength(0);
  });

  it('AI coach sends aggregated context only and persists the conversation', async () => {
    const res = await a.post('/api/v1/ai/chat').set('x-csrf-token', a.csrf).send({ message: 'Analyze my emotional leaks', language: 'en', account: 'real' }).expect(200);
    expect(res.body.messages).toHaveLength(2);
    const req = ai.requests.at(-1)!;
    expect(req.context).toContain('"overall"');
    expect(req.context).not.toContain('analyst@example.com');
    const convs = await a.get('/api/v1/ai/conversations').expect(200);
    expect(convs.body).toHaveLength(1);
    const msgs = await a.get(`/api/v1/ai/conversations/${res.body.conversationId}/messages`).expect(200);
    expect(msgs.body.map((m: { role: string }) => m.role)).toEqual(['user', 'assistant']);
  });
});

describe('AI not configured', () => {
  const t2 = createTestApp({ ai: null });
  afterAll(() => t2.pool.end());
  it('disables AI gracefully with an explicit message', async () => {
    const b = await login(t2.app, t2.idp, { sub: 'noai', email: 'noai@example.com' });
    const status = await b.get('/api/v1/ai/status').expect(200);
    expect(status.body).toEqual({ configured: false, message: 'AI Coach requires server configuration.' });
    const res = await b.post('/api/v1/ai/chat').set('x-csrf-token', b.csrf).send({ message: 'hi' }).expect(503);
    expect(res.body.error.message).toBe('AI Coach requires server configuration.');
  });
});
