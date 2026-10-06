CREATE TABLE "ai_conversations" (
	"id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
	"user_id" uuid NOT NULL,
	"title" varchar(160) NOT NULL,
	"language" varchar(8) DEFAULT 'en' NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"updated_at" timestamp with time zone DEFAULT now() NOT NULL
);
--> statement-breakpoint
CREATE TABLE "ai_messages" (
	"id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
	"conversation_id" uuid NOT NULL,
	"user_id" uuid NOT NULL,
	"role" varchar(12) NOT NULL,
	"content" text NOT NULL,
	"model" varchar(64),
	"input_tokens" integer,
	"output_tokens" integer,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL
);
--> statement-breakpoint
CREATE TABLE "audit_logs" (
	"id" bigserial PRIMARY KEY NOT NULL,
	"user_id" uuid,
	"action" varchar(64) NOT NULL,
	"ip" varchar(64),
	"user_agent" varchar(400),
	"request_id" varchar(64),
	"metadata" jsonb DEFAULT '{}'::jsonb NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL
);
--> statement-breakpoint
CREATE TABLE "broker_connections" (
	"id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
	"user_id" uuid NOT NULL,
	"trading_account_id" uuid,
	"provider" varchar(40) NOT NULL,
	"label" varchar(80) NOT NULL,
	"status" varchar(20) DEFAULT 'ACTIVE' NOT NULL,
	"encrypted_credentials" text,
	"encrypted_webhook_secret" text,
	"credential_hint" varchar(32),
	"config" jsonb DEFAULT '{}'::jsonb NOT NULL,
	"last_synced_at" timestamp with time zone,
	"last_error" text,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"updated_at" timestamp with time zone DEFAULT now() NOT NULL
);
--> statement-breakpoint
CREATE TABLE "capital_transactions" (
	"id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
	"user_id" uuid NOT NULL,
	"trading_account_id" uuid NOT NULL,
	"type" varchar(12) NOT NULL,
	"amount" numeric(20, 2) NOT NULL,
	"note" varchar(500),
	"occurred_at" timestamp with time zone DEFAULT now() NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	CONSTRAINT "capital_tx_type_chk" CHECK ("capital_transactions"."type" IN ('DEPOSIT','WITHDRAWAL','ADJUSTMENT'))
);
--> statement-breakpoint
CREATE TABLE "checklist_entries" (
	"id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
	"user_id" uuid NOT NULL,
	"entry_date" date NOT NULL,
	"item_key" varchar(40) NOT NULL,
	"completed" boolean DEFAULT false NOT NULL,
	"completed_at" timestamp with time zone,
	"updated_at" timestamp with time zone DEFAULT now() NOT NULL
);
--> statement-breakpoint
CREATE TABLE "instruments" (
	"symbol" varchar(20) PRIMARY KEY NOT NULL,
	"display_name" varchar(60) NOT NULL,
	"asset_class" varchar(20) NOT NULL,
	"base_currency" varchar(10) NOT NULL,
	"quote_currency" varchar(10) NOT NULL,
	"contract_size" numeric(20, 6) NOT NULL,
	"tick_size" numeric(20, 10) NOT NULL,
	"tick_value" numeric(20, 10) NOT NULL,
	"pip_size" numeric(20, 10) NOT NULL,
	"decimals" smallint NOT NULL,
	"aliases" text[] DEFAULT '{}'::text[] NOT NULL
);
--> statement-breakpoint
CREATE TABLE "journal_entries" (
	"id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
	"user_id" uuid NOT NULL,
	"journal_date" date NOT NULL,
	"compliance" smallint,
	"emotional_state" varchar(20),
	"discipline_rating" smallint,
	"reflection" text,
	"key_lesson" text,
	"voice_transcript" text,
	"voice_language" varchar(8),
	"demo" boolean DEFAULT false NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"updated_at" timestamp with time zone DEFAULT now() NOT NULL,
	CONSTRAINT "journal_compliance_chk" CHECK ("journal_entries"."compliance" IS NULL OR "journal_entries"."compliance" BETWEEN 1 AND 5),
	CONSTRAINT "journal_discipline_chk" CHECK ("journal_entries"."discipline_rating" IS NULL OR "journal_entries"."discipline_rating" BETWEEN 1 AND 10)
);
--> statement-breakpoint
CREATE TABLE "performance_snapshots" (
	"id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
	"user_id" uuid NOT NULL,
	"kind" varchar(20) NOT NULL,
	"scope" varchar(40) NOT NULL,
	"period_start" date NOT NULL,
	"period_end" date NOT NULL,
	"metrics" jsonb NOT NULL,
	"computed_at" timestamp with time zone DEFAULT now() NOT NULL
);
--> statement-breakpoint
CREATE TABLE "strategies" (
	"id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
	"user_id" uuid NOT NULL,
	"name" varchar(80) NOT NULL,
	"description" text,
	"target_rr" numeric(6, 2),
	"checklist" jsonb DEFAULT '[]'::jsonb NOT NULL,
	"active" boolean DEFAULT true NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"updated_at" timestamp with time zone DEFAULT now() NOT NULL
);
--> statement-breakpoint
CREATE TABLE "strategy_templates" (
	"id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
	"name" varchar(80) NOT NULL,
	"description" text,
	"target_rr" numeric(6, 2),
	"checklist" jsonb DEFAULT '[]'::jsonb NOT NULL,
	"sort_order" integer DEFAULT 0 NOT NULL,
	CONSTRAINT "strategy_templates_name_unique" UNIQUE("name")
);
--> statement-breakpoint
CREATE TABLE "trades" (
	"id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
	"user_id" uuid NOT NULL,
	"trading_account_id" uuid NOT NULL,
	"executed_at" timestamp with time zone NOT NULL,
	"closed_at" timestamp with time zone,
	"symbol" varchar(20) NOT NULL,
	"asset_class" varchar(20) NOT NULL,
	"side" varchar(5) NOT NULL,
	"status" varchar(6) DEFAULT 'CLOSED' NOT NULL,
	"entry_price" numeric(24, 10) NOT NULL,
	"exit_price" numeric(24, 10),
	"stop_loss" numeric(24, 10),
	"take_profit" numeric(24, 10),
	"lot_size" numeric(18, 6) NOT NULL,
	"pnl" numeric(20, 2),
	"pnl_overridden" boolean DEFAULT false NOT NULL,
	"fees" numeric(20, 2) DEFAULT '0' NOT NULL,
	"rr" numeric(12, 4),
	"risk_amount" numeric(20, 2),
	"strategy_id" uuid,
	"setup_tag" varchar(120),
	"session" varchar(20),
	"emotion" varchar(20),
	"mistake_tag" varchar(30),
	"rules_followed" boolean DEFAULT true NOT NULL,
	"notes" text,
	"screenshot_url" text,
	"source" varchar(20) DEFAULT 'MANUAL' NOT NULL,
	"broker_trade_id" varchar(120),
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"updated_at" timestamp with time zone DEFAULT now() NOT NULL,
	CONSTRAINT "trades_side_chk" CHECK ("trades"."side" IN ('LONG','SHORT')),
	CONSTRAINT "trades_status_chk" CHECK ("trades"."status" IN ('OPEN','CLOSED')),
	CONSTRAINT "trades_lot_positive_chk" CHECK ("trades"."lot_size" > 0),
	CONSTRAINT "trades_entry_positive_chk" CHECK ("trades"."entry_price" > 0)
);
--> statement-breakpoint
CREATE TABLE "trading_accounts" (
	"id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
	"user_id" uuid NOT NULL,
	"account_name" varchar(80) NOT NULL,
	"broker_name" varchar(80),
	"account_type" varchar(32) DEFAULT 'PERSONAL' NOT NULL,
	"currency" varchar(3) DEFAULT 'USD' NOT NULL,
	"starting_capital" numeric(20, 2) NOT NULL,
	"current_capital" numeric(20, 2) NOT NULL,
	"demo" boolean DEFAULT false NOT NULL,
	"sample_data" boolean DEFAULT false NOT NULL,
	"archived" boolean DEFAULT false NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"updated_at" timestamp with time zone DEFAULT now() NOT NULL,
	CONSTRAINT "trading_accounts_capital_chk" CHECK ("trading_accounts"."starting_capital" >= 0),
	CONSTRAINT "trading_accounts_sample_demo_chk" CHECK (NOT "trading_accounts"."sample_data" OR "trading_accounts"."demo")
);
--> statement-breakpoint
CREATE TABLE "user_sessions" (
	"sid" varchar PRIMARY KEY NOT NULL,
	"sess" json NOT NULL,
	"expire" timestamp (6) NOT NULL
);
--> statement-breakpoint
CREATE TABLE "user_settings" (
	"id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
	"user_id" uuid NOT NULL,
	"theme" varchar(32) DEFAULT 'dark-terminal' NOT NULL,
	"language" varchar(8) DEFAULT 'en' NOT NULL,
	"timezone" varchar(64) DEFAULT 'UTC' NOT NULL,
	"base_currency" varchar(3) DEFAULT 'USD' NOT NULL,
	"default_risk_percentage" numeric(6, 3) DEFAULT '1.000' NOT NULL,
	"max_daily_loss" numeric(20, 2),
	"max_weekly_loss" numeric(20, 2),
	"default_target_rr" numeric(6, 2) DEFAULT '2.00' NOT NULL,
	"primary_markets" text[] DEFAULT '{}'::text[] NOT NULL,
	"active_account_scope" varchar(40) DEFAULT 'real' NOT NULL,
	"tilt_loss_count" smallint DEFAULT 3 NOT NULL,
	"tilt_window_minutes" integer DEFAULT 20 NOT NULL,
	"tilt_cooldown_minutes" integer DEFAULT 30 NOT NULL,
	"terminal_lock_until" timestamp with time zone,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"updated_at" timestamp with time zone DEFAULT now() NOT NULL
);
--> statement-breakpoint
CREATE TABLE "users" (
	"id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
	"email" varchar(320) NOT NULL,
	"name" varchar(120),
	"avatar_url" text,
	"google_subject_id" varchar(255),
	"auth_provider" varchar(20) DEFAULT 'google' NOT NULL,
	"email_verified" boolean DEFAULT false NOT NULL,
	"onboarded_at" timestamp with time zone,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"updated_at" timestamp with time zone DEFAULT now() NOT NULL,
	"last_login_at" timestamp with time zone
);
--> statement-breakpoint
CREATE TABLE "webhook_events" (
	"id" uuid PRIMARY KEY DEFAULT gen_random_uuid() NOT NULL,
	"connection_id" uuid NOT NULL,
	"event_id" varchar(120) NOT NULL,
	"status" varchar(20) NOT NULL,
	"trade_id" uuid,
	"error" text,
	"received_at" timestamp with time zone DEFAULT now() NOT NULL
);
--> statement-breakpoint
ALTER TABLE "ai_conversations" ADD CONSTRAINT "ai_conversations_user_id_users_id_fk" FOREIGN KEY ("user_id") REFERENCES "public"."users"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "ai_messages" ADD CONSTRAINT "ai_messages_conversation_id_ai_conversations_id_fk" FOREIGN KEY ("conversation_id") REFERENCES "public"."ai_conversations"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "ai_messages" ADD CONSTRAINT "ai_messages_user_id_users_id_fk" FOREIGN KEY ("user_id") REFERENCES "public"."users"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "audit_logs" ADD CONSTRAINT "audit_logs_user_id_users_id_fk" FOREIGN KEY ("user_id") REFERENCES "public"."users"("id") ON DELETE set null ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "broker_connections" ADD CONSTRAINT "broker_connections_user_id_users_id_fk" FOREIGN KEY ("user_id") REFERENCES "public"."users"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "broker_connections" ADD CONSTRAINT "broker_connections_trading_account_id_trading_accounts_id_fk" FOREIGN KEY ("trading_account_id") REFERENCES "public"."trading_accounts"("id") ON DELETE set null ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "capital_transactions" ADD CONSTRAINT "capital_transactions_user_id_users_id_fk" FOREIGN KEY ("user_id") REFERENCES "public"."users"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "capital_transactions" ADD CONSTRAINT "capital_transactions_trading_account_id_trading_accounts_id_fk" FOREIGN KEY ("trading_account_id") REFERENCES "public"."trading_accounts"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "checklist_entries" ADD CONSTRAINT "checklist_entries_user_id_users_id_fk" FOREIGN KEY ("user_id") REFERENCES "public"."users"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "journal_entries" ADD CONSTRAINT "journal_entries_user_id_users_id_fk" FOREIGN KEY ("user_id") REFERENCES "public"."users"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "performance_snapshots" ADD CONSTRAINT "performance_snapshots_user_id_users_id_fk" FOREIGN KEY ("user_id") REFERENCES "public"."users"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "strategies" ADD CONSTRAINT "strategies_user_id_users_id_fk" FOREIGN KEY ("user_id") REFERENCES "public"."users"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "trades" ADD CONSTRAINT "trades_user_id_users_id_fk" FOREIGN KEY ("user_id") REFERENCES "public"."users"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "trades" ADD CONSTRAINT "trades_trading_account_id_trading_accounts_id_fk" FOREIGN KEY ("trading_account_id") REFERENCES "public"."trading_accounts"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "trades" ADD CONSTRAINT "trades_strategy_id_strategies_id_fk" FOREIGN KEY ("strategy_id") REFERENCES "public"."strategies"("id") ON DELETE set null ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "trading_accounts" ADD CONSTRAINT "trading_accounts_user_id_users_id_fk" FOREIGN KEY ("user_id") REFERENCES "public"."users"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "user_settings" ADD CONSTRAINT "user_settings_user_id_users_id_fk" FOREIGN KEY ("user_id") REFERENCES "public"."users"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "webhook_events" ADD CONSTRAINT "webhook_events_connection_id_broker_connections_id_fk" FOREIGN KEY ("connection_id") REFERENCES "public"."broker_connections"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "webhook_events" ADD CONSTRAINT "webhook_events_trade_id_trades_id_fk" FOREIGN KEY ("trade_id") REFERENCES "public"."trades"("id") ON DELETE set null ON UPDATE no action;--> statement-breakpoint
CREATE INDEX "ai_conversations_user_idx" ON "ai_conversations" USING btree ("user_id","updated_at" DESC NULLS LAST);--> statement-breakpoint
CREATE INDEX "ai_messages_conversation_idx" ON "ai_messages" USING btree ("conversation_id","created_at");--> statement-breakpoint
CREATE INDEX "audit_logs_user_idx" ON "audit_logs" USING btree ("user_id","created_at" DESC NULLS LAST);--> statement-breakpoint
CREATE INDEX "audit_logs_action_idx" ON "audit_logs" USING btree ("action","created_at" DESC NULLS LAST);--> statement-breakpoint
CREATE INDEX "broker_connections_user_idx" ON "broker_connections" USING btree ("user_id");--> statement-breakpoint
CREATE INDEX "capital_tx_account_idx" ON "capital_transactions" USING btree ("trading_account_id","occurred_at");--> statement-breakpoint
CREATE INDEX "capital_tx_user_idx" ON "capital_transactions" USING btree ("user_id");--> statement-breakpoint
CREATE UNIQUE INDEX "checklist_user_date_item_uq" ON "checklist_entries" USING btree ("user_id","entry_date","item_key");--> statement-breakpoint
CREATE UNIQUE INDEX "journal_user_date_demo_uq" ON "journal_entries" USING btree ("user_id","journal_date","demo");--> statement-breakpoint
CREATE UNIQUE INDEX "perf_snapshots_uq" ON "performance_snapshots" USING btree ("user_id","kind","scope","period_start","period_end");--> statement-breakpoint
CREATE INDEX "strategies_user_idx" ON "strategies" USING btree ("user_id");--> statement-breakpoint
CREATE UNIQUE INDEX "strategies_user_name_uq" ON "strategies" USING btree ("user_id",lower("name"));--> statement-breakpoint
CREATE INDEX "trades_user_executed_idx" ON "trades" USING btree ("user_id","executed_at" DESC NULLS LAST);--> statement-breakpoint
CREATE INDEX "trades_user_symbol_idx" ON "trades" USING btree ("user_id","symbol");--> statement-breakpoint
CREATE INDEX "trades_user_strategy_idx" ON "trades" USING btree ("user_id","strategy_id");--> statement-breakpoint
CREATE INDEX "trades_account_executed_idx" ON "trades" USING btree ("trading_account_id","executed_at" DESC NULLS LAST);--> statement-breakpoint
CREATE UNIQUE INDEX "trades_broker_trade_uq" ON "trades" USING btree ("user_id","trading_account_id","source","broker_trade_id") WHERE "trades"."broker_trade_id" IS NOT NULL;--> statement-breakpoint
CREATE INDEX "trading_accounts_user_idx" ON "trading_accounts" USING btree ("user_id");--> statement-breakpoint
CREATE INDEX "IDX_user_sessions_expire" ON "user_sessions" USING btree ("expire");--> statement-breakpoint
CREATE UNIQUE INDEX "user_settings_user_uq" ON "user_settings" USING btree ("user_id");--> statement-breakpoint
CREATE UNIQUE INDEX "users_email_lower_uq" ON "users" USING btree (lower("email"));--> statement-breakpoint
CREATE UNIQUE INDEX "users_google_subject_uq" ON "users" USING btree ("google_subject_id");--> statement-breakpoint
CREATE UNIQUE INDEX "webhook_events_conn_event_uq" ON "webhook_events" USING btree ("connection_id","event_id");