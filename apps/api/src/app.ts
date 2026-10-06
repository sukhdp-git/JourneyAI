import './auth/sessionTypes.js';
import path from 'node:path';
import { randomUUID } from 'node:crypto';
import { existsSync } from 'node:fs';
import express, { type ErrorRequestHandler } from 'express';
import session from 'express-session';
import connectPgSimple from 'connect-pg-simple';
import helmet from 'helmet';
import cors from 'cors';
import { pinoHttp } from 'pino-http';
import { buildContext, type AppDeps } from './context.js';
import { errorHandler, notFoundHandler } from './lib/errors.js';
import { requireAuth } from './middleware/auth.js';
import { csrfProtection } from './middleware/csrf.js';
import { createLimiters } from './middleware/rateLimit.js';
import { authRouter, SESSION_COOKIE } from './routes/auth.routes.js';
import { tradesRouter } from './routes/trades.routes.js';
import { analyticsRouter, calendarRouter, dashboardRouter } from './routes/analytics.routes.js';
import { accountsRouter, checklistRouter, journalRouter, strategiesRouter } from './routes/resources.routes.js';
import { accountRouter, demoRouter, onboardingRouter, settingsRouter } from './routes/user.routes.js';
import { aiRouter, brokersRouter, marketRouter, webhooksRouter } from './routes/integrations.routes.js';
import { healthRouter } from './routes/health.routes.js';
import { adminIpAllowlist, controlPanelRouter, ADMIN_API_PREFIX } from './routes/controlPanel.routes.js';
import type { SiteConfig } from '@journzey/shared';

export interface CreateAppOptions extends AppDeps {
  reportError?: (err: unknown) => void;
}

