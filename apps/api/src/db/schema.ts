import { sql } from 'drizzle-orm';
import {
  bigserial,
  boolean,
  check,
  date,
  index,
  integer,
  jsonb,
  numeric,
  pgTable,
  smallint,
  text,
  timestamp,
  uniqueIndex,
  uuid,
  varchar,
  json,
} from 'drizzle-orm/pg-core';

/**
 * journzey.ai PostgreSQL schema.
 * - All money/price values use NUMERIC (never floating point).
 * - All timestamps are TIMESTAMPTZ stored in UTC.
 * - Every user-owned table carries user_id with ON DELETE CASCADE so account deletion
 *   removes all personal data atomically.
 */

const ts = (name: string) => timestamp(name, { withTimezone: true, mode: 'date' });
const money = (name: string) => numeric(name, { precision: 20, scale: 2 });
const price = (name: string) => numeric(name, { precision: 24, scale: 10 });

export const users = pgTable(
  'users',
  {
    id: uuid('id').primaryKey().defaultRandom(),
    email: varchar('email', { length: 320 }).notNull(),
    name: varchar('name', { length: 120 }),
    avatarUrl: text('avatar_url'),
    googleSubjectId: varchar('google_subject_id', { length: 255 }),
    authProvider: varchar('auth_provider', { length: 20 }).notNull().default('google'),
    emailVerified: boolean('email_verified').notNull().default(false),
    onboardedAt: ts('onboarded_at'),
    /** Set by a control-panel admin; suspended users cannot sign in or use the API. */
    suspendedAt: ts('suspended_at'),
    createdAt: ts('created_at').notNull().defaultNow(),
    updatedAt: ts('updated_at').notNull().defaultNow(),
    lastLoginAt: ts('last_login_at'),
  },
  (t) => [
    uniqueIndex('users_email_lower_uq').on(sql`lower(${t.email})`),
    uniqueIndex('users_google_subject_uq').on(t.googleSubjectId),
  ],
);

export const userSettings = pgTable(
  'user_settings',
  {
    id: uuid('id').primaryKey().defaultRandom(),
    userId: uuid('user_id')
      .notNull()
      .references(() => users.id, { onDelete: 'cascade' }),
    theme: varchar('theme', { length: 32 }).notNull().default('dark-terminal'),
    language: varchar('language', { length: 8 }).notNull().default('en'),
    timezone: varchar('timezone', { length: 64 }).notNull().default('UTC'),
    baseCurrency: varchar('base_currency', { length: 3 }).notNull().default('USD'),
    defaultRiskPercentage: numeric('default_risk_percentage', { precision: 6, scale: 3 }).notNull().default('1.000'),
    maxDailyLoss: money('max_daily_loss'),
    maxWeeklyLoss: money('max_weekly_loss'),
    defaultTargetRr: numeric('default_target_rr', { precision: 6, scale: 2 }).notNull().default('2.00'),
    primaryMarkets: text('primary_markets').array().notNull().default(sql`'{}'::text[]`),
    activeAccountScope: varchar('active_account_scope', { length: 40 }).notNull().default('real'),
    tiltLossCount: smallint('tilt_loss_count').notNull().default(3),
    tiltWindowMinutes: integer('tilt_window_minutes').notNull().default(20),
    tiltCooldownMinutes: integer('tilt_cooldown_minutes').notNull().default(30),
    terminalLockUntil: ts('terminal_lock_until'),
    createdAt: ts('created_at').notNull().defaultNow(),
    updatedAt: ts('updated_at').notNull().defaultNow(),
  },
  (t) => [uniqueIndex('user_settings_user_uq').on(t.userId)],
);

export const tradingAccounts = pgTable(
  'trading_accounts',
  {
    id: uuid('id').primaryKey().defaultRandom(),
    userId: uuid('user_id')
      .notNull()
      .references(() => users.id, { onDelete: 'cascade' }),
    accountName: varchar('account_name', { length: 80 }).notNull(),
    brokerName: varchar('broker_name', { length: 80 }),
    accountType: varchar('account_type', { length: 32 }).notNull().default('PERSONAL'),
    currency: varchar('currency', { length: 3 }).notNull().default('USD'),
    startingCapital: money('starting_capital').notNull(),
    currentCapital: money('current_capital').notNull(),
    /** Demo/practice broker account — excluded from "real" analytics. */
    demo: boolean('demo').notNull().default(false),
    /** Generated DEMO DATA account (onboarding / Reset Demo Data). Always demo=true too. */
    sampleData: boolean('sample_data').notNull().default(false),
    archived: boolean('archived').notNull().default(false),
    createdAt: ts('created_at').notNull().defaultNow(),
    updatedAt: ts('updated_at').notNull().defaultNow(),
  },
  (t) => [
    index('trading_accounts_user_idx').on(t.userId),
    check('trading_accounts_capital_chk', sql`${t.startingCapital} >= 0`),
    check('trading_accounts_sample_demo_chk', sql`NOT ${t.sampleData} OR ${t.demo}`),
  ],
);

