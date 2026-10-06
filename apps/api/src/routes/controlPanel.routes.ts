import express, { Router, type RequestHandler } from 'express';
import session from 'express-session';
import connectPgSimple from 'connect-pg-simple';
import type pg from 'pg';
import QRCode from 'qrcode';
import { sql } from 'drizzle-orm';
import rateLimit from 'express-rate-limit';
import { z } from 'zod';
import type { AppContext } from '../context.js';
import { ah } from '../lib/asyncHandler.js';
import { AppError, forbidden, notFound, unauthorized, badRequest } from '../lib/errors.js';
import { randomToken } from '../lib/crypto.js';
import { parse } from '../lib/validate.js';
import { csrfProtection } from '../middleware/csrf.js';
import { generateTotpSecret, otpauthUrl } from '../lib/totp.js';
import { testGoogleCredentials } from '../auth/google.js';
import { testAnthropicKey } from '../services/ai.js';
import { testTwelveDataKey } from '../services/marketData.js';
import { toAdminDto, type AdminRole } from '../services/admin.js';
import { migrationsFolder } from '../db/migrations.js';
import { readFileSync } from 'node:fs';
import path from 'node:path';

export const ADMIN_COOKIE = 'jz.cp';
export const ADMIN_API_PREFIX = '/api/v1/admin';
const STARTED_AT = Date.now();

/** Returns 404 (not 403) for clients outside ADMIN_IP_ALLOWLIST so the panel's existence is not revealed. */
export function adminIpAllowlist(list: string | undefined): RequestHandler {
  const allowed = list?.split(',').map((s) => s.trim()).filter(Boolean) ?? [];
  return (req, res, next) => {
    if (allowed.length === 0) return next();
    const ip = (req.ip ?? '').replace(/^::ffff:/, '');
    if (allowed.includes(ip)) return next();
    res.status(404).json({ error: { code: 'NOT_FOUND', message: 'Not found' } });
  };
}

const ROLE_RANK: Record<AdminRole, number> = { viewer: 0, admin: 1, owner: 2 };

/**
 * Control-panel API, mounted at /api/v1/admin BEFORE the trader session middleware.
 * It uses its own cookie (jz.cp, Path=/api/v1/admin, SameSite=Strict), its own session id,
 * its own CSRF token and short idle/absolute timeouts. Trader sessions never grant admin access.
 */
