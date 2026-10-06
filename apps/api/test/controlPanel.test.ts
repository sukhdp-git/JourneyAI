import request from 'supertest';
import { afterAll, beforeAll, describe, expect, it } from 'vitest';
import { totpCode } from '../src/lib/totp.js';
import { createTestApp, login, onboard, resetData, FakeAiClient, type Agent } from './helpers.js';

const OWNER = { email: 'owner@journzey.test', password: 'Strong-Passw0rd-123' };
const t = createTestApp({ ai: new FakeAiClient() });
afterAll(() => t.pool.end());

type AdminAgent = ReturnType<typeof request.agent> & { csrf: string };

async function adminLogin(email: string, password: string, totp?: string): Promise<AdminAgent> {
  const a = request.agent(t.app) as AdminAgent;
  const csrf = (await a.get('/api/v1/admin/auth/csrf').expect(200)).body.csrfToken;
  const res = await a.post('/api/v1/admin/auth/login').set('x-csrf-token', csrf).send({ email, password, ...(totp ? { totp } : {}) });
  if (res.status !== 200 || !res.body.csrfToken) throw new Error(`admin login failed: ${res.status} ${JSON.stringify(res.body)}`);
  a.csrf = res.body.csrfToken;
  return a;
}

let owner: AdminAgent;
let trader: Agent;

beforeAll(async () => {
  await resetData(t.pool);
  await t.ctx.runtime.reload();
  await t.ctx.admin.create({ email: OWNER.email, name: 'Owner', password: OWNER.password, role: 'owner' });
  owner = await adminLogin(OWNER.email, OWNER.password);
  trader = await login(t.app, t.idp, { sub: 'cp-trader', email: 'trader@journzey.test' });
  await onboard(trader);
});

describe('control panel authentication', () => {
  it('reports setup status and rejects unauthenticated access', async () => {
    await request(t.app).get('/api/v1/admin/overview').expect(401);
    expect((await request(t.app).get('/api/v1/admin/auth/status').expect(200)).body).toEqual({ setupRequired: false });
  });

  it('a trader session never grants control-panel access', async () => {
    await trader.get('/api/v1/admin/overview').expect(401);
    await trader.get('/api/v1/admin/settings').expect(401);
  });

  it('uses a separate, path-scoped, SameSite=Strict HttpOnly cookie', async () => {
    const a = request.agent(t.app);
    const csrf = (await a.get('/api/v1/admin/auth/csrf')).body.csrfToken;
    const res = await a.post('/api/v1/admin/auth/login').set('x-csrf-token', csrf).send(OWNER).expect(200);
    const cookie = String(res.headers['set-cookie']);
    expect(cookie).toMatch(/jz\.cp=/);
    expect(cookie).toMatch(/Path=\/api\/v1\/admin/);
    expect(cookie).toMatch(/SameSite=Strict/i);
    expect(cookie).toMatch(/HttpOnly/i);
    expect(res.headers['cache-control']).toBe('no-store');
    expect(res.headers['x-robots-tag']).toMatch(/noindex/);
  });

  it('rejects bad passwords and locks the account after repeated failures', async () => {
    await t.ctx.admin.create({ email: 'victim@journzey.test', name: 'V', password: 'Lockme-Passw0rd-123', role: 'viewer' });
    const a = request.agent(t.app);
    const csrf = (await a.get('/api/v1/admin/auth/csrf')).body.csrfToken;
    for (let i = 0; i < 5; i++) {
      const r = await a.post('/api/v1/admin/auth/login').set('x-csrf-token', csrf).send({ email: 'victim@journzey.test', password: 'wrong-password' });
      expect(r.status).toBe(401);
    }
    const locked = await a.post('/api/v1/admin/auth/login').set('x-csrf-token', csrf).send({ email: 'victim@journzey.test', password: 'Lockme-Passw0rd-123' });
    expect(locked.status).toBe(423);
    const unknown = await a.post('/api/v1/admin/auth/login').set('x-csrf-token', csrf).send({ email: 'nobody@journzey.test', password: 'x' });
    expect(unknown.status).toBe(401);
  });

  it('requires CSRF tokens for state changes', async () => {
    const r = await owner.patch('/api/v1/admin/settings').send({ 'site.name': 'X' });
    expect(r.status).toBe(403);
  });

  it('enforces the password policy', async () => {
    const r = await owner.post('/api/v1/admin/admins').set('x-csrf-token', owner.csrf).send({ email: 'weak@journzey.test', name: 'W', password: 'short', role: 'admin' });
    expect(r.status).toBe(400);
  });

  it('supports TOTP two-factor authentication', async () => {
    await t.ctx.admin.create({ email: 'mfa@journzey.test', name: 'M', password: 'Second-Factor-123', role: 'admin' });
    const a = await adminLogin('mfa@journzey.test', 'Second-Factor-123');
    const setup = await a.post('/api/v1/admin/auth/totp/setup').set('x-csrf-token', a.csrf).expect(200);
    expect(setup.body.qrDataUrl).toMatch(/^data:image\/png;base64,/);
    await a.post('/api/v1/admin/auth/totp/enable').set('x-csrf-token', a.csrf).send({ code: '000000' }).expect(400);
    await a.post('/api/v1/admin/auth/totp/enable').set('x-csrf-token', a.csrf).send({ code: totpCode(setup.body.secret) }).expect(204);
    const b = request.agent(t.app);
    const csrf = (await b.get('/api/v1/admin/auth/csrf')).body.csrfToken;
    const step1 = await b.post('/api/v1/admin/auth/login').set('x-csrf-token', csrf).send({ email: 'mfa@journzey.test', password: 'Second-Factor-123' }).expect(200);
    expect(step1.body).toEqual({ totpRequired: true });
    await b.get('/api/v1/admin/auth/me').expect(401);
    await adminLogin('mfa@journzey.test', 'Second-Factor-123', totpCode(setup.body.secret));
  });
});

