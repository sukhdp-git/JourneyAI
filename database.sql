-- ---------------------------------------------------------------------------------------------
-- journzey.ai website — database schema and starter content
-- Import with phpMyAdmin (Import tab) into an EMPTY database, or let /setup import it for you.
-- Requires MySQL 5.7+ / MariaDB 10.3+ with InnoDB and utf8mb4.
-- No admin account is created here: the installer (/setup) creates the first Super Admin.
-- ---------------------------------------------------------------------------------------------
SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE roles (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(80) NOT NULL,
  slug VARCHAR(80) NOT NULL,
  description VARCHAR(255) NULL,
  permissions TEXT NOT NULL,
  is_system TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_roles_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admins (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  role_id INT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  status ENUM('active','disabled') NOT NULL DEFAULT 'active',
  last_login_at DATETIME NULL,
  last_login_ip VARCHAR(45) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_admins_email (email),
  KEY idx_admins_role (role_id),
  CONSTRAINT fk_admins_role FOREIGN KEY (role_id) REFERENCES roles (id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_attempts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  email VARCHAR(190) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  user_agent VARCHAR(255) NULL,
  success TINYINT(1) NOT NULL DEFAULT 0,
  reason VARCHAR(30) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_login_email (email, created_at),
  KEY idx_login_ip (ip, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rate_limits (
  `key` CHAR(64) NOT NULL,
  attempts INT UNSIGNED NOT NULL DEFAULT 0,
  reset_at DATETIME NOT NULL,
  PRIMARY KEY (`key`),
  KEY idx_rate_reset (reset_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE settings (
  `key` VARCHAR(100) NOT NULL,
  `value` MEDIUMTEXT NULL,
  group_name VARCHAR(50) NOT NULL DEFAULT 'general',
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`key`),
  KEY idx_settings_group (group_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE navigation (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location VARCHAR(30) NOT NULL DEFAULT 'header',
  parent_id INT UNSIGNED NULL,
  label VARCHAR(100) NOT NULL,
  url VARCHAR(500) NOT NULL,
  target ENUM('_self','_blank') NOT NULL DEFAULT '_self',
  sort_order INT NOT NULL DEFAULT 0,
  is_enabled TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_nav_location (location, is_enabled, sort_order),
  KEY idx_nav_parent (parent_id),
  CONSTRAINT fk_nav_parent FOREIGN KEY (parent_id) REFERENCES navigation (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE media (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  filename VARCHAR(190) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  path VARCHAR(255) NOT NULL,
  mime VARCHAR(60) NOT NULL,
  size INT UNSIGNED NOT NULL DEFAULT 0,
  width INT UNSIGNED NULL,
  height INT UNSIGNED NULL,
  alt_text VARCHAR(255) NULL,
  uploaded_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_media_path (path),
  KEY idx_media_created (created_at),
  CONSTRAINT fk_media_admin FOREIGN KEY (uploaded_by) REFERENCES admins (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pages (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(200) NOT NULL,
  slug VARCHAR(150) NOT NULL,
  template ENUM('default','about','legal','landing') NOT NULL DEFAULT 'default',
  hero_eyebrow VARCHAR(120) NULL,
  hero_title VARCHAR(255) NULL,
  hero_subtitle TEXT NULL,
  featured_image VARCHAR(255) NULL,
  content MEDIUMTEXT NULL,
  blocks MEDIUMTEXT NULL,
  status ENUM('draft','published') NOT NULL DEFAULT 'draft',
  in_sitemap TINYINT(1) NOT NULL DEFAULT 1,
  meta_title VARCHAR(200) NULL,
  meta_description VARCHAR(320) NULL,
  og_image VARCHAR(255) NULL,
  canonical_url VARCHAR(500) NULL,
  noindex TINYINT(1) NOT NULL DEFAULT 0,
  is_system TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_pages_slug (slug),
  KEY idx_pages_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE homepage_sections (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  section_key VARCHAR(40) NOT NULL,
  label VARCHAR(80) NOT NULL,
  is_enabled TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  eyebrow VARCHAR(120) NULL,
  heading VARCHAR(255) NULL,
  subheading TEXT NULL,
  body MEDIUMTEXT NULL,
  image VARCHAR(255) NULL,
  background ENUM('default','muted','dark','brand','image') NOT NULL DEFAULT 'default',
  background_image VARCHAR(255) NULL,
  cta_label VARCHAR(80) NULL,
  cta_url VARCHAR(500) NULL,
  cta2_label VARCHAR(80) NULL,
  cta2_url VARCHAR(500) NULL,
  items MEDIUMTEXT NULL,
  options TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_home_key (section_key),
  KEY idx_home_order (is_enabled, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE services (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(200) NOT NULL,
  slug VARCHAR(150) NOT NULL,
  icon VARCHAR(40) NULL,
  thumbnail VARCHAR(255) NULL,
  hero_image VARCHAR(255) NULL,
  short_description TEXT NULL,
  full_description MEDIUMTEXT NULL,
  benefits MEDIUMTEXT NULL,
  process MEDIUMTEXT NULL,
  faqs MEDIUMTEXT NULL,
  cta_label VARCHAR(80) NULL,
  cta_url VARCHAR(500) NULL,
  meta_title VARCHAR(200) NULL,
  meta_description VARCHAR(320) NULL,
  og_image VARCHAR(255) NULL,
  status ENUM('draft','published') NOT NULL DEFAULT 'draft',
  is_featured TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_services_slug (slug),
  KEY idx_services_status (status, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE blog_categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(150) NOT NULL,
  description TEXT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_blog_cat_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE blog_tags (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(80) NOT NULL,
  slug VARCHAR(100) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_blog_tag_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE blog_posts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  category_id INT UNSIGNED NULL,
  author_id INT UNSIGNED NULL,
  author_name VARCHAR(120) NULL,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(190) NOT NULL,
  excerpt TEXT NULL,
  content MEDIUMTEXT NULL,
  featured_image VARCHAR(255) NULL,
  status ENUM('draft','published','scheduled') NOT NULL DEFAULT 'draft',
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  published_at DATETIME NULL,
  meta_title VARCHAR(200) NULL,
  meta_description VARCHAR(320) NULL,
  og_image VARCHAR(255) NULL,
  views INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_blog_slug (slug),
  KEY idx_blog_public (status, published_at),
  KEY idx_blog_category (category_id),
  KEY idx_blog_featured (is_featured),
  CONSTRAINT fk_blog_category FOREIGN KEY (category_id) REFERENCES blog_categories (id) ON DELETE SET NULL,
  CONSTRAINT fk_blog_author FOREIGN KEY (author_id) REFERENCES admins (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE blog_post_tags (
  post_id INT UNSIGNED NOT NULL,
  tag_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (post_id, tag_id),
  KEY idx_bpt_tag (tag_id),
  CONSTRAINT fk_bpt_post FOREIGN KEY (post_id) REFERENCES blog_posts (id) ON DELETE CASCADE,
  CONSTRAINT fk_bpt_tag FOREIGN KEY (tag_id) REFERENCES blog_tags (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE testimonials (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  designation VARCHAR(120) NULL,
  company VARCHAR(120) NULL,
  photo VARCHAR(255) NULL,
  message TEXT NOT NULL,
  rating TINYINT UNSIGNED NULL,
  is_demo TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('draft','published') NOT NULL DEFAULT 'draft',
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_testimonials_status (status, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE faqs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  question VARCHAR(255) NOT NULL,
  answer TEXT NOT NULL,
  category VARCHAR(60) NOT NULL DEFAULT 'general',
  status ENUM('draft','published') NOT NULL DEFAULT 'published',
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_faqs_status (status, category, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE process_steps (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  step_number VARCHAR(10) NULL,
  title VARCHAR(150) NOT NULL,
  description TEXT NULL,
  icon VARCHAR(40) NULL,
  image VARCHAR(255) NULL,
  status ENUM('draft','published') NOT NULL DEFAULT 'published',
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_process_status (status, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE custom_sections (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  placement VARCHAR(40) NOT NULL DEFAULT 'home',
  layout ENUM('text','split','banner') NOT NULL DEFAULT 'split',
  eyebrow VARCHAR(120) NULL,
  heading VARCHAR(255) NULL,
  content MEDIUMTEXT NULL,
  image VARCHAR(255) NULL,
  background ENUM('default','muted','dark','brand') NOT NULL DEFAULT 'default',
  cta_label VARCHAR(80) NULL,
  cta_url VARCHAR(500) NULL,
  status ENUM('draft','published') NOT NULL DEFAULT 'draft',
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_custom_sections (placement, status, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE contact_messages (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(40) NULL,
  subject VARCHAR(200) NULL,
  message TEXT NOT NULL,
  status ENUM('new','read','replied','archived') NOT NULL DEFAULT 'new',
  email_status ENUM('pending','sent','failed','disabled') NOT NULL DEFAULT 'pending',
  email_error VARCHAR(400) NULL,
  ip VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_messages_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE email_templates (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  template_key VARCHAR(60) NOT NULL,
  name VARCHAR(120) NOT NULL,
  description VARCHAR(255) NULL,
  subject VARCHAR(255) NOT NULL,
  body MEDIUMTEXT NOT NULL,
  is_enabled TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_email_templates_key (template_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE email_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  template_key VARCHAR(60) NULL,
  recipient VARCHAR(190) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  status ENUM('sent','failed','disabled') NOT NULL,
  error VARCHAR(400) NULL,
  related_type VARCHAR(30) NULL,
  related_id INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_email_logs_created (created_at),
  KEY idx_email_logs_related (related_type, related_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE smtp_settings (
  id TINYINT UNSIGNED NOT NULL,
  host VARCHAR(190) NOT NULL DEFAULT '',
  port SMALLINT UNSIGNED NOT NULL DEFAULT 587,
  username VARCHAR(190) NOT NULL DEFAULT '',
  password_enc TEXT NULL,
  encryption ENUM('none','ssl','tls') NOT NULL DEFAULT 'tls',
  from_email VARCHAR(190) NOT NULL DEFAULT '',
  from_name VARCHAR(120) NOT NULL DEFAULT '',
  reply_to VARCHAR(190) NOT NULL DEFAULT '',
  is_enabled TINYINT(1) NOT NULL DEFAULT 0,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE activity_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  admin_id INT UNSIGNED NULL,
  action VARCHAR(50) NOT NULL,
  module VARCHAR(50) NOT NULL,
  record_id INT UNSIGNED NULL,
  details VARCHAR(500) NULL,
  ip VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_activity_created (created_at),
  KEY idx_activity_module (module, created_at),
  KEY idx_activity_admin (admin_id),
  CONSTRAINT fk_activity_admin FOREIGN KEY (admin_id) REFERENCES admins (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------------------------
-- Trading platform: members (website users), trading data, subscriptions and payments.
-- Money values use DECIMAL, never floating point. All timestamps are stored in UTC.
-- ---------------------------------------------------------------------------------------------

CREATE TABLE plans (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(80) NOT NULL,
  slug VARCHAR(80) NOT NULL,
  tagline VARCHAR(200) NULL,
  price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  currency CHAR(3) NOT NULL DEFAULT 'USD',
  interval_days SMALLINT UNSIGNED NOT NULL DEFAULT 30,
  interval_label VARCHAR(30) NOT NULL DEFAULT 'month',
  features TEXT NULL,
  allow_live TINYINT(1) NOT NULL DEFAULT 1,
  max_live_accounts SMALLINT UNSIGNED NOT NULL DEFAULT 3,
  ai_daily_limit SMALLINT UNSIGNED NOT NULL DEFAULT 50,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_plans_slug (slug),
  KEY idx_plans_active (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email VARCHAR(190) NOT NULL,
  name VARCHAR(120) NOT NULL,
  avatar_url VARCHAR(500) NULL,
  google_sub VARCHAR(64) NULL,
  password_hash VARCHAR(255) NULL,
  email_verified TINYINT(1) NOT NULL DEFAULT 0,
  signup_method ENUM('google','email') NOT NULL,
  status ENUM('active','suspended') NOT NULL DEFAULT 'active',
  plan_id INT UNSIGNED NULL,
  plan_expires_at DATETIME NULL,
  onboarded TINYINT(1) NOT NULL DEFAULT 0,
  primary_markets VARCHAR(120) NULL,
  signup_ip VARCHAR(45) NULL,
  last_login_at DATETIME NULL,
  last_login_ip VARCHAR(45) NULL,
  login_count INT UNSIGNED NOT NULL DEFAULT 0,
  admin_note TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  UNIQUE KEY uq_users_google (google_sub),
  KEY idx_users_created (created_at),
  KEY idx_users_plan (plan_id, plan_expires_at),
  KEY idx_users_last_login (last_login_at),
  CONSTRAINT fk_users_plan FOREIGN KEY (plan_id) REFERENCES plans (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_settings (
  user_id INT UNSIGNED NOT NULL,
  theme VARCHAR(30) NOT NULL DEFAULT 'dark-terminal',
  language VARCHAR(5) NOT NULL DEFAULT 'en',
  timezone VARCHAR(64) NOT NULL DEFAULT 'UTC',
  base_currency CHAR(3) NOT NULL DEFAULT 'USD',
  default_risk_pct DECIMAL(5,2) NOT NULL DEFAULT 1.00,
  max_daily_loss DECIMAL(14,2) NULL,
  max_weekly_loss DECIMAL(14,2) NULL,
  default_target_rr DECIMAL(6,2) NOT NULL DEFAULT 2.00,
  tilt_loss_count TINYINT UNSIGNED NOT NULL DEFAULT 3,
  tilt_window_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 20,
  tilt_cooldown_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 30,
  active_account_id INT UNSIGNED NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  CONSTRAINT fk_user_settings_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_logins (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NULL,
  email VARCHAR(190) NULL,
  method ENUM('google','email','signup_google','signup_email','reset') NOT NULL,
  success TINYINT(1) NOT NULL DEFAULT 1,
  reason VARCHAR(40) NULL,
  ip VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_user_logins_user (user_id, created_at),
  KEY idx_user_logins_created (created_at),
  CONSTRAINT fk_user_logins_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_resets (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_password_resets_token (token_hash),
  KEY idx_password_resets_user (user_id),
  CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_audit_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NULL,
  action VARCHAR(50) NOT NULL,
  details VARCHAR(500) NULL,
  ip VARCHAR(45) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_user_audit_user (user_id, created_at),
  CONSTRAINT fk_user_audit_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE trading_accounts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  name VARCHAR(80) NOT NULL,
  broker_name VARCHAR(80) NULL,
  account_type ENUM('PERSONAL','PROP_CHALLENGE','PROP_FUNDED','OTHER') NOT NULL DEFAULT 'PERSONAL',
  currency CHAR(3) NOT NULL DEFAULT 'USD',
  starting_capital DECIMAL(16,2) NOT NULL DEFAULT 0.00,
  is_demo TINYINT(1) NOT NULL DEFAULT 1,
  has_demo_data TINYINT(1) NOT NULL DEFAULT 0,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_accounts_user (user_id, is_archived),
  CONSTRAINT fk_accounts_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE capital_transactions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  account_id INT UNSIGNED NOT NULL,
  type ENUM('DEPOSIT','WITHDRAWAL','ADJUSTMENT') NOT NULL,
  amount DECIMAL(16,2) NOT NULL,
  note VARCHAR(255) NULL,
  occurred_at DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_capital_account (account_id, occurred_at),
  KEY idx_capital_user (user_id),
  CONSTRAINT fk_capital_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_capital_account FOREIGN KEY (account_id) REFERENCES trading_accounts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE strategies (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  name VARCHAR(80) NOT NULL,
  description VARCHAR(500) NULL,
  target_rr DECIMAL(6,2) NULL,
  checklist TEXT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_strategies_user_name (user_id, name),
  CONSTRAINT fk_strategies_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE trades (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  account_id INT UNSIGNED NOT NULL,
  strategy_id INT UNSIGNED NULL,
  executed_at DATETIME NOT NULL,
  closed_at DATETIME NULL,
  symbol VARCHAR(20) NOT NULL,
  asset_class VARCHAR(20) NOT NULL,
  side ENUM('LONG','SHORT') NOT NULL,
  status ENUM('OPEN','CLOSED') NOT NULL DEFAULT 'CLOSED',
  entry_price DECIMAL(20,8) NOT NULL,
  exit_price DECIMAL(20,8) NULL,
  stop_loss DECIMAL(20,8) NULL,
  take_profit DECIMAL(20,8) NULL,
  lot_size DECIMAL(16,4) NOT NULL,
  fees DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  pnl DECIMAL(16,2) NULL,
  pnl_override TINYINT(1) NOT NULL DEFAULT 0,
  rr DECIMAL(10,4) NULL,
  risk_amount DECIMAL(16,2) NULL,
  setup_tag VARCHAR(120) NULL,
  session VARCHAR(20) NULL,
  emotion VARCHAR(20) NULL,
  mistake_tag VARCHAR(20) NOT NULL DEFAULT 'NONE',
  rules_followed TINYINT(1) NOT NULL DEFAULT 1,
  notes TEXT NULL,
  screenshot_path VARCHAR(255) NULL,
  source VARCHAR(20) NOT NULL DEFAULT 'MANUAL',
  broker_trade_id VARCHAR(80) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_trades_user_time (user_id, executed_at),
  KEY idx_trades_account_time (account_id, executed_at),
  KEY idx_trades_symbol (user_id, symbol),
  KEY idx_trades_strategy (strategy_id),
  UNIQUE KEY uq_trades_broker (account_id, broker_trade_id),
  CONSTRAINT fk_trades_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_trades_account FOREIGN KEY (account_id) REFERENCES trading_accounts (id) ON DELETE CASCADE,
  CONSTRAINT fk_trades_strategy FOREIGN KEY (strategy_id) REFERENCES strategies (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE journal_entries (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  account_id INT UNSIGNED NULL,
  journal_date DATE NOT NULL,
  is_demo TINYINT(1) NOT NULL DEFAULT 0,
  compliance TINYINT UNSIGNED NULL,
  emotional_state VARCHAR(20) NULL,
  discipline_rating TINYINT UNSIGNED NULL,
  reflection TEXT NULL,
  key_lesson VARCHAR(500) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_journal_user_date (user_id, journal_date, is_demo),
  CONSTRAINT fk_journal_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE checklist_entries (
  user_id INT UNSIGNED NOT NULL,
  entry_date DATE NOT NULL,
  item_key VARCHAR(40) NOT NULL,
  completed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, entry_date, item_key),
  CONSTRAINT fk_checklist_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ai_conversations (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  title VARCHAR(160) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_ai_conv_user (user_id, updated_at),
  CONSTRAINT fk_ai_conv_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ai_messages (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  conversation_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  role ENUM('user','assistant') NOT NULL,
  content MEDIUMTEXT NOT NULL,
  model VARCHAR(80) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_ai_msg_conv (conversation_id, id),
  KEY idx_ai_msg_user_time (user_id, role, created_at),
  CONSTRAINT fk_ai_msg_conv FOREIGN KEY (conversation_id) REFERENCES ai_conversations (id) ON DELETE CASCADE,
  CONSTRAINT fk_ai_msg_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE broker_connections (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  account_id INT UNSIGNED NOT NULL,
  provider VARCHAR(30) NOT NULL,
  label VARCHAR(80) NOT NULL,
  public_id CHAR(24) NOT NULL,
  secret_enc TEXT NULL,
  status ENUM('active','disabled') NOT NULL DEFAULT 'active',
  last_event_at DATETIME NULL,
  events_count INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_broker_public (public_id),
  KEY idx_broker_user (user_id),
  CONSTRAINT fk_broker_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_broker_account FOREIGN KEY (account_id) REFERENCES trading_accounts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE webhook_events (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  connection_id INT UNSIGNED NOT NULL,
  event_id VARCHAR(100) NOT NULL,
  status VARCHAR(20) NOT NULL,
  message VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_webhook_event (connection_id, event_id),
  CONSTRAINT fk_webhook_conn FOREIGN KEY (connection_id) REFERENCES broker_connections (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payments (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NULL,
  plan_id INT UNSIGNED NULL,
  gateway ENUM('razorpay','stripe','manual') NOT NULL,
  gateway_order_id VARCHAR(120) NULL,
  gateway_payment_id VARCHAR(120) NULL,
  amount DECIMAL(12,2) NOT NULL,
  currency CHAR(3) NOT NULL,
  status ENUM('created','paid','failed','refunded') NOT NULL DEFAULT 'created',
  period_days SMALLINT UNSIGNED NOT NULL DEFAULT 30,
  access_until DATETIME NULL,
  customer_email VARCHAR(190) NULL,
  note VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  paid_at DATETIME NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_payments_order (gateway, gateway_order_id),
  KEY idx_payments_user (user_id, created_at),
  KEY idx_payments_status (status, created_at),
  CONSTRAINT fk_payments_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL,
  CONSTRAINT fk_payments_plan FOREIGN KEY (plan_id) REFERENCES plans (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE market_cache (
  cache_key VARCHAR(100) NOT NULL,
  payload MEDIUMTEXT NOT NULL,
  expires_at DATETIME NOT NULL,
  PRIMARY KEY (cache_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
-- ---------------------------------------------------------------------------------------------
-- Starter content. Everything below is editable in the Control Panel.
-- Items marked [Demo] or [Replace] are placeholders and must not be presented as real facts.
-- ---------------------------------------------------------------------------------------------

INSERT INTO `roles` (`id`, `name`, `slug`, `description`, `permissions`, `is_system`) VALUES
(1, 'Super Admin', 'super-admin', 'Full access to every module, including SMTP, users, roles and system settings.', '["*"]', 1),
(2, 'Editor', 'editor', 'Manages website content and media, and can view members. No access to SMTP credentials, API keys, payments, admin users, roles or system settings.', '["pages","services","blog","testimonials","faqs","process","sections","homepage","navigation","media","messages","members.view"]', 1);

INSERT INTO `settings` (`key`, `value`, `group_name`) VALUES
('site_name', 'journzey.ai', 'general'),
('tagline', 'Institutional multi-broker trading journal & AI discipline terminal', 'general'),
('logo_desktop', '', 'general'),
('logo_mobile', '', 'general'),
('logo_light', '', 'general'),
('favicon', '', 'general'),
('contact_phone', '', 'general'),
('contact_whatsapp', '', 'general'),
('contact_email', '', 'general'),
('contact_address', '', 'general'),
('business_hours', '', 'general'),
('timezone', 'UTC', 'general'),
('copyright_text', '© {year} journzey.ai. All rights reserved.', 'general'),
('map_embed_url', '', 'general'),
('media_max_mb', '5', 'general'),
('maintenance_mode', '0', 'general'),
('maintenance_message', 'We are making improvements and will be back shortly.', 'general'),
('header_cta_enabled', '1', 'header'),
('header_cta_label', 'Start free', 'header'),
('header_cta_url', '/signup', 'header'),
('header_secondary_label', 'Sign in', 'header'),
('header_secondary_url', '/login', 'header'),
('header_sticky', '1', 'header'),
('header_transparent_home', '1', 'header'),
('announcement_enabled', '0', 'header'),
('announcement_text', '', 'header'),
('announcement_url', '', 'header'),
('footer_about', 'journzey.ai is a trading journal and discipline terminal that brings every broker account into one place, measures execution against your own rules and turns your history into clear feedback.', 'footer'),
('footer_col1_title', 'Platform', 'footer'),
('footer_col2_title', 'Company', 'footer'),
('footer_col3_title', 'Legal', 'footer'),
('footer_show_services', '1', 'footer'),
('footer_show_contact', '1', 'footer'),
('footer_show_social', '1', 'footer'),
('footer_cta_enabled', '1', 'footer'),
('footer_cta_heading', 'See your trading clearly.', 'footer'),
('footer_cta_text', 'Create a free account, load the demo journal and explore every tool. Upgrade when you are ready to track live accounts.', 'footer'),
('footer_cta_label', 'Start free', 'footer'),
('footer_cta_url', '/signup', 'footer'),
('footer_disclaimer', 'Trading financial instruments involves substantial risk and is not suitable for every investor. journzey.ai is a journaling and analytics tool; it does not provide investment advice, signals or brokerage services. Past performance does not guarantee future results.', 'footer'),
('social_x', '', 'social'),
('social_linkedin', '', 'social'),
('social_youtube', '', 'social'),
('social_instagram', '', 'social'),
('social_facebook', '', 'social'),
('social_discord', '', 'social'),
('social_telegram', '', 'social'),
('social_github', '', 'social'),
('whatsapp_enabled', '0', 'whatsapp'),
('whatsapp_number', '', 'whatsapp'),
('whatsapp_message', 'Hi journzey.ai team, I would like to know more about the platform.', 'whatsapp'),
('whatsapp_position', 'right', 'whatsapp'),
('whatsapp_mobile', '1', 'whatsapp'),
('whatsapp_desktop', '1', 'whatsapp'),
('whatsapp_label', 'Chat with us', 'whatsapp'),
('seo_default_title', 'journzey.ai — Multi-Broker Trading Journal & AI Discipline Terminal', 'seo'),
('seo_default_description', 'Bring every broker account into one trading journal, track your rules and risk, and get objective AI feedback on your execution with journzey.ai.', 'seo'),
('seo_title_separator', '|', 'seo'),
('seo_canonical_base', '', 'seo'),
('seo_robots', 'index, follow', 'seo'),
('seo_allow_indexing', '1', 'seo'),
('seo_og_title', '', 'seo'),
('seo_og_description', '', 'seo'),
('seo_og_image', '', 'seo'),
('seo_twitter_card', 'summary_large_image', 'seo'),
('seo_twitter_site', '', 'seo'),
('seo_schema_enabled', '1', 'seo'),
('seo_local_business', '0', 'seo'),
('seo_robots_extra', '', 'seo'),
('seo_home_title', '', 'seo'),
('seo_home_description', '', 'seo'),
('seo_services_title', 'Platform capabilities', 'seo'),
('seo_services_description', 'Explore the journzey.ai trading journal, performance analytics, risk and rules engine, AI discipline coach and trade review tools.', 'seo'),
('seo_blog_title', 'Blog', 'seo'),
('seo_blog_description', 'Practical articles on trading journals, risk management, review routines and trading psychology.', 'seo'),
('seo_contact_title', 'Contact', 'seo'),
('seo_contact_description', 'Get in touch with the journzey.ai team.', 'seo'),
('seo_pricing_title', 'Pricing', 'seo'),
('seo_pricing_description', 'Start free with a demo trading journal. Upgrade to track live accounts with the full journzey.ai terminal.', 'seo'),
('google_client_id', '', 'integrations'),
('google_client_secret', '', 'integrations'),
('payment_gateway', 'none', 'integrations'),
('payment_currency_note', '', 'integrations'),
('razorpay_key_id', '', 'integrations'),
('razorpay_key_secret', '', 'integrations'),
('razorpay_webhook_secret', '', 'integrations'),
('stripe_secret_key', '', 'integrations'),
('stripe_webhook_secret', '', 'integrations'),
('ai_provider', 'none', 'integrations'),
('ai_api_key', '', 'integrations'),
('ai_model', '', 'integrations'),
('market_data_api_key', '', 'integrations'),
('ga_enabled', '0', 'analytics'),
('ga_id', '', 'analytics'),
('gtm_enabled', '0', 'analytics'),
('gtm_id', '', 'analytics'),
('pixel_enabled', '0', 'analytics'),
('pixel_id', '', 'analytics'),
('gsc_enabled', '0', 'analytics'),
('gsc_code', '', 'analytics'),
('color_primary', '#7c5cff', 'appearance'),
('color_secondary', '#22d3ee', 'appearance'),
('color_accent', '#34d399', 'appearance'),
('color_text', '#c9cfdd', 'appearance'),
('color_heading', '#ffffff', 'appearance'),
('color_muted', '#8f99ae', 'appearance'),
('color_bg', '#070a12', 'appearance'),
('color_surface', '#0d1220', 'appearance'),
('color_border', '#1c2436', 'appearance'),
('font_heading', 'Space Grotesk', 'appearance'),
('font_body', 'Inter', 'appearance'),
('font_base_size', '16', 'appearance'),
('heading_weight', '600', 'appearance'),
('button_style', 'pill', 'appearance'),
('radius', '16', 'appearance'),
('button_glow', '1', 'appearance'),
('button_uppercase', '0', 'appearance'),
('container_width', '1200', 'appearance'),
('section_spacing', 'normal', 'appearance'),
('animations_enabled', '1', 'appearance'),
('card_style', 'glass', 'appearance'),
('custom_css', '', 'appearance'),
('services_eyebrow', 'Platform', 'pages'),
('services_heading', 'Capabilities built for disciplined trading', 'pages'),
('services_intro', 'Every part of journzey.ai is designed around one idea: decisions improve when they are measured honestly. Explore what the platform does.', 'pages'),
('blog_eyebrow', 'Journal', 'pages'),
('blog_heading', 'Insights on process, risk and review', 'pages'),
('blog_intro', 'Practical, no-hype articles on building a trading process you can measure and improve.', 'pages'),
('contact_eyebrow', 'Contact', 'pages'),
('contact_heading', 'Talk to the journzey.ai team', 'pages'),
('contact_intro', 'Questions about the platform, onboarding or your account? Send us a message and we will reply by email.', 'pages'),
('contact_success', 'Thank you — your message has been received. We will reply by email as soon as we can.', 'pages'),
('pricing_eyebrow', 'Pricing', 'pages'),
('pricing_heading', 'Start free. Upgrade when you go live.', 'pages'),
('pricing_intro', 'Every account starts free with demo mode: log test trades, load the demo journal and try every analytics tool. Paid plans unlock live trading accounts.', 'pages'),
('free_plan_name', 'Free (Demo)', 'pages'),
('free_plan_features', 'Unlimited demo accounts and test trades\nLoad the 40-trade demo journal\nDashboard, calendar and Edge Matrix on demo data\nAI Coach: 3 messages per day', 'pages'),
('free_ai_daily_limit', '3', 'pages'),
('signup_enabled', '1', 'pages'),
('email_signup_enabled', '1', 'pages'),
('notify_email', '', 'pages'),
('contact_send_confirmation', '1', 'pages');

INSERT INTO `services` (`title`, `slug`, `icon`, `thumbnail`, `hero_image`, `short_description`, `full_description`, `benefits`, `process`, `faqs`, `cta_label`, `cta_url`, `meta_title`, `meta_description`, `og_image`, `status`, `is_featured`, `sort_order`) VALUES
('Multi-Broker Trade Journal', 'multi-broker-trade-journal', 'journal', '', '', 'Bring trades from every broker account into a single, structured journal — with setups, tags, notes and screenshots attached to each trade.', '<p>Most traders keep their history scattered across broker statements, spreadsheets and memory. The journzey.ai journal gives every account a single home, so you can review your trading as one coherent record instead of disconnected fragments.</p><h2>What you can capture</h2><ul><li>Entries, exits, size, fees and outcome for every trade</li><li>The setup, timeframe and market conditions you traded</li><li>Your pre-trade plan and post-trade reflection</li><li>Chart screenshots and free-form notes</li></ul><p>Accounts stay separate where it matters and combined where it helps, so you can compare a funded account with a personal one without mixing up the numbers.</p>', '[{"title":"One record for all accounts","text":"Review every broker account side by side or combined."},{"title":"Structured, searchable history","text":"Filter by setup, tag, session, symbol or account."},{"title":"Context that survives","text":"Plans, notes and screenshots stay attached to the trade."}]', '[{"title":"Add or import","text":"Add your accounts manually and bring in your trade history from a CSV export."},{"title":"Tag and annotate","text":"Label setups and add notes and screenshots."},{"title":"Review","text":"Use filters and analytics to study your record."}]', '[{"question":"Can I keep accounts separate?","answer":"Yes. Each account keeps its own history and you can choose to view them individually or combined."},{"question":"Which brokers are supported?","answer":"Any broker. You add accounts manually and log trades by hand, by quick command or by voice, or import a CSV history export (MT4/MT5, cTrader, NinjaTrader). There is no live broker synchronisation."}]', 'Start free', '/signup', '', 'Bring trades from every broker account into a single, structured journal — with setups, tags, notes and screenshots attached to each trade.', '', 'published', 1, 10),
('Performance Analytics', 'performance-analytics', 'chart', '', '', 'Measure what actually drives your results: expectancy, R-multiples, drawdown and performance by setup, session and instrument.', '<p>P&amp;L alone hides more than it reveals. journzey.ai breaks your results down into the measurements that explain them, so you can see which setups carry your performance and which quietly drain it.</p><h2>Key views</h2><ul><li>Expectancy and average R per trade</li><li>Win rate alongside average win and loss size</li><li>Equity curve and drawdown</li><li>Breakdowns by setup, tag, weekday, session and symbol</li></ul>', '[{"title":"Expectancy, not just P&L","text":"Understand the edge behind each strategy."},{"title":"Drill into any slice","text":"Compare setups, sessions and instruments."},{"title":"Spot leaks early","text":"See where losses concentrate before they compound."}]', '[{"title":"Collect","text":"Your journal feeds analytics automatically."},{"title":"Slice","text":"Filter by any dimension you track."},{"title":"Act","text":"Turn findings into rules for the next session."}]', '[{"question":"Do I need to calculate R-multiples myself?","answer":"No. When a trade includes its planned stop, the R-multiple is derived from it."}]', 'Start free', '/signup', '', 'Measure what actually drives your results: expectancy, R-multiples, drawdown and performance by setup, session and instrument.', '', 'published', 1, 20),
('Risk & Rules Engine', 'risk-and-rules-engine', 'shield', '', '', 'Write down the rules you trade by — daily loss limits, maximum trades, position sizing — and see every time a trade breaks them.', '<p>Every trader has rules. Few have a record of how often they follow them. The rules engine lets you define your own limits and highlights the trades and days that breached them, so discipline becomes something you can measure.</p><h2>Typical rules</h2><ul><li>Maximum daily or weekly loss</li><li>Maximum number of trades per session</li><li>Risk per trade as a percentage of the account</li><li>Allowed sessions, instruments or setups</li></ul>', '[{"title":"Your rules, written down","text":"Turn your trading plan into explicit, checkable limits."},{"title":"Breaches made visible","text":"See exactly which trades or days broke a rule."},{"title":"Discipline over time","text":"Track how consistently you follow your plan."}]', '[{"title":"Define","text":"Set limits that match your plan."},{"title":"Trade","text":"Each trade is checked against your rules."},{"title":"Review","text":"Study breaches and adjust your process."}]', '[]', 'Start free', '/signup', '', 'Write down the rules you trade by — daily loss limits, maximum trades, position sizing — and see every time a trade breaks them.', '', 'published', 1, 30),
('AI Discipline Coach', 'ai-discipline-coach', 'brain', '', '', 'Objective, unemotional feedback on your execution — patterns in your behaviour surfaced from your own journal data.', '<p>It is hard to judge your own trading objectively. The AI discipline coach reviews your journal and highlights behavioural patterns — for example trading more after a loss, cutting winners early or drifting from your planned setups.</p><p>The coach works only from your own records and rules. It does not generate trade signals or financial advice; its job is to help you see your process clearly.</p>', '[{"title":"Pattern detection","text":"Surfaces recurring behaviours across many trades."},{"title":"Grounded in your data","text":"Feedback is based on your journal and your rules."},{"title":"No signals, no hype","text":"A review tool, not a prediction engine."}]', '[{"title":"Journal","text":"Log trades with plans and reflections."},{"title":"Analyse","text":"The coach reviews patterns in your history."},{"title":"Adjust","text":"Apply the feedback to your next sessions."}]', '[{"question":"Does the AI tell me what to trade?","answer":"No. It reviews your behaviour and process. It does not provide trade signals or investment advice."}]', 'Start free', '/signup', '', 'Objective, unemotional feedback on your execution — patterns in your behaviour surfaced from your own journal data.', '', 'published', 1, 40),
('Trade Review & Playbooks', 'trade-review-and-playbooks', 'camera', '', '', 'Build a playbook of your best setups with annotated examples, and run structured daily and weekly reviews.', '<p>A playbook turns experience into a repeatable process. Capture your setups with clear criteria and real examples from your journal, then use structured reviews to compare new trades against the standard you set.</p><ul><li>Setup definitions with entry and invalidation criteria</li><li>Annotated screenshots of A-grade examples</li><li>Daily and weekly review checklists</li></ul>', '[{"title":"Repeatable setups","text":"Define what a valid trade looks like before you take it."},{"title":"Visual examples","text":"Keep your best examples one click away."},{"title":"Review routine","text":"Make reflection a habit, not an afterthought."}]', '[]', '[]', 'Start free', '/signup', '', 'Build a playbook of your best setups with annotated examples, and run structured daily and weekly reviews.', '', 'published', 1, 50);

INSERT INTO `navigation` (`id`, `location`, `parent_id`, `label`, `url`, `target`, `sort_order`, `is_enabled`) VALUES
(1, 'header', NULL, 'Platform', '/services', '_self', 10, 1),
(2, 'header', 1, 'Multi-Broker Trade Journal', '/services/multi-broker-trade-journal', '_self', 11, 1),
(3, 'header', 1, 'Performance Analytics', '/services/performance-analytics', '_self', 12, 1),
(4, 'header', 1, 'Risk & Rules Engine', '/services/risk-and-rules-engine', '_self', 13, 1),
(5, 'header', 1, 'AI Discipline Coach', '/services/ai-discipline-coach', '_self', 14, 1),
(6, 'header', 1, 'Trade Review & Playbooks', '/services/trade-review-and-playbooks', '_self', 15, 1),
(7, 'header', NULL, 'About', '/about', '_self', 20, 1),
(10, 'header', NULL, 'Pricing', '/pricing', '_self', 25, 1),
(8, 'header', NULL, 'Blog', '/blog', '_self', 30, 1),
(9, 'header', NULL, 'Contact', '/contact', '_self', 40, 1),
(20, 'footer_2', NULL, 'About', '/about', '_self', 10, 1),
(21, 'footer_2', NULL, 'Blog', '/blog', '_self', 20, 1),
(22, 'footer_2', NULL, 'Contact', '/contact', '_self', 30, 1),
(23, 'footer_2', NULL, 'Pricing', '/pricing', '_self', 40, 1),
(24, 'footer_2', NULL, 'Sign in', '/login', '_self', 50, 1),
(30, 'footer_3', NULL, 'Privacy Policy', '/privacy-policy', '_self', 10, 1),
(31, 'footer_3', NULL, 'Terms & Conditions', '/terms-and-conditions', '_self', 20, 1),
(32, 'footer_3', NULL, 'Risk Disclaimer', '/disclaimer', '_self', 30, 1),
(33, 'footer_3', NULL, 'Security', '/security', '_self', 40, 1);

INSERT INTO `pages` (`title`, `slug`, `template`, `hero_eyebrow`, `hero_title`, `hero_subtitle`, `featured_image`, `content`, `blocks`, `status`, `in_sitemap`, `meta_title`, `meta_description`, `og_image`, `canonical_url`, `noindex`, `is_system`, `sort_order`) VALUES
('About', 'about', 'about', 'About journzey.ai', 'We help traders run their trading like a professional desk', 'A trading journal and discipline terminal built on one belief: you cannot improve what you do not measure honestly.', '', '', '[{"type":"split","eyebrow":"Our story","heading":"Built for traders who want evidence, not opinions","body":"<p>journzey.ai started from a simple frustration: traders spend hours studying charts and minutes studying themselves. Broker statements show what happened, but not why — and memory is a biased narrator.</p><p>We are building the tool we wanted: one place for every account, honest measurements of execution, and feedback grounded in a trader''s own rules.</p>","image":"","image_side":"right","items":"","cta_label":"","cta_url":"","background":"default"},{"type":"cards","eyebrow":"Direction","heading":"Mission and vision","body":"","image":"","image_side":"right","items":"Mission | Give every trader an institutional-grade record of their decisions and the tools to review them objectively. | target\\nVision | A trading culture where process is measured as carefully as profit and loss. | compass","cta_label":"","cta_url":"","background":"muted"},{"type":"cards","eyebrow":"Values","heading":"What we stand for","body":"","image":"","image_side":"right","items":"Honesty over hype | We never promise profits. We help you see your process clearly. | shield\\nYour data, your edge | Your journal belongs to you and exists to serve your review. | lock\\nSimplicity under pressure | Tools that stay clear when markets are not. | zap\\nContinuous improvement | Small, measured changes compound over time. | trend-up","cta_label":"","cta_url":"","background":"default"},{"type":"process","eyebrow":"How it works","heading":"A review loop you can repeat every week","body":"","image":"","image_side":"right","items":"","cta_label":"","cta_url":"","background":"muted"},{"type":"stats","eyebrow":"","heading":"[Replace] Add verified company figures","body":"<p>This block is hidden until you add real, verifiable figures. Edit or delete it in Control Panel → Pages → About.</p>","image":"","image_side":"right","items":"[Replace] | Your first verified metric\\n[Replace] | Your second verified metric","cta_label":"","cta_url":"","background":"default","hidden":1},{"type":"cta","eyebrow":"","heading":"See the platform for yourself","body":"<p>Create a free account and explore the full terminal with demo data.</p>","image":"","image_side":"right","items":"","cta_label":"Start free","cta_url":"/signup","background":"brand"}]', 'published', 1, 'About', 'Learn why journzey.ai exists and how it helps traders measure and improve their process.', '', '', 0, 1, 10),
('Privacy Policy', 'privacy-policy', 'legal', 'Legal', 'Privacy Policy', 'How we collect, use and protect your information.', '', '<p class="notice"><strong>Template text.</strong> Replace this page with a policy reviewed by a qualified legal professional for your jurisdiction before launch.</p><h2>Who we are</h2><p>This website is operated by journzey.ai ("we", "us"). [Replace with your registered company name and address.]</p><h2>Information we collect</h2><p>When you submit a form on this website we collect the details you provide, such as your name, email address, phone number and message. We also record basic technical information (IP address and browser type) to protect the site against abuse.</p><h2>How we use it</h2><ul><li>To respond to your enquiry or demo request</li><li>To operate, secure and improve this website</li><li>To comply with legal obligations</li></ul><h2>Cookies and analytics</h2><p>This site uses a strictly necessary session cookie for security. If analytics tools are enabled, they may set additional cookies. [Describe the tools you enable.]</p><h2>Retention</h2><p>We keep enquiry records only as long as needed for the purposes above. [State your retention period.]</p><h2>Your rights</h2><p>You may request access to, correction of or deletion of your personal data by contacting us. [Add your privacy contact address.]</p><h2>Changes</h2><p>We may update this policy from time to time. The latest version is always published on this page.</p>', '[]', 'published', 1, 'Privacy Policy', 'How journzey.ai collects, uses and protects personal information.', '', '', 0, 1, 20),
('Terms & Conditions', 'terms-and-conditions', 'legal', 'Legal', 'Terms & Conditions', 'The terms that apply when you use this website.', '', '<p class="notice"><strong>Template text.</strong> Replace this page with a policy reviewed by a qualified legal professional for your jurisdiction before launch.</p><h2>Use of this website</h2><p>By using this website you agree to these terms. If you do not agree, please do not use the site.</p><h2>No investment advice</h2><p>Content on this website and within the journzey.ai platform is provided for information and educational purposes only. It is not investment, financial, legal or tax advice, and nothing here is a recommendation to buy or sell any financial instrument.</p><h2>Risk warning</h2><p>Trading involves substantial risk of loss. You are solely responsible for your trading decisions.</p><h2>Intellectual property</h2><p>The website design, text and software are owned by or licensed to journzey.ai. You may not copy or reuse them without permission.</p><h2>Limitation of liability</h2><p>To the extent permitted by law, we are not liable for losses arising from the use of this website. [Have this clause reviewed for your jurisdiction.]</p><h2>Governing law</h2><p>[Replace with your governing law and jurisdiction.]</p>', '[]', 'published', 1, 'Terms & Conditions', 'Terms and conditions for using the journzey.ai website.', '', '', 0, 1, 30);

INSERT INTO `homepage_sections` (`section_key`, `label`, `is_enabled`, `sort_order`, `eyebrow`, `heading`, `subheading`, `body`, `image`, `background`, `background_image`, `cta_label`, `cta_url`, `cta2_label`, `cta2_url`, `items`, `options`) VALUES
('hero', 'Hero', 1, 10, 'Trading journal · AI discipline terminal', 'Trade your plan. Prove it with data.', 'journzey.ai brings every broker account into one institutional-grade journal, measures your execution against your own rules and turns your history into clear, unemotional feedback.', '', '', 'default', '', 'Start free', '/signup', 'See pricing', '/pricing', '[{"title":"Multi-broker journal","text":"","icon":"layers"},{"title":"Rules & risk tracking","text":"","icon":"shield"},{"title":"AI discipline feedback","text":"","icon":"brain"}]', '{"media_type":"visual","video_url":"","mobile_image":"","overlay_color":"#070a12","overlay_opacity":"55","alignment":"left","animate":"1"}'),
('intro', 'Introduction', 1, 20, 'Why it matters', 'Your broker shows what happened. Your journal should show why.', 'Most trading mistakes are not about analysis — they are about execution and discipline. journzey.ai is built to make those patterns visible.', '', '', 'muted', '', '', '', '', '', '[{"title":"Journal","text":"Capture every trade with the plan, context and reflection behind it.","icon":"journal"},{"title":"Measure","text":"See the statistics that explain your results, not just the P&L.","icon":"bars"},{"title":"Improve","text":"Turn findings into rules and check whether you follow them.","icon":"trend-up"}]', '{}'),
('about', 'About', 1, 30, 'The approach', 'Discipline is a process, not a personality trait', '', '<p>Consistency comes from a repeatable loop: plan the trade, execute the plan, review the outcome honestly and adjust. journzey.ai gives that loop structure — from the first fill to the weekly review.</p><p>No signals, no promises. Just a clear record of your decisions and the tools to learn from them.</p>', '', 'default', '', 'About journzey.ai', '/about', '', '', '[]', '{}'),
('services', 'Services', 1, 40, 'Platform', 'Everything you need to run your trading like a desk', 'Five connected capabilities that turn raw trade history into better decisions.', '', '', 'default', '', 'View all capabilities', '/services', '', '', '[]', '{"limit":"6"}'),
('benefits', 'Why journzey.ai', 1, 50, 'Why a journal', 'What changes when you measure your process', '', '', '', 'muted', '', '', '', '', '', '[{"title":"Objective feedback","text":"Replace gut feel about your trading with numbers you can check.","icon":"scale"},{"title":"Every account in one view","text":"Stop reconciling spreadsheets across brokers and platforms.","icon":"layers"},{"title":"Rules you can verify","text":"Know how often you actually follow your trading plan.","icon":"shield"},{"title":"Patterns surfaced early","text":"Spot behavioural leaks before they become expensive habits.","icon":"eye"},{"title":"A review routine","text":"Make daily and weekly reviews quick enough to keep doing.","icon":"calendar"},{"title":"Calm under pressure","text":"A clear interface built for focus during and after the session.","icon":"target"}]', '{}'),
('stats', 'Statistics', 0, 60, '', '[Replace] Add verified figures before enabling this section', 'Do not publish statistics you cannot verify.', '', '', 'dark', '', '', '', '', '', '[{"title":"[Replace]","text":"Your first verified metric","icon":""},{"title":"[Replace]","text":"Your second verified metric","icon":""},{"title":"[Replace]","text":"Your third verified metric","icon":""}]', '{}'),
('process', 'Process', 1, 70, 'How it works', 'From raw fills to better decisions', 'A simple loop you can repeat after every session.', '', '', 'default', '', 'Start free', '/signup', '', '', '[]', '{}'),
('featured', 'Featured content', 1, 80, 'From the blog', 'Ideas for a more measurable trading process', '', '', '', 'muted', '', 'Read the blog', '/blog', '', '', '[]', '{"limit":"3"}'),
('testimonials', 'Testimonials', 1, 90, 'Testimonials', 'What traders say', 'Demo testimonials are shown with a "Demo" label until you replace them with real reviews.', '', '', 'default', '', '', '', '', '', '[]', '{"limit":"6"}'),
('pricing', 'Pricing', 1, 100, 'Pricing', 'Simple pricing. Start free.', 'Explore everything in demo mode, then upgrade to journal your live accounts.', '', '', 'muted', '', 'Compare plans', '/pricing', '', '', '[]', '{}'),
('faq', 'FAQ', 1, 110, 'FAQ', 'Frequently asked questions', '', '', '', 'muted', '', 'Ask a question', '/contact', '', '', '[]', '{"category":"general","limit":"8"}'),
('cta', 'Call to action', 1, 120, 'Get started', 'Ready to see your trading clearly?', 'Create a free account in seconds with Google, explore the terminal with demo data, and upgrade when you are ready to journal live accounts.', '', '', 'brand', '', 'Start free', '/signup', 'View pricing', '/pricing', '[]', '{}'),
('contact', 'Contact', 1, 130, 'Contact', 'Talk to the team', 'Questions about the platform or onboarding? We are happy to help.', '', '', 'default', '', 'Send a message', '/contact', '', '', '[]', '{}');

INSERT INTO `process_steps` (`step_number`, `title`, `description`, `icon`, `image`, `status`, `sort_order`) VALUES
('01', 'Bring your accounts together', 'Add each broker account and import your trade history into one journal.', 'link', '', 'published', 10),
('02', 'Journal with context', 'Tag setups and attach your plan, notes and screenshots to each trade.', 'journal', '', 'published', 20),
('03', 'Measure what matters', 'Review expectancy, drawdown and rule adherence by setup, session and account.', 'chart', '', 'published', 30),
('04', 'Improve with feedback', 'Use AI-assisted reviews to spot behavioural patterns and refine your rules.', 'brain', '', 'published', 40);

INSERT INTO `faqs` (`question`, `answer`, `category`, `status`, `sort_order`) VALUES
('What is journzey.ai?', 'journzey.ai is a trading journal and discipline terminal. It brings your trades from multiple broker accounts into one place, measures your performance and rule adherence, and provides objective feedback on your execution.', 'general', 'published', 10),
('Does journzey.ai give trading signals or advice?', 'No. journzey.ai is a journaling and analytics tool. It helps you review your own decisions; it does not provide signals, recommendations or investment advice.', 'general', 'published', 20),
('Can I use it with more than one broker?', 'Yes — the journal is designed for traders with several accounts. Accounts are added manually; trades are logged by hand, by quick command or by voice, or imported from a CSV history export. There is no live broker synchronisation.', 'general', 'published', 30),
('Who is it for?', 'Active traders who want to treat their trading like a professional process — including traders working towards or managing funded accounts and those running several personal accounts.', 'general', 'published', 40),
('How is my data handled?', '[Replace] Describe where data is stored, who can access it and how it is protected. Link to your Privacy Policy for full details.', 'general', 'published', 50),
('How do I get started?', 'Sign up free with Google or email. You can load a demo journal or log test trades in a demo account straight away. Upgrade to a paid plan to add live accounts.', 'general', 'published', 60);

INSERT INTO `testimonials` (`name`, `designation`, `company`, `photo`, `message`, `rating`, `is_demo`, `status`, `sort_order`) VALUES
('Demo Testimonial', 'Sample reviewer', 'Replace in Control Panel', '', '[Demo content] This is a placeholder testimonial. Replace it with a genuine review from a real customer, with their permission, or disable the testimonials section.', NULL, 1, 'published', 10),
('Demo Testimonial', 'Sample reviewer', 'Replace in Control Panel', '', '[Demo content] Placeholder text used to preview the layout. It is not a real customer statement.', NULL, 1, 'published', 20),
('Demo Testimonial', 'Sample reviewer', 'Replace in Control Panel', '', '[Demo content] Add real testimonials under Content → Testimonials and untick “Demo content”.', NULL, 1, 'published', 30);

INSERT INTO `blog_categories` (`id`, `name`, `slug`, `description`, `sort_order`) VALUES
(1, 'Process & Review', 'process-and-review', 'Building and keeping a review routine.', 10),
(2, 'Risk Management', 'risk-management', 'Position sizing, limits and drawdown.', 20),
(3, 'Trading Psychology', 'trading-psychology', 'Behaviour, discipline and decision-making.', 30);

INSERT INTO `blog_tags` (`id`, `name`, `slug`) VALUES
(1, 'Journaling', 'journaling'),
(2, 'Checklists', 'checklists'),
(3, 'R-multiples', 'r-multiples'),
(4, 'Discipline', 'discipline'),
(5, 'Multi-account', 'multi-account');

INSERT INTO `blog_posts` (`id`, `category_id`, `author_id`, `author_name`, `title`, `slug`, `excerpt`, `content`, `featured_image`, `status`, `is_featured`, `published_at`, `meta_title`, `meta_description`, `og_image`) VALUES
(1, 1, NULL, 'journzey.ai Team', 'How to build a pre-trade checklist you will actually use', 'how-to-build-a-pre-trade-checklist', 'A checklist only helps if it is short enough to use under pressure. Here is a practical way to build one from your own trading history.', '<p>Pre-trade checklists fail for one of two reasons: they are too long to use in the moment, or they are written from generic advice rather than your own mistakes. The fix for both is the same — build the checklist from your journal.</p><h2>1. Start from your losses</h2><p>Filter your journal for your largest losing trades and ask a single question of each: what would I have needed to check to avoid this? Write the answers down without editing them.</p><h2>2. Group and reduce</h2><p>You will find the same few causes repeating — entering before confirmation, trading outside your session, sizing up after a loss. Merge similar items until you have five or fewer.</p><h2>3. Make each item binary</h2><p>"Is the market trending?" invites debate. "Is price above the 20-period average on the higher timeframe?" does not. Every item should be answerable with yes or no in seconds.</p><h2>4. Record whether you used it</h2><p>Add a field to your journal for checklist completion. After a few weeks, compare results for trades taken with and without a complete checklist. The data will tell you whether the checklist is working.</p><blockquote>A checklist is a hypothesis about what makes a good trade. Your journal is how you test it.</blockquote>', '', 'published', 1, '2026-09-26 20:38:00', '', 'A checklist only helps if it is short enough to use under pressure. Here is a practical way to build one from your own trading history.', ''),
(2, 2, NULL, 'journzey.ai Team', 'Why R-multiples beat dollar P&L for reviewing trades', 'why-r-multiples-beat-dollar-pnl', 'Dollar P&L changes with position size. R-multiples measure the quality of the decision itself — and make different accounts comparable.', '<p>If you risk different amounts on different trades, or trade several accounts of different sizes, dollar P&L is a noisy way to judge your decisions. R-multiples remove that noise.</p><h2>What is an R-multiple?</h2><p>R is the amount you planned to risk on a trade — the distance from entry to your stop, multiplied by size. A trade that makes twice what you risked is +2R; one that hits its stop is −1R.</p><h2>Why it matters for review</h2><ul><li><strong>Comparable across accounts.</strong> A +2R trade is +2R whether the account is large or small.</li><li><strong>Separates decision from size.</strong> You can see whether a setup is good independently of how big you traded it.</li><li><strong>Exposes stop discipline.</strong> Losses larger than −1R show where stops were moved or ignored.</li></ul><h2>Expectancy in R</h2><p>Average R per trade is your expectancy. Tracked by setup, it shows which strategies deserve more of your attention — and which are quietly costing you.</p>', '', 'published', 1, '2026-09-30 20:38:00', '', 'Dollar P&L changes with position size. R-multiples measure the quality of the decision itself — and make different accounts comparable.', ''),
(3, 1, NULL, 'journzey.ai Team', 'Journaling across multiple broker accounts: a practical workflow', 'journaling-across-multiple-broker-accounts', 'Running several accounts makes review harder. A simple, consistent workflow keeps your record complete without eating your evening.', '<p>Many traders run more than one account — a funded account alongside a personal one, or separate accounts for different strategies. Each extra account multiplies the review work unless you have a routine.</p><h2>Keep accounts separate, review them together</h2><p>Each account should keep its own history so balances and limits stay accurate. But your behaviour is shared across all of them, so your review should look at the combined picture too.</p><h2>A ten-minute daily routine</h2><ol><li>Bring in the day&#39;s trades from every account.</li><li>Tag each trade with its setup.</li><li>Add a one-line reflection to any trade that broke a rule.</li><li>Check the day against your daily loss and trade-count limits.</li></ol><h2>A weekly deep-dive</h2><p>Once a week, compare setups across accounts. A strategy that works in one account but not another usually points to a difference in execution, sizing or session — exactly the kind of insight a combined journal makes visible.</p>', '', 'published', 0, '2026-10-04 20:38:00', '', 'Running several accounts makes review harder. A simple, consistent workflow keeps your record complete without eating your evening.', '');

INSERT INTO `blog_post_tags` (`post_id`, `tag_id`) VALUES
(1, 1),
(1, 2),
(2, 3),
(2, 1),
(3, 5),
(3, 1),
(3, 4);

INSERT INTO `custom_sections` (`name`, `placement`, `layout`, `eyebrow`, `heading`, `content`, `image`, `background`, `cta_label`, `cta_url`, `status`, `sort_order`) VALUES
('Example banner', 'home', 'banner', 'Announcement', 'Use custom sections for announcements or extra content', '<p>This is a draft example. Edit it under Content → Custom Sections, choose where it appears and publish it.</p>', '', 'brand', 'Contact us', '/contact', 'draft', 10);

INSERT INTO `email_templates` (`template_key`, `name`, `description`, `subject`, `body`, `is_enabled`) VALUES
('welcome', 'Welcome email', 'Sent to a member after they sign up.', 'Welcome to {site_name}', '<p>Hi {name},</p><p>Your {site_name} account is ready. Sign in at {site_url}/login to start journaling. You can load the demo journal from the Home Hub to explore every tool.</p><p>— The {site_name} team</p>', 1),
('password_reset', 'Password reset', 'Sent when a member asks to reset their password.', 'Reset your {site_name} password', '<p>Hi {name},</p><p>Use the link below to choose a new password. It expires in 60 minutes and can be used once.</p><p><a href="{reset_url}">{reset_url}</a></p><p>If you did not ask for this, you can ignore this email.</p>', 1),
('payment_receipt', 'Payment receipt', 'Sent to a member after a successful payment.', 'Payment received — {plan}', '<p>Hi {name},</p><p>Thank you for your payment of {amount} for <strong>{plan}</strong>. Your access is active until {access_until}.</p><p>Payment reference: {reference}</p><p>— The {site_name} team</p>', 1),
('payment_admin', 'New payment notification', 'Sent to the team when a payment succeeds.', 'New payment: {plan} — {amount}', '<p>{name} ({email}) paid {amount} for {plan}.</p><p>Reference: {reference}</p>', 1),
('contact_admin', 'Contact message notification', 'Sent to the team when the contact form is submitted.', 'New contact message: {subject}', '<p>A new message was submitted on {site_name}.</p><p><strong>Name:</strong> {name}<br><strong>Email:</strong> {email}<br><strong>Phone:</strong> {phone}<br><strong>Subject:</strong> {subject}</p><p><strong>Message:</strong><br>{message}</p><p>Submitted {date}.</p>', 1),
('contact_user', 'Contact confirmation', 'Sent to the visitor after using the contact form.', 'Thanks for contacting {site_name}', '<p>Hi {name},</p><p>Thanks for getting in touch. We have received your message and will reply as soon as we can.</p><p><strong>Your message:</strong><br>{message}</p><p>— The {site_name} team</p>', 1);

INSERT INTO `plans` (`id`, `name`, `slug`, `tagline`, `price`, `currency`, `interval_days`, `interval_label`, `features`, `allow_live`, `max_live_accounts`, `ai_daily_limit`, `is_featured`, `is_active`, `sort_order`) VALUES
(1, 'Pro', 'pro-monthly', 'For active traders journaling live accounts.', '19.00', 'USD', 30, 'month', 'Everything in Free\nUp to 3 live trading accounts\nFull analytics on live data\nCSV statement import\nAI Coach: 50 messages per day\nWeekly and monthly AI reviews', 1, 3, 50, 1, 1, 10),
(2, 'Pro Annual', 'pro-annual', 'Pro, billed once a year.', '190.00', 'USD', 365, 'year', 'Everything in Pro\nUp to 3 live trading accounts\nAI Coach: 50 messages per day\nTwo months free compared with monthly', 1, 3, 50, 0, 1, 20),
(3, 'Desk', 'desk-monthly', 'For prop traders running many accounts.', '49.00', 'USD', 30, 'month', 'Everything in Pro\nUp to 15 live trading accounts\nAI Coach: 200 messages per day\nPriority support', 1, 15, 200, 0, 1, 30);

INSERT INTO `pages` (`title`, `slug`, `template`, `hero_eyebrow`, `hero_title`, `hero_subtitle`, `featured_image`, `content`, `blocks`, `status`, `in_sitemap`, `meta_title`, `meta_description`, `og_image`, `canonical_url`, `noindex`, `is_system`, `sort_order`) VALUES
('Risk Disclaimer', 'disclaimer', 'legal', 'Legal', 'Risk Disclaimer', 'Please read this before using journzey.ai.', '', '<p class="notice"><strong>Template text.</strong> Have this page reviewed by a qualified professional before launch.</p><h2>Journal and analytics software only</h2><p>journzey.ai is trading journal and performance-analytics software. It does not execute trades, hold funds or act as a broker.</p><h2>No guaranteed outcomes</h2><p>journzey.ai does not guarantee profits or any investment outcome. Analytics are based on the historical data you enter or import, and past performance does not predict future results.</p><h2>Hypothetical and statistical results</h2><p>Features such as the Monte Carlo risk simulation, the discipline leak mirror and the runner auditor produce hypothetical or statistical estimates. They are not predictions and not recommendations.</p><h2>AI Coach</h2><p>AI-generated analysis can be incomplete or wrong. It is educational feedback on your own data, not personalised financial advice.</p><h2>Not financial advice</h2><p>Nothing in journzey.ai replaces advice from a licensed financial professional. Trading involves substantial risk of loss.</p>', '[]', 'published', 1, 'Risk Disclaimer', 'journzey.ai is trading journal software and does not guarantee outcomes.', '', '', 0, 1, 40),
('Security', 'security', 'legal', 'Legal', 'Security', 'How we protect your account and trading data.', '', '<p class="notice"><strong>Template text.</strong> Have this page reviewed by a qualified professional before launch.</p><h2>Your data is private to your account</h2><p>Every trade, journal entry, strategy and AI conversation is linked to your account and checked on the server on every request. Other members can never see your data.</p><h2>Sign-in</h2><p>Sign in with Google (OpenID Connect) or with an email and password. Passwords are stored only as salted one-way hashes. Sign-in attempts are rate-limited.</p><h2>Sessions and transport</h2><p>The site runs over HTTPS. Session cookies are HttpOnly and Secure, and every form is protected against cross-site request forgery.</p><h2>Secrets</h2><p>API keys are encrypted at rest and never shown in the browser after creation.</p><h2>Payments</h2><p>Card and UPI details are entered on the payment provider&#39;s secure checkout and never reach our servers.</p><h2>Report a vulnerability</h2><p>[Replace] Add the email address for security reports.</p>', '[]', 'published', 1, 'Security', 'How journzey.ai protects member accounts and trading data.', '', '', 0, 1, 50);

INSERT INTO `smtp_settings` (`id`, `host`, `port`, `username`, `password_enc`, `encryption`, `from_email`, `from_name`, `reply_to`, `is_enabled`) VALUES (1, '', 587, '', NULL, 'tls', '', 'journzey.ai', '', 0);

-- ---------------------------------------------------------------------------------------------
-- journzey.ai — database update (schema versions 2, 3 and 4)
-- Safe to run on an existing database: it only ADDS columns, tables and rows. Nothing is dropped,
-- reset or overwritten, and running it twice is harmless. Import it with phpMyAdmin → Import.
-- (The website also applies these changes automatically on the first request after updating.)
-- ---------------------------------------------------------------------------------------------

SET NAMES utf8mb4;

-- Daily / weekly risk limits: % of account capital or a fixed amount; optional A+ risk tier
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `user_settings` ADD COLUMN `daily_limit_type` ENUM(''amount'',''percent'') NOT NULL DEFAULT ''amount'' AFTER `max_weekly_loss`', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_settings' AND COLUMN_NAME = 'daily_limit_type');
PREPARE jz_stmt FROM @s;
EXECUTE jz_stmt;
DEALLOCATE PREPARE jz_stmt;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `user_settings` ADD COLUMN `weekly_limit_type` ENUM(''amount'',''percent'') NOT NULL DEFAULT ''amount'' AFTER `daily_limit_type`', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_settings' AND COLUMN_NAME = 'weekly_limit_type');
PREPARE jz_stmt FROM @s;
EXECUTE jz_stmt;
DEALLOCATE PREPARE jz_stmt;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `user_settings` ADD COLUMN `a_plus_risk_pct` DECIMAL(5,2) NULL AFTER `weekly_limit_type`', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_settings' AND COLUMN_NAME = 'a_plus_risk_pct');
PREPARE jz_stmt FROM @s;
EXECUTE jz_stmt;
DEALLOCATE PREPARE jz_stmt;

-- Personal strategy builder: trading style, edge/thesis and sub-setups (rules stay in `checklist`, one per line)
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `strategies` ADD COLUMN `style` VARCHAR(30) NULL AFTER `description`', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'strategies' AND COLUMN_NAME = 'style');
PREPARE jz_stmt FROM @s;
EXECUTE jz_stmt;
DEALLOCATE PREPARE jz_stmt;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `strategies` ADD COLUMN `thesis` TEXT NULL AFTER `style`', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'strategies' AND COLUMN_NAME = 'thesis');
PREPARE jz_stmt FROM @s;
EXECUTE jz_stmt;
DEALLOCATE PREPARE jz_stmt;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `strategies` ADD COLUMN `setups` TEXT NULL AFTER `checklist`', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'strategies' AND COLUMN_NAME = 'setups');
PREPARE jz_stmt FROM @s;
EXECUTE jz_stmt;
DEALLOCATE PREPARE jz_stmt;

-- Daily psychology journal: "Did you follow your trading rules?"
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `journal_entries` ADD COLUMN `rules_answer` ENUM(''yes'',''partial'',''no'') NULL AFTER `compliance`', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'journal_entries' AND COLUMN_NAME = 'rules_answer');
PREPARE jz_stmt FROM @s;
EXECUTE jz_stmt;
DEALLOCATE PREPARE jz_stmt;

-- Editable instrument specifications (overrides the built-in defaults)
CREATE TABLE IF NOT EXISTS `instruments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `symbol` VARCHAR(20) NOT NULL,
  `name` VARCHAR(80) NOT NULL,
  `asset_class` ENUM('METALS','FOREX','INDICES','CRYPTO','COMMODITIES') NOT NULL,
  `base_currency` VARCHAR(10) NOT NULL,
  `quote_currency` CHAR(3) NOT NULL,
  `contract_size` DECIMAL(20,6) NOT NULL,
  `tick_size` DECIMAL(20,10) NOT NULL,
  `pip_size` DECIMAL(20,10) NOT NULL,
  `price_decimals` TINYINT UNSIGNED NOT NULL DEFAULT 2,
  `min_lot` DECIMAL(12,4) NOT NULL DEFAULT 0.0100,
  `lot_step` DECIMAL(12,4) NOT NULL DEFAULT 0.0100,
  `aliases` VARCHAR(255) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_instruments_symbol` (`symbol`),
  KEY `idx_instruments_active` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `instruments` (`symbol`, `name`, `asset_class`, `base_currency`, `quote_currency`, `contract_size`, `tick_size`, `pip_size`, `price_decimals`, `min_lot`, `lot_step`, `aliases`, `is_active`, `sort_order`) VALUES
('XAUUSD', 'Gold', 'METALS', 'XAU', 'USD', 100, 0.01, 0.1, 2, 0.01, 0.01, 'gold,xau,gc', 1, 10),
('XAGUSD', 'Silver', 'METALS', 'XAG', 'USD', 5000, 0.001, 0.01, 3, 0.01, 0.01, 'silver,xag,si', 1, 20),
('EURUSD', 'EUR/USD', 'FOREX', 'EUR', 'USD', 100000, 0.00001, 0.0001, 5, 0.01, 0.01, 'eu,fiber,euro dollar,euro', 1, 30),
('GBPUSD', 'GBP/USD', 'FOREX', 'GBP', 'USD', 100000, 0.00001, 0.0001, 5, 0.01, 0.01, 'gu,cable,pound dollar,pound', 1, 40),
('USDJPY', 'USD/JPY', 'FOREX', 'USD', 'JPY', 100000, 0.001, 0.01, 3, 0.01, 0.01, 'uj,yen,dollar yen', 1, 50),
('AUDUSD', 'AUD/USD', 'FOREX', 'AUD', 'USD', 100000, 0.00001, 0.0001, 5, 0.01, 0.01, 'au,aussie,aussie dollar', 1, 60),
('NZDUSD', 'NZD/USD', 'FOREX', 'NZD', 'USD', 100000, 0.00001, 0.0001, 5, 0.01, 0.01, 'nu,kiwi,kiwi dollar', 1, 70),
('USDCAD', 'USD/CAD', 'FOREX', 'USD', 'CAD', 100000, 0.00001, 0.0001, 5, 0.01, 0.01, 'uc,loonie,dollar cad', 1, 80),
('USDCHF', 'USD/CHF', 'FOREX', 'USD', 'CHF', 100000, 0.00001, 0.0001, 5, 0.01, 0.01, 'swissy,dollar swiss', 1, 90),
('EURJPY', 'EUR/JPY', 'FOREX', 'EUR', 'JPY', 100000, 0.001, 0.01, 3, 0.01, 0.01, 'ej,euro yen', 1, 100),
('GBPJPY', 'GBP/JPY', 'FOREX', 'GBP', 'JPY', 100000, 0.001, 0.01, 3, 0.01, 0.01, 'gj,guppy,pound yen', 1, 110),
('EURGBP', 'EUR/GBP', 'FOREX', 'EUR', 'GBP', 100000, 0.00001, 0.0001, 5, 0.01, 0.01, 'eg,euro pound', 1, 120),
('EURAUD', 'EUR/AUD', 'FOREX', 'EUR', 'AUD', 100000, 0.00001, 0.0001, 5, 0.01, 0.01, '', 1, 130),
('EURCAD', 'EUR/CAD', 'FOREX', 'EUR', 'CAD', 100000, 0.00001, 0.0001, 5, 0.01, 0.01, '', 1, 140),
('EURCHF', 'EUR/CHF', 'FOREX', 'EUR', 'CHF', 100000, 0.00001, 0.0001, 5, 0.01, 0.01, '', 1, 150),
('GBPAUD', 'GBP/AUD', 'FOREX', 'GBP', 'AUD', 100000, 0.00001, 0.0001, 5, 0.01, 0.01, '', 1, 160),
('GBPCHF', 'GBP/CHF', 'FOREX', 'GBP', 'CHF', 100000, 0.00001, 0.0001, 5, 0.01, 0.01, '', 1, 170),
('AUDJPY', 'AUD/JPY', 'FOREX', 'AUD', 'JPY', 100000, 0.001, 0.01, 3, 0.01, 0.01, 'aussie yen', 1, 180),
('CADJPY', 'CAD/JPY', 'FOREX', 'CAD', 'JPY', 100000, 0.001, 0.01, 3, 0.01, 0.01, '', 1, 190),
('CHFJPY', 'CHF/JPY', 'FOREX', 'CHF', 'JPY', 100000, 0.001, 0.01, 3, 0.01, 0.01, '', 1, 200),
('NZDJPY', 'NZD/JPY', 'FOREX', 'NZD', 'JPY', 100000, 0.001, 0.01, 3, 0.01, 0.01, '', 1, 210),
('AUDNZD', 'AUD/NZD', 'FOREX', 'AUD', 'NZD', 100000, 0.00001, 0.0001, 5, 0.01, 0.01, '', 1, 220),
('US30', 'Dow Jones 30', 'INDICES', 'DJI', 'USD', 1, 0.01, 1, 2, 0.01, 0.01, 'dow,dji,ym,dj30,dow jones,wall street', 1, 230),
('US500', 'S&P 500', 'INDICES', 'SPX', 'USD', 1, 0.01, 0.1, 2, 0.01, 0.01, 'spx,sp500,es,spx500,s&p,s&p 500,s and p', 1, 240),
('NAS100', 'Nasdaq 100', 'INDICES', 'NDX', 'USD', 1, 0.01, 0.1, 2, 0.01, 0.01, 'nas,nq,ustec,us100,ndx,nasdaq', 1, 250),
('US2000', 'Russell 2000', 'INDICES', 'RUT', 'USD', 1, 0.01, 0.1, 2, 0.01, 0.01, 'russell,rty,rut', 1, 260),
('GER40', 'DAX 40', 'INDICES', 'DAX', 'EUR', 1, 0.01, 1, 2, 0.01, 0.01, 'dax,de40,ger30,germany 40', 1, 270),
('UK100', 'FTSE 100', 'INDICES', 'FTSE', 'GBP', 1, 0.01, 1, 2, 0.01, 0.01, 'ftse,ftse100', 1, 280),
('FRA40', 'CAC 40', 'INDICES', 'CAC', 'EUR', 1, 0.01, 1, 2, 0.01, 0.01, 'cac,cac40,france 40', 1, 290),
('EU50', 'Euro Stoxx 50', 'INDICES', 'SX5E', 'EUR', 1, 0.01, 1, 2, 0.01, 0.01, 'stoxx,eustx50,stoxx50', 1, 300),
('JPN225', 'Nikkei 225 (JP225)', 'INDICES', 'NKY', 'JPY', 1, 1, 1, 0, 0.01, 0.01, 'nikkei,nk225,jp225,japan 225', 1, 310),
('HK50', 'Hang Seng 50', 'INDICES', 'HSI', 'HKD', 1, 1, 1, 0, 0.01, 0.01, 'hang seng,hsi,hk33', 1, 320),
('AUS200', 'ASX 200', 'INDICES', 'ASX', 'AUD', 1, 0.1, 1, 1, 0.01, 0.01, 'asx,asx200,aus 200', 1, 330),
('USOIL', 'WTI Crude', 'COMMODITIES', 'WTI', 'USD', 1000, 0.01, 0.01, 2, 0.01, 0.01, 'wti,oil,cl,crude,crude oil', 1, 340),
('UKOIL', 'Brent Crude', 'COMMODITIES', 'BRENT', 'USD', 1000, 0.01, 0.01, 2, 0.01, 0.01, 'brent,bz', 1, 350),
('NATGAS', 'Natural Gas', 'COMMODITIES', 'NG', 'USD', 10000, 0.001, 0.001, 3, 0.01, 0.01, 'ng,gas,xngusd,natural gas', 1, 360),
('COPPER', 'Copper', 'COMMODITIES', 'HG', 'USD', 25000, 0.0005, 0.0005, 4, 0.01, 0.01, 'hg,xcuusd', 1, 370),
('BTCUSDT', 'Bitcoin', 'CRYPTO', 'BTC', 'USD', 1, 0.01, 1, 2, 0.01, 0.01, 'btc,bitcoin,btcusd,xbt', 1, 380),
('ETHUSDT', 'Ethereum', 'CRYPTO', 'ETH', 'USD', 1, 0.01, 0.1, 2, 0.01, 0.01, 'eth,ethereum,ethusd,ether', 1, 390),
('SOLUSDT', 'Solana', 'CRYPTO', 'SOL', 'USD', 1, 0.001, 0.01, 3, 0.01, 0.01, 'sol,solana,solusd', 1, 400),
('XRPUSDT', 'XRP', 'CRYPTO', 'XRP', 'USD', 1, 0.0001, 0.001, 4, 0.01, 0.01, 'xrp,ripple,xrpusd', 1, 410),
('BNBUSDT', 'BNB', 'CRYPTO', 'BNB', 'USD', 1, 0.01, 0.1, 2, 0.01, 0.01, 'bnb,binance coin,bnbusd', 1, 420),
('ADAUSDT', 'Cardano', 'CRYPTO', 'ADA', 'USD', 1, 0.0001, 0.001, 4, 0.01, 0.01, 'ada,cardano,adausd', 1, 430),
('DOGEUSDT', 'Dogecoin', 'CRYPTO', 'DOGE', 'USD', 1, 0.00001, 0.0001, 5, 0.01, 0.01, 'doge,dogecoin,dogeusd', 1, 440),
('LTCUSDT', 'Litecoin', 'CRYPTO', 'LTC', 'USD', 1, 0.01, 0.1, 2, 0.01, 0.01, 'ltc,litecoin,ltcusd', 1, 450);

-- Mistake tag for impulsive entries is stored in trades.mistake_tag (VARCHAR) — no change needed.

-- Copy updates: broker sync / signed trade webhooks are no longer offered (only exact old phrases are replaced)
UPDATE `services` SET `faqs` = REPLACE(`faqs`, '{"title":"Connect or import","text":"Add your accounts and bring in your trade history."}', '{"title":"Add or import","text":"Add your accounts manually and bring in your trade history from a CSV export."}') WHERE `faqs` LIKE '%{"title":"Connect or import","text":"Add%';
UPDATE `services` SET `process` = REPLACE(`process`, '{"title":"Connect or import","text":"Add your accounts and bring in your trade history."}', '{"title":"Add or import","text":"Add your accounts manually and bring in your trade history from a CSV export."}') WHERE `process` LIKE '%{"title":"Connect or import","text":"Add%';
UPDATE `faqs` SET `answer` = REPLACE(`answer`, '{"title":"Connect or import","text":"Add your accounts and bring in your trade history."}', '{"title":"Add or import","text":"Add your accounts manually and bring in your trade history from a CSV export."}') WHERE `answer` LIKE '%{"title":"Connect or import","text":"Add%';
UPDATE `pages` SET `content` = REPLACE(`content`, '{"title":"Connect or import","text":"Add your accounts and bring in your trade history."}', '{"title":"Add or import","text":"Add your accounts manually and bring in your trade history from a CSV export."}') WHERE `content` LIKE '%{"title":"Connect or import","text":"Add%';
UPDATE `plans` SET `features` = REPLACE(`features`, '{"title":"Connect or import","text":"Add your accounts and bring in your trade history."}', '{"title":"Add or import","text":"Add your accounts manually and bring in your trade history from a CSV export."}') WHERE `features` LIKE '%{"title":"Connect or import","text":"Add%';
UPDATE `services` SET `faqs` = REPLACE(`faqs`, '"answer":"[Replace] List the broker connections and import formats your deployment supports."', '"answer":"Any broker. You add accounts manually and log trades by hand, by quick command or by voice, or import a CSV history export (MT4/MT5, cTrader, NinjaTrader). There is no live broker synchronisation."') WHERE `faqs` LIKE '%"answer":"[Replace] List the broker conn%';
UPDATE `services` SET `process` = REPLACE(`process`, '"answer":"[Replace] List the broker connections and import formats your deployment supports."', '"answer":"Any broker. You add accounts manually and log trades by hand, by quick command or by voice, or import a CSV history export (MT4/MT5, cTrader, NinjaTrader). There is no live broker synchronisation."') WHERE `process` LIKE '%"answer":"[Replace] List the broker conn%';
UPDATE `faqs` SET `answer` = REPLACE(`answer`, '"answer":"[Replace] List the broker connections and import formats your deployment supports."', '"answer":"Any broker. You add accounts manually and log trades by hand, by quick command or by voice, or import a CSV history export (MT4/MT5, cTrader, NinjaTrader). There is no live broker synchronisation."') WHERE `answer` LIKE '%"answer":"[Replace] List the broker conn%';
UPDATE `pages` SET `content` = REPLACE(`content`, '"answer":"[Replace] List the broker connections and import formats your deployment supports."', '"answer":"Any broker. You add accounts manually and log trades by hand, by quick command or by voice, or import a CSV history export (MT4/MT5, cTrader, NinjaTrader). There is no live broker synchronisation."') WHERE `content` LIKE '%"answer":"[Replace] List the broker conn%';
UPDATE `plans` SET `features` = REPLACE(`features`, '"answer":"[Replace] List the broker connections and import formats your deployment supports."', '"answer":"Any broker. You add accounts manually and log trades by hand, by quick command or by voice, or import a CSV history export (MT4/MT5, cTrader, NinjaTrader). There is no live broker synchronisation."') WHERE `features` LIKE '%"answer":"[Replace] List the broker conn%';
UPDATE `services` SET `faqs` = REPLACE(`faqs`, '[Replace] List the specific broker connections and import formats available in your plan.', 'Accounts are added manually; trades are logged by hand, by quick command or by voice, or imported from a CSV history export. There is no live broker synchronisation.') WHERE `faqs` LIKE '%[Replace] List the specific broker conne%';
UPDATE `services` SET `process` = REPLACE(`process`, '[Replace] List the specific broker connections and import formats available in your plan.', 'Accounts are added manually; trades are logged by hand, by quick command or by voice, or imported from a CSV history export. There is no live broker synchronisation.') WHERE `process` LIKE '%[Replace] List the specific broker conne%';
UPDATE `faqs` SET `answer` = REPLACE(`answer`, '[Replace] List the specific broker connections and import formats available in your plan.', 'Accounts are added manually; trades are logged by hand, by quick command or by voice, or imported from a CSV history export. There is no live broker synchronisation.') WHERE `answer` LIKE '%[Replace] List the specific broker conne%';
UPDATE `pages` SET `content` = REPLACE(`content`, '[Replace] List the specific broker connections and import formats available in your plan.', 'Accounts are added manually; trades are logged by hand, by quick command or by voice, or imported from a CSV history export. There is no live broker synchronisation.') WHERE `content` LIKE '%[Replace] List the specific broker conne%';
UPDATE `plans` SET `features` = REPLACE(`features`, '[Replace] List the specific broker connections and import formats available in your plan.', 'Accounts are added manually; trades are logged by hand, by quick command or by voice, or imported from a CSV history export. There is no live broker synchronisation.') WHERE `features` LIKE '%[Replace] List the specific broker conne%';
UPDATE `services` SET `faqs` = REPLACE(`faqs`, 'Webhook secrets and API keys are encrypted at rest', 'API keys are encrypted at rest') WHERE `faqs` LIKE '%Webhook secrets and API keys are encrypt%';
UPDATE `services` SET `process` = REPLACE(`process`, 'Webhook secrets and API keys are encrypted at rest', 'API keys are encrypted at rest') WHERE `process` LIKE '%Webhook secrets and API keys are encrypt%';
UPDATE `faqs` SET `answer` = REPLACE(`answer`, 'Webhook secrets and API keys are encrypted at rest', 'API keys are encrypted at rest') WHERE `answer` LIKE '%Webhook secrets and API keys are encrypt%';
UPDATE `pages` SET `content` = REPLACE(`content`, 'Webhook secrets and API keys are encrypted at rest', 'API keys are encrypted at rest') WHERE `content` LIKE '%Webhook secrets and API keys are encrypt%';
UPDATE `plans` SET `features` = REPLACE(`features`, 'Webhook secrets and API keys are encrypted at rest', 'API keys are encrypted at rest') WHERE `features` LIKE '%Webhook secrets and API keys are encrypt%';
UPDATE `services` SET `faqs` = REPLACE(`faqs`, 'CSV import and signed webhooks', 'CSV statement import') WHERE `faqs` LIKE '%CSV import and signed webhooks%';
UPDATE `services` SET `process` = REPLACE(`process`, 'CSV import and signed webhooks', 'CSV statement import') WHERE `process` LIKE '%CSV import and signed webhooks%';
UPDATE `faqs` SET `answer` = REPLACE(`answer`, 'CSV import and signed webhooks', 'CSV statement import') WHERE `answer` LIKE '%CSV import and signed webhooks%';
UPDATE `pages` SET `content` = REPLACE(`content`, 'CSV import and signed webhooks', 'CSV statement import') WHERE `content` LIKE '%CSV import and signed webhooks%';
UPDATE `plans` SET `features` = REPLACE(`features`, 'CSV import and signed webhooks', 'CSV statement import') WHERE `features` LIKE '%CSV import and signed webhooks%';

-- ---------------------------------------------------------------------------------------------
-- journzey.ai — database update (schema version 3): public Learning playbook
-- ---------------------------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `learn_strategies` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(150) NOT NULL,
  `title` VARCHAR(160) NOT NULL,
  `short_title` VARCHAR(80) NOT NULL,
  `summary` VARCHAR(500) NOT NULL,
  `style` VARCHAR(30) NULL,
  `logic` TEXT NULL,
  `assets` VARCHAR(255) NULL,
  `primary_assets` VARCHAR(120) NULL,
  `session_window` VARCHAR(120) NULL,
  `timeframe` VARCHAR(40) NULL,
  `timeframe_detail` VARCHAR(255) NULL,
  `setup_rules` TEXT NULL,
  `entry_trigger` TEXT NULL,
  `stop_loss` TEXT NULL,
  `take_profit` TEXT NULL,
  `target_rr` VARCHAR(20) NULL,
  `rr_value` DECIMAL(6,2) NULL,
  `educator_note` TEXT NULL,
  `meta_title` VARCHAR(200) NULL,
  `meta_description` VARCHAR(320) NULL,
  `og_image` VARCHAR(500) NULL,
  `status` ENUM('published','draft') NOT NULL DEFAULT 'published',
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_learn_slug` (`slug`),
  KEY `idx_learn_status` (`status`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The 10-strategy institutional playbook (existing rows with the same slug are left untouched)
INSERT IGNORE INTO `learn_strategies` (`slug`, `title`, `short_title`, `summary`, `style`, `logic`, `assets`, `primary_assets`, `session_window`, `timeframe`, `timeframe_detail`, `setup_rules`, `entry_trigger`, `stop_loss`, `take_profit`, `target_rr`, `rr_value`, `educator_note`, `meta_title`, `meta_description`, `status`, `sort_order`) VALUES
('london-asian-range-liquidity-raid', 'London Session Asian High/Low Liquidity Raid', 'Asian High/Low Raid', 'Trade the London open sweep of the Asian session high or low, then enter on the Fair Value Gap left by the market structure shift.', 'SMC', 'The Asian session range builds clear liquidity pools: buy-side liquidity above the Asian high and sell-side liquidity below the Asian low. Around the London open, large participants often run these stops to fill orders before price commits to the true daily direction.', 'Forex majors (EURUSD, GBPUSD), precious metals (XAUUSD)', 'EURUSD, XAUUSD', 'London Open (07:30 UTC)', '15M / 1M', '15-minute for higher-timeframe bias → 1-minute / 3-minute for entry execution.', 'Mark the exact high and low of the Asian session (00:00 – 07:00 UTC).
Wait for the London open (07:30 – 08:30 UTC) to aggressively sweep past the Asian high or low.
Look for an immediate, sharp rejection candle closing back inside the Asian range.
Confirm a lower-timeframe Market Structure Shift (MSS) with displacement — an energetic expansion candle breaking the previous swing high/low.', 'Limit order at the 50% equilibrium of the Fair Value Gap (FVG) formed during the Market Structure Shift.', '2–3 pips beyond the liquidity-sweep high/low.', 'Opposite side of the Asian range (the Asian low if shorting a sweep of the high), or a minimum 1:3 R:R target.', '1:3.0', 3, 'London opens at 08:00 UK time, which is 07:00 UTC during UK summer time (BST) and 08:00 UTC in winter — adjust the window to the season.', '1. London Session Asian High/Low Liquidity Raid — Intraday Strategy', 'Trade the London open sweep of the Asian session high or low, then enter on the Fair Value Gap left by the market structure shift.', 'published', 10),
('volume-profile-poc-rejection', 'Volume Profile Point of Control (POC) Rejection', 'Volume Profile POC', 'Fade a low-volume retest of the previous session’s Point of Control when order flow shows absorption, targeting the opposite Value Area boundary.', 'VOLUME_PROFILE', 'The Point of Control (POC) is the price where the most volume traded in the prior session. Rejections at the POC suggest participants are defending that high-volume value area.', 'Gold (XAUUSD), equity indices (NAS100, US30)', 'XAUUSD, NAS100', 'London / New York', '30M / 5M', '30-minute for volume node identification → 5-minute for the trigger.', 'Plot a fixed-range or session Volume Profile on the previous trading day’s session.
Identify the previous day POC (vPOC) and the Value Area High/Low (VAH/VAL).
Allow price in the active session to retest the vPOC on declining relative volume.
Observe delta absorption: heavy aggressive order flow executing at the level without price progressing further (passive limit orders absorbing it).', 'Market order on a 5-minute engulfing bar that closes back away from the POC toward the Value Area interior.', '1.5 ATR beyond the POC level.', 'Opposite Value Area boundary (VAH if buying at VAL; VAL if selling at VAH). Target R:R 1:2.5+.', '1:2.5', 2.5, '', '2. Volume Profile Point of Control (POC) Rejection — Intraday Strategy', 'Fade a low-volume retest of the previous session’s Point of Control when order flow shows absorption, targeting the opposite Value Area boundary.', 'published', 20),
('session-vwap-mean-reversion', 'Session VWAP Institutional Mean Reversion', 'VWAP Mean Reversion', 'When price stretches beyond the 2.5–3.0 standard deviation VWAP bands in quieter hours, wait for exhaustion and a close back inside 2.0 SD, then target VWAP.', 'MEAN_REVERSION', 'The Volume-Weighted Average Price (VWAP) is the benchmark execution desks use to judge fill quality. When price stretches far from VWAP during lower-volatility hours, aggressive buying or selling tends to pause and price often reverts toward the benchmark.', 'Global indices (NAS100, SPX500), large-cap crypto (BTCUSD, ETHUSD)', 'NAS100, BTCUSD', 'Mid-Day NY / Asian', '15M / 5M', '5-minute / 15-minute with session VWAP and ±2.0 / ±3.0 standard deviation bands.', 'Attach the daily session VWAP with ±1, ±2 and ±3 standard deviation bands.
Wait for price to stretch violently beyond the ±2.5 or ±3.0 band (overbought above, oversold below).
Confirm exhaustion with RSI (below 30 for a long, above 70 for a short, ideally with divergence) or volume delta dropping off sharply at the extreme band.
Wait for price to close back inside the 2.0 standard deviation band.', 'Market entry on the candle close back inside the 2.0 SD band.', 'Beyond the extreme swing high/low formed outside the band.', 'Primary target: the session VWAP centre line. Target R:R 1:2.0 to 1:3.0.', '1:2.0', 2, '', '3. Session VWAP Institutional Mean Reversion — Intraday Strategy', 'When price stretches beyond the 2.5–3.0 standard deviation VWAP bands in quieter hours, wait for exhaustion and a close back inside 2.0 SD, then target VWAP.', 'published', 30),
('order-block-fvg-confluence', 'Institutional Order Block + Fair Value Gap Confluence', 'Order Block + FVG', 'Buy or sell the first passive return into an Order Block that overlaps a Fair Value Gap left by a structure-breaking displacement.', 'SMC', 'An Order Block is the last opposing candle before an aggressive expansion that leaves an imbalance (Fair Value Gap). Price frequently returns to this imbalance to fill remaining orders before resuming its direction.', 'All FX majors, XAUUSD, NAS100', 'All assets', 'London / NY Open', '15M / 3M', '15-minute structure → 3-minute execution.', 'Locate an aggressive displacement that cleanly breaks market structure (BOS) and leaves a distinct three-candle Fair Value Gap (FVG).
Mark the body of the last down-candle before the upward expansion (bullish OB) or the last up-candle before the downward move (bearish OB).
Make sure the Order Block overlaps with, or sits directly next to, the Fair Value Gap.
Wait for price to retrace passively into the FVG / OB zone.', 'Limit order at the distal edge or the 50% midpoint (Consequent Encroachment) of the FVG/OB confluence.', '2 pips beyond the invalidation point of the Order Block.', 'Unmitigated liquidity pool or opposing higher-timeframe swing level. Minimum 1:3 R:R.', '1:3.5', 3.5, '', '4. Institutional Order Block + Fair Value Gap Confluence — Intraday Strategy', 'Buy or sell the first passive return into an Order Block that overlaps a Fair Value Gap left by a structure-breaking displacement.', 'published', 40),
('footprint-delta-absorption', 'Order Flow Footprint Delta Divergence & Absorption', 'Footprint Delta Absorption', 'At a key level, look for aggressive buying that fails to make progress plus stacked sell imbalances, then short the break of the absorption bar (reverse for longs).', 'ORDER_FLOW', 'Footprint charts and cumulative volume delta (CVD) show aggressive market orders against passive limit orders. When aggressive buyers keep lifting the offer but price fails to make new highs, passive sellers are absorbing that pressure — a common precursor to a reversal.', 'Futures & CFDs (US30, NAS100, XAUUSD)', 'US30, NAS100', 'New York Open', '3M / 1M', '1-minute / 3-minute order flow footprint chart (bid/ask volume).', 'Identify price reaching a critical support or resistance level (e.g. prior day high/low, weekly open).
Observe a surge in positive delta (aggressive buying) on the footprint while price forms a lower high or stalls at resistance.
Look for a stacked sell imbalance — for example 3+ consecutive price levels where aggressive sellers outnumber buyers by 3:1 or more.', 'Enter short as soon as the low of the absorption footprint bar is broken (mirror the rules for a long at support).', 'Above the high of the absorption bar.', 'Nearest high-volume node or the VWAP benchmark. Target R:R 1:2.5+.', '1:2.5', 2.5, 'Footprint data needs a platform with bid/ask volume. On CFDs, volume is your broker’s own tick volume rather than exchange volume.', '5. Order Flow Footprint Delta Divergence & Absorption — Intraday Strategy', 'At a key level, look for aggressive buying that fails to make progress plus stacked sell imbalances, then short the break of the absorption bar (reverse for longs).', 'published', 50),
('new-york-judas-swing-reversal', 'New York Morning Session “Judas Swing” Reversal', 'NY Judas Swing', 'After the 09:30 New York bell, fade a fast false break of the pre-market range once it sweeps liquidity and shifts structure back inside.', 'ICT', 'Shortly after the New York open (09:30 New York time), price often makes a fast “Judas Swing” in the opposite direction to the day’s eventual move, collecting pre-market liquidity before the true direction develops.', 'Equity indices (NAS100, US30, SPX500), BTCUSD', 'NAS100, SPX500', 'NY Open (09:30 New York time)', '5M / 1M', '1-minute / 5-minute.', 'Define the pre-market range: the high and low from 04:00 to 09:30 New York time.
At 09:30 (NYSE opening bell), watch for a fast, violent 5-to-15-minute expansion that breaks the pre-market high or low.
Look for that expansion to sweep pre-market liquidity and fail immediately, leaving a long rejection wick or a rapid displacement back inside the pre-market range.
Confirm a lower-timeframe Market Structure Shift (MSS).', 'Retest of the Fair Value Gap created by the displacement candle that follows the Judas Swing.', '3–5 ticks beyond the Judas Swing spike high/low.', 'Opposite pre-market boundary (if the high was swept, target the pre-market low). Target R:R 1:3 to 1:4.', '1:3.5', 3.5, '09:30 New York time is 13:30 UTC while US daylight saving time is in effect (mid-March to early November) and 14:30 UTC in winter.', '6. New York Morning Session “Judas Swing” Reversal — Intraday Strategy', 'After the 09:30 New York bell, fade a fast false break of the pre-market range once it sweeps liquidity and shifts structure back inside.', 'published', 60),
('multi-timeframe-supply-demand-retest', 'Multi-Timeframe Supply & Demand Zone Imbalance Retest', 'Multi-TF Supply/Demand', 'Map fresh 1H/4H supply and demand zones, then take the first return into the zone only after a 5-minute structural confirmation.', 'SUPPLY_DEMAND', 'Supply and demand zones mark prices where a severe imbalance between buyers and sellers forced price away explosively. The first retest of an untouched (fresh) zone is a widely used structural re-entry point.', 'All financial assets (forex, metals, indices, crypto)', 'FX, Metals, Crypto', 'Any active session', '1H / 5M', '1-hour for zone mapping → 15-minute / 5-minute for confirmation.', 'Identify a 1-hour or 4-hour fresh demand zone (drop-base-rally) or supply zone (rally-base-drop) with strong departure candles.
Verify the departure candle broke market structure and left an unmitigated imbalance.
Wait for price to return to the zone for the first time (fresh touch).
On the 5-minute chart inside the zone, wait for a bullish/bearish engulfing bar or a structural change before entering.', 'Market entry on the 5-minute structural shift inside the 1-hour zone.', 'Beyond the distal boundary of the supply/demand zone.', 'Next opposing structural swing zone. Target R:R 1:2.5+.', '1:2.5', 2.5, '', '7. Multi-Timeframe Supply & Demand Zone Imbalance Retest — Intraday Strategy', 'Map fresh 1H/4H supply and demand zones, then take the first return into the zone only after a 5-minute structural confirmation.', 'published', 70),
('opening-range-breakout-volume', 'Opening Range Breakout (ORB) with Volume Acceleration', 'Opening Range Breakout', 'Trade a confirmed 5-minute close outside the first 15-minute range, only when relative volume is at least double the average.', 'BREAKOUT', 'The first 15–30 minutes of a session often establish the day’s initial positioning. A break beyond this initial balance on expanding volume can signal trend continuation driven by larger order flow.', 'High-beta assets (NAS100, GER40, US30, crude oil)', 'NAS100, GER40', 'US / EU market open', '15M / 5M', '15-minute for range formation → 5-minute for breakout execution.', 'Mark the high and low of the first 15 minutes after the market opens (e.g. 09:30 – 09:45 New York time for US indices).
Check volume: relative volume (RVOL) must be above 2.0 — double the average.
Wait for a full 5-minute candle to close outside the 15-minute opening range.', 'Market entry at the open of the candle after the confirmed breakout close, or a limit entry on a retest of the broken range high/low.', 'Midpoint of the 15-minute opening range.', '1.5× and 2.0× projections of the 15-minute opening range height. Target R:R 1:2.0+.', '1:2.0', 2, '', '8. Opening Range Breakout (ORB) with Volume Acceleration — Intraday Strategy', 'Trade a confirmed 5-minute close outside the first 15-minute range, only when relative volume is at least double the average.', 'published', 80),
('breaker-block-flip', 'Liquidity Void Run & Breaker Block Flip', 'Breaker Block Flip', 'When an Order Block fails and is smashed through, mark it as a Breaker Block and trade the retest from the other side.', 'SMC', 'A Breaker Block is a failed Order Block. When an Order Block fails to hold and is violently breached, its role flips — former support becomes resistance (or vice versa) as trapped positions look for breakeven exits.', 'XAUUSD, GBPUSD, BTCUSD', 'XAUUSD, GBPUSD', 'London / NY session', '15M / 5M', '15-minute / 5-minute.', 'Identify an established swing-low/high Order Block that was expected to hold price.
Observe price aggressively smashing straight through the Order Block without stopping, creating a structural shift.
Mark the exact boundaries of the invalidated Order Block — this is now a Breaker Block.
Wait for price to retrace back into the Breaker Block from the opposite direction.', 'Limit order at the proximal edge of the Breaker Block.', 'Beyond the distal edge of the Breaker Block.', 'Previous major liquidity pool or structural swing low/high. Target R:R 1:3.0+.', '1:3.0', 3, '', '9. Liquidity Void Run & Breaker Block Flip — Intraday Strategy', 'When an Order Block fails and is smashed through, mark it as a Breaker Block and trade the retest from the other side.', 'published', 90),
('vwap-3sd-extreme-exhaustion', 'VWAP Standard Deviation Extreme Exhaustion Mean Reversion', 'VWAP 3.0 SD Extreme', 'Fade a stretch to the ±3.0 SD VWAP band only with volume exhaustion, a reversal candle and an extreme RSI; scale out at 1.0 SD and exit at VWAP.', 'MEAN_REVERSION', 'Statistical desks measure extreme moves with standard deviation bands around VWAP. Under a normal distribution about 99.7% of observations fall within ±3 standard deviations, so a move beyond the 3 SD band is statistically rare and often over-extended. Market returns have fatter tails than a normal distribution, so treat the band as a measure of stretch — not as a probability that price will reverse.', 'Highly liquid instruments (SPX500, EURUSD, XAUUSD)', 'SPX500, EURUSD', 'Late-session exhaustion', '5M', '5-minute with VWAP and ±1, ±2, ±3 standard deviation bands.', 'Track price as it moves rapidly toward or beyond the +3.0 SD upper band or the −3.0 SD lower band.
Confirm volume exhaustion: relative volume drops as price strikes the third SD band.
Look for a reversal candle (pin bar, doji or engulfing bar) touching or piercing the 3.0 SD line.
Confirm momentum oscillator alignment (RSI above 80 or below 20).', 'Enter on the close of the reversal candle, pointing back toward the VWAP centre line.', '1.0 ATR beyond the high/low of the exhaustion wick.', 'Target 1: the 1.0 standard deviation band (scale out 50%).
Target 2: the central VWAP baseline (final exit). Target R:R 1:2.5 to 1:4.0.', '1:3.0', 3, '', '10. VWAP Standard Deviation Extreme Exhaustion Mean Reversion — Intraday Strategy', 'Fade a stretch to the ±3.0 SD VWAP band only with volume exhaustion, a reversal candle and an extreme RSI; scale out at 1.0 SD and exit at VWAP.', 'published', 100);

-- "Learn" links in the header and footer menus (only added once; edit or remove them in Control Panel → Navigation)
INSERT INTO `navigation` (`location`, `parent_id`, `label`, `url`, `target`, `sort_order`, `is_enabled`) SELECT 'header', NULL, 'Learn', '/learn', '_self', 22, 1 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `navigation` WHERE `location` = 'header' AND `url` = '/learn');
INSERT INTO `navigation` (`location`, `parent_id`, `label`, `url`, `target`, `sort_order`, `is_enabled`) SELECT 'footer_2', NULL, 'Strategy playbook', '/learn', '_self', 15, 1 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `navigation` WHERE `location` = 'footer_2' AND `url` = '/learn');

-- SEO for the /learn page
INSERT IGNORE INTO `settings` (`key`, `value`, `group_name`) VALUES
('seo_learn_title', 'Learn: 10 intraday trading strategies', 'seo'),
('seo_learn_description', 'An educational playbook of 10 intraday strategies — liquidity raids, order blocks, VWAP reversion, volume profile, order flow and opening range breakouts — with rules, entries, stops and targets.', 'seo');

-- Schema version 3 reached
INSERT INTO `settings` (`key`, `value`, `group_name`) VALUES ('schema_version', '3', 'system') ON DUPLICATE KEY UPDATE `value` = IF(CAST(`value` AS UNSIGNED) < 3, '3', `value`);

-- ---------------------------------------------------------------------------------------------
-- journzey.ai — database update (schema version 4): premium terminal, runner audit, per-account limits
-- ---------------------------------------------------------------------------------------------

-- 20% runner audit results (one row per trade; hypothetical analysis only)
CREATE TABLE IF NOT EXISTS `trade_runner_audits` (
  `trade_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `status` ENUM('ok','unavailable') NOT NULL,
  `runner_exit` DECIMAL(20,8) NULL,
  `best_price` DECIMAL(20,8) NULL,
  `stopped` TINYINT(1) NOT NULL DEFAULT 0,
  `extra_r` DECIMAL(10,4) NULL,
  `extra_pnl` DECIMAL(16,2) NULL,
  `is_demo` TINYINT(1) NOT NULL DEFAULT 0,
  `message` VARCHAR(255) NULL,
  `checked_at` DATETIME NOT NULL,
  PRIMARY KEY (`trade_id`),
  KEY `idx_runner_user` (`user_id`),
  CONSTRAINT `fk_runner_trade` FOREIGN KEY (`trade_id`) REFERENCES `trades` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_runner_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Premium "Obsidian Pro" terminal theme becomes the default (members can still pick another theme in the user menu)
ALTER TABLE `user_settings` ALTER COLUMN `theme` SET DEFAULT 'obsidian-pro';
UPDATE `user_settings` SET `theme` = 'obsidian-pro' WHERE `theme` IN ('dark-terminal', 'clean-light') AND (SELECT `value` FROM `settings` WHERE `key` = 'schema_version') < 4;

-- Daily / weekly loss limits per trading account (copied once from the member's previous global setting)
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `trading_accounts` ADD COLUMN `max_daily_loss` DECIMAL(16,2) NULL AFTER `starting_capital`', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'trading_accounts' AND COLUMN_NAME = 'max_daily_loss');
PREPARE jz_stmt FROM @s;
EXECUTE jz_stmt;
DEALLOCATE PREPARE jz_stmt;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `trading_accounts` ADD COLUMN `daily_limit_type` ENUM(''amount'',''percent'') NOT NULL DEFAULT ''amount'' AFTER `max_daily_loss`', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'trading_accounts' AND COLUMN_NAME = 'daily_limit_type');
PREPARE jz_stmt FROM @s;
EXECUTE jz_stmt;
DEALLOCATE PREPARE jz_stmt;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `trading_accounts` ADD COLUMN `max_weekly_loss` DECIMAL(16,2) NULL AFTER `daily_limit_type`', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'trading_accounts' AND COLUMN_NAME = 'max_weekly_loss');
PREPARE jz_stmt FROM @s;
EXECUTE jz_stmt;
DEALLOCATE PREPARE jz_stmt;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `trading_accounts` ADD COLUMN `weekly_limit_type` ENUM(''amount'',''percent'') NOT NULL DEFAULT ''amount'' AFTER `max_weekly_loss`', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'trading_accounts' AND COLUMN_NAME = 'weekly_limit_type');
PREPARE jz_stmt FROM @s;
EXECUTE jz_stmt;
DEALLOCATE PREPARE jz_stmt;
UPDATE `trading_accounts` a JOIN `user_settings` s ON s.user_id = a.user_id
  SET a.max_daily_loss = s.max_daily_loss, a.daily_limit_type = s.daily_limit_type, a.max_weekly_loss = s.max_weekly_loss, a.weekly_limit_type = s.weekly_limit_type
  WHERE a.max_daily_loss IS NULL AND a.max_weekly_loss IS NULL AND (s.max_daily_loss IS NOT NULL OR s.max_weekly_loss IS NOT NULL)
    AND (SELECT `value` FROM `settings` WHERE `key` = 'schema_version') < 4;

-- Daily flex cards: short public code behind the QR "Verified by journzey.ai" link (stores only the card's own figures)
CREATE TABLE IF NOT EXISTS `share_cards` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` CHAR(12) NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `account_id` INT UNSIGNED NOT NULL,
  `card_date` DATE NOT NULL,
  `payload` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_share_code` (`code`),
  UNIQUE KEY `uq_share_day` (`user_id`, `account_id`, `card_date`),
  CONSTRAINT `fk_share_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_share_account` FOREIGN KEY (`account_id`) REFERENCES `trading_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Customer support email (set it in Control Panel → Settings → Contact details)
INSERT IGNORE INTO `settings` (`key`, `value`, `group_name`) VALUES ('support_email', '', 'general');

-- Record the schema version
INSERT INTO `settings` (`key`, `value`, `group_name`) VALUES ('schema_version', '4', 'system') ON DUPLICATE KEY UPDATE `value` = IF(CAST(`value` AS UNSIGNED) < 4, '4', `value`);