export const strategies = pgTable(
  'strategies',
  {
    id: uuid('id').primaryKey().defaultRandom(),
    userId: uuid('user_id')
      .notNull()
      .references(() => users.id, { onDelete: 'cascade' }),
    name: varchar('name', { length: 80 }).notNull(),
    description: text('description'),
    targetRr: numeric('target_rr', { precision: 6, scale: 2 }),
    checklist: jsonb('checklist').$type<string[]>().notNull().default(sql`'[]'::jsonb`),
    active: boolean('active').notNull().default(true),
    createdAt: ts('created_at').notNull().defaultNow(),
    updatedAt: ts('updated_at').notNull().defaultNow(),
  },
  (t) => [index('strategies_user_idx').on(t.userId), uniqueIndex('strategies_user_name_uq').on(t.userId, sql`lower(${t.name})`)],
);

export const trades = pgTable(
  'trades',
  {
    id: uuid('id').primaryKey().defaultRandom(),
    userId: uuid('user_id')
      .notNull()
      .references(() => users.id, { onDelete: 'cascade' }),
    tradingAccountId: uuid('trading_account_id')
      .notNull()
      .references(() => tradingAccounts.id, { onDelete: 'cascade' }),
    executedAt: ts('executed_at').notNull(),
    closedAt: ts('closed_at'),
    symbol: varchar('symbol', { length: 20 }).notNull(),
    assetClass: varchar('asset_class', { length: 20 }).notNull(),
    side: varchar('side', { length: 5 }).notNull(),
    status: varchar('status', { length: 6 }).notNull().default('CLOSED'),
    entryPrice: price('entry_price').notNull(),
    exitPrice: price('exit_price'),
    stopLoss: price('stop_loss'),
    takeProfit: price('take_profit'),
    lotSize: numeric('lot_size', { precision: 18, scale: 6 }).notNull(),
    pnl: money('pnl'),
    pnlOverridden: boolean('pnl_overridden').notNull().default(false),
    fees: money('fees').notNull().default('0'),
    rr: numeric('rr', { precision: 12, scale: 4 }),
    riskAmount: money('risk_amount'),
    strategyId: uuid('strategy_id').references(() => strategies.id, { onDelete: 'set null' }),
    setupTag: varchar('setup_tag', { length: 120 }),
    session: varchar('session', { length: 20 }),
    emotion: varchar('emotion', { length: 20 }),
    mistakeTag: varchar('mistake_tag', { length: 30 }),
    rulesFollowed: boolean('rules_followed').notNull().default(true),
    notes: text('notes'),
    screenshotUrl: text('screenshot_url'),
    source: varchar('source', { length: 20 }).notNull().default('MANUAL'),
    brokerTradeId: varchar('broker_trade_id', { length: 120 }),
    createdAt: ts('created_at').notNull().defaultNow(),
    updatedAt: ts('updated_at').notNull().defaultNow(),
  },
  (t) => [
    index('trades_user_executed_idx').on(t.userId, t.executedAt.desc()),
    index('trades_user_symbol_idx').on(t.userId, t.symbol),
    index('trades_user_strategy_idx').on(t.userId, t.strategyId),
    index('trades_account_executed_idx').on(t.tradingAccountId, t.executedAt.desc()),
    uniqueIndex('trades_broker_trade_uq')
      .on(t.userId, t.tradingAccountId, t.source, t.brokerTradeId)
      .where(sql`${t.brokerTradeId} IS NOT NULL`),
    check('trades_side_chk', sql`${t.side} IN ('LONG','SHORT')`),
    check('trades_status_chk', sql`${t.status} IN ('OPEN','CLOSED')`),
    check('trades_lot_positive_chk', sql`${t.lotSize} > 0`),
    check('trades_entry_positive_chk', sql`${t.entryPrice} > 0`),
  ],
);

