# journzey.ai — Website & Control Panel

The production marketing website and CMS for **journzey.ai**, an institutional multi-broker trading journal and AI discipline terminal.

- **Stack:** plain PHP 8.1+ (8.2+ recommended), MySQL/MariaDB with PDO, vanilla JavaScript and Apache `.htaccess`.
- **Hosting:** built for cPanel shared hosting (e.g. Namecheap). No Node, npm, Composer, Docker or SSH is needed.
- **Admin:** a separate Control Panel at **`/control-panel/`** manages every part of the public site.

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
   - logo, homepage, navigation, services, blog, pages, leads, media, SEO, analytics, WhatsApp
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
| `/blog`, `/blog/{slug}` | Blog with featured post, search, pagination, related posts and sharing. |
| `/blog/category/{slug}`, `/blog/tag/{slug}`, `/blog/page/2` | Blog archives and pagination. |
| `/contact` | Contact details, map and contact form (saved to MySQL). |
| `/book-consultation` | Demo/consultation booking form (saved to MySQL as a lead). |
| `/privacy-policy`, `/terms-and-conditions` | Legal pages (CMS pages). |
| `/{any-page-slug}` | Any page created in the Control Panel, e.g. `/refund-policy`. |
| `/sitemap.xml`, `/robots.txt` | Generated automatically. |

### Control Panel (`/control-panel/`)
The Control Panel has its own visual identity: a SaaS dashboard with sidebar, breadcrumbs, toasts, modals and responsive tables.

- **Dashboard:** real metrics, a 30-day leads chart, the lead pipeline, recent leads and messages, quick actions and a setup checklist.
- **Website:** General settings, Homepage sections, Header, Footer, Navigation, SEO, Social links, Contact details, WhatsApp, Analytics.
- **Content:** Pages (with content blocks), Services, Blog (posts, categories, tags), Testimonials, FAQs, Process steps, Custom sections.
- **Leads:** Consultation leads (status, notes, assignment, history, email log, CSV export) and Contact messages.
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
│   ├── controllers/   ← public controllers; controllers/admin/ = Control Panel (+ resources.php, settings.php)
│   ├── models/        ← read-side queries (Content, Blog, Navigation)
│   ├── helpers/       ← helper functions + icon set
│   └── views/         ← templates: public/, admin/, setup/
├── config/            ← config.example.php (and config.php after install) — web access denied
├── routes/            ← web.php (public) and admin.php (Control Panel) — web access denied
├── public/assets/     ← css/site.css, js/site.js, admin/admin.css, admin/admin.js, images/
├── storage/           ← logs/, sessions/, installed.lock — web access denied
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

### 4.4 Folder permissions
On cPanel the defaults are usually right: folders `755`, files `644`.

- `config/`, `storage/` (with `storage/logs`, `storage/sessions`) and `uploads/` must be writable by PHP.
- After installation you may set `config/config.php` to `640` or `600`.

### 4.5 Configuration reference (`config/config.php`)

| Constant | Meaning |
|---|---|
| `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` | Database connection. |
| `BASE_URL` | Full site URL with **no trailing slash**. Include a sub-folder if installed in one, e.g. `https://example.com/site`. |
| `APP_KEY` | 32+ random characters. Encrypts the SMTP password. **Back it up.** If you change it, re-enter the SMTP password. |
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
1. **Website → General settings:** site name, tagline, logos, favicon, timezone, copyright.
2. **Website → Contact details:** email, phone, WhatsApp, address, hours, map, form texts, and where notifications go.
3. **Email → SMTP settings:** configure and **Send test email** (section 6).
4. **Content → Testimonials:** replace the **demo** testimonials with genuine ones, or unpublish them.
5. **Pages → Privacy Policy / Terms:** replace the template text with legally reviewed policies.
6. **Website → SEO & Analytics:** set the default meta data and enable tracking if needed.

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
- **Templates.** Edit the email templates under **Email → Email templates**. Available variables: `{name} {email} {phone} {service} {message} {date} {subject} {preferred_date} {preferred_time} {site_name} {site_url}`.
- **Failure handling.** Every lead or contact message is **saved to MySQL first**, so an SMTP failure never loses it. The lead shows "Email failed" with a safe diagnostic. All attempts appear in **Email → Delivery log**.

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

### Lead management
Go to **Leads → Consultation leads**.

- **Statuses:** New, Contacted, Follow-up, Converted, Closed.
- **List:** filter by status, assignee, service and date range; search; sort.
- **Status changes:** change the status straight from the list.
- **Export:** export to CSV. Values are protected against spreadsheet formula injection.

The **lead detail** page has:
- the full submission;
- internal notes;
- a history timeline (status changes, assignment, edits, email results);
- the email delivery log;
- assignment to an admin, editing and deleting.

**Leads → Contact messages** works the same way with the statuses New, Read, Replied and Archived.

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
- **Page titles:** title and description for the homepage, services list, blog, contact and booking pages.
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
| **Editor** | Pages, Services, Blog, Testimonials, FAQs, Process steps, Custom sections, Homepage, Navigation, Media, Leads and Contact messages. **No** access to SMTP credentials, email templates, admin users, roles, website and SEO settings, analytics, appearance, custom CSS, activity logs or system information. |

You can create more roles by ticking permissions per module. Every permission is checked **on the server** for each request; hidden menu items are only a convenience. Denied attempts are written to the activity log.

**System → Activity logs** records:
- sign-ins, sign-outs and recent sign-in attempts;
- every create, update, delete, publish and reorder;
- settings and SMTP changes, uploads, lead actions and user management.

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
| **Secrets** | The SMTP password is encrypted at rest and never sent to the browser or shown in errors. No credentials ship in the ZIP. |
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
- **Dates or times look wrong.** Set **General settings → Timezone**. All data is stored in UTC.

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
- **Routing:** `routes/web.php` and `routes/admin.php`. All browser URLs are clean; `index.php` is the only PHP entry point.
- **Adding a Control Panel module:** add an entry to `app/controllers/admin/resources.php` (table, columns, fields, filters). The generic CRUD engine supplies list, search, filter, sort, create, edit, delete, toggle and reorder. Add the menu item in `app/views/admin/layouts/app.php` and a permission key in `App\Core\Auth::PERMISSIONS`.
- **Adding a setting:** add the field to `app/controllers/admin/settings.php` and read it anywhere with `setting('key')`.
- **Third-party code:** PHPMailer 6.12 (LGPL-2.1) is in `vendor/phpmailer/`. There are no other dependencies.
