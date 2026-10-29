# journzey.ai — Website & Control Panel

**journzey.ai** is an AI trading-journal SaaS: traders sign up with **Google or email**, journal trades in a full trading terminal, analyse their performance and get AI coaching. Demo accounts are free; **live accounts require a paid plan**. A separate Control Panel manages the website, members, plans, payments and integrations.

- **Stack:** plain PHP 8.1+ (8.2+ recommended), MySQL/MariaDB with PDO, vanilla JavaScript and Apache `.htaccess`.
- **Hosting:** built for cPanel shared hosting (e.g. Namecheap). No Node, npm, Composer, Docker or SSH is needed.
- **Members:** sign-up/sign-in at **`/signup`** and **`/login`** (email + password or Google OAuth), then the terminal at **`/terminal`**.
- **Admin:** a separate Control Panel at **`/control-panel/`** manages the website, members, sign-ins, plans, payments and API keys.

---

## Contents
1. [Project overview](#1-project-overview)
2. [Requirements](#2-requirements)
3. [Folder structure](#3-folder-structure)
4. [Installation on cPanel](#4-installation-on-cpanel)
   - upload, database creation, SQL import, configuration, installer
5. [Admin setup & Admin URL](#5-admin-setup--admin-url)
6. [SMTP setup & test email](#6-smtp-setup--test-email)
7. [Managing the website](#7-managing-the-website)
   - logo, homepage, navigation, services, blog, pages, members, plans & payments, media, SEO, analytics, WhatsApp
   - [Google sign-in](#google-sign-in-setup), [payments](#payments-setup-razorpay-or-stripe), [AI Coach & market data](#ai-coach--market-data-setup)
8. [Admin users & roles](#8-admin-users--roles)
9. [Backups](#9-backups)
10. [Security](#10-security)
11. [Troubleshooting](#11-troubleshooting)
    - general, 404, `.htaccess`, SMTP
12. [Content rules & placeholders](#12-content-rules--placeholders)
13. [Development notes](#13-development-notes)

---

## 1. Project overview

### Public website
All URLs are clean and never end in `.php`.

| URL | Purpose |
|---|---|
| `/` | Homepage. Its 12 sections are CMS-managed, reorderable and can be switched on or off. Right after the hero, a **scroll story** (fixed, not CMS-managed: `app/views/public/sections/pnl-story.php`) pins a P&L card that moves from red to breakeven to profit as the visitor scrolls (labelled as an illustration, not a promise of results), followed by six feature cards that tilt in 3D under the cursor. The same tilt is used on service and pricing cards. Both are off for touch screens and reduced motion. |
| `/wall` | **Wall of L's**: real losing trades with the lesson behind each one, posted anonymously by members who ticked a consent box and approved by the team first. Visitors can react (💀 🫠 🤝, once per post per visitor). |
| `/certificate/{code}` | Public check behind the QR code on a University certificate: name, course and issue date (no other member data). |
| `/about` | About page: hero plus content blocks. |
| `/services`, `/services/{slug}` | Platform capabilities, each with benefits, process, FAQ and SEO. |
| `/learn`, `/learn/{slug}` | Learning section: **3 free strategies** from the intraday playbook (logic, setup rules, entry, stop, target), a summary matrix and a glossary. The other strategies are shown as locked cards with a “Sign up free to unlock” prompt; members open them in the terminal **University**. Choose which strategies are free in **Content → Learning playbook** (“Free on the public Learn page”). |
| `/affiliates` | Affiliate programme: how it works and the application form (applicants need a free member account). |
| `/r/{CODE}` | An affiliate's personal link: counts the click, remembers the code for 60 days and opens `/pricing`; the code is pre-filled at checkout. |
| `/blog`, `/blog/{slug}` | Blog with featured post, search, pagination, related posts and sharing. |
| `/blog/category/{slug}`, `/blog/tag/{slug}`, `/blog/page/2` | Blog archives and pagination. |
| `/contact` | Contact details, map and contact form (saved to MySQL). |
| `/pricing` | Plans (free demo + paid plans managed in the Control Panel) with links to checkout. |
| `/signup`, `/login`, `/forgot-password` | Member accounts: email + password or **Continue with Google**. |
| `/onboarding` | First-run setup: markets, timezone, risk rules and one demo account (the demo journal with sample data, or an empty demo account). Broker and prop-firm accounts are added later from **＋ Add account**. |
| `/checkout/{plan}` | Plan checkout through Razorpay or Stripe (signed-in members). |
| `/terminal/…` | The trading terminal (members only, see below). |
| `/privacy-policy`, `/terms-and-conditions`, `/disclaimer`, `/security` | Legal pages (CMS pages). |
| `/{any-page-slug}` | Any page created in the Control Panel, e.g. `/refund-policy`. |
| `/sitemap.xml`, `/robots.txt` | Generated automatically. |

### Trading terminal (`/terminal`, members only)

| Page | What it does |
|---|---|
| **Home Hub** | “Hi [name],” greeting, trading-psychology quotes, a clock in the member's chosen timezone with the London / New York / Tokyo session indicator (daylight saving handled), daily & weekly risk-limit status, 9-step checklist, and **Quick Log** on the execution desk (the same Quick Log opens from the ⚡ button / `/` hotkey on every terminal page): **1** pick an instrument from your own favourites (*Edit list* — up to 8, defaults to the instruments you trade most), **2** Buy or Sell, **3** lot size (remembered per instrument), **4** tap Entry / Exit / Stop loss to open a **price wheel** — scroll with a finger, drag or scroll with the mouse, or use the arrow keys; it is centred on the reference price with smaller prices above and larger below (e.g. 4110 · **4111** · 4112), and ÷10 / ×10 change the step. Date, time and session are taken automatically from the moment you log; the result card shows risk, P&L and R. A typed one-line command is still available under *Prefer typing? Use a one-line command*. Voice logging was removed from Quick Log and the Log Trade form. | |
| **Dashboard** | Date presets, four headline numbers plus a compact stats strip, account-equity curve that includes deposits/withdrawals (shown as green/red dots), cumulative P&L and drawdown charts, breakdowns by weekday, session, instrument and strategy. Every chart **draws itself in** from left to right (about 1.8 s) each time the page opens — lines and areas with a live “pen” dot, bars growing from zero; charts further down start when scrolled into view. Members who turn on *reduce motion* in their device settings see static charts. |
| **Calendar** | Highlighted monthly P&L box (green/red days, best/worst day), daily P&L calendar with weekly summaries, day drill-down and the daily **flex card**. |
| **Trade Log** | Compact table (cards on mobile) and a **Screenshot journal** gallery. **Voice on every field:** a small microphone next to each field of the Log Trade form (asset, direction, date, time, entry, exit, stop loss, lot size, take profit, session, strategy, setup, emotion, mistake, notes) fills just that field: say "twenty eight thirty", "half a lot", "London" or "revenge" (6 languages, picked in the form header); notes dictation keeps adding sentences. The Trade Log search box and the instrument / strategy / session filters have microphones too. Voice needs Chrome, Edge or Safari. **Log New Trade** modal: glowing LONG/SHORT badge; asset chips, date + time defaulting to now; live risk / R:R / net P&L card with the Gold formula badge and an optional manual P&L override; Asian / London / New York / NY PM session buttons; strategy cards with Volume Profile level triggers, catalyst chips and rules preview; chart screenshot by paste (Ctrl/⌘+V), upload (≤10 MB) or image link, plus **Read chart** when AI is configured (reads the instrument, the date/time on the time axis and the TradingView long/short position tool — entry is the line between the green and red boxes, the green box end is the target, the red box end the stop; prices from the right-hand axis labels; direction checked against the box layout); mindset, mistake (auto-set for FOMO/Revenge) and “Followed all trading rules” box. **Needs details** bell on the right of the Trade Log banner counts quick-logged trades without a setup/strategy, emotion or notes, with a *Complete now* filter and an *Add details* badge per row. **Import CSV** button (see Trade import). Filters, CSV export, **Share** card per trade, runner audit. |
| **Risk Calculator** | Position size from % or fixed risk per account, and P&L / R:R for any instrument — both with **voice mode** in English, Русский, 中文, Português, हिंदी (Hindi) or العربية (Arabic) (language buttons next to the mic; “skip”, “back” and “stop” work in each language). |
| **Strategy Analysis** | Personal strategy builder (style, edge/thesis, reorderable rules, sub-setups), scoreboard, win-rate rings, win rate by Asian/London/New York session — rings, session bars and the P&L chart animate in the same way as the Dashboard. |
| **Edge Matrix** | Highlighted in the sidebar with its own radar icon and EDGE tag. Colour-coded cards, each with a **?** help explanation: compact last-month audit (keep doing / cut out), best trading window with cautious A+ detection, anti-window, **Blow-up probability radar** (last 30 days of win rate, reward:risk, drawdown and position size → chance the account falls by −5/−10/−20/−50% in the next 14 days, with what-if risk buttons), **20% runner audit** (what 20% left open for 2 more hours with a breakeven stop would have done; needs market data, simulated for DEMO DATA), discipline leak and tilt rule. |
| **Daily Notepad** | “How was your trading day?”, rules followed (yes/partially/no), emotional state, own discipline rating plus a separate system discipline score, key lessons library; voice dictation in English, Russian, Chinese and Portuguese. |
| **Strategy vs setup** | One rule everywhere: a trade's **strategy** is only the strategy assigned to it; **Setup trigger / tags** is a separate free-text label (searchable, analysed in Edge Matrix as “Setup”) and never counts as a strategy. Trades without a strategy appear as “No strategy assigned”, which is also a Trade Log filter. |
| **Moving candle backdrop** | Every terminal page has a slow, faint background of rising candles and soft colour glows (`app/views/terminal/partials/backdrop.php`, styles `.tm-bg` in `terminal.css`). It sits behind all panels and cards, never over numbers, ignores clicks, is a little stronger on Confessions, fainter on light themes, stops for members who turn on *reduce motion* and is hidden when printing. |
| **Confessions** | Replaces the Coach tab (old Coach links redirect here). An anonymous feed, **open to every member for now (testing)**; switch it to *members with an active plan only* in **Control Panel → Confessions → Who can use Confessions** (one click, either way): each member picks a unique **@handle** on first visit (letters, numbers, dot, underscore; reserved and selling-style names refused; one change every 30 days) and gets a generated avatar; real names and emails are never shown. A post has a topic (Confession, Loss, Win, Psychology, Discipline, Journey, Meme, Question), an optional mood and an optional trade **attached from the member's own journal**, so any P&L shown is real (demo trades are labelled). **Likes are shown as $15 of respect each** (a scoreboard only: no money is paid or charged), on posts and on replies; replies show an *author* badge; **Share** makes a 1080×1350 image card; members can copy a link (members only), report or delete their own posts. A **spam shield** blocks links, emails, phone numbers, wallet addresses, DMs, signals, groups, account management, guaranteed profits, promo codes and disguised spellings (spaced letters, "dot com", leetspeak) before anything is saved, with a live warning while typing; posting is rate-limited and three reports hide a post until the team reviews it. When locked to plan members, free members see a preview with “Unlock with a plan”. Code: `app/trading/Confessions.php`, `ConfessionsController`, `public/assets/js/confessions.js`; checks in `php tests/confessions.php`. |
| **University** | **Trading Foundations**, a free course from zero to a trader with a plan: 13 lessons in 4 levels (about 2.6 hours). *Start here:* the harsh reality, market fundamentals, risk management. *Read the market:* sessions, spreads, swap and leverage; reading charts (top-down, structure, breakouts vs fakeouts, patterns); indicators (MA, RSI, MACD, ATR, Bollinger, VWAP); news and central banks. *Build your edge:* psychology, building and backtesting a strategy, trade management. *Go pro:* habits, brokers / prop firms / scam red flags, running trading like a business. Every lesson opens with an "In 30 seconds" summary, its sections open one at a time (so nothing looks like a wall of text), and it ends with a quiz; a cursor spotlight and ring make the course feel alive (off for touch screens and reduced motion). Progress is kept in the member's browser. Course content: `app/trading/Course.php` (levels, order, summaries) and `app/views/terminal/course/m*.php`. Below it, all playbook strategies in one clean numbered column (01 → 10), each opening a step-by-step explanation (logic, rules, entry, stop, targets), colour-coded by style; **Add to my strategies** copies one into the member's strategy builder. Every lesson opens with its own **animated illustration**, lessons 9–13 gained diagrams, and progress is now saved on the server: a lesson completes only after its quick check is answered with **60%+** (with a retry), giving confetti, **XP** (100 per lesson + up to 50 for the quiz) and four **level badges**. After all 13 lessons the member claims a **certificate of completion** (name to print, unique ID, QR to the public check page `/certificate/{code}`), downloadable as an A4 PNG or printed / saved as PDF. |
| **Mind** | Turns the journal into something that talks back. **Roast Mode** — the morning after a rule break, a short sarcastic message about that exact mistake (revenge, FOMO, moved stop, no stop, oversized, overtrading, tilt…); many variants per rule so it rarely repeats; Off / Mild / Savage (default Savage, saved per member); after a day that lost 4% or more of equity it switches to a supportive message instead. Also shown once a day on the Home Hub. **Death Clock** — needs 10+ closed trades; from the average result per trading day over the last 60 trades it projects the date the account reaches zero (capped at 10 years; shows "alive" when the average is positive). It ticks faster after a rule-breaking day and slower after a clean one, with an optional tick sound. **Boss Fight** — this month's costliest habit becomes a boss with 100 HP and shows how much it has "stolen"; every clean trading day takes 15 HP, every repeat heals it 10. **The Ghost** — a copy of you that took the same last 60 trades but followed the rules (rule-breaking trades skipped, moved/missing stops capped at planned risk); it never invents profit. **Psych Art** — each ISO week painted as generative art (white symmetry = disciplined, red spikes = revenge, black tentacles = FOMO, cracks = moved stop), browsable by week, downloadable as a 4K wallpaper. Roasts, boss kills and art export as 1080×1920 story cards. Code: `app/trading/Mind.php`, `MindController`, `public/assets/js/mind.js`. |
| **Markets** | Free TradingView widgets in five tabs: **live chart** (TradingView Advanced Real-Time Chart with indicators, timeframes, symbol search and the full drawing toolbar; quick buttons for gold, EUR/USD, GBP/USD, Nasdaq, S&P 500 and Bitcoin), **stock heatmap** (S&P 500 / Nasdaq 100), **crypto heatmap**, **market news** and **economic calendar**. Each loads only when its tab is opened and follows the member's theme and language. The Home Hub shows a scrolling **TradingView ticker tape** in its own box below the daily quote. Chart drawings are not saved between visits. |
| **Affiliate** | Shown only to members who applied to the affiliate programme: personal link and coupon code with copy buttons, clicks, referred and paying customers, earnings (pending / paid), commissions list with masked customer emails, and payout details. |
| **Accounts** | The account menu in the sidebar lists your accounts plus **＋ Add account**, which offers a **Broker account** (broker name, optional account name, starting equity, currency) or a **Prop firm account** (firm name, account size, challenge type). Prop presets fill in typical rules — profit target, daily loss, max overall loss (static or trailing), minimum trading days and consistency — which the member checks and edits; the **Dashboard** then shows live progress bars and an IN PROGRESS / TARGET MET / RULE BREACHED status. Each account keeps its own trades, equity (Edit / adjust equity), daily/weekly loss limits, deposits/withdrawals and **CSV import** of past trades from any broker. Live accounts need a paid plan; on the free plan accounts are added in Practice (demo) mode. |
| **Settings / Plan & Billing** | Profile, timezone, language, theme (**Obsidian Pro** dark — default — or **Clean Light**), risk rules, A+ risk tier, tilt rule, password, **Export my data** (JSON/CSV), **Delete account**, plan status and payment history. |

**Free vs paid.** Every member can create unlimited **demo** accounts and load the demo journal (marked DEMO DATA, kept in its own account; about 130 trades over five months, anchored to the day it is loaded and refreshed automatically on the next visit when it is more than a week old or still the old 42-trade set; only demo trades are replaced, trades a member logged in the demo account are kept. It tells one story: disciplined and profitable at 1% risk, then a tilt month at ~2% risk with revenge trades, FOMO and moved stops, so every feature shows a real example: blow-up radar, Edge Matrix, Roast Mode, Death Clock, Boss Fight, Ghost and Psych Art. `php tests/demo.php` checks this). **Live** accounts can be created and written to only while a paid plan is active; when a plan ends, live data stays visible but read-only.

### Control Panel (`/control-panel/`)
The Control Panel has its own visual identity: a SaaS dashboard with sidebar, breadcrumbs, toasts, modals and responsive tables.

- **Dashboard:** total members, Google vs email sign-ups, who signed in today, paid subscribers, revenue, trades journaled, a 30-day sign-up chart, newest members, latest sign-ins, integration status and a setup checklist.
- **Website:** General settings, Homepage sections, Header, Footer, Navigation, SEO, Social links, Contact details, WhatsApp, Analytics.
- **Content:** Pages (with content blocks), Services, Blog (posts, categories, tags), Testimonials, FAQs, Process steps, Custom sections.
- **Members:** All members (search, filter by Google/email, plan, activity, status; CSV export), member detail (profile, sign-in history with IP and device, accounts, payments, activity; grant/extend/end plans, suspend, delete), Sign-in log, Payments, Plans & pricing, Integrations (Google, Razorpay/Stripe, AI, market data), Contact messages.
- **Media:** Library with secure uploads, WebP optimisation, alt text, copy URL and a picker inside every image field.
- **Email:** SMTP settings, Email templates, Send test email, Delivery log.
- **Appearance:** Colours, Typography, Buttons, Layout options, Custom CSS.
- **System:** Admin users, Roles & permissions, Activity logs, System information, My profile.

---

## 2. Requirements

- PHP **8.1 or newer** (8.2+ recommended), with these extensions:
  - `pdo_mysql`, `gd` (with WebP), `sodium`, `fileinfo`, `mbstring`, `dom`, `openssl`
  - `exif` is optional
  - all are enabled by default on Namecheap/cPanel
- MySQL **5.7+** or MariaDB **10.3+** (InnoDB, utf8mb4).
- Apache with `mod_rewrite` and `AllowOverride All`, which is standard on cPanel. `mod_headers`, `mod_expires` and `mod_deflate` are used when available.
- An SSL certificate. Use cPanel → SSL/TLS Status → AutoSSL; it is free.

---

## 3. Folder structure

```
/                      ← upload these files into public_html (or the domain's folder)
├── index.php          ← the single front controller (all clean URLs route here)
├── .htaccess          ← HTTPS redirect, clean URLs, file protection, caching, compression
├── database.sql       ← schema + starter content (import with phpMyAdmin or via /setup)
├── README.md
├── app/               ← application code (web access denied)
│   ├── bootstrap.php
│   ├── core/          ← Router, Request, Database (PDO), Auth, Csrf, Session, Mailer, Media, Seo, sanitizers…
│   ├── controllers/   ← public controllers; admin/ = Control Panel; terminal/ = member trading terminal
│   ├── trading/       ← trading domain: analytics, trade maths, instruments, demo data, members, payments, AI coach, imports
│   ├── lang/          ← terminal translations (en, ru, zh, pt)
│   ├── models/        ← read-side queries (Content, Blog, Navigation)
│   ├── helpers/       ← helper functions + icon set
│   └── views/         ← templates: public/, admin/, terminal/, setup/
├── config/            ← config.example.php (and config.php after install) — web access denied
├── routes/            ← web.php (public), app.php (members, terminal, billing, webhooks), admin.php — web access denied
├── public/assets/     ← css/site.css, js/site.js, admin/admin.css, admin/admin.js, images/
├── storage/           ← logs/, sessions/, private/ (trade screenshots), installed.lock — web access denied
├── uploads/           ← media uploads (script execution disabled by uploads/.htaccess)
└── vendor/phpmailer/  ← PHPMailer 6 (bundled; no Composer needed)
```

> The Control Panel lives at the clean URL `/control-panel/`. It is a route, not a physical folder. Its views are in `app/views/admin/` and its assets are in `public/assets/admin/`. **Do not create a real `control-panel` folder**, because Apache would serve the folder instead of the route.

---

## 4. Installation on cPanel

### 4.1 Upload the files
1. Open **cPanel → File Manager** and go to `public_html`, or the document root of your domain or subdomain.
2. Click **Upload** and upload `journzey-ai-production.zip`.
3. Right-click the ZIP and choose **Extract**. Make sure the files end up directly in `public_html`, so `public_html/index.php` exists. If they extracted into a sub-folder, move them up.
4. In File Manager, open **Settings** (top right) and tick **Show Hidden Files**. Confirm that `.htaccess` is present in `public_html`, `uploads/` and the private folders.
5. Delete the ZIP afterwards.

### 4.2 Create the database
1. Open **cPanel → MySQL® Databases** and create a database, e.g. `cpuser_journzey`.
2. Create a MySQL user with a strong password, e.g. `cpuser_jzuser`.
3. Under **Add User To Database**, add the user to the database and tick **ALL PRIVILEGES**.

### 4.3 Install

You can install in one of two ways.

#### Option A — Web installer (recommended)
1. Visit `https://YOUR-DOMAIN/setup`. Any page redirects there until installation is complete.
2. Check the requirements list. Everything should show ✓.
3. Enter the database details:
   - host: usually `localhost`
   - database name, user and password
   - the website URL, e.g. `https://journzey.ai`
4. Create your **Super Admin**: name, email, and a password of at least 10 characters with letters and numbers.
5. Click **Install**. The installer then:
   - tests the database connection;
   - imports `database.sql` if the tables are not already there;
   - writes `config/config.php` with a random `APP_KEY`;
   - creates your Super Admin;
   - writes `storage/installed.lock`. After that, `/setup` returns 404 and **cannot run again**.

#### Option B — phpMyAdmin and a manual config
1. Open **cPanel → phpMyAdmin**, select the database, open **Import**, choose `database.sql` and click **Import**.
2. Copy `config/config.example.php` to `config/config.php` and edit it:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'cpuser_journzey');
   define('DB_USER', 'cpuser_jzuser');
   define('DB_PASS', 'your-db-password');
   define('BASE_URL', 'https://journzey.ai');
   define('APP_KEY', '64 random hex characters');
   ```
   Generate the APP_KEY with any password generator (letters and digits, 64 characters).
3. Visit `https://YOUR-DOMAIN/setup`. The installer detects the config and only asks you to create the Super Admin, then locks itself.

### 4.3a Updating an existing installation
1. Back up the database (phpMyAdmin → Export) and `config/config.php`.
2. Upload the new ZIP and extract it over the existing files (your `config/config.php`, `uploads/` and `storage/` are not in the ZIP and are kept).
3. Open the website once. The site applies `database-updated.sql` automatically (it only adds columns, tables and rows — nothing is deleted; schema version 4 also copies each member's old global loss limits onto each of their accounts and switches members on the old Dark Terminal / Clean Light themes to the new Obsidian Pro theme, which they can change back from the user menu; schema version 8 adds prop-firm rule fields to accounts, per-trade share cards and the homepage product showcase; schema version 7 adds the market-widget settings; schema version 6 moves anyone on a removed theme to Obsidian Pro, marks the first 3 playbook strategies as free and adds the affiliate tables and “Affiliates” links; schema version 10 adds one nullable column, `user_settings.quick_prefs`, for Quick Log favourites and remembered lot sizes; schema version 11 adds plan reminder tracking; schema version 12 adds `user_settings.roast_level` (default `savage`), the `wall_posts` table and the Wall of L's navigation links — to roll it back, drop `wall_posts` and the column; schema version 13 adds the Confessions tables (`confession_profiles`, `confessions`, `confession_likes`, `confession_comments`, `confession_comment_likes`, `confession_reports`, `confession_blocked`) and the University tables (`course_completions`, `course_certificates`) — new tables only; the rollback list is at the top of that block in the SQL). If you prefer, import **database-updated.sql** yourself in phpMyAdmin → Import first; running it twice is harmless.

### 4.4 Folder permissions
On cPanel the defaults are usually right: folders `755`, files `644`.

- `config/`, `storage/` (with `storage/logs`, `storage/sessions`) and `uploads/` must be writable by PHP.
- After installation you may set `config/config.php` to `640` or `600`.

### 4.5 Configuration reference (`config/config.php`)

| Constant | Meaning |
|---|---|
| `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` | Database connection. |
| `BASE_URL` | Full site URL with **no trailing slash**. Include a sub-folder if installed in one, e.g. `https://example.com/site`. |
| `APP_KEY` | 32+ random characters. Encrypts the SMTP password, API keys and webhook secrets. **Back it up.** If you change it, re-enter the SMTP password and every key under **Integrations**. |
| `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` | Optional. If defined (non-empty) they override the values saved under **Integrations**. |
| `APP_ENV` | `production` (default) or `development`. |
| `APP_DEBUG` | `false` on live sites. Errors are then logged to `storage/logs/`, never shown to visitors. |
| `ANTHROPIC_API_BASE` | Optional. Base URL for Anthropic requests (e.g. an API gateway). Leave undefined to use `https://api.anthropic.com`. |

### 4.6 Verify the installation

| Check | Expected |
|---|---|
| `https://YOUR-DOMAIN/` | The homepage loads. |
| `https://YOUR-DOMAIN/about` and `/services` | Load with clean URLs. |
| `http://YOUR-DOMAIN/` | Redirects to `https://`. |
| `https://YOUR-DOMAIN/config/config.php` and `/database.sql` | Show the 404 page, so they are protected. |
| `https://YOUR-DOMAIN/control-panel/` | Opens the sign-in page. |

---

## 5. Admin setup & Admin URL

**Admin URL:** `https://YOUR-DOMAIN/control-panel/`. It redirects to `/control-panel/login`.

After the first sign-in, follow the **Finish setting up** checklist on the dashboard:
1. **Members → Integrations:** Google sign-in, a payment gateway, an AI key and (optionally) a market-data key — see section 7.
2. **Members → Plans & pricing:** check plan names, prices, currency and limits.
3. **Website → General settings:** site name, tagline, logos, favicon, timezone, copyright.
4. **Website → Contact details:** email, phone, WhatsApp, address, hours, map, form texts, and where notifications go.
5. **Email → SMTP settings:** configure and **Send test email** (section 6). Welcome, password-reset and payment-receipt emails need SMTP.
6. **Content → Testimonials:** replace the **demo** testimonials with genuine ones, or unpublish them.
7. **Pages → Privacy Policy / Terms / Disclaimer / Security:** replace the template text with legally reviewed policies.
8. **Website → SEO & Analytics:** set the default meta data and enable tracking if needed.

The session times out after **30 minutes** of inactivity, and after 12 hours in total.

---

## 6. SMTP setup & test email

The Control Panel route is `/control-panel/email/smtp`.

1. In cPanel, open **Email Accounts** and create a mailbox, e.g. `noreply@yourdomain.com`.
2. Use **Connect Devices** to find the settings.
3. In the Control Panel, fill in:

   | Field | Value |
   |---|---|
   | SMTP host | `mail.yourdomain.com` |
   | Encryption / port | **SSL / 465** (recommended on cPanel) or **TLS / 587** |
   | Username | the full mailbox address |
   | Password | the mailbox password |
   | From email | the same mailbox (must be on your domain) |
   | From name | e.g. `journzey.ai` |
   | Reply-to | optional |

4. Tick **Enable SMTP** and click **Save settings**.
5. Under **Send test email**, enter a recipient and send. The panel shows a safe success or error message. The password is never displayed.

How email and form submissions work:
- **Password storage.** The SMTP password is encrypted in the database with `APP_KEY` (libsodium). The field always shows empty with a "saved" placeholder; leave it empty to keep the saved password.
- **Notifications.** Form notifications go to **Contact details → Send form notifications to**, falling back to the contact email.
- **Templates.** Edit the email templates under **Email → Email templates**. Available variables include `{name} {email} {message} {subject} {date} {site_name} {site_url}`; member emails also use `{reset_url}` (password reset) and `{plan} {amount} {access_until} {reference}` (payment receipt and admin payment notification).
- **Failure handling.** Contact messages, sign-ups and payments are **saved to MySQL first**, so an SMTP failure never loses them. All attempts appear in **Email → Delivery log**.

---

## 7. Managing the website

### Logo management
Go to **Website → General settings** and set:
- **Desktop logo**, plus an optional **Mobile logo**. Use SVG or a transparent PNG about 36 px tall.
- **Favicon**.

With no logo, the text wordmark is used.

### Homepage management
The **Product showcase** section (right after the hero) uses real terminal screenshots taken with the demo journal, stored in `public/assets/images/product/` (WebP). It includes a scroll-animated product view, an auto-playing feature tour, a moving feature strip and feature rows; the hero shows the real dashboard with floating voice-log and flex-card visuals. Hide it or change its heading in **Content → Homepage sections**. To refresh the screenshots after a redesign, replace the `.webp` files with the same names (a `-sm` version is used on small screens).
Go to **Website → Homepage**. To change the order of the 12 sections, drag a row or use the arrows. The switch shows or hides a section.

Edit a section to change:
- eyebrow, heading, subheading, body, image, background (including an image), buttons and repeatable items such as pillars, benefit cards and statistics;
- for the **Hero**: style (built-in product visual, background image, background video or text only), alignment, mobile image, overlay colour and opacity, and animation;
- for **Services, Blog, Testimonials and FAQ**: how many items to show.

The **Statistics** section is disabled by default and contains `[Replace]` placeholders. Only enable it with real, verifiable numbers.

**Content → Custom sections** adds extra banners or text sections to the homepage (before the final call to action), or to the About, Services or Contact pages.

### Header, footer and navigation management
- **Website → Navigation:** add, edit, delete, enable/disable and drag-reorder links for the **Header** menu and **Footer columns 1–3**.
  - Set a **Parent** to create a dropdown (header only).
  - Set **Open in** to open the link in the same tab or a new tab.
- **Website → Header:** sticky header, transparent over the hero, CTA button, optional secondary link and announcement bar.
- **Website → Footer:**
  - column titles;
  - column 1 can list the services automatically;
  - contact and social toggles;
  - the footer call-to-action band and the risk disclaimer.

### Services management
Go to **Content → Services**. Each service has:
- title, URL slug and icon;
- short and full description (rich text);
- thumbnail and hero image;
- CTA, benefits, process steps and FAQs;
- SEO and OG image, status, and whether it appears on the homepage.

You can search, filter and sort. Drag rows to reorder, and use the switch to publish or unpublish. A service's FAQs produce `FAQPage` structured data.

### Learning playbook management
Go to **Content → Learning playbook** (needs the *Pages* permission). Each strategy has a name, short name for the summary matrix, style, summary, institutional logic, assets, session, timeframes, setup rules (one per line), entry trigger, stop loss, take-profit targets (one per line), target R:R and SEO fields. Drag rows to reorder; use the switch to publish or hide. Tick **Free on the public Learn page** for the strategies visitors can read (3 by default); the rest are members-only and appear in the terminal **University**. The content is educational — keep the "not financial advice" wording and avoid promising results.

### Blog management
Go to **Content → Blog** to manage posts. **Categories** and **Tags** buttons are at the top of the list. Each post has:
- title, slug, excerpt, rich-text content (with images from the library) and featured image;
- category and tags (comma-separated; new tags are created automatically);
- author name, featured flag, SEO and OG image.

Statuses:
- **Draft:** hidden from the site.
- **Published:** live from its publish date.
- **Scheduled:** goes live automatically at the publish date. No cron job is needed.

### Page management
Go to **Content → Pages**. Pages are served at `/{slug}`. System slugs such as `blog`, `services`, `contact` and `control-panel` are reserved. Each page can use:
- a template: Default, About, Legal or Landing;
- hero texts and a featured image;
- rich-text content;
- **content blocks**: Text, Text + image, Cards/values, Statistics, Process steps, FAQs, Services grid, Testimonials, Call to action. For cards and stats, enter one item per line as `Title | Text | icon`.

System pages (About, Privacy, Terms) cannot be deleted; unpublish them instead.

### Members
Go to **Members → All members**. Every account created on the website appears here, whether the person signed up with Google or email.

- **Filters:** sign-up method (Google / email), plan (paid, free, expired), activity (signed in today, last 7 days, never), status; search by name or email; sort by newest, last sign-in, most sign-ins or most trades. **Export CSV** (formula-injection safe).
- **Member detail:** profile, sign-in methods, preferences, last sign-in and IP, total sign-ins, sign-up IP, plan, usage, trading accounts with P&L, sign-in history (IP and device), payments and the member's activity log.
- **Actions:** grant or extend a plan (recorded as a *manual* payment — useful for bank transfers or trials), end a plan now, suspend / re-activate (suspended members are signed out immediately), internal note, delete permanently.
- **Members → Sign-in log:** every sign-in, sign-up and password reset, successful or failed, with method, IP and device. Passwords and tokens are never logged.

### Plans & payments
- **Members → Plans & pricing:** name, checkout key (`/checkout/{key}`), price, currency, access period in days, period label, features (one per line), whether it unlocks live accounts, max live accounts, AI messages per day, “Most popular” highlight, availability. Plans can be reordered and switched off; a plan with members on it cannot be deleted.
- The free plan's name, features and AI limit are under **Members → Integrations → Member sign-up & free plan**.
- **Members → Payments:** every checkout with status (started, paid, failed, refunded), gateway reference and access period, plus revenue per currency (manual grants excluded).
- At checkout members choose **Autopay** (default; renews automatically every period until they turn it off) or **Pay once** (no renewal). Buying the same plan again stacks on the remaining time.
- **Settings** and **Plan & Billing** show a countdown card: days left, a progress bar for the current period, whether autopay is on, and **Turn off autopay** / **Renew** buttons. Turning autopay off stops future charges; the time already paid for is kept.

### Member emails (automatic)
All are editable or switchable under **Email → Templates**, sent through your SMTP settings and logged in **Email → Logs**.

| Email | When |
|---|---|
| Welcome | After sign-up (email or first Google sign-in) |
| New sign-in alert | When a member signs in from a browser/device that has not been used for their account before (switch off under **Members → Integrations → Member sign-up**) |
| Payment receipt | After every successful payment, including each autopay renewal |
| Plan ending soon | 3 days before the plan ends, when autopay is off |
| Autopay renewal reminder | 3 days before an autopay charge |
| Autopay payment failed | When a renewal charge fails (at most once a day) |
| Plan ended | Once, 12 hours after a plan ended without renewal |

**Scheduler (cron job) for the reminder emails** — cPanel → **Cron Jobs** → *Once Per Hour*, command:
```
php /home/USERNAME/public_html/cron.php
```
(Replace the path with the folder where journzey.ai is installed.) Without a cron job the site still checks at most once an hour when someone visits a page, but a cron job makes reminders punctual. Each reminder is sent only once per plan end date.

### Google sign-in setup
1. Open [Google Cloud Console](https://console.cloud.google.com/) → *APIs & Services* → **OAuth consent screen**: choose *External*, add your app name, support email, your domain and the scopes `openid`, `email`, `profile`. Publish the app.
2. *Credentials* → **Create credentials → OAuth client ID** → *Web application*.
3. **Authorized redirect URI:** copy it from **Members → Integrations** — it is `https://YOUR-DOMAIN/auth/google/callback`.
4. Paste the **Client ID** and **Client secret** into **Members → Integrations → Google sign-in** and save. “Continue with Google” on `/login` and `/signup` becomes active.

The flow uses the authorization-code flow with PKCE, `state` and `nonce`, and checks the ID token's audience, issuer and expiry. Only Google-verified email addresses are accepted. If a Google sign-in matches an existing email account, the accounts are linked.

### Payments setup (Razorpay or Stripe)
Choose one gateway under **Members → Integrations → Payments**.

**Razorpay** (India — INR, cards, UPI):
1. Razorpay Dashboard → *Account & Settings* → **API Keys** → generate. Paste the **Key ID** and **Key secret**.
2. Razorpay Dashboard → **Webhooks** → add `https://YOUR-DOMAIN/webhooks/razorpay` with events `payment.captured`, `order.paid`, `payment.failed` and, for autopay, `subscription.authenticated`, `subscription.activated`, `subscription.charged`, `subscription.pending`, `subscription.halted`, `subscription.cancelled`, `subscription.completed`. Type a secret and paste the same value as **Webhook secret**.
   - **Autopay** uses Razorpay **Subscriptions** (ask Razorpay support to enable it on your account if the Subscriptions menu is missing). journzey creates the Razorpay plan automatically from your plan's price and period. Razorpay handles the card / UPI AutoPay mandate and the pre-debit notifications required in India; members may be asked to approve the mandate once in their bank or UPI app. Plans shorter than 7 days cannot use autopay.
3. Set your plan currency to **INR** unless your Razorpay account is enabled for international currencies.

**Stripe** (international):
1. Stripe Dashboard → *Developers* → **API keys** → copy the **Secret key** (`sk_live_…`, or `sk_test_…` for testing).
2. *Developers* → **Webhooks** → add endpoint `https://YOUR-DOMAIN/webhooks/stripe` with `checkout.session.completed`, `checkout.session.async_payment_succeeded`, `checkout.session.async_payment_failed`, `charge.refunded` and, for autopay, `invoice.paid`, `invoice.payment_failed`, `customer.subscription.updated`, `customer.subscription.deleted`. Paste the **Signing secret** (`whsec_…`). Autopay uses Stripe Billing subscriptions created at checkout; no products need to be set up in Stripe.

Autopay can be switched off for everyone with **Offer autopay at checkout**. Deleting a member account (by the member or in the Control Panel) cancels their autopay first. Use **Test connection** after saving. Plans are activated only after a verified signature (Razorpay checkout signature / webhook HMAC, Stripe `Stripe-Signature` with a 5-minute tolerance) or a server-side Stripe lookup — never because the browser says a payment succeeded. Use test keys first and make a test purchase.

### AI (chart reading) & market data setup
The AI Coach chat was replaced by **Confessions**. An AI key is now only used for **Read chart** on the Log Trade form; the notes below still apply to it.
- **AI Coach:** **Members → Integrations → AI Coach**. Choose **Anthropic Claude** (key from console.anthropic.com; default model `claude-opus-5-5`) or **Google Gemini** (key from aistudio.google.com; default `gemini-2.5-flash`). Leave *Model* empty to use the default. Claude requests use adaptive thinking and server-side fallbacks, so a request the main model declines is retried on a fallback model automatically. The coach only receives aggregated statistics for the signed-in member plus their own short journal notes. Without a key, the AI mode is greyed out and members use the free **Smart Coach**, which works without any key or credits.
- **Daily limits:** per plan (and the free limit). Counted per member per UTC day. Only **answered** questions count: a request that fails (provider error, timeout, connection problem) stores nothing and does not use a message.
- **When the provider fails:** members see a short, safe message with a reference (e.g. “AI Coach is temporarily unavailable … (Ref: 1A2B3C4D)”), their question stays in the box and **Try again** resends it. Provider and billing details (invalid key, wrong model, *credit balance too low*) are never shown to members: they are written to `storage/logs/app-YYYY-MM.log` under the same reference, and problems only you can fix appear as a red notice on **Members → Integrations → AI Coach** until a **Test connection** succeeds. Test connection sends the same kind of request the coach uses and shows the provider's own message.
- **Credits are an owner action.** If the provider reports a low credit balance, add credits in the provider's console (for Anthropic: console.anthropic.com → Plans & Billing); no code change can do this.
- **Timeouts:** each provider request is limited to 90 seconds. The AI request no longer holds the member's session, so their other pages keep loading while the coach is thinking.
- **Market data (optional):** a [Twelve Data](https://twelvedata.com) key under **Members → Integrations** enables the post-trade Runner Auditor and **live reference prices for the Quick Log price wheel** (cached 15 s). The TradingView widgets run inside TradingView's own frames, so the site cannot read prices from them. Without a key the wheel is centred on the member's own last logged price for that instrument (labelled as such), or on a price they type — no price is ever invented.

### Trade import (members)
- **Accounts are manual.** Members create live equity, prop-firm and custom accounts themselves, enter the starting capital and record deposits, withdrawals and equity adjustments (*Edit / adjust equity* in the sidebar). There is no broker synchronisation or live broker feed.
- **CSV import from any broker:** *Trade Log → Import CSV* (or *Accounts → Import trade history*). Upload the export; the header row (report titles are skipped), delimiter (`,` `;` tab `|`) and columns are matched automatically and shown in a mapping screen where every column can be changed. Choose the date format (day/month/year first — guessed from the data), the timezone of the file's times (offsets in the data such as `Z` or `+05:30` are used exactly) and whether sizes are lots or units (100,000 units = 1 forex lot). **Preview** lists the parsed trades and every rejected row with its line number and reason; nothing is saved until **Import**. Two layouts: one row per trade (MT4/MT5 statements including the duplicate Time/Price columns, cTrader, OANDA, NinjaTrader, prop-firm dashboards…) or one row per execution (Binance, IBKR, Zerodha-style tradebooks…), which are paired first-in-first-out into round trips with anything left over imported as open trades. Broker P&L is used when present (profit + commission + swap); otherwise P&L is calculated from prices with the standard formulas. Cancelled/pending orders and balance/deposit rows are skipped, symbols such as `XAUUSD.m`, `EUR_USD`, `OANDA:XAUUSD` or `BTCUSDT.P` are recognised, duplicates are skipped (safe to import the same file twice). Afterwards the dashboard opens on *All time* so the whole history is analysed. Files are stored in `storage/private/imports` (web access denied) only until the import is finished or cancelled, at most 24 hours. Limit: 5 MB per file; Excel files must be saved as CSV first. Futures root symbols (e.g. `ES`, `NQ`) map to the matching index CFD instrument, so for futures exports without a P&L column check the calculated P&L or include the broker's P&L column.
- **One-line command:** in Quick Log, open *Prefer typing? Use a one-line command* and type e.g. “XAUUSD long 2645.50 sl 2639 tp 2660 0.5 lots exit 2655”; the trade is logged with **Undo** and **Edit** buttons, and without an exit it is logged as open. Voice logging was removed from Quick Log and the Log Trade form (it was unreliable); voice remains in the Risk Calculator, Daily Notepad and AI Coach.

### Instruments & the Position Size & Risk Calculator
- **Members → Instruments** holds every instrument's contract size, tick size, pip/point size, price decimals, minimum lot, lot step and aliases (45 metals, forex, indices, commodities and crypto instruments are pre-loaded). Edit them to match your broker; all P&L, R and lot-size maths reads from this table.
- *Terminal → Risk Calculator* has two modes: **Trade P&L / R** (P&L and risk-to-reward for any entry, stop and exit/take profit) and **Risk-based lot size** (pick an account, % risk or a fixed amount → recommended lots, rounded down to the lot step). Instruments quoted in another currency (e.g. GER40 in a USD account) ask for a conversion rate — journzey.ai does not fetch live prices.
- `php tests/calculations.php` (SSH/terminal only) runs the deterministic calculation and voice-parsing checks.

### Upload size for chart screenshots
Screenshots can be up to 10 MB. The included `.user.ini` raises PHP's upload limit on hosts that use PHP-FPM/CGI (most cPanel servers). If uploads above 2 MB fail, set *upload_max_filesize* = 12M and *post_max_size* = 16M in cPanel → MultiPHP INI Editor.

### Affiliate programme
Influencers apply at **/affiliates** with their member account (name or brand, platform, channel link, audience size). Applications appear under **Members → Affiliates** (the badge shows how many are waiting; needs the *Billing* permission).

- **Approve:** the coupon code is suggested from the name (e.g. `PRIYA25`) and can be changed; set the commission % (default **25%**, changeable under *Programme settings*, which also opens or closes applications). The affiliate's link is `/r/CODE`.
- **Attribution:** a customer is linked to an affiliate the first time they enter a valid code at checkout — typed in, or pre-filled from the link (the link alone does not attribute; the code must be applied). The first code wins; an affiliate cannot use their own code; a suspended affiliate's code is not accepted.
- **Commission:** every paid plan payment from a linked customer — each monthly renewal, online or a manual grant with an amount — creates a *pending* commission of payment × rate. Refunds void unpaid commissions automatically.
- **Payouts are manual:** on the affiliate's page, approve commissions, pay the affiliate outside the website using the payout details they entered, then **Mark paid** (or *Mark all approved as paid*). Void removes a commission.
- **Who sees what:** the affiliate sees only their own dashboard (**Terminal → Affiliate**) with masked customer emails; full details are only in the Control Panel. Suspend pauses an affiliate; reject sends an optional note.
- Payments are one-time per plan period, so “monthly” commission is earned each time the customer renews.

### Market widgets (TradingView)
**Website → Market widgets** switches the TradingView widgets on or off for the whole terminal and sets the ticker tape instruments (one per line, `EXCHANGE:SYMBOL | Name`, e.g. `OANDA:XAUUSD | Gold` — search the symbol on tradingview.com to find the code) and the economic-calendar countries.
- The widgets are free and need no account or API key. The visitor's browser loads them directly from TradingView, which supplies the prices, news and calendar; the website never stores or uses these prices (they cannot fill trades or the calculator).
- Some exchanges' data is delayed; TradingView marks this inside the widget. Keep the “by TradingView” credit links — the free widget terms require them.
- TradingView's script can see visitors' IP addresses and may set cookies: mention TradingView in your privacy policy.
- If TradingView is blocked (network or ad-blocker), the widget shows a short “could not load” message and the rest of the page works normally.

### Public verified P&L links
Members create them in **Terminal → Settings → Public verified P&L link**: choose an account, a date range (presets: this month, last month, last 3 months, this year, all time — or any dates), an optional title, and whether to show money amounts (off = % return and R only), the trade list, the equity curve and their first name. The link (`/p/{code}`) opens for anyone, signed in or not. Figures are computed live from the journal each time; the page shows how each trade was recorded (imported from a broker statement, voice, quick command, manual), how many were logged within 2 days, and labels demo data. Notes, screenshots, balances and account names are never shown. Links can be edited, paused or deleted; the page is excluded from search engines.

### Weekly brief (Monday & Tuesday mornings)
On Monday and Tuesday before noon (member's timezone) a small “journzey Coach” messenger pops up once per day with last week's result and at most three **Don't** and three **Do** points, each with one highlighted number (e.g. “Don't move your stop — 3× · −$420”). It is built from the active account's tagged mistakes, rule breaks, emotional trades, weakest/strongest session, overtrading days and best setup/market. It can be reopened any day from **Weekly brief** on the Home Hub.

### 24-hour sign-in & welcome intro
Every member sign-in (email or Google) lasts **24 hours**. After that the next request signs the member out and the login page explains why; a terminal tab left open moves to the login page by itself when the time is up. Signing out manually ends the session straight away. The session cookie also expires after 24 hours.

After **every** sign-in — manual or after the automatic 24-hour sign-out — the first Home Hub visit plays a short animated welcome (about 3 seconds, skippable); later Home visits in the same session simply rise in. It is skipped for members who turn on *reduce motion*. Admin Control Panel sessions are unchanged (30 minutes idle, 12 hours maximum).

### Risk alerts sound
When the daily or weekly loss limit is reached, or the tilt breaker fires (3 losses within 20 minutes), the terminal plays a short beep together with the banner. Browsers only allow sound after the member has clicked or typed on the page, so a beep that is blocked on page load plays on the first click. Each alert beeps at most once every 10 minutes.

### Customer support email
Set **Control Panel → Settings → Contact details → Customer support email**. It appears as “Customer support” on the contact page, in the footer and as **Help & support** in the member terminal's user menu. Nothing is shown while it is empty.

### Flex cards & “Verified by journzey.ai”
Members can create a shareable card for any trading day (Home, Calendar day view, Daily Notepad) and for any closed trade (Trade Log **Share** button). Both use the same clean design (dark or light, no glow effects): P&L in green/red, four stat tiles and a footer with **Verified by journzey.ai** and a QR code. The Trade Log also has a **Weekly card** button: pick any week with closed trades and get a brand-gradient card with net P&L, a win-rate ring, daily P&L bars with the cumulative curve, trades / profit factor / total R / green days, best trade and day, and a trade-by-trade strip — download, copy or share it. Weekly cards are stored in `share_cards` with `card_date` = the Monday and `card_ref` = 4294967295 (no schema change). The QR code opens `/verify/{code}`, which confirms the card was generated from that member's journal and shows the same figures (no balances or account names). The page states clearly that trades are recorded by the trader and not confirmed with a broker.

### Confessions moderation
**Control Panel → Confessions** (needs the *Members* permission; the menu badge counts hidden items). Tabs: **Reported** (posts and replies with reports), **Hidden** (auto-hidden after 3 reports, or hidden by you), **Blocked by the shield** (what the spam filter stopped, with the reason), **All recent** and **Handles** (mute a member for 7 days or suspend them from Confessions). Restore, hide or remove any post or reply. Only you see the real email next to a handle.

### Wall of L's moderation
Members submit from **Mind → Wall of L's** or a losing trade's **Post to Wall of L's** button (up to 3 a day, consent box required; the handle is optional and real names are never shown). Nothing is public until approved in **Control Panel → Wall of L's** (Pending / Published / Rejected tabs; Publish, Reject, Unpublish, Delete). Reject anything with personal details, broker account numbers, abuse or promotion. The page is linked from the header and footer navigation (removable in Navigation).

### Contact messages
**Members → Contact messages** lists messages from `/contact` with the statuses New, Read, Replied and Archived.

### Media management
Go to **Media → Media library**.

- **Upload:** drag and drop, or click. Images are re-encoded to WebP with a medium-size variant for fast loading.
- **Per file:** search, set alt text, copy the URL and delete. File info (size, dimensions, uploader) is shown.
- **Picker:** every image field opens the same library and can upload too.

Upload rules:

| Rule | Detail |
|---|---|
| Allowed types | JPG, JPEG, PNG, WebP, and SVG only after strict sanitising (scripts, event handlers and external references are rejected). |
| Validation | Extension, real MIME type, image decoding, size (default limit 5 MB, set in General settings) and file name. |
| Rejected | `.php`, `.phtml`, `.php5`, `.phar`, double extensions such as `x.php.jpg`, executables, and files whose content does not match their extension. |
| Folder protection | `uploads/.htaccess` disables script execution and directory listing. |

### SEO setup
Go to **Website → SEO**.
- **Defaults:** default title and description, title separator, robots, a global **Allow indexing** switch (untick it on staging) and the canonical base URL.
- **Social sharing:** default OG title, description and image, Twitter card and handle.
- **Page titles:** title and description for the homepage, services list, blog, contact and pricing pages.
- **Structured data:** `Organization` and `WebSite` are always output. `LocalBusiness` only appears when you enable it **and** a real address is configured. `BreadcrumbList`, `Article` and `FAQPage` are generated automatically.
- **robots.txt:** add extra rules. `/robots.txt` and `/sitemap.xml` are generated from published content and contain clean URLs only.
- **Per-item SEO:** each page, service and post has meta title, description and OG image fields, plus canonical override and noindex for pages.

### Analytics setup
Go to **Website → Analytics** and enable any of:
- **Google Analytics 4** (`G-…`)
- **Google Tag Manager** (`GTM-…`)
- **Meta Pixel** (digits)
- **Search Console** verification (paste the code or the whole meta tag)

Nothing is hard-coded. Each tag is output only when it is enabled and the ID format is valid.

### WhatsApp setup
Go to **Website → WhatsApp** and set:
- enable/disable, the number (international format) and the button label;
- the default message, position (left or right), and mobile/desktop visibility.

### Appearance
Go to **Appearance**.

| Screen | What it controls |
|---|---|
| **Colours** | 9 tokens, with a live preview. |
| **Typography** | Google Fonts from an allow-list, or System. |
| **Buttons** | Pill, rounded or square buttons, corner radius, glow, uppercase. |
| **Layout options** | Content width, section spacing, animations. |
| **Custom CSS** | Only for roles with the *Custom CSS* permission; Super Admin by default. |

Animations always respect the visitor's *reduce motion* setting.

### Brand design system
The website, sign-in pages and terminal share one visual system: deep indigo surfaces, the **violet → pink brand gradient** (`#6541E9` → `#FEB2B4`) for branding, primary buttons, selected states and every equity curve, and emerald/red reserved for gains and losses.

- **Defaults.** Appearance values that are still a shipped default (the original violet/cyan or the earlier gold) are drawn with the brand defaults instead (primary `#6541e9`, secondary `#feb2b4`, Plus Jakarta Sans, rounded buttons). Anything you have changed in **Appearance** is used exactly as saved. To bring back a shipped default exactly, pick a value that differs by one digit (for example `#7c5cfe`).
- **Fonts** are self-hosted in `public/assets/fonts/` (Inter, Plus Jakarta Sans, Instrument Serif for accents, JetBrains Mono). Other fonts chosen in Appearance still load from Google Fonts.
- **Tokens** live in the "ROYAL DESIGN SYSTEM" blocks and the "BRAND THEME" layer at the end of `public/assets/css/site.css` (website) and `public/assets/css/terminal.css` (terminal, both themes): colours, `--brand` / `--brand-btn` gradients, spacing (4/8/12/16/24/32/48/64), radii, shadows, motion (160 ms small, 420 ms reveals). The `--gold*` variable names are kept as accent hooks and now hold brand violet.
- **Terminal themes.** *Obsidian Pro* is the deep-indigo dark theme; *Clean Light* is a soft lavender-white theme with violet primary buttons. The theme setting and keys are unchanged.
- **Charts.** Equity curves (dashboard, strategy analysis, public P&L page, landing visuals) use the brand gradient for the line and a soft fade below it; drawdown charts stay red.
- **Motion** is short and state-driven: the hero candles and equity line draw once, sections rise in once, calculated results pulse briefly when they change. Nothing loops on the dashboard, and *reduce motion* switches the decorative motion off.

---

## 8. Admin users & roles

**System → Admin users** lets you create, edit, disable and delete admins and reset their passwords. Safeguards:
- you cannot disable or delete yourself;
- the last active Super Admin cannot be removed or downgraded;
- only a Super Admin can grant the Super Admin role.

**System → Roles** has two built-in roles:

| Role | Access |
|---|---|
| **Super Admin** | Everything. Cannot be edited. |
| **Editor** | Pages, Services, Blog, Testimonials, FAQs, Process steps, Custom sections, Homepage, Navigation, Media, Contact messages and **viewing** members and sign-ins. **No** access to member management, plans, payments, Integrations/API keys, SMTP credentials, email templates, admin users, roles, website and SEO settings, analytics, appearance, custom CSS, activity logs or system information. |

You can create more roles by ticking permissions per module. Every permission is checked **on the server** for each request; hidden menu items are only a convenience. Denied attempts are written to the activity log.

**System → Activity logs** records:
- sign-ins, sign-outs and recent sign-in attempts;
- every create, update, delete, publish and reorder;
- settings, SMTP and integration changes (which keys changed, never their values), uploads, member actions (plan grants, suspensions, deletions, exports) and admin-user management.

Passwords and secrets are never logged.

---

## 9. Backups

- **Database.** Use cPanel → **Backup** → *Download a MySQL Database Backup*, or phpMyAdmin → Export. Do this at least weekly and before updates.
- **Files.** Back up `uploads/` (your media) and `config/config.php`, which holds the DB credentials and **APP_KEY**. Store them privately. cPanel → Backup → *Home Directory* covers everything.
- **Restore.** Re-upload the files, import the SQL dump into an empty database and restore `config/config.php`. Keep `storage/installed.lock` so the installer stays disabled.
- **Never** leave backup files (`*.sql`, `*.zip`) inside `public_html`. `.htaccess` blocks `.sql`, `.bak`, `.log` and `.md`, but a ZIP is not blocked.

---

## 10. Security

| Area | How it is handled |
|---|---|
| **SQL injection** | Every query is a PDO prepared statement, with native prepares. Column names only come from code. |
| **XSS** | All output is escaped with `e()`. Rich text is cleaned server-side by an allow-list HTML sanitizer: scripts, styles, event handlers and `javascript:` URLs are removed, and iframes are limited to YouTube, Vimeo and Google Maps. Custom CSS cannot break out of its `<style>` tag. |
| **CSRF** | A per-session token is required on every POST, in forms and AJAX. Failures show a branded "session expired" page. |
| **Sessions** | Separate cookies for the public site (`JZSESS`) and the Control Panel (`JZADMIN`, path `/control-panel`, `SameSite=Strict`). Cookies are `HttpOnly`, and `Secure` on HTTPS. Strict mode is on. The session ID is regenerated at sign-in. Sessions time out after 30 minutes idle or 12 hours absolute. |
| **Authentication** | `password_hash()`/`password_verify()` with automatic rehash. Sign-in is rate-limited to 5 attempts per email and 20 per IP per 15 minutes. Failed sign-ins are recorded with their IP. Responses are timing-safe for unknown accounts. |
| **Authorization** | Role permissions are checked server-side in every controller. |
| **Forms** | CSRF, a honeypot field, an HMAC-signed minimum fill time and a per-IP rate limit (5 per 10 minutes). Validation runs on the server; browser validation is only a convenience. |
| **Uploads** | See [Media management](#media-management). Files are re-encoded, given random names and stored in a non-executable folder. Path-traversal-safe deletion. |
| **Secrets** | The SMTP password, Google client secret, payment keys, AI and market-data keys and webhook signing secrets are encrypted at rest (libsodium + `APP_KEY`), never sent back to the browser and never logged. No credentials ship in the ZIP. |
| **Members** | Separate `users` table and session from admins. Every terminal query is scoped to the signed-in member's id from the session — never an id from the browser. Passwords use `password_hash()`; sign-in is rate-limited; password reset tokens are single-use, hashed and expire after 60 minutes. Changing a password signs out other sessions. |
| **Google OAuth** | Authorization code + PKCE, `state` and `nonce`; ID token audience/issuer/expiry checked; only verified Google emails; tokens are never stored in the browser. |
| **Payments & webhooks** | HMAC signature verification (Razorpay and Stripe), timestamp windows, idempotent processing; plan access is only granted server-side. |
| **Member files** | Trade screenshots (PNG/JPEG/WebP ≤ 5 MB) are re-encoded and stored in `storage/private/`, served only to their owner. |
| **Files** | `.htaccess` denies `app/`, `config/`, `routes/`, `storage/`, `vendor/`, dot-files and `*.sql/.md/.log/.ini/.lock/.bak`. Directory listing is disabled. Direct `*.php` URLs return 404 (`/index.php` redirects to `/`). |
| **Errors** | `display_errors` is off in production. Branded 404 and 403 pages, and a 500 page. Errors are logged to `storage/logs/`. |
| **Headers** | HSTS (on HTTPS), `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`. The Control Panel sends `noindex` and `no-store`. |

Recommended extras:
- use a long, unique Super Admin password;
- keep PHP up to date in cPanel → *Select PHP Version*;
- optionally restrict `/control-panel` by IP with cPanel → *IP Blocker*/*Directory Privacy* or Cloudflare.

---

## 11. Troubleshooting

### General
- **White page or "Something went wrong".** Check `storage/logs/app-YYYY-MM.log` and `storage/logs/php-error.log` in File Manager. Temporarily setting `APP_DEBUG` to `true` in `config/config.php` shows the error; **turn it off again**.
- **Redirect loop to `/setup`.** `config/config.php` or `storage/installed.lock` is missing or unreadable. Restore it, or run `/setup` again. The installer will not overwrite existing data, and it locks again if an admin already exists.
- **"Session expired" when submitting a form.** The page was open too long, or cookies are blocked. Reload and resubmit. Behind Cloudflare, make sure "Always Use HTTPS" is on so the Secure cookies work.
- **Images do not upload.**
  - Check that `uploads/` is writable (755).
  - Check PHP `upload_max_filesize` and `post_max_size` in cPanel → *Select PHP Version → Options*. System → System information shows the current limits.
- **Dates or times look wrong.** Set **General settings → Timezone** (website) or the member's own timezone in *Terminal → Settings*. All data is stored in UTC.
- **Google says `redirect_uri_mismatch`.** The redirect URI in Google Cloud must match exactly the one shown under **Members → Integrations** (including `https` and `www`). `BASE_URL` must match the address members use.
- **A payment succeeded but the plan is not active.** Check the webhook URL and secret in the gateway dashboard and **Members → Payments**. You can grant the plan manually from the member's page.
- **AI Coach says it requires server configuration.** Add a provider and API key under **Members → Integrations → AI Coach** and click **Test connection**.
- **A member reports an error reference.** Every response carries an `X-Request-Id` header and every log line starts with the same `rid`; AI Coach failures also log a `ref`. Search `storage/logs/app-YYYY-MM.log` for the reference. Requests slower than 20 seconds are logged as `Slow request` with the path.
- **Intermittent 502/504 errors.** If a 502/504 has **no** matching entry in `storage/logs`, it came from the hosting proxy/server (PHP worker limits, LiteSpeed/Apache timeouts, HTTP/2 connection resets) — ask the host for the error log at that time and the gateway timeout value. The application itself never returns 502; provider outages are reported as 503 with `Retry-After`.

### 404 troubleshooting
- **Every page except the homepage returns 404.** `mod_rewrite` or `.htaccess` is not active:
  - make sure `.htaccess` was uploaded (enable *Show Hidden Files*);
  - make sure the hosting allows overrides (`AllowOverride All`), which is standard on cPanel.
- **Installed in a sub-folder** such as `https://example.com/site`:
  - set `BASE_URL` to the full sub-folder URL;
  - in `.htaccess` change `RewriteBase /` to `RewriteBase /site/`;
  - change the `ErrorDocument` lines to `/site/index.php`;
  - change the trailing-slash rule's `^/control-panel/$` to `^/site/control-panel/$`.
- **A new page shows 404.** Check that it is **Published** and its slug is not reserved.
- **Blog post 404.** Check that it is published or scheduled and that the publish date is in the past.
- **Images return 404.** The file was deleted from the library. Choose a new image in the field.

### `.htaccess` troubleshooting
- **500 Internal Server Error right after upload.** Your server may not support a directive.
  - First comment out (`#`) the `Options` line, then the `ServerSignature` line, then the `<IfModule mod_headers.c>` block, until it works.
  - The `<IfModule>` blocks are skipped automatically when a module is missing.
- **Too many redirects.** HTTPS is terminated by a proxy such as Cloudflare in *Flexible* mode. Switch Cloudflare SSL to **Full**, or remove the HTTPS block in `.htaccess`.
- **CSS or JS not loading.** Check that the `public/assets/` folder exists and that `BASE_URL` matches the address you are visiting, including `https` and `www`.
- **`www` versus non-`www`.** Choose one and redirect the other in cPanel → *Domains/Redirects*. Make `BASE_URL` match the one you chose.

### SMTP troubleshooting
| Message | Fix |
|---|---|
| *Could not authenticate* | Wrong username or password. Use the full email address as the username. Re-enter the password and save. |
| *Could not connect to SMTP host* | Wrong host, port or encryption. Try SSL/465, then TLS/587. Some hosts block outbound SMTP to other providers; use your cPanel mailbox, or ask the host to allow it. |
| Mail sends but never arrives | Check spam. Set the **From email** to a mailbox on your own domain. Enable **SPF, DKIM and DMARC** in cPanel → *Email Deliverability*. |
| Test passes but forms don't notify | Set **Contact details → Send form notifications to**. Check that the templates are enabled in Email → Email templates. Check the Delivery log. |
| SMTP password stopped working after moving servers | `APP_KEY` changed. Re-enter the SMTP password. |

---

## 12. Content rules & placeholders

The starter content describes the journzey.ai product honestly and **contains no invented facts**: no customer counts, statistics, reviews, awards, addresses or team members. Items to replace before launch are clearly marked:

| Location | Placeholder |
|---|---|
| Testimonials | 3 entries flagged **Demo content**. Demo testimonials are **never shown on the public site**; add genuine, approved reviews with “Demo” unticked to show the section. |
| Homepage → Statistics and About → statistics block | Disabled or hidden; contain `[Replace]`. |
| FAQs | Answers containing `[Replace]`: supported brokers, data handling. |
| Privacy Policy, Terms, Security, Risk Disclaimer | **Launch blocker.** Template text with owner placeholders. While any `[Replace…]`-style placeholder or the template notice remains, the page is shown with a “Draft — not yet final” banner, placeholders appear as *[to be completed]*, the page is `noindex`, and the Control Panel dashboard lists it. See `docs/LAUNCH-POLICY-INPUTS.md` for the exact inputs needed. |
| Contact details, logos, social links, analytics IDs | Empty until you add them. |

---

## 13. Development notes

- **Local development:** any Apache + PHP 8.2 + MySQL stack (XAMPP, MAMP, Laragon) with the project as the document root and `AllowOverride All`. Visit `/setup`.
- **Routing:** `routes/web.php`, `routes/app.php` and `routes/admin.php`. All browser URLs are clean; `index.php` is the only PHP entry point.
- **AI client:** `app/trading/AiCoach.php` calls the Anthropic Messages API (and Gemini) over HTTPS with cURL, because the official PHP SDK needs Composer, which cPanel shared hosting usually cannot run.
- **Terminal translations:** `app/lang/{en,ru,zh,pt}.php`.
- **Adding a Control Panel module:** add an entry to `app/controllers/admin/resources.php` (table, columns, fields, filters). The generic CRUD engine supplies list, search, filter, sort, create, edit, delete, toggle and reorder. Add the menu item in `app/views/admin/layouts/app.php` and a permission key in `App\Core\Auth::PERMISSIONS`.
- **Adding a setting:** add the field to `app/controllers/admin/settings.php` and read it anywhere with `setting('key')`.
- **Third-party code:** PHPMailer 6.12 (LGPL-2.1) is in `vendor/phpmailer/`. Razorpay Checkout (`checkout.razorpay.com`) is loaded on the checkout page only when Razorpay is the active gateway. There are no other dependencies.
