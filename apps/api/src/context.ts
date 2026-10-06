import type pg from 'pg';
import type { FeatureFlags } from '@journzey/shared';
import type { Env } from './config/env.js';
import type { Database } from './db/client.js';
import type { Logger } from './lib/logger.js';
import { GoogleIdentityProvider, type IdentityProvider } from './auth/google.js';
import type { StorageProvider } from './services/storage.js';
import { SecretBox } from './lib/crypto.js';
import { TradeService } from './services/trades.js';
import { AnalyticsService } from './services/analytics.js';
import { UserService } from './services/users.js';
import { AccountService } from './services/accounts.js';
import { StrategyService } from './services/strategies.js';
import { JournalService } from './services/journal.js';
import { DemoService } from './services/demo.js';
import { AiCoachService, AnthropicAiClient, type AiClient } from './services/ai.js';
import { MarketDataService, TwelveDataProvider, type MarketDataProvider } from './services/marketData.js';
import { BrokerService } from './services/brokers/service.js';
import { RunnerAuditService } from './services/runner.js';
import { RuntimeConfig } from './services/runtimeConfig.js';
import { AdminService } from './services/admin.js';

export interface AppDeps {
  env: Env;
  db: Database;
  pool: pg.Pool;
  log: Logger;
  storage: StorageProvider;
  /** Defaults to Google OIDC driven by runtime config (control panel → env). Tests inject a fake. */
  identityProvider?: IdentityProvider;
  /** Builds the AI client from the effective key/model. Defaults to Anthropic. */
  aiClientFactory?: (apiKey: string, model: string) => AiClient;
  /** Builds the market-data provider from the effective key. Defaults to Twelve Data. */
  marketDataFactory?: (apiKey: string) => MarketDataProvider;
}

/** Caches a client per configuration so it is rebuilt only when the saved key/model change. */
function memoByKey<T>(build: (key: string) => T) {
  let last: { key: string; value: T } | null = null;
  return (key: string): T => {
    if (!last || last.key !== key) last = { key, value: build(key) };
    return last.value;
  };
}

export function buildContext(deps: AppDeps) {
  const { db, env, log } = deps;
  const box = new SecretBox(env.ENCRYPTION_KEY);
  const runtime = new RuntimeConfig(db, env, box, log);
  const identityProvider = deps.identityProvider ?? new GoogleIdentityProvider(() => runtime.google());

  const aiFactory = deps.aiClientFactory ?? ((k, m) => new AnthropicAiClient(k, m));
  const aiMemo = memoByKey((composite) => {
    const [k, m] = JSON.parse(composite) as [string, string];
    return aiFactory(k, m);
  });
  const aiSource = (): AiClient | null => {
    const c = runtime.ai();
    return c.enabled && c.apiKey ? aiMemo(JSON.stringify([c.apiKey, c.model])) : null;
  };

  const mdFactory = deps.marketDataFactory ?? ((k) => new TwelveDataProvider(k, log));
  const mdMemo = memoByKey(mdFactory);
  const mdSource = (): MarketDataProvider | null => {
    const c = runtime.marketData();
    return c.enabled && c.apiKey ? mdMemo(c.apiKey) : null;
  };

  const trades = new TradeService(db);
  const analytics = new AnalyticsService(db);
  const market = new MarketDataService(mdSource, log);
  const ctx = {
    ...deps,
    identityProvider,
    runtime,
    box,
    trades,
    analytics,
    users: new UserService(db),
    accounts: new AccountService(db),
    strategies: new StrategyService(db),
    journal: new JournalService(db),
    demo: new DemoService(db),
    ai: new AiCoachService(db, analytics, aiSource, log),
    market,
    brokers: new BrokerService(db, box, env.API_URL, log, env.BINANCE_API_BASE),
    runner: new RunnerAuditService(db, trades, market),
    admin: new AdminService(db, box, log),
    features(): FeatureFlags {
      const f = runtime.features();
      return {
        googleAuth: identityProvider.configured,
        devLogin: env.DEV_AUTH_BYPASS && env.NODE_ENV !== 'production',
        ai: aiSource() !== null,
        marketData: mdSource() !== null,
        storage: deps.storage.kind,
        aiCoach: f.aiCoach,
        brokerSync: f.brokerSync,
        demoMode: f.demoMode,
        marketTicker: f.marketTicker,
      };
    },
  };
  return ctx;
}

export type AppContext = ReturnType<typeof buildContext>;