export const journalEntries = pgTable(
  'journal_entries',
  {
    id: uuid('id').primaryKey().defaultRandom(),
    userId: uuid('user_id')
      .notNull()
      .references(() => users.id, { onDelete: 'cascade' }),
    journalDate: date('journal_date', { mode: 'string' }).notNull(),
    compliance: smallint('compliance'),
    emotionalState: varchar('emotional_state', { length: 20 }),
    disciplineRating: smallint('discipline_rating'),
    reflection: text('reflection'),
    keyLesson: text('key_lesson'),
    voiceTranscript: text('voice_transcript'),
    voiceLanguage: varchar('voice_language', { length: 8 }),
    /** Generated DEMO DATA journal entry; never shown alongside real entries. */
    demo: boolean('demo').notNull().default(false),
    createdAt: ts('created_at').notNull().defaultNow(),
    updatedAt: ts('updated_at').notNull().defaultNow(),
  },
  (t) => [
    uniqueIndex('journal_user_date_demo_uq').on(t.userId, t.journalDate, t.demo),
    check('journal_compliance_chk', sql`${t.compliance} IS NULL OR ${t.compliance} BETWEEN 1 AND 5`),
    check('journal_discipline_chk', sql`${t.disciplineRating} IS NULL OR ${t.disciplineRating} BETWEEN 1 AND 10`),
  ],
);

export const checklistEntries = pgTable(
  'checklist_entries',
  {
    id: uuid('id').primaryKey().defaultRandom(),
    userId: uuid('user_id')
      .notNull()
      .references(() => users.id, { onDelete: 'cascade' }),
    entryDate: date('entry_date', { mode: 'string' }).notNull(),
    itemKey: varchar('item_key', { length: 40 }).notNull(),
    completed: boolean('completed').notNull().default(false),
    completedAt: ts('completed_at'),
    updatedAt: ts('updated_at').notNull().defaultNow(),
  },
  (t) => [uniqueIndex('checklist_user_date_item_uq').on(t.userId, t.entryDate, t.itemKey)],
);

export const capitalTransactions = pgTable(
  'capital_transactions',
  {
    id: uuid('id').primaryKey().defaultRandom(),
    userId: uuid('user_id')
      .notNull()
      .references(() => users.id, { onDelete: 'cascade' }),
    tradingAccountId: uuid('trading_account_id')
      .notNull()
      .references(() => tradingAccounts.id, { onDelete: 'cascade' }),
    type: varchar('type', { length: 12 }).notNull(),
    amount: money('amount').notNull(),
    note: varchar('note', { length: 500 }),
    occurredAt: ts('occurred_at').notNull().defaultNow(),
    createdAt: ts('created_at').notNull().defaultNow(),
  },
  (t) => [
    index('capital_tx_account_idx').on(t.tradingAccountId, t.occurredAt),
    index('capital_tx_user_idx').on(t.userId),
    check('capital_tx_type_chk', sql`${t.type} IN ('DEPOSIT','WITHDRAWAL','ADJUSTMENT')`),
  ],
);

export const brokerConnections = pgTable(
  'broker_connections',
  {
    id: uuid('id').primaryKey().defaultRandom(),
    userId: uuid('user_id')
      .notNull()
      .references(() => users.id, { onDelete: 'cascade' }),
    tradingAccountId: uuid('trading_account_id').references(() => tradingAccounts.id, { onDelete: 'set null' }),
    provider: varchar('provider', { length: 40 }).notNull(),
    label: varchar('label', { length: 80 }).notNull(),
    status: varchar('status', { length: 20 }).notNull().default('ACTIVE'),
    /** AES-256-GCM encrypted JSON of provider credentials. Never returned to clients. */
    encryptedCredentials: text('encrypted_credentials'),
    /** AES-256-GCM encrypted webhook signing secret. Shown to the user exactly once. */
    encryptedWebhookSecret: text('encrypted_webhook_secret'),
    credentialHint: varchar('credential_hint', { length: 32 }),
    config: jsonb('config').$type<Record<string, unknown>>().notNull().default(sql`'{}'::jsonb`),
    lastSyncedAt: ts('last_synced_at'),
    lastError: text('last_error'),
    createdAt: ts('created_at').notNull().defaultNow(),
    updatedAt: ts('updated_at').notNull().defaultNow(),
  },
  (t) => [index('broker_connections_user_idx').on(t.userId)],
);

