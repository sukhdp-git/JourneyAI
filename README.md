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
| `/` | Homepage. Its 12 sections are CMS-managed, reorderable and can be switched on or off. |
| `/about` | About page: hero plus content blocks. |
| `/services`, `/services/{slug}` | Platform capabilities, each with benefits, process, FAQ and SEO. |
| `/learn`, `/learn/{slug}` | Learning section: the 10-strategy intraday playbook (logic, setup rules, entry, stop, target), a summary matrix and a glossary. Signed-in members can copy any strategy into their personal strategies. |
| `/blog`, `/blog/{slug}` | Blog with featured post, search, pagination, related posts and sharing. |
| `/blog/category/{slug}`, `/blog/tag/{slug}`, `/blog/page/2` | Blog archives and pagination. |
| `/contact` | Contact details, map and contact form (saved to MySQL). |
| `/pricing` | Plans (free demo + paid plans managed in the Control Panel) with links to checkout. |
| `/signup`, `/login`, `/forgot-password` | Member accounts: email + password or **Continue with Google**. |
| `/onboarding` | First-run setup: markets, timezone, risk rules, first account, optional demo journal. |
| `/checkout/{plan}` | Plan checkout through Razorpay or Stripe (signed-in members). |
| `/terminal/…` | The trading terminal (members only, see below). |
| `/privacy-policy`, `/terms-and-conditions`, `/disclaimer`, `/security` | Legal pages (CMS pages). |
| `/{any-page-slug}` | Any page created in the Control Panel, e.g. `/refund-policy`. |
| `/sitemap.xml`, `/robots.txt` | Generated automatically. |

### Trading terminal (`/terminal`, members only)

| Page | What it does |
|---|---|
| **Home Hub** | Trading-psychology quotes, a clock in the member's chosen timezone with the London / New York / Tokyo session indicator (daylight saving handled), daily & weekly risk-limit status, 9-step checklist, quick trade command (`/` hotkey). |
| **Dashboard** | Date presets, KPIs (net P&L, win rate, profit factor, payoff, expectancy…), equity, cumulative P&L and drawdown charts, breakdowns by weekday, session, instrument and strategy. |
| **Calendar** | Monthly P&L calendar with weekly summaries and day drill-down. |
| **Trade Log** | Compact table (cards on mobile), voice/sentence trade entry, filters, add/edit/delete, CSV export, private screenshots, **Share** image per trade (green/red, dark/light), runner audit. |
| **Risk Calculator** | Position size from % or fixed risk per account, and P&L / R:R for any instrument. |
| **Strategy Analysis** | Personal strategy builder (style, edge/thesis, reorderable rules, sub-setups), scoreboard, win-rate rings, win rate by Asian/London/New York session. |
| **Edge Matrix** | Last month audit (positive edges and negative traps), best trading window with cautious A+ setup detection, anti-window detector, Ruin Probability Radar (2,000-path simulation at 0.5–4% risk), discipline-leak counter with actual vs rule-compliant curve, tilt rule. |
| **Daily Notepad** | “How was your trading day?”, rules followed (yes/partially/no), emotional state, own discipline rating plus a separate system discipline score, key lessons library; voice dictation in English, Russian, Chinese and Portuguese. |
| **AI Coach** | Automatic strengths & critical-leaks summary, weekly/monthly reviews (with optional AI narrative), and chat with voice input, grounded in the member's trades, journals, lessons and limits (Anthropic Claude or Google Gemini). |
| **Accounts** | Manual demo, live equity, prop-firm and custom accounts; deposits, withdrawals, equity adjustments and capital history; **CSV statement import**. |
| **Settings / Plan & Billing** | Profile, timezone, language, theme (4 themes), risk rules, daily/weekly loss limits (% or fixed), A+ risk tier, tilt rule, password, **Export my data** (JSON/CSV), **Delete account**, plan status and payment history. |

