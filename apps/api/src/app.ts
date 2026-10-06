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
  v1.use('/auth', authRouter(ctx, limiters));
  v1.use('/webhooks', limiters.webhook, webhooksRouter(ctx));

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
  authed.use('/demo', demoRouter(ctx));
  authed.use('/ai', aiRouter(ctx, limiters));
  authed.use('/market', marketRouter(ctx));
  authed.use('/broker-connections', brokersRouter(ctx, limiters));
  v1.use(authed);

  app.use('/api/v1', v1);
  app.use('/api', notFoundHandler);

  // Optional: serve the built web app from the same origin (single-container deployments).
  if (env.WEB_DIST_DIR) {
    const dist = path.resolve(env.WEB_DIST_DIR);
    if (existsSync(path.join(dist, 'index.html'))) {
      app.use(express.static(dist, { index: false, maxAge: '1h', setHeaders: (res, p) => {
        if (p.includes(`${path.sep}assets${path.sep}`)) res.setHeader('Cache-Control', 'public, max-age=31536000, immutable');
      } }));
      app.get('*', (req, res, next) => {
        if (req.path.startsWith('/api/')) return next();
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
