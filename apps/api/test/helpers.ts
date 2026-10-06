import request from 'supertest';
import type { Express } from 'express';
import { createApp } from '../src/app.js';
import { loadEnv } from '../src/config/env.js';
import { createDb, createPool } from '../src/db/client.js';
import { createLogger } from '../src/lib/logger.js';
import { LocalStorage } from '../src/services/storage.js';
import type { AuthorizationRequest, IdentityProvider, VerifiedIdentity } from '../src/auth/google.js';
import type { AiClient, CompletionRequest, CompletionResult } from '../src/services/ai.js';
import type { MarketDataProvider } from '../src/services/marketData.js';
import { TEST_DATABASE_URL } from './globalSetup.js';
import path from 'node:path';
import os from 'node:os';

/**
 * Fake IdP: behaves like Google from the app's perspective (state/nonce/PKCE are still
 * generated and validated by the real callback route) but resolves identities locally.
 * The authorization "code" is the key into the registered identities map.
 */
export class FakeIdentityProvider implements IdentityProvider {
  readonly configured = true;
  identities = new Map<string, VerifiedIdentity>();
  lastNonce: string | null = null;
  createAuthorizationRequest(): AuthorizationRequest {
    const state = `state-${Math.random().toString(36).slice(2)}`;
    const nonce = `nonce-${Math.random().toString(36).slice(2)}`;
    return { url: `https://accounts.google.com/o/oauth2/v2/auth?state=${state}&nonce=${nonce}`, state, nonce, codeVerifier: 'verifier' };
  }
  async exchangeCode(code: string, _verifier: string, nonce: string): Promise<VerifiedIdentity> {
    this.lastNonce = nonce;
    const id = this.identities.get(code);
    if (!id) throw new Error('invalid_grant');
    return id;
  }
}

export class FakeAiClient implements AiClient {
  readonly model = 'test-model';
  requests: CompletionRequest[] = [];
  async complete(req: CompletionRequest): Promise<CompletionResult> {
    this.requests.push(req);
    return { text: 'Coach reply based on your statistics.', model: this.model, inputTokens: 10, outputTokens: 5, refused: false };
  }
}

export function createTestApp(overrides: { ai?: AiClient | null; market?: MarketDataProvider | null; env?: Record<string, string> } = {}) {
  const env = loadEnv({
    NODE_ENV: 'test',
    DATABASE_URL: TEST_DATABASE_URL,
    SESSION_SECRET: 'test-session-secret-0123456789-abcdefghijklmnop',
    ENCRYPTION_KEY: Buffer.alloc(32, 7).toString('base64'),
    APP_URL: 'http://localhost:3000',
    API_URL: 'http://localhost:3000/api',
    LOG_LEVEL: 'silent',
    ...overrides.env,
  });
  const pool = createPool(env.DATABASE_URL);
  const db = createDb(pool);
  const idp = new FakeIdentityProvider();
  const { app, ctx } = createApp({
    env,
    db,
    pool,
    log: createLogger('silent'),
    identityProvider: idp,
    storage: new LocalStorage(path.join(os.tmpdir(), `journzey-test-uploads-${process.pid}`)),
    aiClient: overrides.ai === undefined ? null : overrides.ai,
    marketDataProvider: overrides.market ?? null,
  });
  return { app, ctx, idp, pool, db };
}

export async function resetData(pool: import('pg').Pool) {
  await pool.query(
    'TRUNCATE users, user_sessions, audit_logs, webhook_events RESTART IDENTITY CASCADE',
  );
}

export type Agent = ReturnType<typeof request.agent> & { csrf: string };

/** Logs in through the real /auth/google → /auth/google/callback flow. */
export async function login(app: Express, idp: FakeIdentityProvider, who: { sub: string; email: string; name?: string }): Promise<Agent> {
  const agent = request.agent(app) as Agent;
  const code = `code-${who.sub}`;
  idp.identities.set(code, { subject: who.sub, email: who.email, emailVerified: true, name: who.name ?? who.sub, picture: null });
  const start = await agent.get('/api/v1/auth/google').expect(302);
  const state = new URL(start.headers.location as string).searchParams.get('state')!;
  const cb = await agent.get(`/api/v1/auth/google/callback?state=${state}&code=${code}`).expect(303);
  if (!String(cb.headers.location).includes('/auth/callback?status=success')) throw new Error(`login failed: ${cb.headers.location}`);
  const csrf = await agent.get('/api/v1/auth/csrf').expect(200);
  agent.csrf = csrf.body.csrfToken;
  return agent;
}

export async function onboard(agent: Agent, opts: { demo?: boolean; loadDemoData?: boolean; currency?: string } = {}) {
  const res = await agent
    .post('/api/v1/onboarding')
    .set('x-csrf-token', agent.csrf)
    .send({
      primaryMarkets: ['METALS', 'FOREX'],
      accountName: 'Main Account',
      startingCapital: '10000',
      currency: opts.currency ?? 'USD',
      demo: opts.demo ?? false,
      defaultRiskPercentage: '1',
      maxDailyLoss: '300',
      maxWeeklyLoss: '900',
      defaultTargetRr: '2',
      timezone: 'UTC',
      loadDemoData: opts.loadDemoData ?? false,
    })
    .expect(201);
  return res.body.accountId as string;
}

export const tradeBody = (accountId: string, extra: Record<string, unknown> = {}) => ({
  tradingAccountId: accountId,
  executedAt: '2026-09-15T09:30:00Z',
  symbol: 'XAUUSD',
  side: 'LONG',
  entryPrice: '2862',
  exitPrice: '2874',
  stopLoss: '2858',
  lotSize: '0.5',
  rulesFollowed: true,
  ...extra,
});
