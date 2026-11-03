# journzey.ai — project handover (for the next Claude / developer)

Read this first, then `CLAUDE.md` (Instagram rules) and `README.md` (install, admin, features, compliance).

## What this is
journzey.ai is an AI-era **trading journal SaaS** for forex, gold, index and crypto traders (India + international).
Plain **PHP 8.2 + MySQL/MariaDB**, Apache with `.htaccess`, built for **cPanel shared hosting**. No Composer or npm
packages on the server (the only vendor file is `public/assets/js/vendor/qrcode.js`).

- Git repo: `sukhdp-git/JourneyAI` (GitHub), working branch **`claude/journzey-production-build-0w5zx0`**. No PR opened.
- Deliverables each round: `git archive --format=zip -o journzey-ai-updated.zip HEAD`, plus `database-updated.sql` when the schema changes.
- Database schema version: **16** (`app/core/Migrator::VERSION`). `database-updated.sql` is applied automatically on the
  first request after an upload, and is idempotent. `database.sql` is a fresh install (same blocks appended).

## Owner's standing rules (keep following them)
- Never put real secrets in the repo or ZIP (`config/config.php` is git-ignored; API keys live encrypted in the `settings` table via `secret_setting()`).
- Never store plaintext passwords or expose hashes. No SMTP credentials in frontend JS.
- Don't make real payments, touch production customer records, or deploy automatically.
- Don't invent business claims, statistics or testimonials. Label demo data as "sample data / demo".
- Don't change correct trading formulas (P&L, R, risk, contract specs, rounding).
- Prefer no DB changes; when needed, a minimal non-destructive migration with rollback notes in the SQL comments.
- No model names in commits. Commit trailer used so far: `Co-Authored-By: Claude …` + session link (replace with your own).
- Instagram/social images: follow `docs/brand/instagram/STYLE.md` ("Edge Notes" light theme, dark "Trader Notes" only when asked). Pre-launch CTA: "journzey is coming soon · Follow for launch day 🔔". No "free"/"link in bio" yet.

## Code map
| Area | Where |
|---|---|
| Front controller, routes | `index.php`, `routes/web.php` (public), `routes/app.php` (member terminal), `routes/admin.php` (Control Panel at `/control-panel`) |
| Core framework | `app/core/` (Router, Request, Response, Database, Settings, Session, Auth (admins), Csrf, RateLimiter, Crypto, Totp, Mailer, Migrator, View) |
| Trading domain | `app/trading/` (Ledger, TradeMath, Analytics, Edge, Mind, Confessions, Course, DemoData, Backtest, BacktestData, Payments, Subscriptions, Sessions, MarketData, Members …) |
| Controllers | `app/controllers/` (public), `app/controllers/terminal/` (all extend `TerminalController`: `$this->m`, `$this->uid`, `$this->acc`, `render()`, `back()`; route params via `$req->params['id']`), `app/controllers/admin/` |
| Views | `app/views/public`, `app/views/terminal`, `app/views/admin` |
| CSS / JS | `public/assets/css/site.css` (public), `terminal.css`, `public/assets/js/*.js` (`terminal.js` has `tmPost`/`tmToast`) |
| Tests (CLI only) | `php tests/calculations.php`, `tests/demo.php`, `tests/confessions.php`, `tests/backtest.php`, `tests/security.php` — all pass |
| Brand / marketing | `docs/brand/` (Instagram builders + PNGs, reel scripts + PDFs, reels, site B-roll) |

## Features (all built)
Public site (CMS homepage sections, pricing, learn, blog, Wall of L's, legal pages, cookie consent, Grievance Officer page) ·
Auth (email + Google, 18+ consent record, onboarding with demo journal) · Terminal: Home Hub (Quick Log, live **session globe**),
dashboard, calendar, trade log (voice mic on every field, screenshot reading, CSV import), risk calculator, strategies, Edge Matrix,
Mind tab (roast, Death Clock, Ghost, Boss fight), notepad, **Confessions** (anonymous feed, $15-per-like, spam shield; open to all
members for now — switch in Control Panel → Confessions), University (13 lessons, quizzes, verifiable certificate), Markets
(TradingView widgets with attribution), accounts (prop-firm rule presets, risk limits, tilt breaker), billing (Razorpay + Stripe,
autopay + cancel), affiliates (25% recurring), data export + account deletion, global candle backdrop.
Control Panel: members, payments, plans, content, settings screens (incl. **Legal & compliance**, **Backtester**), admin **2-step sign-in (TOTP)**.

## Unfinished / decided-but-not-built
1. **Backtester** (`/terminal/backtest`): engine, Twelve Data + CSV price history, rule builder page and tests are done; it is
   **hidden** until Control Panel → Backtester is switched on. Still to do: browser test, screenshots for the owner, polish.
2. **MT5 sync** (proposed, not started): Phase A = MT5 Expert Advisor that posts closed deals to a signed endpoint (₹0 running cost,
   multi-account via one terminal per account, dedupe by account+server+ticket, backfill, UTC conversion, symbol suffix mapping,
   heartbeat). Phase B = cloud sync (e.g. MetaApi, per-account fee). Owner was undecided; needs a Windows PC + 2–3 MT5 demo accounts to test.
3. **30-Day Prop-Ready Challenge** (growth idea, not started): Wordle-style daily discipline grid to share on WhatsApp/Telegram,
   team leaderboards for trading groups, reward finishing (certificate + 1 month Pro + prop-firm discount code), not ranking.
4. **Marketing plan** given in chat: launch open beta (remove "coming soon"), prop-firm wedge positioning, founding-member pricing
   (India ₹299 locked / Pro ₹499; international $9 locked / Pro $19), founder DMs, communities, free TradingView indicator, free public calculators.
5. Owner to-dos: fill Control Panel → Legal & compliance; enable admin 2FA; rename/rewrite the "AI Discipline Coach" service page
   (AI Coach was removed); check the pricing note about "one-time payments" vs autopay; lawyer/CA review; VAPT before launch.

## Local test setup used so far (not needed on cPanel)
Apache on `localhost:8080`, MariaDB database `journzey`, Playwright + Chromium for screenshots/e2e. Local test logins existed only in
that sandbox database; create your own with the signup page or `/setup` on a fresh install.
