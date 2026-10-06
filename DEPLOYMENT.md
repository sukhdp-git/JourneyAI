# Deploying journzey.ai

This guide describes one complete production architecture, plus alternatives. Every option keeps the browser and the API on **one origin**, so the session cookie stays first-party (`SameSite=Lax`, `Secure`, `HttpOnly`) and no cross-site cookie configuration is needed.

## Recommended architecture

```
                 ┌──────────────── journzey.ai (HTTPS, managed TLS) ─────────────────┐
 Browser ───────▶│  Railway / Render / Fly.io service — docker/api.Dockerfile        │
                 │  Node API  (/api/v1/*)  +  built SPA (WEB_DIST_DIR, SPA fallback) │
                 └───────────────┬───────────────────────────────┬───────────────────┘
                                 │ TLS                            │ S3 API (signed URLs)
                      Neon PostgreSQL (pooled)            Cloudflare R2 private bucket
```

- **App:** a single container built from `docker/api.Dockerfile`. It serves the API and the static web build.
- **Database:** Neon PostgreSQL. Supabase Postgres, Railway PostgreSQL or AWS RDS work the same way.
- **Object storage:** Cloudflare R2 (or AWS S3 / Supabase Storage via its S3 API).
- **Optional:** Sentry for errors, and a log drain (Logtail, Datadog) reading the JSON logs on stdout.

### 1. Database (Neon)

1. Create a Neon project and database, e.g. `journzey`.
2. Copy the connection string and use the **pooled** host for the app: `postgres://user:pass@ep-xxx-pooler.region.aws.neon.tech/journzey?sslmode=require`.
3. Set `DATABASE_SSL=true`.

### 2. Object storage (Cloudflare R2)

1. Create a **private** bucket, e.g. `journzey-screenshots`. Do not enable public access.
2. Create an R2 API token with *Object Read & Write* on that bucket.
3. Configure:
   ```dotenv
   STORAGE_PROVIDER=s3
   STORAGE_BUCKET=journzey-screenshots
   STORAGE_REGION=auto
   STORAGE_ENDPOINT=https://<account-id>.r2.cloudflarestorage.com
   STORAGE_ACCESS_KEY=<r2 access key id>
   STORAGE_SECRET_KEY=<r2 secret>
   ```
   Screenshots are uploaded through the API and read through short-lived (5-minute) signed URLs after an ownership check.

### 3. Application service (Railway example)

1. New project → **Deploy from GitHub repo**. Set the builder to *Dockerfile* with path `docker/api.Dockerfile`.
2. Variables (all from `.env.example`):
   ```dotenv
   NODE_ENV=production
   PORT=4000
   DATABASE_URL=<neon pooled url>
   DATABASE_SSL=true
   RUN_MIGRATIONS_ON_START=true          # or run migrations as a release command (below)
   APP_URL=https://journzey.ai
   API_URL=https://journzey.ai/api
   WEB_DIST_DIR=/app/apps/web/dist
   TRUST_PROXY=1
   SESSION_SECRET=<48+ random bytes>
   ENCRYPTION_KEY=<32 random bytes, base64>   # back this up — losing it makes stored broker credentials unreadable
   GOOGLE_CLIENT_ID=...
   GOOGLE_CLIENT_SECRET=...
   GOOGLE_CALLBACK_URL=https://journzey.ai/api/v1/auth/google/callback
   AI_API_KEY=...                         # optional
   MARKET_DATA_API_KEY=...                # optional
   STORAGE_*=...                          # from step 2
   SENTRY_DSN=...                         # optional
   ```
   **Never** set `DEV_AUTH_BYPASS` in production; the API refuses to start if it is `true` with `NODE_ENV=production`.
3. Health check path: `/api/v1/health` (liveness). `/api/v1/ready` also checks the database (readiness).
4. Expose port `4000` and attach the custom domain (step 5).

Render: *New Web Service → Docker*, same Dockerfile, variables and health check. Fly.io: `fly launch --dockerfile docker/api.Dockerfile`, set secrets with `fly secrets set`, and use `[http_service] internal_port = 4000` with a check on `/api/v1/health`.

### 4. Migrations

The image includes `database/migrations` (`MIGRATIONS_DIR=/app/database/migrations`).

- **Simple:** `RUN_MIGRATIONS_ON_START=true` applies pending migrations and idempotent reference data at boot. Drizzle's migrator is transactional.
- **Release step** (preferred with several replicas): set `RUN_MIGRATIONS_ON_START=false` and run once per deploy:
  ```bash
  npm ci && npm run db:migrate && npm run db:seed        # from a CI job with DATABASE_URL set
  ```
  or `psql "$DATABASE_URL" -f database/schema.sql -f database/seed.sql` for a brand-new database.