export const webhookEvents = pgTable(
  'webhook_events',
  {
    id: uuid('id').primaryKey().defaultRandom(),
    connectionId: uuid('connection_id')
      .notNull()
      .references(() => brokerConnections.id, { onDelete: 'cascade' }),
    eventId: varchar('event_id', { length: 120 }).notNull(),
    status: varchar('status', { length: 20 }).notNull(),
    tradeId: uuid('trade_id').references(() => trades.id, { onDelete: 'set null' }),
    error: text('error'),
    receivedAt: ts('received_at').notNull().defaultNow(),
  },
  (t) => [uniqueIndex('webhook_events_conn_event_uq').on(t.connectionId, t.eventId)],
);

export const performanceSnapshots = pgTable(
  'performance_snapshots',
  {
    id: uuid('id').primaryKey().defaultRandom(),
    userId: uuid('user_id')
      .notNull()
      .references(() => users.id, { onDelete: 'cascade' }),
    kind: varchar('kind', { length: 20 }).notNull(),
    scope: varchar('scope', { length: 40 }).notNull(),
    periodStart: date('period_start', { mode: 'string' }).notNull(),
    periodEnd: date('period_end', { mode: 'string' }).notNull(),
    metrics: jsonb('metrics').$type<Record<string, unknown>>().notNull(),
    computedAt: ts('computed_at').notNull().defaultNow(),
  },
  (t) => [uniqueIndex('perf_snapshots_uq').on(t.userId, t.kind, t.scope, t.periodStart, t.periodEnd)],
);

export const aiConversations = pgTable(
  'ai_conversations',
  {
    id: uuid('id').primaryKey().defaultRandom(),
    userId: uuid('user_id')
      .notNull()
      .references(() => users.id, { onDelete: 'cascade' }),
    title: varchar('title', { length: 160 }).notNull(),
    language: varchar('language', { length: 8 }).notNull().default('en'),
    createdAt: ts('created_at').notNull().defaultNow(),
    updatedAt: ts('updated_at').notNull().defaultNow(),
  },
  (t) => [index('ai_conversations_user_idx').on(t.userId, t.updatedAt.desc())],
);

export const aiMessages = pgTable(
  'ai_messages',
  {
    id: uuid('id').primaryKey().defaultRandom(),
    conversationId: uuid('conversation_id')
      .notNull()
      .references(() => aiConversations.id, { onDelete: 'cascade' }),
    userId: uuid('user_id')
      .notNull()
      .references(() => users.id, { onDelete: 'cascade' }),
    role: varchar('role', { length: 12 }).notNull(),
    content: text('content').notNull(),
    model: varchar('model', { length: 64 }),
    inputTokens: integer('input_tokens'),
    outputTokens: integer('output_tokens'),
    createdAt: ts('created_at').notNull().defaultNow(),
  },
  (t) => [index('ai_messages_conversation_idx').on(t.conversationId, t.createdAt)],
);

export const auditLogs = pgTable(
  'audit_logs',
  {
    id: bigserial('id', { mode: 'number' }).primaryKey(),
    userId: uuid('user_id').references(() => users.id, { onDelete: 'set null' }),
    action: varchar('action', { length: 64 }).notNull(),
    ip: varchar('ip', { length: 64 }),
    userAgent: varchar('user_agent', { length: 400 }),
    requestId: varchar('request_id', { length: 64 }),
    metadata: jsonb('metadata').$type<Record<string, unknown>>().notNull().default(sql`'{}'::jsonb`),
    createdAt: ts('created_at').notNull().defaultNow(),
  },
  (t) => [index('audit_logs_user_idx').on(t.userId, t.createdAt.desc()), index('audit_logs_action_idx').on(t.action, t.createdAt.desc())],
);

/** Reference data: strategy playbook templates copied to each user on onboarding. */
export const strategyTemplates = pgTable('strategy_templates', {
  id: uuid('id').primaryKey().defaultRandom(),
  name: varchar('name', { length: 80 }).notNull().unique(),
  description: text('description'),
  targetRr: numeric('target_rr', { precision: 6, scale: 2 }),
  checklist: jsonb('checklist').$type<string[]>().notNull().default(sql`'[]'::jsonb`),
  sortOrder: integer('sort_order').notNull().default(0),
});

