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

CREATE TABLE leads (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(40) NULL,
  whatsapp VARCHAR(40) NULL,
  company VARCHAR(150) NULL,
  service_id INT UNSIGNED NULL,
  service_name VARCHAR(200) NULL,
  preferred_date DATE NULL,
  preferred_time VARCHAR(20) NULL,
  message TEXT NULL,
  consent TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('new','contacted','follow_up','converted','closed') NOT NULL DEFAULT 'new',
  assigned_to INT UNSIGNED NULL,
  source VARCHAR(60) NOT NULL DEFAULT 'book-consultation',
  email_status ENUM('pending','sent','failed','disabled') NOT NULL DEFAULT 'pending',
  email_error VARCHAR(400) NULL,
  ip VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_leads_status (status, created_at),
  KEY idx_leads_created (created_at),
  KEY idx_leads_email (email),
  KEY idx_leads_assigned (assigned_to),
  KEY idx_leads_service (service_id),
  CONSTRAINT fk_leads_service FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE SET NULL,
  CONSTRAINT fk_leads_admin FOREIGN KEY (assigned_to) REFERENCES admins (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE lead_notes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  lead_id INT UNSIGNED NOT NULL,
  admin_id INT UNSIGNED NULL,
  type ENUM('note','status','assignment','email','edit') NOT NULL DEFAULT 'note',
  body TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_lead_notes_lead (lead_id, created_at),
  CONSTRAINT fk_lead_notes_lead FOREIGN KEY (lead_id) REFERENCES leads (id) ON DELETE CASCADE,
  CONSTRAINT fk_lead_notes_admin FOREIGN KEY (admin_id) REFERENCES admins (id) ON DELETE SET NULL
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

SET FOREIGN_KEY_CHECKS = 1;
-- ---------------------------------------------------------------------------------------------
-- Starter content. Everything below is editable in the Control Panel.
-- Items marked [Demo] or [Replace] are placeholders and must not be presented as real facts.
-- ---------------------------------------------------------------------------------------------

INSERT INTO `roles` (`id`, `name`, `slug`, `description`, `permissions`, `is_system`) VALUES
(1, 'Super Admin', 'super-admin', 'Full access to every module, including SMTP, users, roles and system settings.', '["*"]', 1),
(2, 'Editor', 'editor', 'Manages website content, media and leads. No access to SMTP credentials, users, roles or system settings.', '["pages","services","blog","testimonials","faqs","process","sections","homepage","navigation","media","leads","messages"]', 1);

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
('header_cta_label', 'Book a demo', 'header'),
('header_cta_url', '/book-consultation', 'header'),
('header_secondary_label', '', 'header'),
('header_secondary_url', '', 'header'),
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
('footer_cta_text', 'Book a walkthrough of the journal, analytics and discipline tools.', 'footer'),
('footer_cta_label', 'Book a demo', 'footer'),
('footer_cta_url', '/book-consultation', 'footer'),
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
('seo_booking_title', 'Book a demo', 'seo'),
('seo_booking_description', 'Book a walkthrough of the journzey.ai trading journal and discipline terminal.', 'seo'),
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
('booking_eyebrow', 'Book a demo', 'pages'),
('booking_heading', 'See journzey.ai on your own workflow', 'pages'),
('booking_intro', 'Tell us a little about how you trade and pick a time that suits you. We will confirm the session by email.', 'pages'),
('booking_points', 'A guided tour of the journal, analytics and discipline tools\nHow to bring multiple broker accounts into one place\nSetting up rules and risk limits that match your plan\nTime for your questions', 'pages'),
('booking_success', 'Thank you — your request has been received. We will confirm your session by email.', 'pages'),
('booking_consent_text', 'I agree to be contacted about my request and accept the Privacy Policy.', 'pages'),
('notify_email', '', 'pages'),
('booking_send_confirmation', '1', 'pages'),
('contact_send_confirmation', '1', 'pages');

INSERT INTO `services` (`title`, `slug`, `icon`, `thumbnail`, `hero_image`, `short_description`, `full_description`, `benefits`, `process`, `faqs`, `cta_label`, `cta_url`, `meta_title`, `meta_description`, `og_image`, `status`, `is_featured`, `sort_order`) VALUES
('Multi-Broker Trade Journal', 'multi-broker-trade-journal', 'journal', '', '', 'Bring trades from every broker account into a single, structured journal — with setups, tags, notes and screenshots attached to each trade.', '<p>Most traders keep their history scattered across broker statements, spreadsheets and memory. The journzey.ai journal gives every account a single home, so you can review your trading as one coherent record instead of disconnected fragments.</p><h2>What you can capture</h2><ul><li>Entries, exits, size, fees and outcome for every trade</li><li>The setup, timeframe and market conditions you traded</li><li>Your pre-trade plan and post-trade reflection</li><li>Chart screenshots and free-form notes</li></ul><p>Accounts stay separate where it matters and combined where it helps, so you can compare a funded account with a personal one without mixing up the numbers.</p>', '[{"title":"One record for all accounts","text":"Review every broker account side by side or combined."},{"title":"Structured, searchable history","text":"Filter by setup, tag, session, symbol or account."},{"title":"Context that survives","text":"Plans, notes and screenshots stay attached to the trade."}]', '[{"title":"Connect or import","text":"Add your accounts and bring in your trade history."},{"title":"Tag and annotate","text":"Label setups and add notes and screenshots."},{"title":"Review","text":"Use filters and analytics to study your record."}]', '[{"question":"Can I keep accounts separate?","answer":"Yes. Each account keeps its own history and you can choose to view them individually or combined."},{"question":"Which brokers are supported?","answer":"[Replace] List the broker connections and import formats your deployment supports."}]', 'Book a demo', '/book-consultation', '', 'Bring trades from every broker account into a single, structured journal — with setups, tags, notes and screenshots attached to each trade.', '', 'published', 1, 10),
('Performance Analytics', 'performance-analytics', 'chart', '', '', 'Measure what actually drives your results: expectancy, R-multiples, drawdown and performance by setup, session and instrument.', '<p>P&amp;L alone hides more than it reveals. journzey.ai breaks your results down into the measurements that explain them, so you can see which setups carry your performance and which quietly drain it.</p><h2>Key views</h2><ul><li>Expectancy and average R per trade</li><li>Win rate alongside average win and loss size</li><li>Equity curve and drawdown</li><li>Breakdowns by setup, tag, weekday, session and symbol</li></ul>', '[{"title":"Expectancy, not just P&L","text":"Understand the edge behind each strategy."},{"title":"Drill into any slice","text":"Compare setups, sessions and instruments."},{"title":"Spot leaks early","text":"See where losses concentrate before they compound."}]', '[{"title":"Collect","text":"Your journal feeds analytics automatically."},{"title":"Slice","text":"Filter by any dimension you track."},{"title":"Act","text":"Turn findings into rules for the next session."}]', '[{"question":"Do I need to calculate R-multiples myself?","answer":"No. When a trade includes its planned stop, the R-multiple is derived from it."}]', 'Book a demo', '/book-consultation', '', 'Measure what actually drives your results: expectancy, R-multiples, drawdown and performance by setup, session and instrument.', '', 'published', 1, 20),
('Risk & Rules Engine', 'risk-and-rules-engine', 'shield', '', '', 'Write down the rules you trade by — daily loss limits, maximum trades, position sizing — and see every time a trade breaks them.', '<p>Every trader has rules. Few have a record of how often they follow them. The rules engine lets you define your own limits and highlights the trades and days that breached them, so discipline becomes something you can measure.</p><h2>Typical rules</h2><ul><li>Maximum daily or weekly loss</li><li>Maximum number of trades per session</li><li>Risk per trade as a percentage of the account</li><li>Allowed sessions, instruments or setups</li></ul>', '[{"title":"Your rules, written down","text":"Turn your trading plan into explicit, checkable limits."},{"title":"Breaches made visible","text":"See exactly which trades or days broke a rule."},{"title":"Discipline over time","text":"Track how consistently you follow your plan."}]', '[{"title":"Define","text":"Set limits that match your plan."},{"title":"Trade","text":"Each trade is checked against your rules."},{"title":"Review","text":"Study breaches and adjust your process."}]', '[]', 'Book a demo', '/book-consultation', '', 'Write down the rules you trade by — daily loss limits, maximum trades, position sizing — and see every time a trade breaks them.', '', 'published', 1, 30),
('AI Discipline Coach', 'ai-discipline-coach', 'brain', '', '', 'Objective, unemotional feedback on your execution — patterns in your behaviour surfaced from your own journal data.', '<p>It is hard to judge your own trading objectively. The AI discipline coach reviews your journal and highlights behavioural patterns — for example trading more after a loss, cutting winners early or drifting from your planned setups.</p><p>The coach works only from your own records and rules. It does not generate trade signals or financial advice; its job is to help you see your process clearly.</p>', '[{"title":"Pattern detection","text":"Surfaces recurring behaviours across many trades."},{"title":"Grounded in your data","text":"Feedback is based on your journal and your rules."},{"title":"No signals, no hype","text":"A review tool, not a prediction engine."}]', '[{"title":"Journal","text":"Log trades with plans and reflections."},{"title":"Analyse","text":"The coach reviews patterns in your history."},{"title":"Adjust","text":"Apply the feedback to your next sessions."}]', '[{"question":"Does the AI tell me what to trade?","answer":"No. It reviews your behaviour and process. It does not provide trade signals or investment advice."}]', 'Book a demo', '/book-consultation', '', 'Objective, unemotional feedback on your execution — patterns in your behaviour surfaced from your own journal data.', '', 'published', 1, 40),
('Trade Review & Playbooks', 'trade-review-and-playbooks', 'camera', '', '', 'Build a playbook of your best setups with annotated examples, and run structured daily and weekly reviews.', '<p>A playbook turns experience into a repeatable process. Capture your setups with clear criteria and real examples from your journal, then use structured reviews to compare new trades against the standard you set.</p><ul><li>Setup definitions with entry and invalidation criteria</li><li>Annotated screenshots of A-grade examples</li><li>Daily and weekly review checklists</li></ul>', '[{"title":"Repeatable setups","text":"Define what a valid trade looks like before you take it."},{"title":"Visual examples","text":"Keep your best examples one click away."},{"title":"Review routine","text":"Make reflection a habit, not an afterthought."}]', '[]', '[]', 'Book a demo', '/book-consultation', '', 'Build a playbook of your best setups with annotated examples, and run structured daily and weekly reviews.', '', 'published', 1, 50);

INSERT INTO `navigation` (`id`, `location`, `parent_id`, `label`, `url`, `target`, `sort_order`, `is_enabled`) VALUES
(1, 'header', NULL, 'Platform', '/services', '_self', 10, 1),
(2, 'header', 1, 'Multi-Broker Trade Journal', '/services/multi-broker-trade-journal', '_self', 11, 1),
(3, 'header', 1, 'Performance Analytics', '/services/performance-analytics', '_self', 12, 1),
(4, 'header', 1, 'Risk & Rules Engine', '/services/risk-and-rules-engine', '_self', 13, 1),
(5, 'header', 1, 'AI Discipline Coach', '/services/ai-discipline-coach', '_self', 14, 1),
(6, 'header', 1, 'Trade Review & Playbooks', '/services/trade-review-and-playbooks', '_self', 15, 1),
(7, 'header', NULL, 'About', '/about', '_self', 20, 1),
(8, 'header', NULL, 'Blog', '/blog', '_self', 30, 1),
(9, 'header', NULL, 'Contact', '/contact', '_self', 40, 1),
(20, 'footer_2', NULL, 'About', '/about', '_self', 10, 1),
(21, 'footer_2', NULL, 'Blog', '/blog', '_self', 20, 1),
(22, 'footer_2', NULL, 'Contact', '/contact', '_self', 30, 1),
(23, 'footer_2', NULL, 'Book a demo', '/book-consultation', '_self', 40, 1),
(30, 'footer_3', NULL, 'Privacy Policy', '/privacy-policy', '_self', 10, 1),
(31, 'footer_3', NULL, 'Terms & Conditions', '/terms-and-conditions', '_self', 20, 1);

INSERT INTO `pages` (`title`, `slug`, `template`, `hero_eyebrow`, `hero_title`, `hero_subtitle`, `featured_image`, `content`, `blocks`, `status`, `in_sitemap`, `meta_title`, `meta_description`, `og_image`, `canonical_url`, `noindex`, `is_system`, `sort_order`) VALUES
('About', 'about', 'about', 'About journzey.ai', 'We help traders run their trading like a professional desk', 'A trading journal and discipline terminal built on one belief: you cannot improve what you do not measure honestly.', '', '', '[{"type":"split","eyebrow":"Our story","heading":"Built for traders who want evidence, not opinions","body":"<p>journzey.ai started from a simple frustration: traders spend hours studying charts and minutes studying themselves. Broker statements show what happened, but not why — and memory is a biased narrator.</p><p>We are building the tool we wanted: one place for every account, honest measurements of execution, and feedback grounded in a trader''s own rules.</p>","image":"","image_side":"right","items":"","cta_label":"","cta_url":"","background":"default"},{"type":"cards","eyebrow":"Direction","heading":"Mission and vision","body":"","image":"","image_side":"right","items":"Mission | Give every trader an institutional-grade record of their decisions and the tools to review them objectively. | target\\nVision | A trading culture where process is measured as carefully as profit and loss. | compass","cta_label":"","cta_url":"","background":"muted"},{"type":"cards","eyebrow":"Values","heading":"What we stand for","body":"","image":"","image_side":"right","items":"Honesty over hype | We never promise profits. We help you see your process clearly. | shield\\nYour data, your edge | Your journal belongs to you and exists to serve your review. | lock\\nSimplicity under pressure | Tools that stay clear when markets are not. | zap\\nContinuous improvement | Small, measured changes compound over time. | trend-up","cta_label":"","cta_url":"","background":"default"},{"type":"process","eyebrow":"How it works","heading":"A review loop you can repeat every week","body":"","image":"","image_side":"right","items":"","cta_label":"","cta_url":"","background":"muted"},{"type":"stats","eyebrow":"","heading":"[Replace] Add verified company figures","body":"<p>This block is hidden until you add real, verifiable figures. Edit or delete it in Control Panel → Pages → About.</p>","image":"","image_side":"right","items":"[Replace] | Your first verified metric\\n[Replace] | Your second verified metric","cta_label":"","cta_url":"","background":"default","hidden":1},{"type":"cta","eyebrow":"","heading":"See the platform for yourself","body":"<p>Book a walkthrough and bring your questions.</p>","image":"","image_side":"right","items":"","cta_label":"Book a demo","cta_url":"/book-consultation","background":"brand"}]', 'published', 1, 'About', 'Learn why journzey.ai exists and how it helps traders measure and improve their process.', '', '', 0, 1, 10),
('Privacy Policy', 'privacy-policy', 'legal', 'Legal', 'Privacy Policy', 'How we collect, use and protect your information.', '', '<p class="notice"><strong>Template text.</strong> Replace this page with a policy reviewed by a qualified legal professional for your jurisdiction before launch.</p><h2>Who we are</h2><p>This website is operated by journzey.ai ("we", "us"). [Replace with your registered company name and address.]</p><h2>Information we collect</h2><p>When you submit a form on this website we collect the details you provide, such as your name, email address, phone number and message. We also record basic technical information (IP address and browser type) to protect the site against abuse.</p><h2>How we use it</h2><ul><li>To respond to your enquiry or demo request</li><li>To operate, secure and improve this website</li><li>To comply with legal obligations</li></ul><h2>Cookies and analytics</h2><p>This site uses a strictly necessary session cookie for security. If analytics tools are enabled, they may set additional cookies. [Describe the tools you enable.]</p><h2>Retention</h2><p>We keep enquiry records only as long as needed for the purposes above. [State your retention period.]</p><h2>Your rights</h2><p>You may request access to, correction of or deletion of your personal data by contacting us. [Add your privacy contact address.]</p><h2>Changes</h2><p>We may update this policy from time to time. The latest version is always published on this page.</p>', '[]', 'published', 1, 'Privacy Policy', 'How journzey.ai collects, uses and protects personal information.', '', '', 0, 1, 20),
('Terms & Conditions', 'terms-and-conditions', 'legal', 'Legal', 'Terms & Conditions', 'The terms that apply when you use this website.', '', '<p class="notice"><strong>Template text.</strong> Replace this page with a policy reviewed by a qualified legal professional for your jurisdiction before launch.</p><h2>Use of this website</h2><p>By using this website you agree to these terms. If you do not agree, please do not use the site.</p><h2>No investment advice</h2><p>Content on this website and within the journzey.ai platform is provided for information and educational purposes only. It is not investment, financial, legal or tax advice, and nothing here is a recommendation to buy or sell any financial instrument.</p><h2>Risk warning</h2><p>Trading involves substantial risk of loss. You are solely responsible for your trading decisions.</p><h2>Intellectual property</h2><p>The website design, text and software are owned by or licensed to journzey.ai. You may not copy or reuse them without permission.</p><h2>Limitation of liability</h2><p>To the extent permitted by law, we are not liable for losses arising from the use of this website. [Have this clause reviewed for your jurisdiction.]</p><h2>Governing law</h2><p>[Replace with your governing law and jurisdiction.]</p>', '[]', 'published', 1, 'Terms & Conditions', 'Terms and conditions for using the journzey.ai website.', '', '', 0, 1, 30);

INSERT INTO `homepage_sections` (`section_key`, `label`, `is_enabled`, `sort_order`, `eyebrow`, `heading`, `subheading`, `body`, `image`, `background`, `background_image`, `cta_label`, `cta_url`, `cta2_label`, `cta2_url`, `items`, `options`) VALUES
('hero', 'Hero', 1, 10, 'Trading journal · AI discipline terminal', 'Trade your plan. Prove it with data.', 'journzey.ai brings every broker account into one institutional-grade journal, measures your execution against your own rules and turns your history into clear, unemotional feedback.', '', '', 'default', '', 'Book a demo', '/book-consultation', 'Explore the platform', '/services', '[{"title":"Multi-broker journal","text":"","icon":"layers"},{"title":"Rules & risk tracking","text":"","icon":"shield"},{"title":"AI discipline feedback","text":"","icon":"brain"}]', '{"media_type":"visual","video_url":"","mobile_image":"","overlay_color":"#070a12","overlay_opacity":"55","alignment":"left","animate":"1"}'),
('intro', 'Introduction', 1, 20, 'Why it matters', 'Your broker shows what happened. Your journal should show why.', 'Most trading mistakes are not about analysis — they are about execution and discipline. journzey.ai is built to make those patterns visible.', '', '', 'muted', '', '', '', '', '', '[{"title":"Journal","text":"Capture every trade with the plan, context and reflection behind it.","icon":"journal"},{"title":"Measure","text":"See the statistics that explain your results, not just the P&L.","icon":"bars"},{"title":"Improve","text":"Turn findings into rules and check whether you follow them.","icon":"trend-up"}]', '{}'),
('about', 'About', 1, 30, 'The approach', 'Discipline is a process, not a personality trait', '', '<p>Consistency comes from a repeatable loop: plan the trade, execute the plan, review the outcome honestly and adjust. journzey.ai gives that loop structure — from the first fill to the weekly review.</p><p>No signals, no promises. Just a clear record of your decisions and the tools to learn from them.</p>', '', 'default', '', 'About journzey.ai', '/about', '', '', '[]', '{}'),
('services', 'Services', 1, 40, 'Platform', 'Everything you need to run your trading like a desk', 'Five connected capabilities that turn raw trade history into better decisions.', '', '', 'default', '', 'View all capabilities', '/services', '', '', '[]', '{"limit":"6"}'),
('benefits', 'Why journzey.ai', 1, 50, 'Why a journal', 'What changes when you measure your process', '', '', '', 'muted', '', '', '', '', '', '[{"title":"Objective feedback","text":"Replace gut feel about your trading with numbers you can check.","icon":"scale"},{"title":"Every account in one view","text":"Stop reconciling spreadsheets across brokers and platforms.","icon":"layers"},{"title":"Rules you can verify","text":"Know how often you actually follow your trading plan.","icon":"shield"},{"title":"Patterns surfaced early","text":"Spot behavioural leaks before they become expensive habits.","icon":"eye"},{"title":"A review routine","text":"Make daily and weekly reviews quick enough to keep doing.","icon":"calendar"},{"title":"Calm under pressure","text":"A clear interface built for focus during and after the session.","icon":"target"}]', '{}'),
('stats', 'Statistics', 0, 60, '', '[Replace] Add verified figures before enabling this section', 'Do not publish statistics you cannot verify.', '', '', 'dark', '', '', '', '', '', '[{"title":"[Replace]","text":"Your first verified metric","icon":""},{"title":"[Replace]","text":"Your second verified metric","icon":""},{"title":"[Replace]","text":"Your third verified metric","icon":""}]', '{}'),
('process', 'Process', 1, 70, 'How it works', 'From raw fills to better decisions', 'A simple loop you can repeat after every session.', '', '', 'default', '', 'Book a demo', '/book-consultation', '', '', '[]', '{}'),
('featured', 'Featured content', 1, 80, 'From the blog', 'Ideas for a more measurable trading process', '', '', '', 'muted', '', 'Read the blog', '/blog', '', '', '[]', '{"limit":"3"}'),
('testimonials', 'Testimonials', 1, 90, 'Testimonials', 'What traders say', 'Demo testimonials are shown with a "Demo" label until you replace them with real reviews.', '', '', 'default', '', '', '', '', '', '[]', '{"limit":"6"}'),
('faq', 'FAQ', 1, 100, 'FAQ', 'Frequently asked questions', '', '', '', 'muted', '', 'Ask a question', '/contact', '', '', '[]', '{"category":"general","limit":"8"}'),
('cta', 'Call to action', 1, 110, 'Get started', 'Ready to see your trading clearly?', 'Book a guided walkthrough of the journal, analytics and discipline tools — built around how you already trade.', '', '', 'brand', '', 'Book a demo', '/book-consultation', 'Contact us', '/contact', '[]', '{}'),
('contact', 'Contact', 1, 120, 'Contact', 'Talk to the team', 'Questions about the platform or onboarding? We are happy to help.', '', '', 'default', '', 'Send a message', '/contact', '', '', '[]', '{}');

INSERT INTO `process_steps` (`step_number`, `title`, `description`, `icon`, `image`, `status`, `sort_order`) VALUES
('01', 'Bring your accounts together', 'Add each broker account and import your trade history into one journal.', 'link', '', 'published', 10),
('02', 'Journal with context', 'Tag setups and attach your plan, notes and screenshots to each trade.', 'journal', '', 'published', 20),
('03', 'Measure what matters', 'Review expectancy, drawdown and rule adherence by setup, session and account.', 'chart', '', 'published', 30),
('04', 'Improve with feedback', 'Use AI-assisted reviews to spot behavioural patterns and refine your rules.', 'brain', '', 'published', 40);

INSERT INTO `faqs` (`question`, `answer`, `category`, `status`, `sort_order`) VALUES
('What is journzey.ai?', 'journzey.ai is a trading journal and discipline terminal. It brings your trades from multiple broker accounts into one place, measures your performance and rule adherence, and provides objective feedback on your execution.', 'general', 'published', 10),
('Does journzey.ai give trading signals or advice?', 'No. journzey.ai is a journaling and analytics tool. It helps you review your own decisions; it does not provide signals, recommendations or investment advice.', 'general', 'published', 20),
('Can I use it with more than one broker?', 'Yes — the journal is designed for traders with several accounts. [Replace] List the specific broker connections and import formats available in your plan.', 'general', 'published', 30),
('Who is it for?', 'Active traders who want to treat their trading like a professional process — including traders working towards or managing funded accounts and those running several personal accounts.', 'general', 'published', 40),
('How is my data handled?', '[Replace] Describe where data is stored, who can access it and how it is protected. Link to your Privacy Policy for full details.', 'general', 'published', 50),
('How do I get started?', 'Book a demo and we will walk you through the platform and help you set up your accounts and rules.', 'general', 'published', 60);

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
(1, 1, NULL, 'journzey.ai Team', 'How to build a pre-trade checklist you will actually use', 'how-to-build-a-pre-trade-checklist', 'A checklist only helps if it is short enough to use under pressure. Here is a practical way to build one from your own trading history.', '<p>Pre-trade checklists fail for one of two reasons: they are too long to use in the moment, or they are written from generic advice rather than your own mistakes. The fix for both is the same — build the checklist from your journal.</p><h2>1. Start from your losses</h2><p>Filter your journal for your largest losing trades and ask a single question of each: what would I have needed to check to avoid this? Write the answers down without editing them.</p><h2>2. Group and reduce</h2><p>You will find the same few causes repeating — entering before confirmation, trading outside your session, sizing up after a loss. Merge similar items until you have five or fewer.</p><h2>3. Make each item binary</h2><p>"Is the market trending?" invites debate. "Is price above the 20-period average on the higher timeframe?" does not. Every item should be answerable with yes or no in seconds.</p><h2>4. Record whether you used it</h2><p>Add a field to your journal for checklist completion. After a few weeks, compare results for trades taken with and without a complete checklist. The data will tell you whether the checklist is working.</p><blockquote>A checklist is a hypothesis about what makes a good trade. Your journal is how you test it.</blockquote>', '', 'published', 1, '2026-09-26 19:22:40', '', 'A checklist only helps if it is short enough to use under pressure. Here is a practical way to build one from your own trading history.', ''),
(2, 2, NULL, 'journzey.ai Team', 'Why R-multiples beat dollar P&L for reviewing trades', 'why-r-multiples-beat-dollar-pnl', 'Dollar P&L changes with position size. R-multiples measure the quality of the decision itself — and make different accounts comparable.', '<p>If you risk different amounts on different trades, or trade several accounts of different sizes, dollar P&L is a noisy way to judge your decisions. R-multiples remove that noise.</p><h2>What is an R-multiple?</h2><p>R is the amount you planned to risk on a trade — the distance from entry to your stop, multiplied by size. A trade that makes twice what you risked is +2R; one that hits its stop is −1R.</p><h2>Why it matters for review</h2><ul><li><strong>Comparable across accounts.</strong> A +2R trade is +2R whether the account is large or small.</li><li><strong>Separates decision from size.</strong> You can see whether a setup is good independently of how big you traded it.</li><li><strong>Exposes stop discipline.</strong> Losses larger than −1R show where stops were moved or ignored.</li></ul><h2>Expectancy in R</h2><p>Average R per trade is your expectancy. Tracked by setup, it shows which strategies deserve more of your attention — and which are quietly costing you.</p>', '', 'published', 1, '2026-09-30 19:22:40', '', 'Dollar P&L changes with position size. R-multiples measure the quality of the decision itself — and make different accounts comparable.', ''),
(3, 1, NULL, 'journzey.ai Team', 'Journaling across multiple broker accounts: a practical workflow', 'journaling-across-multiple-broker-accounts', 'Running several accounts makes review harder. A simple, consistent workflow keeps your record complete without eating your evening.', '<p>Many traders run more than one account — a funded account alongside a personal one, or separate accounts for different strategies. Each extra account multiplies the review work unless you have a routine.</p><h2>Keep accounts separate, review them together</h2><p>Each account should keep its own history so balances and limits stay accurate. But your behaviour is shared across all of them, so your review should look at the combined picture too.</p><h2>A ten-minute daily routine</h2><ol><li>Bring in the day&#39;s trades from every account.</li><li>Tag each trade with its setup.</li><li>Add a one-line reflection to any trade that broke a rule.</li><li>Check the day against your daily loss and trade-count limits.</li></ol><h2>A weekly deep-dive</h2><p>Once a week, compare setups across accounts. A strategy that works in one account but not another usually points to a difference in execution, sizing or session — exactly the kind of insight a combined journal makes visible.</p>', '', 'published', 0, '2026-10-04 19:22:40', '', 'Running several accounts makes review harder. A simple, consistent workflow keeps your record complete without eating your evening.', '');

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
('lead_admin', 'New lead notification', 'Sent to the team when someone books a demo / consultation.', 'New demo request from {name}', '<p>A new demo request was submitted on {site_name}.</p><p><strong>Name:</strong> {name}<br><strong>Email:</strong> {email}<br><strong>Phone:</strong> {phone}<br><strong>Interested in:</strong> {service}<br><strong>Preferred time:</strong> {preferred_date} {preferred_time}</p><p><strong>Message:</strong><br>{message}</p><p>Submitted {date}. View it in the Control Panel under Leads.</p>', 1),
('lead_user', 'Lead confirmation', 'Sent to the visitor after booking a demo / consultation.', 'We received your request — {site_name}', '<p>Hi {name},</p><p>Thank you for your interest in {site_name}. We have received your request and will confirm your session by email.</p><p><strong>Interested in:</strong> {service}<br><strong>Preferred time:</strong> {preferred_date} {preferred_time}</p><p>— The {site_name} team</p>', 1),
('contact_admin', 'Contact message notification', 'Sent to the team when the contact form is submitted.', 'New contact message: {subject}', '<p>A new message was submitted on {site_name}.</p><p><strong>Name:</strong> {name}<br><strong>Email:</strong> {email}<br><strong>Phone:</strong> {phone}<br><strong>Subject:</strong> {subject}</p><p><strong>Message:</strong><br>{message}</p><p>Submitted {date}.</p>', 1),
('contact_user', 'Contact confirmation', 'Sent to the visitor after using the contact form.', 'Thanks for contacting {site_name}', '<p>Hi {name},</p><p>Thanks for getting in touch. We have received your message and will reply as soon as we can.</p><p><strong>Your message:</strong><br>{message}</p><p>— The {site_name} team</p>', 1);

INSERT INTO `smtp_settings` (`id`, `host`, `port`, `username`, `password_enc`, `encryption`, `from_email`, `from_name`, `reply_to`, `is_enabled`) VALUES (1, '', 587, '', NULL, 'tls', '', 'journzey.ai', '', 0);