### 5. DNS and HTTPS

1. In your DNS provider, add the record your host shows: a `CNAME journzey.ai → <app>.up.railway.app` (use an ALIAS/ANAME or flattened CNAME at the apex), plus `www` if desired.
2. The platform issues and renews the TLS certificate automatically.
3. HTTPS is mandatory in production. Cookies are `Secure`, HSTS is sent, and CSP upgrades insecure requests.

### 6. Google OAuth for production

In Google Cloud Console → Credentials → your Web client:

- Authorised redirect URI: `https://journzey.ai/api/v1/auth/google/callback` (must equal `GOOGLE_CALLBACK_URL` exactly)
- Authorised JavaScript origin (optional): `https://journzey.ai`
- OAuth consent screen: add the production domain, privacy policy URL `https://journzey.ai/privacy` and terms URL `https://journzey.ai/terms`, then **publish** the app (leave *Testing*).

### 7. CORS

The SPA and API share one origin, so CORS is effectively closed: only `APP_URL` (plus any `CORS_ORIGINS`) may make credentialed requests, and CSRF checks enforce the same allow-list. Add origins to `CORS_ORIGINS` only if you serve the SPA from another origin.

### 8. Control panel

1. Create the first owner, either as a one-off command (`npm run admin:create -- --email you@example.com --name "You"` with `DATABASE_URL` and `ENCRYPTION_KEY` set), or by setting `ADMIN_BOOTSTRAP_EMAIL`/`ADMIN_BOOTSTRAP_PASSWORD` for the first boot and removing the password afterwards.
2. Sign in at `https://journzey.ai/control-panel/login` and enable two-factor authentication under *Account & security*.
3. Enter the Google, Anthropic and Twelve Data keys under *Integrations & API keys* and use **Test connection**. Environment variables remain as a fallback.
4. Recommended: restrict the panel with `ADMIN_IP_ALLOWLIST` (your office/VPN egress IPs), or with your platform's IP rules or Cloudflare Access on `/control-panel*` and `/api/v1/admin*`.
5. **Back up `ENCRYPTION_KEY`.** Keys saved in the panel are encrypted with it.

### 9. Verify the deployment

```bash
curl -s https://journzey.ai/api/v1/health      # {"status":"ok",...}
curl -s https://journzey.ai/api/v1/ready       # {"status":"ready"}
curl -sI https://journzey.ai/ | grep -i -E 'strict-transport|content-security'
```

Then sign in with Google, complete onboarding, log a quick trade and check the dashboard.

## Alternative: Vercel (frontend) + Railway (API)

To host the SPA on Vercel, keep it same-origin by **proxying** `/api` through Vercel:

```json
// apps/web/vercel.json
{
  "rewrites": [
    { "source": "/api/:path*", "destination": "https://api.journzey.ai/api/:path*" },
    { "source": "/(.*)", "destination": "/index.html" }
  ]
}
```

- Vercel project root `apps/web`, build command `cd ../.. && npm ci && npm run build:shared && npm run build -w @journzey/web`, output `apps/web/dist`.
- API on Railway without `WEB_DIST_DIR`. Use `APP_URL=https://journzey.ai`, `API_URL=https://journzey.ai/api` and `GOOGLE_CALLBACK_URL=https://journzey.ai/api/v1/auth/google/callback` (still via the proxied origin).

## Alternative: Docker Compose on a VM (DigitalOcean, AWS EC2)

`docker compose up -d --build` runs postgres + api + nginx web. Put a TLS-terminating proxy (Caddy or Traefik) in front of port 3000, set `NODE_ENV=production`, the production URLs above, and strong secrets in `.env`, and remove the published Postgres port.

## Scaling notes

- **Stateless API:** sessions live in PostgreSQL, so you can run several replicas behind a load balancer.
- **Rate limiting** is in-memory per replica. With multiple replicas, add a shared limiter at the edge (Cloudflare, a load-balancer WAF) or move to a Redis store.
- **Screenshots:** use `STORAGE_PROVIDER=s3` whenever there is more than one replica or the filesystem is ephemeral.
- **Backups:** enable point-in-time recovery on the database and back up `ENCRYPTION_KEY` and `SESSION_SECRET` in your secret manager.
- **Observability:** JSON logs carry `requestId` (also returned as `X-Request-Id`) and `userId`. Secrets, cookies, tokens and signatures are redacted. Set `SENTRY_DSN` for exception reporting.