/** Reference data: supported instruments (mirrors packages/shared/src/instruments.ts). */
export const instruments = pgTable('instruments', {
  symbol: varchar('symbol', { length: 20 }).primaryKey(),
  displayName: varchar('display_name', { length: 60 }).notNull(),
  assetClass: varchar('asset_class', { length: 20 }).notNull(),
  baseCurrency: varchar('base_currency', { length: 10 }).notNull(),
  quoteCurrency: varchar('quote_currency', { length: 10 }).notNull(),
  contractSize: numeric('contract_size', { precision: 20, scale: 6 }).notNull(),
  tickSize: numeric('tick_size', { precision: 20, scale: 10 }).notNull(),
  tickValue: numeric('tick_value', { precision: 20, scale: 10 }).notNull(),
  pipSize: numeric('pip_size', { precision: 20, scale: 10 }).notNull(),
  decimals: smallint('decimals').notNull(),
  aliases: text('aliases').array().notNull().default(sql`'{}'::text[]`),
});

/** express-session store table (connect-pg-simple layout). */
export const userSessions = pgTable(
  'user_sessions',
  {
    sid: varchar('sid').primaryKey(),
    sess: json('sess').notNull(),
    expire: timestamp('expire', { precision: 6, withTimezone: false }).notNull(),
  },
  (t) => [index('IDX_user_sessions_expire').on(t.expire)],
);


/* ------------------------------------------------------------------ */
/* Control panel (admin portal)                                         */
/* ------------------------------------------------------------------ */

/** Control-panel operators. Completely separate from trader accounts (users). */
export const adminUsers = pgTable(
  'admin_users',
  {
    id: uuid('id').primaryKey().defaultRandom(),
    email: varchar('email', { length: 320 }).notNull(),
    name: varchar('name', { length: 120 }).notNull(),
    /** Argon2id hash. Plaintext passwords are never stored or logged. */
    passwordHash: text('password_hash').notNull(),
    role: varchar('role', { length: 12 }).notNull().default('admin'),
    /** AES-256-GCM encrypted TOTP secret when two-factor authentication is enabled. */
    totpSecretEncrypted: text('totp_secret_encrypted'),
    totpEnabled: boolean('totp_enabled').notNull().default(false),
    failedLogins: integer('failed_logins').notNull().default(0),
    lockedUntil: ts('locked_until'),
    disabled: boolean('disabled').notNull().default(false),
    lastLoginAt: ts('last_login_at'),
    passwordChangedAt: ts('password_changed_at').notNull().defaultNow(),
    createdAt: ts('created_at').notNull().defaultNow(),
    updatedAt: ts('updated_at').notNull().defaultNow(),
  },
  (t) => [uniqueIndex('admin_users_email_lower_uq').on(sql`lower(${t.email})`), check('admin_users_role_chk', sql`${t.role} IN ('owner','admin','viewer')`)],
);

/** Runtime configuration edited from the control panel. Secret values are AES-256-GCM encrypted. */
export const appSettings = pgTable('app_settings', {
  key: varchar('key', { length: 80 }).primaryKey(),
  value: jsonb('value').$type<unknown>(),
  encryptedValue: text('encrypted_value'),
  updatedBy: uuid('updated_by').references(() => adminUsers.id, { onDelete: 'set null' }),
  updatedAt: ts('updated_at').notNull().defaultNow(),
});

export const adminAuditLogs = pgTable(
  'admin_audit_logs',
  {
    id: bigserial('id', { mode: 'number' }).primaryKey(),
    adminId: uuid('admin_id').references(() => adminUsers.id, { onDelete: 'set null' }),
    adminEmail: varchar('admin_email', { length: 320 }),
    action: varchar('action', { length: 64 }).notNull(),
    target: varchar('target', { length: 200 }),
    ip: varchar('ip', { length: 64 }),
    userAgent: varchar('user_agent', { length: 400 }),
    metadata: jsonb('metadata').$type<Record<string, unknown>>().notNull().default(sql`'{}'::jsonb`),
    createdAt: ts('created_at').notNull().defaultNow(),
  },
  (t) => [index('admin_audit_created_idx').on(t.createdAt.desc()), index('admin_audit_admin_idx').on(t.adminId, t.createdAt.desc())],
);

export type AdminUserRow = typeof adminUsers.$inferSelect;
