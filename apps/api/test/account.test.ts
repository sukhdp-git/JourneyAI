import { afterAll, beforeAll, describe, expect, it } from 'vitest';
import { createTestApp, login, onboard, resetData, tradeBody, type Agent } from './helpers.js';

const t = createTestApp();
let a: Agent;
let account: string;

beforeAll(async () => {
  await resetData(t.pool);
  a = await login(t.app, t.idp, { sub: 'owner', email: 'owner@example.com' });
  account = await onboard(a);
});
afterAll(() => t.pool.end());

describe('settings, journal, checklist, capital and account lifecycle', () => {
  it('persists settings and validates time zones', async () => {
    const res = await a.patch('/api/v1/settings').set('x-csrf-token', a.csrf).send({ theme: 'midnight-navy', timezone: 'Asia/Kolkata', language: 'pt' }).expect(200);
    expect(res.body).toMatchObject({ theme: 'midnight-navy', timezone: 'Asia/Kolkata', language: 'pt' });
    await a.patch('/api/v1/settings').set('x-csrf-token', a.csrf).send({ timezone: 'Mars/Olympus' }).expect(400);
    await a.patch('/api/v1/settings').set('x-csrf-token', a.csrf).send({ theme: 'neon-pink' }).expect(400);
  });

  it('upserts journal entries per day', async () => {
    const one = await a.post('/api/v1/journal').set('x-csrf-token', a.csrf).send({ journalDate: '2026-10-01', reflection: 'first', disciplineRating: 7 }).expect(201);
    const two = await a.post('/api/v1/journal').set('x-csrf-token', a.csrf).send({ journalDate: '2026-10-01', keyLesson: 'wait' }).expect(201);
    expect(two.body.id).toBe(one.body.id);
    expect(two.body).toMatchObject({ reflection: 'first', keyLesson: 'wait', disciplineRating: 7 });
    await a.post('/api/v1/journal').set('x-csrf-token', a.csrf).send({ journalDate: '2026-10-01', disciplineRating: 11 }).expect(400);
    await a.delete(`/api/v1/journal/${one.body.id}`).set('x-csrf-token', a.csrf).expect(204);
  });

  it('persists the 9-step checklist', async () => {
    await a.put('/api/v1/checklist').set('x-csrf-token', a.csrf).send({ date: '2026-10-06', itemKey: 'news_checked', completed: true }).expect(200);
    const res = await a.get('/api/v1/checklist?date=2026-10-06').expect(200);
    expect(res.body.items).toHaveLength(9);
    expect(res.body.items.find((i: { key: string }) => i.key === 'news_checked').completed).toBe(true);
    await a.put('/api/v1/checklist').set('x-csrf-token', a.csrf).send({ date: '2026-10-06', itemKey: 'bogus', completed: true }).expect(400);
  });

  it('tracks capital transactions in current capital', async () => {
    await a.post(`/api/v1/accounts/${account}/transactions`).set('x-csrf-token', a.csrf).send({ type: 'DEPOSIT', amount: '500' }).expect(201);
    const w = await a.post(`/api/v1/accounts/${account}/transactions`).set('x-csrf-token', a.csrf).send({ type: 'WITHDRAWAL', amount: '200' }).expect(201);
    expect(w.body.currentCapital).toBe('10300.00');
    await a.post(`/api/v1/accounts/${account}/transactions`).set('x-csrf-token', a.csrf).send({ type: 'WITHDRAWAL', amount: '-5' }).expect(400);
  });

  it('terminal lock can be extended but never shortened', async () => {
    const long = await a.post('/api/v1/settings/terminal-lock').set('x-csrf-token', a.csrf).send({ minutes: 60 }).expect(200);
    const short = await a.post('/api/v1/settings/terminal-lock').set('x-csrf-token', a.csrf).send({ minutes: 5 }).expect(200);
    expect(short.body.terminalLockUntil).toBe(long.body.terminalLockUntil);
  });

  it('exports all personal data and deletes the account with strong confirmation', async () => {
    await a.post('/api/v1/trades').set('x-csrf-token', a.csrf).send(tradeBody(account)).expect(201);
    const exp = await a.get('/api/v1/account/export').expect(200);
    expect(exp.body.trades).toHaveLength(1);
    expect(exp.body.settings.timezone).toBe('Asia/Kolkata');
    expect(JSON.stringify(exp.body)).not.toMatch(/encrypted|google_subject/i);
    await a.delete('/api/v1/account').set('x-csrf-token', a.csrf).send({ confirmation: 'wrong@example.com' }).expect(400);
    await a.delete('/api/v1/account').set('x-csrf-token', a.csrf).send({ confirmation: 'OWNER@example.com' }).expect(204);
    await a.get('/api/v1/auth/me').expect(401);
    const counts = await t.pool.query(`select (select count(*) from users)::int u, (select count(*) from trades)::int t, (select count(*) from trading_accounts)::int a, (select count(*) from audit_logs where action='user.deleted' and user_id is null)::int d`);
    expect(counts.rows[0]).toEqual({ u: 0, t: 0, a: 0, d: 1 });
  });
});
