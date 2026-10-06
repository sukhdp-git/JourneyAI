import * as Sentry from '@sentry/node';
import { loadEnv } from './config/env.js';
import { createLogger } from './lib/logger.js';
import { createDb, createPool } from './db/client.js';
import { runMigrations } from './db/migrations.js';
import { seedReferenceData } from './db/referenceData.js';
import { createStorage } from './services/storage.js';
import { createApp } from './app.js';
import { AppError } from './lib/errors.js';

const env = loadEnv();
const log = createLogger(env.LOG_LEVEL, env.NODE_ENV === 'development');

if (env.SENTRY_DSN) {
  Sentry.init({ dsn: env.SENTRY_DSN, environment: env.NODE_ENV, tracesSampleRate: 0 });
}

const pool = createPool(env.DATABASE_URL, env.DATABASE_SSL);
const db = createDb(pool);

if (process.env.RUN_MIGRATIONS_ON_START === 'true') {
  await runMigrations(db);
  await db.transaction((tx) => seedReferenceData(tx));
  log.info('migrations and reference data applied');
}

const { app, ctx } = createApp({
  env,
  db,
  pool,
  log,
  storage: createStorage(env),
  reportError: env.SENTRY_DSN
    ? (err) => {
        if (!(err instanceof AppError) || err.status >= 500) Sentry.captureException(err);
      }
    : undefined,
});

await ctx.runtime.ready();

// First-run bootstrap: create an owner for the control panel if none exists yet.
if (env.ADMIN_BOOTSTRAP_EMAIL && env.ADMIN_BOOTSTRAP_PASSWORD && (await ctx.admin.count()) === 0) {
  const owner = await ctx.admin.create({ email: env.ADMIN_BOOTSTRAP_EMAIL, name: 'Owner', password: env.ADMIN_BOOTSTRAP_PASSWORD, role: 'owner' });
  await ctx.admin.audit(null, null, 'admin.bootstrapped', owner.email);
  log.warn({ email: owner.email }, 'control-panel owner bootstrapped — remove ADMIN_BOOTSTRAP_PASSWORD from the environment now');
} else if ((await ctx.admin.count()) === 0) {
  log.warn('no control-panel administrator exists — run `npm run admin:create` or set ADMIN_BOOTSTRAP_EMAIL/ADMIN_BOOTSTRAP_PASSWORD');
}

const server = app.listen(env.PORT, () => {
  log.info(
    {
      port: env.PORT,
      env: env.NODE_ENV,
      ...(({ googleAuth, ai, marketData }) => ({ googleAuth, ai, marketData }))(ctx.features()),
      storage: env.STORAGE_PROVIDER,
      devAuthBypass: env.DEV_AUTH_BYPASS,
    },
    'journzey.ai API listening',
  );
});

const shutdown = (signal: string) => {
  log.info({ signal }, 'shutting down');
  ctx.runtime.stop();
  server.close(() => {
    pool.end().finally(() => process.exit(0));
  });
  setTimeout(() => process.exit(1), 10_000).unref();
};
process.on('SIGTERM', () => shutdown('SIGTERM'));
process.on('SIGINT', () => shutdown('SIGINT'));