describe('live settings and API keys', () => {
  it('stores secrets encrypted and never returns them', async () => {
    const secret = 'GOCSPX-super-secret-value-9876';
    const res = await owner
      .patch('/api/v1/admin/settings')
      .set('x-csrf-token', owner.csrf)
      .send({ 'google.clientId': '1234-abc.apps.googleusercontent.com', 'google.clientSecret': secret })
      .expect(200);
    expect(JSON.stringify(res.body)).not.toContain(secret);
    const cs = res.body.settings.find((s: { key: string }) => s.key === 'google.clientSecret');
    expect(cs).toMatchObject({ configured: true, hint: '…9876', source: 'control-panel' });
    const { rows } = await t.pool.query(`select encrypted_value, value from app_settings where key = 'google.clientSecret'`);
    expect(rows[0].encrypted_value).toMatch(/^v1:/);
    expect(rows[0].encrypted_value).not.toContain(secret);
    const audit = await t.pool.query(`select metadata::text from admin_audit_logs where action = 'settings.updated'`);
    expect(audit.rows.map((r) => r.metadata).join()).not.toContain(secret);
  });

  it('Google keys saved in the control panel drive the live sign-in flow without restart', async () => {
    const live = createTestApp({ env: { APP_URL: 'http://localhost:3000' } });
    // Use the real Google provider (no fake injected) backed by the same database settings.
    const { createApp } = await import('../src/app.js');
    const { app, ctx } = createApp({ env: live.ctx.env, db: live.db, pool: live.pool, log: live.ctx.log, storage: live.ctx.storage });
    await ctx.runtime.reload();
    const providers = await request(app).get('/api/v1/auth/providers').expect(200);
    expect(providers.body.google).toBe(true);
    const r = await request(app).get('/api/v1/auth/google').expect(302);
    const url = new URL(r.headers.location as string);
    expect(url.host).toBe('accounts.google.com');
    expect(url.searchParams.get('client_id')).toBe('1234-abc.apps.googleusercontent.com');
    expect(url.searchParams.get('redirect_uri')).toBe('http://localhost:3000/api/v1/auth/google/callback');
    await live.pool.end();
  });

  it('validates setting values and rejects unknown keys', async () => {
    await owner.patch('/api/v1/admin/settings').set('x-csrf-token', owner.csrf).send({ 'google.clientId': 'not-a-client-id' }).expect(400);
    await owner.patch('/api/v1/admin/settings').set('x-csrf-token', owner.csrf).send({ 'evil.key': 'x' }).expect(400);
    await owner.patch('/api/v1/admin/settings').set('x-csrf-token', owner.csrf).send({ 'site.maintenance.enabled': 'yes' }).expect(400);
  });

  it('resetting a setting falls back to the environment/default', async () => {
    const res = await owner.delete('/api/v1/admin/settings/google.clientId').set('x-csrf-token', owner.csrf).expect(200);
    expect(res.body.settings.find((s: { key: string }) => s.key === 'google.clientId').source).toBe('unset');
  });

  it('viewers can read but not change settings', async () => {
    await owner.post('/api/v1/admin/admins').set('x-csrf-token', owner.csrf).send({ email: 'viewer@journzey.test', name: 'Viewer', password: 'Readonly-Passw0rd-1', role: 'viewer' }).expect(201);
    const v = await adminLogin('viewer@journzey.test', 'Readonly-Passw0rd-1');
    await v.get('/api/v1/admin/settings').expect(200);
    await v.patch('/api/v1/admin/settings').set('x-csrf-token', v.csrf).send({ 'site.name': 'Hacked' }).expect(403);
    await v.get('/api/v1/admin/admins').expect(403);
  });
});