**Free vs paid.** Every member can create unlimited **demo** accounts and load the 42-trade demo journal (marked DEMO DATA, kept in its own account). **Live** accounts can be created and written to only while a paid plan is active; when a plan ends, live data stays visible but read-only.

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
3. Open the website once. The site applies `database-updated.sql` automatically (it only adds columns, tables, instruments and the learning playbook — nothing is deleted). If you prefer, import **database-updated.sql** yourself in phpMyAdmin → Import first; running it twice is harmless.

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
Go to **Content → Learning playbook** (needs the *Pages* permission). Each strategy has a name, short name for the summary matrix, style, summary, institutional logic, assets, session, timeframes, setup rules (one per line), entry trigger, stop loss, take-profit targets (one per line), target R:R and SEO fields. Drag rows to reorder; use the switch to publish or hide. The content is educational — keep the "not financial advice" wording and avoid promising results.

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
- Payments are **one-time** for the plan period (no auto-renewal). Buying the same plan again stacks on the remaining time.

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
2. Razorpay Dashboard → **Webhooks** → add `https://YOUR-DOMAIN/webhooks/razorpay` with events `payment.captured`, `order.paid` and `payment.failed`. Type a secret and paste the same value as **Webhook secret**.
3. Set your plan currency to **INR** unless your Razorpay account is enabled for international currencies.

**Stripe** (international):
1. Stripe Dashboard → *Developers* → **API keys** → copy the **Secret key** (`sk_live_…`, or `sk_test_…` for testing).
2. *Developers* → **Webhooks** → add endpoint `https://YOUR-DOMAIN/webhooks/stripe` with `checkout.session.completed`, `checkout.session.async_payment_succeeded`, `checkout.session.async_payment_failed` and `charge.refunded`. Paste the **Signing secret** (`whsec_…`).

Use **Test connection** after saving. Plans are activated only after a verified signature (Razorpay checkout signature / webhook HMAC, Stripe `Stripe-Signature` with a 5-minute tolerance) or a server-side Stripe lookup — never because the browser says a payment succeeded. Use test keys first and make a test purchase.

### AI Coach & market data setup
- **AI Coach:** **Members → Integrations → AI Coach**. Choose **Anthropic Claude** (key from console.anthropic.com; default model `claude-opus-5-5`) or **Google Gemini** (key from aistudio.google.com; default `gemini-2.5-flash`). Leave *Model* empty to use the default. Claude requests use adaptive thinking and server-side fallbacks, so a request the main model declines is retried on a fallback model automatically. The coach only receives aggregated statistics for the signed-in member plus their own short journal notes. Without a key, members see “AI Coach requires server configuration.”
- **Daily limits:** per plan (and the free limit). Counted per member per UTC day.
- **Market data (optional):** a [Twelve Data](https://twelvedata.com) key under **Members → Integrations** enables the post-trade Runner Auditor. The terminal never displays market prices.

### Trade import (members)
- **Accounts are manual.** Members create live equity, prop-firm and custom accounts themselves, enter the starting capital and record deposits, withdrawals and equity adjustments (*Edit / adjust equity* in the sidebar). There is no broker synchronisation or live broker feed.
- **CSV import:** *Accounts → Import statement*. MT4/MT5, cTrader and NinjaTrader history exports are auto-detected; balance rows are skipped; duplicates are skipped by ticket number; broker server-time offsets are supported.
- **Voice / sentence entry:** *Trade Log → New trade → Voice trade entry*. Say or type “Bought gold at 2645.50, stop loss 2639, take profit 2660, 0.5 lots”; the values fill the form and nothing is saved until the member presses **Save trade**. Voice needs Chrome, Edge or Safari with microphone permission.

### Instruments & the Position Size & Risk Calculator
- **Members → Instruments** holds every instrument's contract size, tick size, pip/point size, price decimals, minimum lot, lot step and aliases (45 metals, forex, indices, commodities and crypto instruments are pre-loaded). Edit them to match your broker; all P&L, R and lot-size maths reads from this table.
- *Terminal → Risk Calculator* has two modes: **Trade P&L / R** (P&L and risk-to-reward for any entry, stop and exit/take profit) and **Risk-based lot size** (pick an account, % risk or a fixed amount → recommended lots, rounded down to the lot step). Instruments quoted in another currency (e.g. GER40 in a USD account) ask for a conversion rate — journzey.ai does not fetch live prices.
- `php tests/calculations.php` (SSH/terminal only) runs the deterministic calculation and voice-parsing checks.

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
| Testimonials | 3 entries flagged **Demo content**, shown with a visible "Demo" badge. |
| Homepage → Statistics and About → statistics block | Disabled or hidden; contain `[Replace]`. |
| FAQs | Answers containing `[Replace]`: supported brokers, data handling. |
| Privacy Policy and Terms | Template text with a visible notice to have them legally reviewed. |
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
