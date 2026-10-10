-- ---------------------------------------------------------------------------------------------
-- journzey.ai — database update (schema versions 2 to 7)
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

-- Schema version 4 reached
INSERT INTO `settings` (`key`, `value`, `group_name`) VALUES ('schema_version', '4', 'system') ON DUPLICATE KEY UPDATE `value` = IF(CAST(`value` AS UNSIGNED) < 4, '4', `value`);

-- ---------------------------------------------------------------------------------------------
-- journzey.ai — database update (schema version 5): new Log Trade format
-- ---------------------------------------------------------------------------------------------

-- Chart screenshot link (TradingView or another hosted image) next to the uploaded screenshot
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `trades` ADD COLUMN `screenshot_url` VARCHAR(500) NULL AFTER `screenshot_path`', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'trades' AND COLUMN_NAME = 'screenshot_url');
PREPARE jz_stmt FROM @s;
EXECUTE jz_stmt;
DEALLOCATE PREPARE jz_stmt;

-- Schema version 5 reached
INSERT INTO `settings` (`key`, `value`, `group_name`) VALUES ('schema_version', '5', 'system') ON DUPLICATE KEY UPDATE `value` = IF(CAST(`value` AS UNSIGNED) < 5, '5', `value`);

-- ---------------------------------------------------------------------------------------------
-- journzey.ai — database update (schema version 6): University, affiliates, two themes
-- ---------------------------------------------------------------------------------------------

-- Public Learn page shows only "free" strategies; all of them are in the member University
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `learn_strategies` ADD COLUMN `is_free` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'learn_strategies' AND COLUMN_NAME = 'is_free');
PREPARE jz_stmt FROM @s;
EXECUTE jz_stmt;
DEALLOCATE PREPARE jz_stmt;
UPDATE `learn_strategies` SET `is_free` = 1 WHERE COALESCE((SELECT `value` FROM `settings` WHERE `key` = 'schema_version'), 0) < 6 ORDER BY `sort_order`, `id` LIMIT 3;

-- Two terminal themes remain: Obsidian Pro (dark, default) and Clean Light
UPDATE `user_settings` SET `theme` = 'obsidian-pro' WHERE `theme` NOT IN ('obsidian-pro', 'clean-light');

-- Affiliate programme: applications/affiliates, customer attribution and commissions
CREATE TABLE IF NOT EXISTS `affiliates` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `code` VARCHAR(32) NULL,
  `status` ENUM('pending','approved','rejected','suspended') NOT NULL DEFAULT 'pending',
  `commission_pct` DECIMAL(5,2) NOT NULL DEFAULT 25.00,
  `full_name` VARCHAR(120) NOT NULL,
  `platform` VARCHAR(40) NULL,
  `channel_url` VARCHAR(255) NULL,
  `audience` VARCHAR(40) NULL,
  `message` TEXT NULL,
  `payout_details` VARCHAR(500) NULL,
  `clicks` INT UNSIGNED NOT NULL DEFAULT 0,
  `admin_note` VARCHAR(500) NULL,
  `approved_at` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_aff_user` (`user_id`),
  UNIQUE KEY `uq_aff_code` (`code`),
  KEY `idx_aff_status` (`status`),
  CONSTRAINT `fk_aff_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `affiliate_commissions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `affiliate_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NULL,
  `payment_id` INT UNSIGNED NOT NULL,
  `payment_amount` DECIMAL(12,2) NOT NULL,
  `rate` DECIMAL(5,2) NOT NULL,
  `commission` DECIMAL(12,2) NOT NULL,
  `currency` CHAR(3) NOT NULL,
  `status` ENUM('pending','approved','paid','void') NOT NULL DEFAULT 'pending',
  `paid_at` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_comm_payment` (`payment_id`),
  KEY `idx_comm_aff` (`affiliate_id`, `status`),
  CONSTRAINT `fk_comm_aff` FOREIGN KEY (`affiliate_id`) REFERENCES `affiliates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_comm_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `users` ADD COLUMN `referred_by_affiliate_id` INT UNSIGNED NULL AFTER `plan_expires_at`', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'referred_by_affiliate_id');
PREPARE jz_stmt FROM @s;
EXECUTE jz_stmt;
DEALLOCATE PREPARE jz_stmt;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `users` ADD COLUMN `referred_at` DATETIME NULL AFTER `referred_by_affiliate_id`', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'referred_at');
PREPARE jz_stmt FROM @s;
EXECUTE jz_stmt;
DEALLOCATE PREPARE jz_stmt;
SET @s := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `users` ADD KEY `idx_users_affiliate` (`referred_by_affiliate_id`)', 'DO 0') FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'idx_users_affiliate');
PREPARE jz_stmt FROM @s;
EXECUTE jz_stmt;
DEALLOCATE PREPARE jz_stmt;

INSERT IGNORE INTO `settings` (`key`, `value`, `group_name`) VALUES ('affiliate_commission_pct', '25', 'billing'), ('affiliates_enabled', '1', 'billing');

-- "Affiliates" links on the public site (added once; edit them in Control Panel → Navigation)
INSERT INTO `navigation` (`location`, `parent_id`, `label`, `url`, `target`, `sort_order`, `is_enabled`) SELECT 'footer_2', NULL, 'Affiliates', '/affiliates', '_self', 45, 1 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `navigation` WHERE `url` = '/affiliates' AND `location` = 'footer_2');
INSERT INTO `navigation` (`location`, `parent_id`, `label`, `url`, `target`, `sort_order`, `is_enabled`) SELECT 'header', NULL, 'Affiliates', '/affiliates', '_self', 27, 1 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `navigation` WHERE `url` = '/affiliates' AND `location` = 'header');

-- Schema version 6 reached
INSERT INTO `settings` (`key`, `value`, `group_name`) VALUES ('schema_version', '6', 'system') ON DUPLICATE KEY UPDATE `value` = IF(CAST(`value` AS UNSIGNED) < 6, '6', `value`);

-- ---------------------------------------------------------------------------------------------
-- Schema version 7: TradingView market widgets (ticker tape on Home, Markets tab). Settings only.
-- ---------------------------------------------------------------------------------------------
INSERT IGNORE INTO `settings` (`key`, `value`, `group_name`) VALUES
  ('tv_widgets_enabled', '1', 'general'),
  ('tv_ticker_symbols', 'OANDA:XAUUSD | Gold\nOANDA:XAGUSD | Silver\nFX:EURUSD | EUR/USD\nFX:GBPUSD | GBP/USD\nFX:USDJPY | USD/JPY\nFX:AUDUSD | AUD/USD\nFX:USDCAD | USD/CAD\nOANDA:NAS100USD | Nasdaq 100\nOANDA:SPX500USD | S&P 500\nOANDA:US30USD | Dow 30\nOANDA:DE30EUR | DAX 40\nTVC:USOIL | WTI Crude\nBITSTAMP:BTCUSD | Bitcoin\nBITSTAMP:ETHUSD | Ethereum\nBINANCE:SOLUSDT | Solana', 'general'),
  ('tv_calendar_countries', 'us,eu,gb,jp,cn,in,au,ca,ch', 'general');

-- Record the schema version
INSERT INTO `settings` (`key`, `value`, `group_name`) VALUES ('schema_version', '7', 'system') ON DUPLICATE KEY UPDATE `value` = IF(CAST(`value` AS UNSIGNED) < 7, '7', `value`);
