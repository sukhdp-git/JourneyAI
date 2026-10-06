import * as Sentry from '@sentry/node';
import { loadEnv } from './config/env.js';
import { createLogger } from './lib/logger.js';
import { createDb, createPool } from './db/client.js';
import { runMigrations } from './db/migrations.js';
import { seedReferenceData } from './db/referenceData.js';
import { GoogleIdentityProvider } from './auth/google.js';
import { createStorage } from './services/storage.js';
import { AnthropicAiClient } from './services/ai.js';
import { TwelveDataProvider } from './services/marketData.js';
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

const { app } = createApp({
  env,
  db,
  pool,
  log,
  identityProvider: new GoogleIdentityProvider(env.GOOGLE_CLIENT_ID, env.GOOGLE_CLIENT_SECRET, env.GOOGLE_CALLBACK_URL),
  storage: createStorage(env),
  aiClient: env.AI_API_KEY ? new AnthropicAiClient(env.AI_API_KEY, env.AI_MODEL) : null,
  marketDataProvider: env.MARKET_DATA_API_KEY ? new TwelveDataProvider(env.MARKET_DATA_API_KEY, log) : null,
  reportError: env.SENTRY_DSN
    ? (err) => {
        if (!(err instanceof AppError) || err.status >= 500) Sentry.captureException(err);
      }
    : undefined,
});

const server = app.listen(env.PORT, () => {
  log.info(
    {
      port: env.PORT,
      env: env.NODE_ENV,
      googleAuth: Boolean(env.GOOGLE_CLIENT_ID),
      ai: Boolean(env.AI_API_KEY),
      marketData: Boolean(env.MARKET_DATA_API_KEY),
      storage: env.STORAGE_PROVIDER,
      devAuthBypass: env.DEV_AUTH_BYPASS,
    },
    'journzey.ai API listening',
  );
});

const shutdown = (signal: string) => {
  log.info({ signal }, 'shutting down');
  server.close(() => {
    pool.end().finally(() => process.exit(0));
  });
  setTimeout(() => process.exit(1), 10_000).unref();
};
process.on('SIGTERM', () => shutdown('SIGTERM'));
process.on('SIGINT', () => shutdown('SIGINT'));
