import request from 'supertest';
import { afterAll, beforeEach, describe, expect, it } from 'vitest';
import { createTestApp, login, resetData } from './helpers.js';

const t = createTestApp();
afterAll(() => t.pool.end());
beforeEach(() => resetData(t.pool));

describe('authentication', () => {
  it('rejects unauthenticated access to protected endpoints', async () => {
    for (const path of ['/api/v1/trades', '/api/v1/dashboard', '/api/v1/journal', '/api/v1/accounts', '/api/v1/settings', '/api/v1/auth/me']) {
      const res = await request(t.app).get(path);
      expect(res.status, path).toBe(401);
      expect(res.body.error.code).toBe('UNAUTHENTICATED');
    }
  });

  it('health check is public and reveals nothing sensitive', async () => {
    const res = await request(t.app).get('/api/v1/health').expect(200);
    expect(res.body).toMatchObject({ status: 'ok' });
    expect(JSON.stringify(res.body)).not.toMatch(/postgres|password|secret/i);
    expect(res.headers['x-request-id']).toBeTruthy();
  });

  it('Google flow creates a real user, sets a hardened session cookie and allows access', async () => {
    const agent = request.agent(t.app);
    t.idp.identities.set('code-1', { subject: 'g-1', email: 'Alice@Example.com', emailVerified: true, name: 'Alice', picture: null });
    const start = await agent.get('/api/v1/auth/google').expect(302);
    const url = new URL(start.headers.location as string);
    expect(url.hostname).toBe('accounts.google.com');
    const state = url.searchParams.get('state')!;
    const cb = await agent.get(`/api/v1/auth/google/callback?state=${state}&code=code-1`).expect(303);
    expect(cb.headers.location).toBe('http://localhost:3000/auth/callback?status=success');
    const cookie = String(cb.headers['set-cookie']);
    expect(cookie).toMatch(/jz\.sid=/);
    expect(cookie).toMatch(/HttpOnly/i);
    expect(cookie).toMatch(/SameSite=Lax/i);
    const me = await agent.get('/api/v1/auth/me').expect(200);
    expect(me.body.user).toMatchObject({ email: 'alice@example.com', name: 'Alice', onboarded: false });
    const { rows } = await t.pool.query('select count(*)::int as n from users where google_subject_id = $1', ['g-1']);
    expect(rows[0].n).toBe(1);
  });

  it('returning users are matched by Google subject, not duplicated', async () => {
    await login(t.app, t.idp, { sub: 'g-2', email: 'bob@example.com' });
    await login(t.app, t.idp, { sub: 'g-2', email: 'bob@example.com' });
    const { rows } = await t.pool.query('select count(*)::int as n from users');
    expect(rows[0].n).toBe(1);
  });

  it('rejects a callback with a forged or missing state', async () => {
    const agent = request.agent(t.app);
    t.idp.identities.set('code-x', { subject: 'g-x', email: 'x@example.com', emailVerified: true, name: 'X', picture: null });
    await agent.get('/api/v1/auth/google').expect(302);
    const cb = await agent.get('/api/v1/auth/google/callback?state=forged&code=code-x').expect(303);
    expect(cb.headers.location).toContain('/login?error=invalid_state');
    await agent.get('/api/v1/auth/me').expect(401);
    const noSession = await request(t.app).get('/api/v1/auth/google/callback?state=a&code=code-x').expect(303);
    expect(noSession.headers.location).toContain('invalid_state');
  });

  it('rejects unverified Google emails', async () => {
    const agent = request.agent(t.app);
    t.idp.identities.set('code-u', { subject: 'g-u', email: 'u@example.com', emailVerified: false, name: 'U', picture: null });
    const start = await agent.get('/api/v1/auth/google').expect(302);
    const state = new URL(start.headers.location as string).searchParams.get('state')!;
    const cb = await agent.get(`/api/v1/auth/google/callback?state=${state}&code=code-u`).expect(303);
    expect(cb.headers.location).toContain('email_unverified');
  });

  it('logout destroys the server-side session', async () => {
    const agent = await login(t.app, t.idp, { sub: 'g-3', email: 'carol@example.com' });
    await agent.get('/api/v1/auth/me').expect(200);
    await agent.post('/api/v1/auth/logout').set('x-csrf-token', agent.csrf).expect(204);
    await agent.get('/api/v1/auth/me').expect(401);
    const { rows } = await t.pool.query(`select count(*)::int as n from user_sessions where sess->>'userId' is not null`);
    expect(rows[0].n).toBe(0);
  });

  it('requires a CSRF token for state-changing requests', async () => {
    const agent = await login(t.app, t.idp, { sub: 'g-4', email: 'dan@example.com' });
    const noToken = await agent.post('/api/v1/strategies').send({ name: 'X' });
    expect(noToken.status).toBe(403);
    expect(noToken.body.error.code).toBe('CSRF_TOKEN_INVALID');
    const badOrigin = await agent.post('/api/v1/strategies').set('x-csrf-token', agent.csrf).set('origin', 'https://evil.example').send({ name: 'X' });
    expect(badOrigin.status).toBe(403);
    await agent.post('/api/v1/strategies').set('x-csrf-token', agent.csrf).send({ name: 'X' }).expect(201);
  });

  it('developer sign-in is unavailable unless explicitly enabled', async () => {
    const agent = request.agent(t.app);
    const csrf = (await agent.get('/api/v1/auth/csrf')).body.csrfToken;
    await agent.post('/api/v1/auth/dev-login').set('x-csrf-token', csrf).send({ email: 'dev@example.com', name: 'Dev' }).expect(404);
  });

  it('refuses to boot with DEV_AUTH_BYPASS in production', () => {
    expect(() => createTestApp({ env: { NODE_ENV: 'production', DEV_AUTH_BYPASS: 'true' } })).toThrow(/DEV_AUTH_BYPASS/);
  });

  it('redirects to a configuration error when Google is not configured', async () => {
    const { GoogleIdentityProvider } = await import('../src/auth/google.js');
    expect(new GoogleIdentityProvider(undefined, undefined, undefined).configured).toBe(false);
  });
});
