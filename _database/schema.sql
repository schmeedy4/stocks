-- =========================================================
-- portfolio_tracker (MySQL 8+)
-- multi-user + FIFO capital gains + DOH-DIV dividends export
-- =========================================================

-- -----------------------------
-- 1) user
-- -----------------------------
CREATE TABLE user (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL,
  password_hash VARCHAR(255) NULL, -- if using local auth; can be null if Keycloak/Zitadel later
  first_name VARCHAR(100) NULL,
  last_name VARCHAR(100) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,

  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uk_user_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------
-- 2) app_settings (one per user)
-- -----------------------------
CREATE TABLE app_settings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,

  tax_payer_id VARCHAR(32) NULL,                 -- davčna številka (TaxPayerID)
  tax_payer_type ENUM('FO','PO') NOT NULL DEFAULT 'FO',
  document_workflow_id ENUM('O','P') NOT NULL DEFAULT 'O',

  base_currency CHAR(3) NOT NULL DEFAULT 'EUR',

  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,

  UNIQUE KEY uk_settings_user (user_id),

  CONSTRAINT fk_settings_user FOREIGN KEY (user_id) REFERENCES user(id),
  CONSTRAINT fk_settings_created_by FOREIGN KEY (created_by) REFERENCES user(id),
  CONSTRAINT fk_settings_updated_by FOREIGN KEY (updated_by) REFERENCES user(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------
-- 3) broker_account (per user)
-- -----------------------------
CREATE TABLE broker_account (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,

  broker_code VARCHAR(64) NOT NULL,              -- 'REVOLUT', 'IBKR', 'NLB', etc.
  name VARCHAR(128) NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'EUR',
  is_active TINYINT(1) NOT NULL DEFAULT 1,

  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,

  UNIQUE KEY uk_broker_user_code (user_id, broker_code),

  CONSTRAINT fk_broker_user FOREIGN KEY (user_id) REFERENCES user(id),
  CONSTRAINT fk_broker_created_by FOREIGN KEY (created_by) REFERENCES user(id),
  CONSTRAINT fk_broker_updated_by FOREIGN KEY (updated_by) REFERENCES user(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------
-- 4) dividend_payer (per user, because address/name formatting differs)
-- -----------------------------
CREATE TABLE dividend_payer (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,

  payer_name VARCHAR(255) NOT NULL,              -- naziv izplačevalca
  payer_address VARCHAR(255) NOT NULL,           -- naslov izplačevalca
  payer_country_code CHAR(2) NOT NULL,           -- država izplačevalca (SI/US/...)

  payer_si_tax_id VARCHAR(32) NULL,              -- only if SI
  payer_foreign_tax_id VARCHAR(64) NULL,         -- optional

  default_source_country_code CHAR(2) NULL,      -- država vira default
  default_dividend_type_code VARCHAR(16) NULL,   -- šifra default

  is_active TINYINT(1) NOT NULL DEFAULT 1,

  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,

  KEY idx_payer_user_name (user_id, payer_name),

  CONSTRAINT fk_payer_user FOREIGN KEY (user_id) REFERENCES user(id),
  CONSTRAINT fk_payer_created_by FOREIGN KEY (created_by) REFERENCES user(id),
  CONSTRAINT fk_payer_updated_by FOREIGN KEY (updated_by) REFERENCES user(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------
-- 5) instrument (GLOBAL catalog)
-- Keep global so multiple users share the same ISIN/ticker rows.
-- -----------------------------
CREATE TABLE instrument (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

  isin VARCHAR(16) NULL,                         -- US0378331005
  ticker VARCHAR(32) NULL,                       -- AAPL
  name VARCHAR(255) NOT NULL,
  instrument_type ENUM('STOCK','ETF','ADR','BOND','OTHER') NOT NULL DEFAULT 'STOCK',
  country_code CHAR(2) NULL,
  trading_currency CHAR(3) NULL,
  dividend_payer_id BIGINT UNSIGNED NULL,

  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uk_instrument_isin (isin),
  KEY idx_instrument_ticker (ticker),
  
  CONSTRAINT fk_instrument_dividend_payer FOREIGN KEY (dividend_payer_id) REFERENCES dividend_payer(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------
-- 6) dividend (per user)
-- -----------------------------
CREATE TABLE dividend (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,

  broker_account_id BIGINT UNSIGNED NULL,
  instrument_id BIGINT UNSIGNED NULL,
  dividend_payer_id BIGINT UNSIGNED NOT NULL,

  received_date DATE NOT NULL,                   -- datum prejema (exported)
  ex_date DATE NULL,
  pay_date DATE NULL,

  dividend_type_code VARCHAR(16) NOT NULL,       -- šifra vrste dividend
  source_country_code CHAR(2) NOT NULL,          -- država vira

  gross_amount_eur DECIMAL(18,2) NOT NULL,
  foreign_tax_eur DECIMAL(18,2) NULL,            -- must be NULL if payer_country=SI

  original_currency CHAR(3) NULL,
  gross_amount_original DECIMAL(18,6) NULL,
  foreign_tax_original DECIMAL(18,6) NULL,
  fx_rate_to_eur DECIMAL(18,8) NULL,             -- used on received_date

  -- Optional: only needed when same payer+same day has multiple dividend rows (edavki collision rule)
  payer_ident_for_export VARCHAR(64) NULL,

  treaty_exemption_text VARCHAR(100) NULL,
  notes VARCHAR(255) NULL,

  is_voided TINYINT(1) NOT NULL DEFAULT 0,
  void_reason VARCHAR(255) NULL,

  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,

  KEY idx_div_user_date (user_id, received_date),
  KEY idx_div_user_payer_date (user_id, dividend_payer_id, received_date),

  -- Optional duplicate guard (you can remove if it blocks valid cases):
  -- prevents accidentally importing the same dividend twice
  UNIQUE KEY uk_dividend_dedupe (user_id, dividend_payer_id, received_date, gross_amount_eur, foreign_tax_eur),

  CONSTRAINT fk_div_user FOREIGN KEY (user_id) REFERENCES user(id),
  CONSTRAINT fk_div_broker FOREIGN KEY (broker_account_id) REFERENCES broker_account(id),
  CONSTRAINT fk_div_instr FOREIGN KEY (instrument_id) REFERENCES instrument(id),
  CONSTRAINT fk_div_payer FOREIGN KEY (dividend_payer_id) REFERENCES dividend_payer(id),
  CONSTRAINT fk_div_created_by FOREIGN KEY (created_by) REFERENCES user(id),
  CONSTRAINT fk_div_updated_by FOREIGN KEY (updated_by) REFERENCES user(id),

  CONSTRAINT chk_div_gross_pos CHECK (gross_amount_eur > 0),
  CONSTRAINT chk_div_tax_nonneg CHECK (foreign_tax_eur IS NULL OR foreign_tax_eur >= 0),
  CONSTRAINT chk_div_tax_le_gross CHECK (foreign_tax_eur IS NULL OR foreign_tax_eur <= gross_amount_eur)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------
-- 7) trade (BUY/SELL events, per user)
-- total_value_eur includes fx conversion; fee_eur stored
-- -----------------------------
CREATE TABLE trade (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,

  broker_account_id BIGINT UNSIGNED NULL,
  instrument_id BIGINT UNSIGNED NOT NULL,

  trade_type ENUM('BUY','SELL') NOT NULL,
  trade_date DATE NOT NULL,

  quantity DECIMAL(18,6) NOT NULL,
  price_per_unit DECIMAL(18,8) NOT NULL,
  price_eur DECIMAL(18,8) NOT NULL,
  trade_currency CHAR(3) NOT NULL,

  fee_amount DECIMAL(18,8) NULL,
  fee_currency CHAR(3) NULL,

  fx_rate_to_eur DECIMAL(18,8) NOT NULL,         -- rate used on trade_date
  total_value_eur DECIMAL(18,2) NOT NULL,        -- (qty*price)*fx_rate
  fee_eur DECIMAL(18,2) NOT NULL DEFAULT 0,

  notes VARCHAR(255) NULL,

  is_voided TINYINT(1) NOT NULL DEFAULT 0,
  void_reason VARCHAR(255) NULL,

  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,

  KEY idx_trade_user_date (user_id, trade_date),
  KEY idx_trade_user_instr_date (user_id, instrument_id, trade_date),

  CONSTRAINT fk_trade_user FOREIGN KEY (user_id) REFERENCES user(id),
  CONSTRAINT fk_trade_broker FOREIGN KEY (broker_account_id) REFERENCES broker_account(id),
  CONSTRAINT fk_trade_instr FOREIGN KEY (instrument_id) REFERENCES instrument(id),
  CONSTRAINT fk_trade_created_by FOREIGN KEY (created_by) REFERENCES user(id),
  CONSTRAINT fk_trade_updated_by FOREIGN KEY (updated_by) REFERENCES user(id),

  CONSTRAINT chk_trade_qty_pos CHECK (quantity > 0),
  CONSTRAINT chk_trade_price_pos CHECK (price_per_unit >= 0),
  CONSTRAINT chk_trade_price_eur_nonneg CHECK (price_eur >= 0),
  CONSTRAINT chk_trade_total_pos CHECK (total_value_eur >= 0),
  CONSTRAINT chk_trade_fee_nonneg CHECK (fee_eur >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------
-- 8) trade_lot (FIFO lots created from BUY trades)
-- cost_basis_eur includes proportional buy fees
-- -----------------------------
CREATE TABLE trade_lot (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,

  buy_trade_id BIGINT UNSIGNED NOT NULL,
  instrument_id BIGINT UNSIGNED NOT NULL,

  opened_date DATE NOT NULL,
  quantity_opened DECIMAL(18,6) NOT NULL,
  quantity_remaining DECIMAL(18,6) NOT NULL,

  cost_basis_eur DECIMAL(18,2) NOT NULL,

  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,

  KEY idx_lot_user_instr_date (user_id, instrument_id, opened_date),

  CONSTRAINT fk_lot_user FOREIGN KEY (user_id) REFERENCES user(id),
  CONSTRAINT fk_lot_buy_trade FOREIGN KEY (buy_trade_id) REFERENCES trade(id),
  CONSTRAINT fk_lot_instr FOREIGN KEY (instrument_id) REFERENCES instrument(id),
  CONSTRAINT fk_lot_created_by FOREIGN KEY (created_by) REFERENCES user(id),
  CONSTRAINT fk_lot_updated_by FOREIGN KEY (updated_by) REFERENCES user(id),

  CONSTRAINT chk_lot_qty_pos CHECK (quantity_opened > 0),
  CONSTRAINT chk_lot_qty_rem_nonneg CHECK (quantity_remaining >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------
-- 9) trade_lot_allocation (SELL consumes FIFO lots)
-- Each SELL creates 1..N allocations
-- -----------------------------
CREATE TABLE trade_lot_allocation (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,

  sell_trade_id BIGINT UNSIGNED NOT NULL,
  trade_lot_id BIGINT UNSIGNED NOT NULL,

  quantity_consumed DECIMAL(18,6) NOT NULL,

  proceeds_eur DECIMAL(18,2) NOT NULL,
  cost_basis_eur DECIMAL(18,2) NOT NULL,
  realized_pnl_eur DECIMAL(18,2) NOT NULL,

  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,

  KEY idx_alloc_user_sell (user_id, sell_trade_id),
  KEY idx_alloc_user_lot (user_id, trade_lot_id),

  CONSTRAINT fk_alloc_user FOREIGN KEY (user_id) REFERENCES user(id),
  CONSTRAINT fk_alloc_sell_trade FOREIGN KEY (sell_trade_id) REFERENCES trade(id),
  CONSTRAINT fk_alloc_lot FOREIGN KEY (trade_lot_id) REFERENCES trade_lot(id),
  CONSTRAINT fk_alloc_created_by FOREIGN KEY (created_by) REFERENCES user(id),
  CONSTRAINT fk_alloc_updated_by FOREIGN KEY (updated_by) REFERENCES user(id),

  CONSTRAINT chk_alloc_qty_pos CHECK (quantity_consumed > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------
-- 10) instrument_price_daily (daily prices for instruments)
-- -----------------------------
CREATE TABLE instrument_price_daily (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

  user_id BIGINT UNSIGNED NOT NULL,
  instrument_id BIGINT UNSIGNED NOT NULL,

  price_date DATE NOT NULL,

  open_price  DECIMAL(18,6) NULL,
  high_price  DECIMAL(18,6) NULL,
  low_price   DECIMAL(18,6) NULL,
  close_price DECIMAL(18,6) NOT NULL,

  volume BIGINT UNSIGNED NULL,

  currency CHAR(3) NOT NULL DEFAULT 'USD',
  source VARCHAR(32) NOT NULL DEFAULT 'twelvedata',
  fetched_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  KEY idx_price_user_date (user_id, price_date),
  KEY idx_price_user_instr (user_id, instrument_id),
  UNIQUE KEY uniq_price_user_instr_date (user_id, instrument_id, price_date),

  CONSTRAINT fk_price_user FOREIGN KEY (user_id) REFERENCES user(id),
  CONSTRAINT fk_price_instr FOREIGN KEY (instrument_id) REFERENCES instrument(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------
-- 11) instrument_price_latest (latest prices for instruments)
-- -----------------------------
CREATE TABLE instrument_price_latest (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

  user_id BIGINT UNSIGNED NOT NULL,
  instrument_id BIGINT UNSIGNED NOT NULL,

  price DECIMAL(18,6) NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'USD',

  source VARCHAR(32) NOT NULL DEFAULT 'twelvedata',
  fetched_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uniq_latest_user_instr (user_id, instrument_id),

  KEY idx_latest_user (user_id),

  CONSTRAINT fk_latest_user FOREIGN KEY (user_id) REFERENCES user(id),
  CONSTRAINT fk_latest_instr FOREIGN KEY (instrument_id) REFERENCES instrument(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------
-- 12) news_article (news articles for instruments)
-- -----------------------------
CREATE TABLE news_article (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

  -- Source & identity
  source VARCHAR(64) NOT NULL,
  url TEXT NOT NULL,
  url_hash BINARY(32) NOT NULL,
  title VARCHAR(512) NOT NULL,

  -- Timing
  published_at DATETIME NULL,
  captured_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  -- Author
  author_name VARCHAR(255) NULL,
  author_url TEXT NULL,
  author_followers INT UNSIGNED NULL,

  -- Analysis results
  sentiment ENUM('bullish','bearish','neutral','mixed') NOT NULL,
  confidence TINYINT UNSIGNED NOT NULL,
  read_grade TINYINT UNSIGNED NOT NULL,
  tickers JSON NOT NULL,

  -- Structured analysis
  drivers JSON NOT NULL,
  key_dates JSON NOT NULL,
  tags JSON NOT NULL,
  recap TEXT NOT NULL,

  -- Full original JSON (for reprocessing / audits)
  raw_json JSON NOT NULL,

  -- Indexes & constraints
  UNIQUE KEY uq_source_urlhash (source, url_hash),
  KEY idx_sentiment (sentiment),
  KEY idx_read_grade (read_grade),
  KEY idx_published_at (published_at),
  KEY idx_author_name (author_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------
-- 13) document (documents for user)
-- -----------------------------
CREATE TABLE document (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

  user_id BIGINT UNSIGNED NOT NULL,

  -- What this document is intended for
  document_kind ENUM(
    'TRADES',
    'DIVIDENDS',
    'BUY_TRADES',
    'SELL_TRADES',
    'MIXED',
    'OTHER'
  ) NOT NULL,

  broker_code VARCHAR(50) NULL,           -- REVOLUT, IBKR, etc (optional)
  statement_period_from DATE NULL,
  statement_period_to DATE NULL,

  -- File info
  original_filename VARCHAR(255) NOT NULL,
  mime_type VARCHAR(120) NOT NULL,
  file_size_bytes BIGINT UNSIGNED NOT NULL,

  storage_disk ENUM('LOCAL','S3') NOT NULL DEFAULT 'LOCAL',
  storage_path VARCHAR(1024) NOT NULL,

  sha256 CHAR(64) NOT NULL,                -- dedupe + integrity check

  -- Parsing / assistance lifecycle
  parse_status ENUM(
    'UPLOADED',
    'READY_FOR_REVIEW',
    'PARTIALLY_REVIEWED',
    'POSTED',
    'ERROR'
  ) NOT NULL DEFAULT 'UPLOADED',

  parse_error VARCHAR(1024) NULL,

  -- Parsed content (assist-only, never blindly trusted)
  extracted_data JSON NULL,                -- parsed rows, OCR output, CSV rows, etc
  extraction_notes VARCHAR(1024) NULL,     -- warnings, ambiguities, hints

  -- Provenance: what this document created
  created_trade_ids JSON NULL,             -- [123,124,...]
  created_dividend_ids JSON NULL,          -- [55,56,...]

  notes VARCHAR(1024) NULL,                -- your manual notes

  uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uniq_user_sha (user_id, sha256),
  KEY idx_user_kind (user_id, document_kind, uploaded_at),
  KEY idx_user_status (user_id, parse_status),

  CONSTRAINT fk_document_user
    FOREIGN KEY (user_id) REFERENCES user(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------
-- 14) dividend_document (documents for dividends)
-- -----------------------------
CREATE TABLE dividend_document (
  dividend_id BIGINT UNSIGNED NOT NULL,
  document_id BIGINT UNSIGNED NOT NULL,

  PRIMARY KEY (dividend_id, document_id),

  CONSTRAINT fk_dd_dividend
    FOREIGN KEY (dividend_id) REFERENCES dividend(id),

  CONSTRAINT fk_dd_document
    FOREIGN KEY (document_id) REFERENCES document(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------
-- 15) trade_document (documents for trades)
-- -----------------------------
CREATE TABLE trade_document (
  trade_id BIGINT UNSIGNED NOT NULL,
  document_id BIGINT UNSIGNED NOT NULL,

  PRIMARY KEY (trade_id, document_id),

  CONSTRAINT fk_td_trade
    FOREIGN KEY (trade_id) REFERENCES trade(id),

  CONSTRAINT fk_td_document
    FOREIGN KEY (document_id) REFERENCES document(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;