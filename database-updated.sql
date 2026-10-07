-- ---------------------------------------------------------------------------------------------
-- journzey.ai — database update (schema version 2)
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

-- Record the schema version
INSERT INTO `settings` (`key`, `value`, `group_name`) VALUES ('schema_version', '2', 'system') ON DUPLICATE KEY UPDATE `value` = IF(CAST(`value` AS UNSIGNED) < 2, '2', `value`);