export function controlPanelRouter(ctx: AppContext, pool: pg.Pool, allowedOrigins: string[]) {
  const { env } = ctx;
  const isProd = env.NODE_ENV === 'production';
  const r = Router();
  r.use(adminIpAllowlist(env.ADMIN_IP_ALLOWLIST));
  r.use((_req, res, next) => {
    res.setHeader('Cache-Control', 'no-store');
    res.setHeader('X-Robots-Tag', 'noindex, nofollow');
    next();
  });
  r.use(express.json({ limit: '64kb' }));

  const PgStore = connectPgSimple(session);
  r.use(
    session({
      name: ADMIN_COOKIE,
      secret: env.SESSION_SECRET,
      store: new PgStore({ pool, tableName: 'user_sessions', createTableIfMissing: false, pruneSessionInterval: false }),
      resave: false,
      saveUninitialized: false,
      rolling: true,
      proxy: isProd,
      cookie: { httpOnly: true, secure: isProd, sameSite: 'strict', path: ADMIN_API_PREFIX, maxAge: env.ADMIN_SESSION_IDLE_MINUTES * 60_000 },
    }),
  );
  r.use(csrfProtection(allowedOrigins, []));

  const loginLimiter = rateLimit({
    windowMs: 15 * 60_000,
    limit: 10,
    standardHeaders: 'draft-7',
    legacyHeaders: false,
    skip: () => env.NODE_ENV === 'test' && process.env.RATE_LIMIT_IN_TESTS !== 'true',
    message: { error: { code: 'AUTH_RATE_LIMITED', message: 'Too many sign-in attempts. Try again in 15 minutes.' } },
  });

  const requireAdmin =
    (min: AdminRole = 'viewer'): RequestHandler =>
    (req, _res, next) => {
      const id = req.session.adminId;
      const started = req.session.adminAuthenticatedAt ?? 0;
      if (!id) return next(unauthorized('Control-panel sign-in required'));
      if (Date.now() - started > env.ADMIN_SESSION_MAX_HOURS * 3600_000) {
        return req.session.destroy(() => next(unauthorized('Session expired — please sign in again')));
      }
      ctx.admin
        .get(id)
        .then((a) => {
          if (!a || a.disabled || a.passwordChangedAt.getTime() > started + 1000) {
            return req.session.destroy(() => next(unauthorized('Session expired — please sign in again')));
          }
          if (ROLE_RANK[a.role as AdminRole] < ROLE_RANK[min]) return next(forbidden(`Requires the ${min} role`));
          req.admin = a;
          next();
        })
        .catch(next);
    };
  const actor = (req: express.Request) => ({ id: req.admin!.id, email: req.admin!.email });

  /* ------------------------------------------------------------ auth */
  r.get('/auth/csrf', (req, res, next) => {
    if (!req.session.csrfToken) req.session.csrfToken = randomToken(32);
    req.session.save((err) => (err ? next(err) : res.json({ csrfToken: req.session.csrfToken })));
  });

  r.get('/auth/status', ah(async (_req, res) => res.json({ setupRequired: (await ctx.admin.count()) === 0 })));

  r.post(
    '/auth/login',
    loginLimiter,
    ah(async (req, res) => {
      const input = parse(z.object({ email: z.string().email().max(320), password: z.string().min(1).max(200), totp: z.string().max(10).optional() }).strict(), req.body);
      let result;
      try {
        result = await ctx.admin.authenticate(input.email, input.password, input.totp);
      } catch (err) {
        await ctx.admin.audit(req, null, 'auth.login_failed', input.email.toLowerCase(), { code: err instanceof AppError ? err.code : 'ERROR' });
        throw err;
      }
      if (result === 'totp_required') return res.json({ totpRequired: true });
      const admin = result.admin;
      await new Promise<void>((resolve, reject) =>
        req.session.regenerate((err) => {
          if (err) return reject(err);
          req.session.adminId = admin.id;
          req.session.adminAuthenticatedAt = Date.now();
          req.session.csrfToken = randomToken(32);
          req.session.save((e) => (e ? reject(e) : resolve()));
        }),
      );
      await ctx.admin.audit(req, admin, 'auth.login');
      res.json({ admin: toAdminDto(admin), csrfToken: req.session.csrfToken });
    }),
  );

  r.post(
    '/auth/logout',
    ah(async (req, res) => {
      if (req.session.adminId) {
        const a = await ctx.admin.get(req.session.adminId);
        if (a) await ctx.admin.audit(req, a, 'auth.logout');
      }
      await new Promise<void>((resolve) => req.session.destroy(() => resolve()));
      res.clearCookie(ADMIN_COOKIE, { path: ADMIN_API_PREFIX });
      res.status(204).end();
    }),
  );

  r.get('/auth/me', requireAdmin(), (req, res) => res.json({ admin: toAdminDto(req.admin!), csrfToken: req.session.csrfToken }));

  r.post(
    '/auth/password',
    requireAdmin(),
    ah(async (req, res) => {
      const input = parse(z.object({ currentPassword: z.string().min(1).max(200), newPassword: z.string().min(1).max(200) }).strict(), req.body);
      await ctx.admin.changePassword(req.admin!.id, input.currentPassword, input.newPassword);
      req.session.adminAuthenticatedAt = Date.now() + 1000; // keep this session valid; others are invalidated
      await ctx.admin.audit(req, actor(req), 'auth.password_changed');
      res.status(204).end();
    }),
  );

  r.post(
    '/auth/totp/setup',
    requireAdmin(),
    ah(async (req, res) => {
      if (req.admin!.totpEnabled) throw badRequest('Two-factor authentication is already enabled');
      const secret = generateTotpSecret();
      req.session.adminTotpSetupSecret = secret;
      const url = otpauthUrl(secret, req.admin!.email);
      res.json({ secret, otpauthUrl: url, qrDataUrl: await QRCode.toDataURL(url, { margin: 1, width: 220 }) });
    }),
  );

  r.post(
    '/auth/totp/enable',
    requireAdmin(),
    ah(async (req, res) => {
      const { code } = parse(z.object({ code: z.string().max(10) }).strict(), req.body);
      const secret = req.session.adminTotpSetupSecret;
      if (!secret) throw badRequest('Start two-factor setup first');
      await ctx.admin.enableTotp(req.admin!.id, secret, code);
      delete req.session.adminTotpSetupSecret;
      await ctx.admin.audit(req, actor(req), 'auth.totp_enabled');
      res.status(204).end();
    }),
  );

  r.post(
    '/auth/totp/disable',
    requireAdmin(),
    ah(async (req, res) => {
      const { password } = parse(z.object({ password: z.string().min(1).max(200) }).strict(), req.body);
      await ctx.admin.disableTotp(req.admin!.id, password);
      await ctx.admin.audit(req, actor(req), 'auth.totp_disabled');
      res.status(204).end();
    }),
  );

  /* ------------------------------------------------------------ overview + system */
  r.get(
    '/overview',
    requireAdmin(),
    ah(async (_req, res) => {
      const f = ctx.features();
      const g = ctx.runtime.google();
      const site = ctx.runtime.site();
      res.json({
        ...(await ctx.admin.overview()),
        integrations: {
          google: { configured: f.googleAuth, enabled: g.enabled },
          ai: { configured: !!ctx.runtime.ai().apiKey, active: f.ai, model: ctx.runtime.ai().model },
          marketData: { configured: !!ctx.runtime.marketData().apiKey, active: f.marketData },
          storage: f.storage,
        },
        site: { maintenance: site.maintenance.enabled, registrationOpen: site.registrationOpen, announcement: site.announcement.enabled },
      });
    }),
  );

  r.get(
    '/system',
    requireAdmin(),
    ah(async (_req, res) => {
      const t0 = Date.now();
      await ctx.db.execute(sql`select 1`);
      const dbLatencyMs = Date.now() - t0;
      let migrations = 0;
      try {
        migrations = (JSON.parse(readFileSync(path.join(migrationsFolder(), 'meta/_journal.json'), 'utf8')) as { entries: unknown[] }).entries.length;
      } catch {
        /* ignore */
      }
      const applied = await ctx.db.execute<{ n: number }>(sql`SELECT count(*)::int AS n FROM drizzle.__drizzle_migrations`).catch(() => ({ rows: [{ n: -1 }] }));
      res.json({
        nodeVersion: process.version,
        environment: env.NODE_ENV,
        uptimeSeconds: Math.round((Date.now() - STARTED_AT) / 1000),
        memoryMb: Math.round(process.memoryUsage().rss / 1024 / 1024),
        dbLatencyMs,
        migrations: { available: migrations, applied: applied.rows[0]?.n ?? -1 },
        storage: ctx.storage.kind,
        devAuthBypass: env.DEV_AUTH_BYPASS,
        ipAllowlist: Boolean(env.ADMIN_IP_ALLOWLIST),
        sentry: Boolean(env.SENTRY_DSN),
      });
    }),
  );

  /* ------------------------------------------------------------ live settings (incl. API keys) */
  r.get('/settings', requireAdmin(), (_req, res) => {
    res.json({ settings: ctx.runtime.list(), derived: { googleRedirectUri: ctx.runtime.google().callbackUrl, appUrl: env.APP_URL } });
  });

  r.patch(
    '/settings',
    requireAdmin('admin'),
    ah(async (req, res) => {
      const body = parse(z.record(z.string().max(80), z.union([z.string().max(2000), z.boolean()])), req.body);
      const changed = await ctx.runtime.update(body, req.admin!.id);
      const secretKeys = new Set(ctx.runtime.list().filter((s) => s.kind === 'secret').map((s) => s.key));
      // Never write secret values to the audit trail — only which keys changed.
      await ctx.admin.audit(req, actor(req), 'settings.updated', changed.join(', '), {
        keys: changed,
        values: Object.fromEntries(changed.filter((k) => !secretKeys.has(k)).map((k) => [k, body[k]])),
      });
      res.json({ settings: ctx.runtime.list(), derived: { googleRedirectUri: ctx.runtime.google().callbackUrl, appUrl: env.APP_URL } });
    }),
  );

  r.delete(
    '/settings/:key',
    requireAdmin('admin'),
    ah(async (req, res) => {
      const key = String(req.params.key);
      await ctx.runtime.reset(key);
      await ctx.admin.audit(req, actor(req), 'settings.reset', key);
      res.json({ settings: ctx.runtime.list(), derived: { googleRedirectUri: ctx.runtime.google().callbackUrl, appUrl: env.APP_URL } });
    }),
  );

  r.post(
    '/integrations/:name/test',
    requireAdmin('admin'),
    ah(async (req, res) => {
      const name = String(req.params.name);
      let result: { ok: boolean; message: string };
      if (name === 'google') result = await testGoogleCredentials(ctx.runtime.google());
      else if (name === 'ai') {
        const c = ctx.runtime.ai();
        result = c.apiKey ? await testAnthropicKey(c.apiKey, c.model) : { ok: false, message: 'No Anthropic API key configured.' };
      } else if (name === 'market-data') {
        const c = ctx.runtime.marketData();
        result = c.apiKey ? await testTwelveDataKey(c.apiKey, ctx.log) : { ok: false, message: 'No Twelve Data API key configured.' };
      } else throw notFound('Unknown integration');
      await ctx.admin.audit(req, actor(req), 'integration.tested', name, { ok: result.ok });
      res.json(result);
    }),
  );

  /* ------------------------------------------------------------ trader users */
  r.get(
    '/users',
    requireAdmin(),
    ah(async (req, res) => {
      const q = parse(
        z.object({
          search: z.string().trim().max(100).optional(),
          status: z.enum(['all', 'active', 'suspended']).default('all'),
          page: z.coerce.number().int().min(1).default(1),
          pageSize: z.coerce.number().int().min(1).max(100).default(25),
        }),
        req.query,
      );
      res.json(await ctx.admin.listUsers(q));
    }),
  );
  const userId = (req: express.Request) => parse(z.object({ id: z.string().uuid() }), req.params).id;
  r.get('/users/:id', requireAdmin(), ah(async (req, res) => res.json(await ctx.admin.userDetail(userId(req)))));
  r.post(
    '/users/:id/suspend',
    requireAdmin('admin'),
    ah(async (req, res) => {
      const id = userId(req);
      await ctx.admin.setSuspended(id, true);
      await ctx.admin.audit(req, actor(req), 'user.suspended', id);
      res.json(await ctx.admin.userDetail(id));
    }),
  );
  r.post(
    '/users/:id/unsuspend',
    requireAdmin('admin'),
    ah(async (req, res) => {
      const id = userId(req);
      await ctx.admin.setSuspended(id, false);
      await ctx.admin.audit(req, actor(req), 'user.unsuspended', id);
      res.json(await ctx.admin.userDetail(id));
    }),
  );
  r.post(
    '/users/:id/sign-out',
    requireAdmin('admin'),
    ah(async (req, res) => {
      const id = userId(req);
      const n = await ctx.admin.signOutUser(id);
      await ctx.admin.audit(req, actor(req), 'user.signed_out', id, { sessions: n });
      res.json({ sessionsRevoked: n });
    }),
  );
  r.delete(
    '/users/:id',
    requireAdmin('owner'),
    ah(async (req, res) => {
      const id = userId(req);
      const { confirmation } = parse(z.object({ confirmation: z.string().max(320) }).strict(), req.body);
      const detail = await ctx.admin.userDetail(id);
      if (confirmation.trim().toLowerCase() !== detail.email.toLowerCase()) throw badRequest('Type the user’s email to confirm', { confirmation: ['Does not match'] });
      await ctx.admin.signOutUser(id);
      const { screenshotKeys } = await ctx.users.deleteAccount(id);
      for (const key of screenshotKeys) await ctx.storage.delete(key).catch(() => undefined);
      await ctx.admin.audit(req, actor(req), 'user.deleted', id, { email: detail.email });
      res.status(204).end();
    }),
  );

  /* ------------------------------------------------------------ administrators (owner only) */
  const roleSchema = z.enum(['owner', 'admin', 'viewer']);
  r.get('/admins', requireAdmin('owner'), ah(async (_req, res) => res.json(await ctx.admin.list())));
  r.post(
    '/admins',
    requireAdmin('owner'),
    ah(async (req, res) => {
      const input = parse(z.object({ email: z.string().email().max(320), name: z.string().trim().min(1).max(120), password: z.string().min(1).max(200), role: roleSchema }).strict(), req.body);
      const a = await ctx.admin.create(input);
      await ctx.admin.audit(req, actor(req), 'admin.created', a.email, { role: a.role });
      res.status(201).json(a);
    }),
  );
  const adminId = (req: express.Request) => parse(z.object({ id: z.string().uuid() }), req.params).id;
  r.patch(
    '/admins/:id',
    requireAdmin('owner'),
    ah(async (req, res) => {
      const input = parse(z.object({ role: roleSchema.optional(), disabled: z.boolean().optional(), name: z.string().trim().min(1).max(120).optional() }).strict(), req.body);
      const a = await ctx.admin.update(req.admin!, adminId(req), input);
      await ctx.admin.audit(req, actor(req), 'admin.updated', a.email, input);
      res.json(a);
    }),
  );
  r.post(
    '/admins/:id/reset-password',
    requireAdmin('owner'),
    ah(async (req, res) => {
      const { password } = parse(z.object({ password: z.string().min(1).max(200) }).strict(), req.body);
      const id = adminId(req);
      await ctx.admin.resetPassword(id, password);
      await ctx.admin.audit(req, actor(req), 'admin.password_reset', id);
      res.status(204).end();
    }),
  );
  r.delete(
    '/admins/:id',
    requireAdmin('owner'),
    ah(async (req, res) => {
      const id = adminId(req);
      await ctx.admin.remove(req.admin!, id);
      await ctx.admin.audit(req, actor(req), 'admin.deleted', id);
      res.status(204).end();
    }),
  );

  /* ------------------------------------------------------------ audit */
  r.get(
    '/audit',
    requireAdmin(),
    ah(async (req, res) => {
      const q = parse(
        z.object({
          source: z.enum(['admin', 'users']).default('admin'),
          search: z.string().trim().max(100).optional(),
          page: z.coerce.number().int().min(1).default(1),
          pageSize: z.coerce.number().int().min(1).max(100).default(50),
        }),
        req.query,
      );
      res.json({ ...(await ctx.admin.auditLog(q)), page: q.page, pageSize: q.pageSize });
    }),
  );

  // Anything else under the admin API is a 404 — never falls through to trader routes.
  r.use((_req, res) => res.status(404).json({ error: { code: 'NOT_FOUND', message: 'Not found' } }));
  return r;
}
