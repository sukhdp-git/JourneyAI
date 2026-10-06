import type pg from 'pg';
import type { FeatureFlags } from '@journzey/shared';
import type { Env } from './config/env.js';
import type { Database } from './db/client.js';
import type { Logger } from './lib/logger.js';
import type { IdentityProvider } from './auth/google.js';
import type { StorageProvider } from './services/storage.js';
import { SecretBox } from './lib/crypto.js';
import { TradeService } from './services/trades.js';
import { AnalyticsService } from './services/analytics.js';
import { UserService } from './services/users.js';
import { AccountService } from './services/accounts.js';
import { StrategyService } from './services/strategies.js';
import { JournalService } from './services/journal.js';
import { DemoService } from './services/demo.js';
import { AiCoachService, type AiClient } from './services/ai.js';
import { MarketDataService, type MarketDataProvider } from './services/marketData.js';
import { BrokerService } from './services/brokers/service.js';
import { RunnerAuditService } from './services/runner.js';

export interface AppDeps {
  env: Env;
  db: Database;
  pool: pg.Pool;
  log: Logger;
  identityProvider: IdentityProvider;
  storage: StorageProvider;
  aiClient: AiClient | null;
  marketDataProvider: MarketDataProvider | null;
}

export function buildContext(deps: AppDeps) {
  const { db, env, log } = deps;
  const box = new SecretBox(env.ENCRYPTION_KEY);
  const trades = new TradeService(db);
  const analytics = new AnalyticsService(db);
  const market = new MarketDataService(deps.marketDataProvider, log);
  const ctx = {
    ...deps,
    box,
    trades,
    analytics,
    users: new UserService(db),
    accounts: new AccountService(db),
    strategies: new StrategyService(db),
    journal: new JournalService(db),
    demo: new DemoService(db),
    ai: new AiCoachService(db, analytics, deps.aiClient, log),
    market,
    brokers: new BrokerService(db, box, env.API_URL, log, env.BINANCE_API_BASE),
    runner: new RunnerAuditService(db, trades, market),
    features(): FeatureFlags {
      return {
        googleAuth: deps.identityProvider.configured,
        devLogin: env.DEV_AUTH_BYPASS && env.NODE_ENV !== 'production',
        ai: deps.aiClient !== null,
        marketData: deps.marketDataProvider !== null,
        storage: deps.storage.kind,
      };
    },
  };
  return ctx;
}

export type AppContext = ReturnType<typeof buildContext>;
