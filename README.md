# journzey.ai

**Institutional Trading Journal & AI Discipline Terminal**

journzey.ai is a full-stack trading journal and analytics terminal for gold, forex, indices, crypto and commodities. Traders log executions in seconds (natural-language Quick Trade or a full manual form), and the platform turns that history into institutional-grade analytics: equity and drawdown, profit factor and expectancy, session and strategy edge, a conservative **Discipline Leak Mirror**, **Monte Carlo risk of ruin**, tilt detection, a psychology journal with voice dictation, and an optional AI Coach.

All authoritative data lives in PostgreSQL behind an authenticated, per-user-isolated REST API. Browser storage is used only for harmless conveniences (theme before first paint, unsaved journal drafts).

> journzey.ai is journaling and analytics software. It does not provide investment advice or guarantee any outcome. See `/disclaimer` in the app.

---

## Contents

1. [What's in the box](#1-whats-in-the-box)
2. [Architecture](#2-architecture)
3. [Requirements](#3-requirements)
4. [Installation](#4-installation)
5. [Environment configuration](#5-environment-configuration)
6. [PostgreSQL setup](#6-postgresql-setup)
7. [Migrations](#7-migrations)
8. [Seeding](#8-seeding)
9. [Google OAuth configuration](#9-google-oauth-configuration)
10. [Running the frontend](#10-running-the-frontend)
11. [Running the backend](#11-running-the-backend)
12. [Running with Docker](#12-running-with-docker)
13. [Running tests](#13-running-tests)
14. [Production build](#14-production-build)
15. [Deployment](#15-deployment)
16. [Integrations: AI, market data, storage, brokers](#16-integrations)
17. [Control panel (admin portal)](#control-panel-admin-portal)
17. [Security model](#17-security-model)
18. [API reference](#18-api-reference)
19. [Project layout](#19-project-layout)

---

## 1. What's in the box

| Area | Highlights |
| --- | --- |
| **Home Hub** | Market ticker (live via Twelve Data, otherwise clearly labelled **DEMO DATA**), six world clocks with session status, rotating discipline quotes, persistent 9-step checklist, execution desk with Quick Trade |
| **Quick Trade** | `buy gold 2862 sl 2858 3r 0.5 lot val bounce` → parsed preview chips → Enter logs it. Parsed in the browser for preview and **re-parsed and validated on the server** |
| **Dashboard** | Date presets (All/Today/Week/Month/Last Month/Custom), Net P&L, win rate, PF, avg win/loss, payoff, best/worst, equity, cumulative P&L and drawdown charts, weekday/session/instrument/strategy breakdowns |
| **Calendar** | Monthly P&L grid in the user's time zone, weekly summaries, day drill-down |
| **Trade Log** | Server-side pagination, search, sort and filters; view/edit/delete/share; CSV export `JournzeyAI_Trades_YYYY-MM-DD.csv` with formula-injection protection; screenshot upload (picker, drag-and-drop, paste) |
| **Strategy Analysis** | Nine default playbooks plus your own; trades, W/L, win rate, P&L, PF, avg R, rule compliance per strategy |
| **Edge Matrix** | Previous-month breakdown by strategy/session/instrument/hour/mistake, best trading window, disciplined vs emotional, Discipline Leak Mirror (+PNG export), Monte Carlo risk of ruin (0.25–5% slider, 5,000 paths), tilt rules |
| **Daily Notepad** | Typed or dictated (EN/RU/ZH/PT, Web Speech API) journal with compliance, emotional state, discipline rating, lessons, history; Performance Messenger weekly/monthly reviews (copy, print-to-PDF, optional AI narrative) |
| **AI Coach** | Claude-powered coach that sees **server-aggregated statistics only**; multilingual; disabled with an explicit message when `AI_API_KEY` is not set |
| **Tilt Circuit Breaker** | N losses within M minutes → TILT RISK DETECTED, cooldown timer, breathing exercise, remaining risk budget, optional **JOURNZEY TERMINAL LOCK**. Clearly states that the **broker** is not locked |
| **Post-Trade Runner Auditor** | Hypothetical 20% runner with a breakeven stop, evaluated on real historical candles from the market-data provider (never simulated prices) |
| **Flex cards** | Canvas-rendered share cards in four themes (Neon Cyan, Matrix Emerald, Cyber Amethyst, Gold Bullion), PNG download and clipboard copy; no account identifiers |
| **Broker Sync Hub** | Honest catalogue with status labels; working CSV statement import (MT4/MT5/cTrader/NinjaTrader), signed webhook ingestion, Binance Spot (beta) |
| **Accounts & capital** | Multiple trading accounts, real vs demo, deposits/withdrawals/adjustments, live current capital |
| **Demo mode** | 42 demo trades (Aug–Oct 2026, XAUUSD/US500/NAS100/EURUSD/BTCUSDT/USOIL) and 13 journal entries in a separate account, always labelled **DEMO DATA**, never mixed into real analytics; reset or remove at any time |
| **Data rights** | Export all data (JSON) and trades (CSV); delete account with typed-email confirmation |
| **UX** | Four themes (Clean Light, Dark Terminal, Cyberpunk Slate, Midnight Navy) saved per user; mobile-first shell with slide-out nav, sticky top bar, bottom quick tabs and a floating Log button; accessible modals, labelled forms, icons + text (never colour alone), tabular numerals |

## 2. Architecture

```
journzey-ai/
├── apps/
│   ├── web/                 React 18 + TypeScript + Vite + Tailwind + TanStack Query + React Router
│   ├── admin/               Control panel — separate React app served at /control-panel/
│   └── api/                 Node.js + TypeScript + Express REST API (/api/v1) + Drizzle ORM
│       └── src/
│           ├── routes/      HTTP layer (controllers) per domain
│           ├── services/    business logic (trades, analytics, AI, brokers, storage, …)
│           ├── middleware/  auth, CSRF, rate limiting
│           ├── validators/  request schemas (re-exports shared Zod schemas)
│           ├── auth/        Google OIDC client, session typing
│           └── db/          Drizzle schema, migrator, seed
├── packages/
│   ├── shared/              domain logic shared by API and web: instruments, decimal-safe trade math,
│   │                        quick-trade parser, analytics, sessions, Zod schemas, API types, demo dataset
│   └── config/              shared TypeScript configuration
├── database/
│   ├── schema.sql           full schema (generated from migrations)
│   ├── seed.sql             reference data + demo-data loader function (generated)
│   └── migrations/          Drizzle SQL migrations (source of truth)
├── docker/                  API + web Dockerfiles, nginx config
├── tests/e2e/               Playwright end-to-end tests
├── scripts/                 schema/seed SQL generators, ZIP packager
├── docker-compose.yml
├── .env.example
├── README.md
└── DEPLOYMENT.md
```

**Request flow:** React UI → TanStack Query → `fetch` (same-origin, HttpOnly session cookie, CSRF header) → Express middleware (request id, Helmet, CORS, rate limit, session, CSRF, auth) → route → service → Drizzle → PostgreSQL.

**Design decisions (documented deviations from the brief):**

- **ORM:** Drizzle ORM (a mature TypeScript PostgreSQL ORM) instead of Prisma. It has no native engine binaries, emits plain SQL migrations, and maps `NUMERIC` to strings so money never passes through floats.
- **`packages/types` merged into `packages/shared`:** API types live next to the Zod schemas that produce them so they cannot drift.
- **Sessions in PostgreSQL** (`user_sessions`) rather than Redis, so no extra service is needed. Redis is optional and unused by default (see §12).
- **Same-origin by default:** the browser talks to `/api` on the web origin (Vite proxy in dev, nginx or the API's static hosting in prod). This keeps cookies first-party with `SameSite=Lax`.
- **Local email/password auth is not enabled.** Google OIDC is the only sign-in method. `users.auth_provider` and the identity-provider abstraction (`apps/api/src/auth/google.ts → IdentityProvider`) let a password provider be added later. A **developer sign-in** exists only when `DEV_AUTH_BYPASS=true` and `NODE_ENV≠production` (the API refuses to boot otherwise). It is labelled "local only, not Google" in the UI.
- **P&L currency conversion:** P&L is computed from instrument metadata when the quote currency equals the account currency, or when the account currency is the pair's base (e.g. USDJPY on a USD account). Otherwise the API refuses to guess an FX rate and asks for the broker-reported P&L.
- **R multiples are signed** (losses are negative). Magnitude equals the brief's `abs(exit-entry)/abs(entry-stop)`.

## 3. Requirements

- **Node.js ≥ 20.11** (22 LTS recommended) and npm 10+
- **PostgreSQL 13+** (16 recommended), local or via Docker
- Optional: Docker 24+ with Compose v2.24+
- Optional: a Google Cloud project (for real sign-in), an Anthropic API key (AI Coach), a Twelve Data key (live prices), an S3-compatible bucket (production screenshots)

## 4. Installation

```bash
git clone <your-repo-url> journzey-ai
cd journzey-ai
npm ci                       # installs all workspaces from package-lock.json
cp .env.example .env         # then edit .env (see below)
```

Generate the two required secrets and paste them into `.env`:

```bash
node -e "console.log('SESSION_SECRET=' + require('crypto').randomBytes(48).toString('base64url'))"
node -e "console.log('ENCRYPTION_KEY=' + require('crypto').randomBytes(32).toString('base64'))"
```

## 5. Environment configuration

All variables are documented inline in [`.env.example`](.env.example). The API validates them at boot and exits with a list of problems if anything is wrong.

| Variable | Required | Purpose |
| --- | --- | --- |
| `DATABASE_URL` | ✅ | PostgreSQL connection string |
| `SESSION_SECRET` | ✅ | ≥ 32 chars; signs the session cookie |
| `ENCRYPTION_KEY` | ✅ | 32 random bytes, base64; AES-256-GCM key for broker credentials and webhook secrets |
| `APP_URL` | ✅ | Browser origin of the web app; the only allowed CORS/CSRF origin (plus `CORS_ORIGINS`) |
| `API_URL` | ✅ | Public API base incl. `/api` (used to build webhook URLs) |
| `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` / `GOOGLE_CALLBACK_URL` | for sign-in | Google OAuth (§9). Can instead be entered in the control panel |
| `ADMIN_BOOTSTRAP_EMAIL` / `ADMIN_BOOTSTRAP_PASSWORD` | first run | Creates the first control-panel owner if none exists |
| `ADMIN_IP_ALLOWLIST`, `ADMIN_SESSION_IDLE_MINUTES`, `ADMIN_SESSION_MAX_HOURS` | optional | Control-panel hardening |
| `ADMIN_DIST_DIR` | optional | Serve the built control panel from the API at `/control-panel/` |
| `DEV_AUTH_BYPASS` | dev only | Enables labelled developer sign-in. Forbidden in production |
| `AI_API_KEY`, `AI_MODEL` | optional | Anthropic API key; model defaults to `claude-opus-5-5` |
| `MARKET_DATA_API_KEY` | optional | Twelve Data key for live quotes and runner audits |
| `STORAGE_PROVIDER` + `STORAGE_*` | optional | `local` (default) or `s3` (AWS S3 / Cloudflare R2 / Supabase S3 API) |
| `RUN_MIGRATIONS_ON_START` | optional | Apply migrations + reference seed on boot (Docker) |
| `WEB_DIST_DIR` | optional | Serve the built web app from the API (single-container deployments) |
| `TRUST_PROXY` | optional | Express trust-proxy setting; defaults to `1` in production |
| `SENTRY_DSN` | optional | Error reporting. Logs are structured JSON on stdout for Logtail/Datadog/etc. |
| `REDIS_URL` | optional | Reserved; not used by the current build |

Never commit `.env`. `.gitignore` and `.dockerignore` already exclude it.

## 6. PostgreSQL setup

**With Docker** (simplest):

```bash
docker compose up -d postgres     # postgres://journzey:journzey@localhost:5432/journzey
```

**With a local PostgreSQL:**

```sql
CREATE ROLE journzey WITH LOGIN PASSWORD 'journzey';
CREATE DATABASE journzey OWNER journzey;
CREATE DATABASE journzey_test OWNER journzey;   -- used by the API integration tests
```

## 7. Migrations

Migrations live in `database/migrations/` and are applied with Drizzle's migrator, which records applied migrations in the `drizzle.__drizzle_migrations` table.

```bash
npm run db:migrate          # apply all pending migrations to DATABASE_URL
npm run db:generate         # after editing apps/api/src/db/schema.ts: generate a new SQL migration
npm run db:schema-sql       # regenerate database/schema.sql from the migrations
```

`database/schema.sql` is a reviewable, single-file copy of the schema (tables, PKs, FKs, unique constraints, check constraints, indexes, `NUMERIC` money columns, `TIMESTAMPTZ` timestamps). You can bootstrap an empty database with `psql "$DATABASE_URL" -f database/schema.sql`, but `npm run db:migrate` is preferred because it tracks state.

## 8. Seeding

```bash
npm run db:seed             # ORM seed: 9 strategy templates + 23 instruments (idempotent)
npm run db:seed-sql         # regenerate database/seed.sql from packages/shared
psql "$DATABASE_URL" -f database/seed.sql   # SQL alternative to db:seed (idempotent)
```

No users are seeded. Demo data is attached to **real signed-in users** only, either:

- during onboarding ("Demo Mode"), or Settings → Data & privacy → **Load / Reset Demo Data**; or
- from SQL: `SELECT journzey_load_demo_data('<existing user uuid>');` (defined in `seed.sql`).

Demo trades go into a separate account flagged `demo = true, sample_data = true`, and demo journal entries carry `demo = true`. The default `real` analytics scope never includes them.

## 9. Google OAuth configuration

journzey.ai uses the **authorization-code flow with PKCE**, plus `state` and `nonce`. The backend exchanges the code, verifies the ID token signature against Google's JWKS, checks issuer, audience, expiry and nonce, requires `email_verified`, then creates or finds the user by the stable Google subject id and rotates the session.

1. Open [Google Cloud Console](https://console.cloud.google.com/) and **create or select a project**.
2. **APIs & Services → OAuth consent screen:** choose *External* (or *Internal* for Workspace), set the app name, support email, and authorised domain (e.g. `journzey.ai`). Scopes: `openid`, `email`, `profile`. While the app is in *Testing*, add your Google accounts as test users.
3. **APIs & Services → Credentials → Create credentials → OAuth client ID → Web application.**
4. **Authorised JavaScript origins:** not required for this server-side flow. Adding your web origin is harmless:
   - Development: `http://localhost:3000`
   - Production: `https://journzey.ai`
5. **Authorised redirect URIs.** These must exactly match `GOOGLE_CALLBACK_URL`. The callback is an **API** route, reached through the same origin as the web app:
   - Development: `http://localhost:3000/api/v1/auth/google/callback`
   - Production: `https://journzey.ai/api/v1/auth/google/callback`
6. Paste the client ID and secret into **Control panel → Integrations & API keys → Google sign-in**, save, then click **Test connection**. This takes effect immediately with no restart. Alternatively, put them in `.env`:

   ```dotenv
   GOOGLE_CLIENT_ID=1234567890-abc.apps.googleusercontent.com
   GOOGLE_CLIENT_SECRET=GOCSPX-...
   GOOGLE_CALLBACK_URL=http://localhost:3000/api/v1/auth/google/callback
   ```

7. Restart the API. The login page's **Continue with Google** button now redirects to Google. After consent, Google calls the callback, the API creates the session and redirects to `/auth/callback`. The SPA then routes to `/onboarding` (first login) or `/app`.

If the variables are missing, the button is disabled and the login page explains which variables an administrator must set. Authentication is never simulated. The client secret is only ever read by the API and is never sent to the browser.

## 10. Running the frontend

```bash
npm run dev:web             # http://localhost:3000 (proxies /api → http://localhost:4000)
```

## 11. Running the backend

```bash
npm run db:migrate && npm run db:seed
npm run dev:api             # http://localhost:4000/api/v1/health (tsx watch)
```

Or run both together with `npm run dev`.

**Local sign-in without Google credentials:** set `DEV_AUTH_BYPASS=true` in `.env` (development only). The login page then shows a dashed "Developer sign-in — local only, not Google" form.

## 12. Running with Docker

```bash
cp .env.example .env        # set SESSION_SECRET and ENCRYPTION_KEY (+ Google vars if available)
docker compose up --build
```

| Service | URL |
| --- | --- |
| web (nginx + SPA, proxies `/api`) | http://localhost:3000 |
| api | http://localhost:4000/api/v1/health |
| postgres | localhost:5432 (journzey/journzey) |

The API container applies migrations and reference data on start (`RUN_MIGRATIONS_ON_START=true`). Screenshots persist in the `uploads` volume, the database in `pgdata`. Optional Redis: `docker compose --profile redis up` (not required).

## 13. Running tests

```bash
npm run typecheck           # strict TypeScript across all workspaces
npm run lint                # ESLint (typescript-eslint + react-hooks)
npm test                    # shared unit tests + API integration tests + web component tests
```

API integration tests (Vitest + Supertest) use a real PostgreSQL database. By default that is `postgres://journzey:journzey@localhost:5432/journzey_test`; override it with `TEST_DATABASE_URL`. The suite **drops and rebuilds** that database's `public` schema from migrations, so never point it at real data. Coverage includes:

- authentication: unauthenticated rejection, the real OAuth callback route with a fake IdP (state, nonce, unverified email), cookie flags, logout destroying the server session, CSRF, dev-login gating
- **multi-user isolation**: user B cannot list, read, edit, delete, export, scope analytics to, or log into user A's trades, journals, strategies, accounts or screenshots
- trades: create/edit/delete, server-side calculations, quick-trade parsing, validation errors, filtering/sorting/pagination, CSV escaping, screenshot content validation
- analytics: win rate, profit factor, payoff, equity, drawdown, discipline leak, calendar, Monte Carlo, demo isolation, AI context aggregation
- brokers: encrypted secrets, webhook signature/timestamp/replay/idempotency, CSV import + duplicate skipping, Binance FIFO pairing
- account lifecycle: settings, journal upsert, checklist, capital flows, terminal lock, export, deletion

**End-to-end (Playwright):** start the stack with `DEV_AUTH_BYPASS=true` (`npm run dev`, or Docker with that flag), then:

```bash
npx playwright install chromium      # once
npm run test:e2e
```

The e2e suite signs in, completes onboarding with demo data, logs a quick trade, edits it, checks the dashboard, Edge Matrix, calendar, journal, AI-not-configured state, theme persistence and broker hub, deletes the trade, signs out, and runs a mobile-viewport flow. It fails on any browser console error. Set `PLAYWRIGHT_CHROMIUM_EXECUTABLE` to use a preinstalled Chromium.

## 14. Production build

```bash
npm run build               # shared → api (tsc → apps/api/dist) → web (vite → apps/web/dist)
NODE_ENV=production node apps/api/dist/server.js
```

With `WEB_DIST_DIR=apps/web/dist` the API also serves the SPA (with SPA fallback and immutable asset caching), so one process serves the whole product. Routes are lazy-loaded and split into chunks (React, query, charts).

## 15. Deployment

See **[DEPLOYMENT.md](DEPLOYMENT.md)** for the recommended architecture (single container on Railway/Render/Fly + Neon PostgreSQL + Cloudflare R2), DNS/HTTPS, environment variables, the Google callback URL, CORS, migrations and health checks.

## 16. Integrations

| Integration | Status in this build | Notes |
| --- | --- | --- |
| Google OAuth / OIDC | **Implemented** | Requires your Google credentials. Tested end-to-end through the real callback route with a fake identity provider. Not exercised against live Google from CI |
| AI Coach (Anthropic Claude) | **Implemented** | `@anthropic-ai/sdk`, model `claude-opus-5-5` (configurable), adaptive thinking, server-side refusal fallback. Sends aggregated statistics only. Disabled with "AI Coach requires server configuration." when `AI_API_KEY` is empty. Verified with a fake client, not against the live API |
| Market data (Twelve Data) | **Implemented, configurable** | `/quote` for the ticker (60 s cache) and `/time_series` for runner audits. Symbol mappings are in `apps/api/src/services/marketData.ts`; coverage depends on your plan, and unavailable symbols show "n/a". Without a key the ticker shows static levels clearly labelled **DEMO DATA** |
| Screenshot storage | **Implemented** | `local` (filesystem, served through an authenticated route) or `s3` (private bucket, 5-minute signed URLs). Files are validated by MIME type, extension, size (≤ 5 MB) and magic bytes |
| CSV statement import | **Available** | MT4/MT5, cTrader, NinjaTrader exports; auto column detection; broker server-time offset; duplicates skipped by ticket |
| Signed webhook | **Available** | See below |
| Binance Spot | **Beta** | Read-only API key verified on connect and encrypted at rest; FIFO pairs spot fills into round trips. Not tested against live Binance |
| Vantage, Exness, FTMO, FundedNext, NinjaTrader | Available **via CSV/webhook** | No public journal API; the hub routes them to statement import |
| cTrader Open API, Tradovate, Topstep, Apex | **Coming soon** | Not implemented; shown as such |
| Broker execution lock | **Unsupported** | journzey.ai cannot block broker orders |

### Broker webhooks

Create a *Signed Webhook* connection in Settings → Broker sync. The secret (`whsec_…`) is shown **once**. POST closed trades to `https://<host>/api/v1/webhooks/broker/<connectionId>`:

```
Content-Type: application/json
X-Journzey-Timestamp: <unix seconds>
X-Journzey-Signature: sha256=<hex HMAC-SHA256(secret, "<timestamp>.<raw body>")>
X-Journzey-Event-Id: <unique id per delivery>        (optional; defaults to brokerTradeId)

{"brokerTradeId":"mt5-1001","symbol":"XAUUSD","side":"LONG","executedAt":"2026-09-20T09:00:00Z",
 "closedAt":"2026-09-20T10:00:00Z","entryPrice":"2862","exitPrice":"2870","stopLoss":"2858","lotSize":"0.5"}
```

Deliveries older or newer than 5 minutes are rejected. Event ids and broker trade ids are deduplicated, so retries are safe (`200 {"status":"duplicate"}`).

```bash
BODY='{"brokerTradeId":"t-1","symbol":"EURUSD","side":"SHORT","executedAt":"2026-09-20T09:00:00Z","entryPrice":"1.165","exitPrice":"1.161","stopLoss":"1.167","lotSize":"1"}'
TS=$(date +%s); SIG=$(printf '%s.%s' "$TS" "$BODY" | openssl dgst -sha256 -hmac "$WHSEC" -hex | sed 's/.* //')
curl -X POST "$URL" -H 'content-type: application/json' -H "x-journzey-timestamp: $TS" -H "x-journzey-signature: sha256=$SIG" -d "$BODY"
```

## Control panel (admin portal)

A separate, private administration app lives at **`/control-panel/`**. It is its own bundle (`apps/admin`), uses a light SaaS-style design that is distinct from the trading terminal, and is never reachable through the public routes. URLs are clean: `/control-panel/login`, `/control-panel/` (dashboard), `/control-panel/users`, `/control-panel/integrations`, `/control-panel/website`, `/control-panel/admins`, `/control-panel/audit`, `/control-panel/system`, `/control-panel/account`.

| Section | What it controls |
| --- | --- |
| **Dashboard** | Traders, active users, live sessions, trades (excluding demo data), AI usage, 30-day sign-up and trade charts, integration status, recent admin activity |
| **Integrations & API keys** | Google OAuth client ID, client secret and redirect URI; Anthropic API key and model; Twelve Data key. Each has an enable switch and a **Test connection** button. Saved values take effect on the live site immediately |
| **Website controls** | Maintenance mode (trader API returns 503 and the site shows a maintenance page), new sign-ups open/closed, announcement banner (text and style), branding (name, tagline, support email), feature switches (AI Coach, Broker Sync, Demo data, Market ticker), all enforced server-side |
| **Users** | Search and filter traders, view usage and security events, force sign-out, suspend/restore (revokes sessions; blocks sign-in), delete with typed confirmation (owner only) |
| **Administrators** | Owner-only: add admins, set roles (`owner`, `admin`, `viewer`), disable, reset passwords, remove. At least one active owner is always kept |
| **Audit log** | Every control-panel action, plus trader security events. Secret values are never recorded |
| **System health** | Environment, uptime, memory, DB latency, migrations applied, storage, Sentry, IP allow-list, dev-bypass warning |
| **Account & security** | Change password; set up TOTP two-factor authentication (QR code) |

**Creating the first administrator** (choose one):

```bash
npm run admin:create -- --email you@example.com --name "Your Name"     # prompts for a password (hidden)
ADMIN_PASSWORD='…' npm run admin:create -- --email you@example.com --name "Your Name" --role owner
npm run admin:reset-password -- --email you@example.com
```

Or set `ADMIN_BOOTSTRAP_EMAIL` and `ADMIN_BOOTSTRAP_PASSWORD` once. If no administrator exists at boot, an owner is created; remove the password variable afterwards.

**How settings are stored:** in the `app_settings` table. Secrets (Google client secret, API keys) are encrypted with AES-256-GCM using `ENCRYPTION_KEY` and are write-only: the panel shows only "configured · …last4". Precedence is **control panel → environment variable → default**, and every API instance re-reads settings within 30 seconds (immediately on the instance that saved them). Reverting a field removes the panel value so the environment variable applies again.

**Control-panel security:**
- Completely separate from trader accounts: own `admin_users` table, own session cookie `jz.cp` (`HttpOnly`, `Secure` in production, `SameSite=Strict`, `Path=/api/v1/admin`), 30-minute idle timeout and 12-hour absolute limit (configurable). A trader session never grants admin access.
- Argon2id password hashing; policy of at least 12 characters with mixed case and digits, not containing the email name. 5 failed attempts lock the account for 15 minutes, plus 10 attempts per 15 minutes per IP. Timing is equalised for unknown emails.
- Optional TOTP two-factor authentication (RFC 6238; any authenticator app). Changing or resetting a password signs out the admin's other sessions.
- Own CSRF token, `Cache-Control: no-store`, `X-Robots-Tag: noindex`, and optional `ADMIN_IP_ALLOWLIST` (other IPs get a 404, so the panel's existence is not revealed).
- Roles: `viewer` (read-only), `admin` (settings, keys, users), `owner` (also administrators and permanent user deletion).

In development, `npm run dev` starts the panel on :3001 and the website proxies `/control-panel` to it, so use `http://localhost:3000/control-panel/`. In Docker and single-container deployments it is served from the same origin (`ADMIN_DIST_DIR` is preset in the image, and nginx serves it in the compose stack).

## 17. Security model

- **Sessions:** server-side in PostgreSQL; cookie `jz.sid` is `HttpOnly`, `SameSite=Lax`, `Secure` in production, and rolling. The session id is regenerated at login. Logout and account deletion destroy the session.
- **Authorization:** every protected request loads the user from the session. Every user-owned query is filtered by `user_id` and cross-resource references (account, strategy) are ownership-checked. Strict Zod schemas reject unknown fields such as a client-supplied `userId`.
- **CSRF:** per-session synchronizer token (`X-CSRF-Token`) plus an Origin/Referer allow-list. Webhooks are exempt and authenticate by HMAC.
- **Headers:** Helmet with CSP, HSTS (production), `frame-ancestors 'none'`, no `X-Powered-By`; strict CORS allow-list.
- **Input:** Zod validation on every endpoint with standardised `{"error":{"code","message","fields"}}` errors; no stack traces in production; parameterised SQL via Drizzle.
- **Secrets:** broker credentials and webhook secrets are AES-256-GCM encrypted and never returned. AI and market-data keys stay server-side. Logs redact cookies, auth headers, tokens, secrets and signatures.
- **Rate limits:** API (600/min), auth (30/15 min), AI (15/min), uploads (40/10 min), webhooks (120/min).
- **Uploads:** content sniffing (magic bytes), type/extension/size checks, `nosniff`, private storage.
- **CSV:** RFC 4180 escaping plus spreadsheet formula-injection neutralisation.
- **Audit log:** login/logout/failed login, account and settings changes, broker connect/disconnect/rotate/sync/import, exports, demo resets, deletions, terminal lock.
- **Data deletion policy:** deleting an account hard-deletes the user row. `ON DELETE CASCADE` removes settings, accounts, trades, journals, strategies, checklist, capital flows, broker connections (with encrypted credentials), webhook events, AI history and snapshots. Stored screenshots are deleted from object storage. Audit rows are kept with `user_id = NULL`.

## 18. API reference

Base path `/api/v1`. All endpoints except `health`, `ready`, `auth/*` and `webhooks/*` require a session; unsafe methods require `X-CSRF-Token`.

```
GET    /health  /ready
GET    /auth/csrf   /auth/providers   /auth/google   /auth/google/callback   /auth/me
POST   /auth/logout   /auth/dev-login (dev only)
POST   /onboarding
GET    /trades?page&pageSize&sort&order&search&account&strategyId&session&symbol&side&outcome&status&from&to
POST   /trades   /trades/quick
GET    /trades/export.csv
GET|PATCH|DELETE /trades/:id
POST|GET|DELETE  /trades/:id/screenshot
POST   /trades/:id/runner-audit
GET    /dashboard?account&from&to      GET /calendar?month=YYYY-MM&account
GET    /analytics/strategies | sessions | discipline | edge-matrix | risk-of-ruin | review
GET|POST /journal     PATCH|DELETE /journal/:id
GET|PUT  /checklist
GET|POST /strategies  PATCH|DELETE /strategies/:id
GET|POST /accounts    PATCH|DELETE /accounts/:id
GET|POST /accounts/:id/transactions   DELETE /accounts/:id/transactions/:txId
GET|PATCH /settings   POST /settings/terminal-lock
PATCH  /account/profile   GET /account/export   DELETE /account
POST   /demo/reset   DELETE /demo
GET    /market/quotes
GET    /ai/status   GET /ai/conversations   GET /ai/conversations/:id/messages   DELETE /ai/conversations/:id
POST   /ai/chat   /ai/monthly-review
GET    /broker-connections/providers   GET|POST /broker-connections   DELETE /broker-connections/:id
POST   /broker-connections/:id/sync | import | rotate-secret
POST   /webhooks/broker/:connectionId   (HMAC-signed, no session)
```

`account` scope values: `real` (default; non-demo accounts), `demo`, `all`, or a trading-account UUID.

## 19. Project layout

See §2. Root scripts: `dev`, `dev:api`, `dev:web`, `dev:admin`, `admin:create`, `admin:reset-password`, `build`, `typecheck`, `lint`, `test`, `test:e2e`, `db:migrate`, `db:seed`, `db:generate`, `db:schema-sql`, `db:seed-sql`, `package:zip`.

Internationalisation: UI strings live in `apps/web/src/locales/{en,ru,zh,pt}.ts`. English is complete. Russian, Chinese and Portuguese cover navigation and core labels and fall back to English per key. The AI Coach and voice dictation support all four languages.