export function createApp(opts: CreateAppOptions) {
  const ctx = buildContext(opts);
  const { env } = ctx;
  const isProd = env.NODE_ENV === 'production';
  const app = express();

  app.disable('x-powered-by');
  app.set('trust proxy', env.TRUST_PROXY ? (/^\d+$/.test(env.TRUST_PROXY) ? Number(env.TRUST_PROXY) : env.TRUST_PROXY) : isProd ? 1 : false);

  const allowedOrigins = [env.APP_URL.replace(/\/$/, ''), ...(env.CORS_ORIGINS?.split(',').map((o) => o.trim().replace(/\/$/, '')) ?? [])];

  // Request id + structured request logging (secrets redacted by the logger).
  app.use(
    pinoHttp({
      logger: ctx.log,
      genReqId: (req, res) => {
        const incoming = req.headers['x-request-id'];
        const id = typeof incoming === 'string' && /^[\w-]{8,64}$/.test(incoming) ? incoming : randomUUID();
        res.setHeader('X-Request-Id', id);
        return id;
      },
      autoLogging: { ignore: (req) => req.url === '/api/v1/health' },
      serializers: {
        req: (req: { id: string; method: string; url: string }) => ({ id: req.id, method: req.method, url: req.url?.split('?')[0] }),
      },
      customProps: (req) => ({ userId: (req as express.Request).session?.userId }),
    }),
  );

  app.use(
    helmet({
      contentSecurityPolicy: {
        useDefaults: true,
        directives: {
          'default-src': ["'self'"],
          'script-src': ["'self'"],
          'style-src': ["'self'", "'unsafe-inline'", 'https://fonts.googleapis.com'],
          'font-src': ["'self'", 'https://fonts.gstatic.com', 'data:'],
          'img-src': ["'self'", 'data:', 'blob:', 'https:'],
          'connect-src': ["'self'"],
          'frame-ancestors': ["'none'"],
          'form-action': ["'self'", 'https://accounts.google.com'],
          'upgrade-insecure-requests': isProd ? [] : null,
        },
      },
      crossOriginEmbedderPolicy: false,
      hsts: isProd ? { maxAge: 31536000, includeSubDomains: true } : false,
      referrerPolicy: { policy: 'strict-origin-when-cross-origin' },
    }),
  );

  app.use(
    '/api',
    cors({
      origin: (origin, cb) => cb(null, !origin || allowedOrigins.includes(origin)),
      credentials: true,
      methods: ['GET', 'POST', 'PATCH', 'PUT', 'DELETE'],
      allowedHeaders: ['Content-Type', 'X-CSRF-Token', 'X-Request-Id'],
      maxAge: 600,
    }),
  );

  app.use(
    express.json({
      limit: '256kb',
      verify: (req, _res, buf) => {
        if ((req as express.Request).originalUrl?.startsWith('/api/v1/webhooks/')) (req as express.Request).rawBody = Buffer.from(buf);
      },
    }),
  );
  app.use(express.urlencoded({ extended: false, limit: '64kb' }));

  // Runtime settings (control panel) must be loaded before any request is served.
  app.use((_req, _res, next) => {
    ctx.runtime.ready().then(() => next(), next);
  });

  // Control-panel API: own cookie + session + CSRF, mounted before (and isolated from) trader sessions.
  app.use(ADMIN_API_PREFIX, controlPanelRouter(ctx, opts.pool, allowedOrigins));

  const PgStore = connectPgSimple(session);
  app.use(
    session({
      name: SESSION_COOKIE,
      secret: env.SESSION_SECRET,
      store: new PgStore({ pool: opts.pool, tableName: 'user_sessions', createTableIfMissing: false, pruneSessionInterval: env.NODE_ENV === 'test' ? false : 15 * 60 }),
      resave: false,
      saveUninitialized: false,
      rolling: true,
      proxy: isProd,
      cookie: {
        httpOnly: true,
        secure: isProd,
        sameSite: 'lax',
        path: '/',
        maxAge: env.SESSION_TTL_HOURS * 3600_000,
      },
    }),
  );

  const limiters = createLimiters(env.NODE_ENV === 'test');
  app.use('/api', limiters.api);
  app.use('/api/v1', csrfProtection(allowedOrigins, ['/api/v1/webhooks/']));

  const v1 = express.Router();
  v1.use(healthRouter(ctx));

  /** Public, non-secret site configuration (branding, maintenance, announcement, feature switches). */
  v1.get('/site', (_req, res) => {
    const site = ctx.runtime.site();
    const f = ctx.features();
    const body: SiteConfig = {
      ...site,
      features: { aiCoach: f.aiCoach, brokerSync: f.brokerSync, demoMode: f.demoMode, marketTicker: f.marketTicker, googleAuth: f.googleAuth },
    };
    res.setHeader('Cache-Control', 'no-cache');
    res.json(body);
  });

  // Maintenance mode (control panel): the trader API returns 503; health, site config and logout keep working.
  const MAINTENANCE_ALLOW = ['/health', '/ready', '/site', '/auth/csrf', '/auth/providers', '/auth/logout'];
  v1.use((req, res, next) => {
    const m = ctx.runtime.site().maintenance;
    if (!m.enabled || MAINTENANCE_ALLOW.includes(req.path)) return next();
    res.setHeader('Retry-After', '300');
    res.status(503).json({ error: { code: 'MAINTENANCE', message: m.message } });
  });
  v1.use('/auth', authRouter(ctx, limiters));
  const requireFeature =
    (flag: 'aiCoach' | 'brokerSync' | 'demoMode' | 'marketTicker', label: string): express.RequestHandler =>
    (_req, res, next) => {
      if (ctx.runtime.features()[flag]) return next();
      res.status(403).json({ error: { code: 'FEATURE_DISABLED', message: `${label} is currently disabled by the site administrator.` } });
    };
  v1.use('/webhooks', limiters.webhook, requireFeature('brokerSync', 'Broker sync'), webhooksRouter(ctx));

  const authed = express.Router();
  authed.use(requireAuth(ctx.db));
  authed.use('/trades', tradesRouter(ctx, limiters));
  authed.use('/dashboard', dashboardRouter(ctx));
  authed.use('/calendar', calendarRouter(ctx));
  authed.use('/analytics', analyticsRouter(ctx));
  authed.use('/accounts', accountsRouter(ctx));
  authed.use('/strategies', strategiesRouter(ctx));
  authed.use('/journal', journalRouter(ctx));
  authed.use('/checklist', checklistRouter(ctx));
  authed.use('/settings', settingsRouter(ctx));
  authed.use('/account', accountRouter(ctx));
  authed.use('/onboarding', onboardingRouter(ctx));
  authed.use('/demo', requireFeature('demoMode', 'Demo data'), demoRouter(ctx));
  authed.use('/ai', requireFeature('aiCoach', 'The AI Coach'), aiRouter(ctx, limiters));
  authed.use('/market', marketRouter(ctx));
  authed.use('/broker-connections', requireFeature('brokerSync', 'Broker sync'), brokersRouter(ctx, limiters));
  v1.use(authed);

  app.use('/api/v1', v1);
  app.use('/api', notFoundHandler);

  // Control panel UI at /control-panel/ (separate app bundle; never served from public routes).
  if (env.ADMIN_DIST_DIR) {
    const adminDist = path.resolve(env.ADMIN_DIST_DIR);
    if (existsSync(path.join(adminDist, 'index.html'))) {
      const cp = express.Router();
      cp.use(adminIpAllowlist(env.ADMIN_IP_ALLOWLIST));
      cp.use((_req, res, next) => {
        res.setHeader('X-Robots-Tag', 'noindex, nofollow');
        next();
      });
      cp.use(express.static(adminDist, { index: false, maxAge: '1h', redirect: false }));
      cp.get('*', (_req, res) => {
        res.setHeader('Cache-Control', 'no-store');
        res.sendFile(path.join(adminDist, 'index.html'));
      });
      app.get('/control-panel', (_req, res) => res.redirect(301, '/control-panel/'));
      app.use('/control-panel', cp);
    } else {
      ctx.log.warn({ adminDist }, 'ADMIN_DIST_DIR set but index.html not found; control panel UI not served');
    }
  }

  // Optional: serve the built web app from the same origin (single-container deployments).
  if (env.WEB_DIST_DIR) {
    const dist = path.resolve(env.WEB_DIST_DIR);
    if (existsSync(path.join(dist, 'index.html'))) {
      app.use(express.static(dist, { index: false, maxAge: '1h', setHeaders: (res, p) => {
        if (p.includes(`${path.sep}assets${path.sep}`)) res.setHeader('Cache-Control', 'public, max-age=31536000, immutable');
      } }));
      app.get('*', (req, res, next) => {
        if (req.path.startsWith('/api/') || req.path.startsWith('/control-panel')) return next();
        res.setHeader('Cache-Control', 'no-cache');
        res.sendFile(path.join(dist, 'index.html'));
      });
    } else {
      ctx.log.warn({ dist }, 'WEB_DIST_DIR set but index.html not found; skipping static hosting');
    }
  }

  const report: ErrorRequestHandler = (err, _req, _res, next) => {
    opts.reportError?.(err);
    next(err);
  };
  app.use(report);
  app.use(errorHandler(isProd));
  return { app, ctx };
}