describe('controlling the live website', () => {
  it('maintenance mode blocks the trader terminal but not the control panel', async () => {
    await owner.patch('/api/v1/admin/settings').set('x-csrf-token', owner.csrf).send({ 'site.maintenance.enabled': true, 'site.maintenance.message': 'Back at 18:00 UTC' }).expect(200);
    const blocked = await trader.get('/api/v1/trades').expect(503);
    expect(blocked.body.error).toEqual({ code: 'MAINTENANCE', message: 'Back at 18:00 UTC' });
    const site = await request(t.app).get('/api/v1/site').expect(200);
    expect(site.body.maintenance).toEqual({ enabled: true, message: 'Back at 18:00 UTC' });
    await owner.get('/api/v1/admin/overview').expect(200);
    await owner.patch('/api/v1/admin/settings').set('x-csrf-token', owner.csrf).send({ 'site.maintenance.enabled': false }).expect(200);
    await trader.get('/api/v1/trades').expect(200);
  });

  it('publishes announcement banners and branding', async () => {
    await owner.patch('/api/v1/admin/settings').set('x-csrf-token', owner.csrf).send({ 'site.announcement.enabled': true, 'site.announcement.text': 'New: AI reviews', 'site.announcement.tone': 'success', 'site.name': 'journzey.ai Pro' }).expect(200);
    const site = await request(t.app).get('/api/v1/site').expect(200);
    expect(site.body).toMatchObject({ name: 'journzey.ai Pro', announcement: { enabled: true, text: 'New: AI reviews', tone: 'success' } });
  });

  it('feature switches are enforced server-side', async () => {
    await owner.patch('/api/v1/admin/settings').set('x-csrf-token', owner.csrf).send({ 'features.aiCoach': false, 'features.brokerSync': false }).expect(200);
    const ai = await trader.post('/api/v1/ai/chat').set('x-csrf-token', trader.csrf).send({ message: 'hi' }).expect(403);
    expect(ai.body.error.code).toBe('FEATURE_DISABLED');
    await trader.get('/api/v1/broker-connections').expect(403);
    const me = await trader.get('/api/v1/auth/me').expect(200);
    expect(me.body.features).toMatchObject({ aiCoach: false, ai: false, brokerSync: false });
    await owner.patch('/api/v1/admin/settings').set('x-csrf-token', owner.csrf).send({ 'features.aiCoach': true, 'features.brokerSync': true }).expect(200);
    await trader.post('/api/v1/ai/chat').set('x-csrf-token', trader.csrf).send({ message: 'hi' }).expect(200);
  });

  it('closing registration refuses new sign-ups but not existing users', async () => {
    await owner.patch('/api/v1/admin/settings').set('x-csrf-token', owner.csrf).send({ 'site.registrationOpen': false }).expect(200);
    await expect(login(t.app, t.idp, { sub: 'brand-new', email: 'new@journzey.test' })).rejects.toThrow(/registration_closed/);
    await login(t.app, t.idp, { sub: 'cp-trader', email: 'trader@journzey.test' });
    await owner.patch('/api/v1/admin/settings').set('x-csrf-token', owner.csrf).send({ 'site.registrationOpen': true }).expect(200);
  });
});

