import { Router, type Request } from 'express';
import type { AppContext } from '../context.js';
import { ah } from '../lib/asyncHandler.js';
import { AppError, notFound } from '../lib/errors.js';
import { randomToken, safeEqual } from '../lib/crypto.js';
import { parse } from '../lib/validate.js';
import { devLoginSchema } from '../validators/index.js';
import { requireAuth, currentUserId } from '../middleware/auth.js';
import { audit } from '../services/audit.js';

const OAUTH_STATE_TTL_MS = 10 * 60_000;
export const SESSION_COOKIE = 'jz.sid';

/** Regenerates the session id on login to prevent session fixation, then binds the user. */
function establishSession(req: Request, userId: string): Promise<void> {
  return new Promise((resolve, reject) => {
    req.session.regenerate((err) => {
      if (err) return reject(err);
      req.session.userId = userId;
      req.session.authenticatedAt = Date.now();
      req.session.csrfToken = randomToken(32);
      req.session.save((e) => (e ? reject(e) : resolve()));
    });
  });
}

export function authRouter(ctx: AppContext, limiters: { auth: import('express').RequestHandler }) {
  const r = Router();
  const appUrl = ctx.env.APP_URL.replace(/\/$/, '');
  const loginError = (code: string) => `${appUrl}/login?error=${encodeURIComponent(code)}`;

  /** CSRF token for the current (possibly anonymous) session. */
  r.get('/csrf', (req, res, next) => {
    if (!req.session.csrfToken) req.session.csrfToken = randomToken(32);
    req.session.save((err) => (err ? next(err) : res.json({ csrfToken: req.session.csrfToken })));
  });

  r.get('/providers', (_req, res) => {
    const f = ctx.features();
    res.json({ google: f.googleAuth, devLogin: f.devLogin });
  });

  /** Step 1: redirect the browser to Google's authorization endpoint (PKCE + state + nonce). */
  r.get('/google', limiters.auth, (req, res, next) => {
    if (!ctx.identityProvider.configured) {
      return res.redirect(303, loginError('google_not_configured'));
    }
    const authz = ctx.identityProvider.createAuthorizationRequest();
    req.session.oauth = { state: authz.state, nonce: authz.nonce, codeVerifier: authz.codeVerifier, createdAt: Date.now() };
    req.session.save((err) => (err ? next(err) : res.redirect(302, authz.url)));
  });

  /** Step 2: Google redirects back here. Validate state, exchange code, verify ID token, create session. */
  r.get(
    '/google/callback',
    limiters.auth,
    ah(async (req, res) => {
      const pending = req.session.oauth;
      delete req.session.oauth;
      const { state, code, error } = req.query as Record<string, string | undefined>;
      if (error) return res.redirect(303, loginError(error === 'access_denied' ? 'access_denied' : 'oauth_error'));
      if (!pending || !state || !code || Date.now() - pending.createdAt > OAUTH_STATE_TTL_MS || !safeEqual(state, pending.state)) {
        await audit(ctx.db, req, 'auth.login_failed', null, { reason: 'state_mismatch' });
        return res.redirect(303, loginError('invalid_state'));
      }
      let identity;
      try {
        identity = await ctx.identityProvider.exchangeCode(code, pending.codeVerifier, pending.nonce);
      } catch (err) {
        req.log.warn({ err: (err as Error).message }, 'oauth exchange failed');
        await audit(ctx.db, req, 'auth.login_failed', null, { reason: 'token_exchange' });
        return res.redirect(303, loginError('oauth_failed'));
      }
      if (!identity.emailVerified) {
        await audit(ctx.db, req, 'auth.login_failed', null, { reason: 'email_unverified' });
        return res.redirect(303, loginError('email_unverified'));
      }
      let result;
      try {
        result = await ctx.users.upsertFromGoogle(identity, { allowCreate: ctx.runtime.site().registrationOpen });
      } catch (err) {
        if (err instanceof AppError && err.status === 409) return res.redirect(303, loginError('account_conflict'));
        if (err instanceof AppError && err.code === 'REGISTRATION_CLOSED') return res.redirect(303, loginError('registration_closed'));
        throw err;
      }
      if (result.user.suspendedAt) {
        await audit(ctx.db, req, 'auth.login_failed', result.user.id, { reason: 'suspended' });
        return res.redirect(303, loginError('account_suspended'));
      }
      await establishSession(req, result.user.id);
      if (result.created) await audit(ctx.db, req, 'user.created', result.user.id, { provider: 'google' });
      await audit(ctx.db, req, 'auth.login', result.user.id, { provider: 'google' });
      res.redirect(303, `${appUrl}/auth/callback?status=success`);
    }),
  );

  /** Local development sign-in. Disabled unless DEV_AUTH_BYPASS=true and never in production. */
  r.post(
    '/dev-login',
    limiters.auth,
    ah(async (req, res) => {
      if (!ctx.features().devLogin) throw notFound();
      const input = parse(devLoginSchema, req.body);
      const { user, created } = await ctx.users.upsertDevUser(input.email, input.name, { allowCreate: ctx.runtime.site().registrationOpen });
      if (user.suspendedAt) throw new AppError(403, 'ACCOUNT_SUSPENDED', 'This account has been suspended. Contact support.');
      await establishSession(req, user.id);
      if (created) await audit(ctx.db, req, 'user.created', user.id, { provider: 'dev' });
      await audit(ctx.db, req, 'auth.dev_login', user.id);
      res.json(await ctx.users.me(user.id, ctx.features()));
    }),
  );

  r.post(
    '/logout',
    ah(async (req, res) => {
      const userId = req.session.userId ?? null;
      if (userId) await audit(ctx.db, req, 'auth.logout', userId);
      await new Promise<void>((resolve, reject) => req.session.destroy((err) => (err ? reject(err) : resolve())));
      res.clearCookie(SESSION_COOKIE, { path: '/', httpOnly: true, sameSite: 'lax', secure: ctx.env.NODE_ENV === 'production' });
      res.status(204).end();
    }),
  );

  r.get(
    '/me',
    requireAuth(ctx.db),
    ah(async (req, res) => {
      res.json(await ctx.users.me(currentUserId(req), ctx.features()));
    }),
  );

  return r;
}