describe('user management', () => {
  it('lists users, suspends (revoking sessions) and restores them', async () => {
    const list = await owner.get('/api/v1/admin/users?search=trader').expect(200);
    expect(list.body.total).toBe(1);
    const id = list.body.items[0].id;
    const detail = await owner.get(`/api/v1/admin/users/${id}`).expect(200);
    expect(detail.body.counts.sessions).toBeGreaterThanOrEqual(1);
    await owner.post(`/api/v1/admin/users/${id}/suspend`).set('x-csrf-token', owner.csrf).expect(200);
    await trader.get('/api/v1/trades').expect(401);
    await expect(login(t.app, t.idp, { sub: 'cp-trader', email: 'trader@journzey.test' })).rejects.toThrow(/account_suspended/);
    await owner.post(`/api/v1/admin/users/${id}/unsuspend`).set('x-csrf-token', owner.csrf).expect(200);
    trader = await login(t.app, t.idp, { sub: 'cp-trader', email: 'trader@journzey.test' });
    await trader.get('/api/v1/trades').expect(200);
  });

  it('overview reports platform stats', async () => {
    const o = await owner.get('/api/v1/admin/overview').expect(200);
    expect(o.body.stats.users).toBe(1);
    expect(o.body.daily).toHaveLength(30);
    expect(o.body.integrations.ai.active).toBe(true);
  });

  it('only owners delete users, with typed confirmation', async () => {
    const id = (await owner.get('/api/v1/admin/users').expect(200)).body.items[0].id;
    const admin = await owner.post('/api/v1/admin/admins').set('x-csrf-token', owner.csrf).send({ email: 'ops@journzey.test', name: 'Ops', password: 'Operations-Pass-123', role: 'admin' }).expect(201);
    const ops = await adminLogin('ops@journzey.test', 'Operations-Pass-123');
    await ops.delete(`/api/v1/admin/users/${id}`).set('x-csrf-token', ops.csrf).send({ confirmation: 'trader@journzey.test' }).expect(403);
    await owner.delete(`/api/v1/admin/users/${id}`).set('x-csrf-token', owner.csrf).send({ confirmation: 'wrong' }).expect(400);
    await owner.delete(`/api/v1/admin/users/${id}`).set('x-csrf-token', owner.csrf).send({ confirmation: 'trader@journzey.test' }).expect(204);
    expect((await owner.get('/api/v1/admin/users').expect(200)).body.total).toBe(0);
    expect(admin.body.role).toBe('admin');
  });

  it('protects the last owner and records an audit trail', async () => {
    const me = (await owner.get('/api/v1/admin/auth/me').expect(200)).body.admin;
    await owner.delete(`/api/v1/admin/admins/${me.id}`).set('x-csrf-token', owner.csrf).expect(403);
    const audit = await owner.get('/api/v1/admin/audit?source=admin').expect(200);
    const actions = audit.body.items.map((i: { action: string }) => i.action);
    expect(actions).toEqual(expect.arrayContaining(['auth.login', 'settings.updated', 'user.suspended', 'user.deleted', 'admin.created']));
  });

  it('unknown control-panel routes are 404 and never fall through', async () => {
    await owner.get('/api/v1/admin/does-not-exist').expect(404);
  });

  it('logout destroys the admin session', async () => {
    const a = await adminLogin(OWNER.email, OWNER.password);
    await a.post('/api/v1/admin/auth/logout').set('x-csrf-token', a.csrf).expect(204);
    await a.get('/api/v1/admin/auth/me').expect(401);
  });
});
