-- Idempotent schema patch for tables modified in the latest implementation.
-- Target: MySQL/MariaDB
-- Notes:
-- 1) This script intentionally avoids hard foreign keys for `created_by` on
--    subscription tables to stay compatible with legacy/test databases where
--    `users.id` type/engine differs.
-- 2) Safe to re-run.

SET @db := DATABASE();

-- ---------------------------------------------------------------------------
-- 1) mpcs_9a_form_settings: add sub_categories_data, no_of_rows_per_page
-- ---------------------------------------------------------------------------
SELECT IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @db AND table_name = 'mpcs_9a_form_settings'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @db
          AND table_name = 'mpcs_9a_form_settings'
          AND column_name = 'sub_categories_data'
    ),
    'ALTER TABLE `mpcs_9a_form_settings` ADD COLUMN `sub_categories_data` LONGTEXT NULL COMMENT ''JSON data for sub-categories with their previous day values'' AFTER `pre_day_grand_total`',
    'SELECT ''skip: mpcs_9a_form_settings.sub_categories_data'''
) INTO @sql;
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @db AND table_name = 'mpcs_9a_form_settings'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @db
          AND table_name = 'mpcs_9a_form_settings'
          AND column_name = 'no_of_rows_per_page'
    ),
    'ALTER TABLE `mpcs_9a_form_settings` ADD COLUMN `no_of_rows_per_page` INT NULL COMMENT ''Number of rows to display per page'' AFTER `sub_categories_data`',
    'SELECT ''skip: mpcs_9a_form_settings.no_of_rows_per_page'''
) INTO @sql;
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- 2) pump_operator_assignments: create table if missing
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pump_operator_assignments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `pump_id` INT UNSIGNED NOT NULL,
  `pump_operator_id` INT UNSIGNED NOT NULL,
  `starting_meter` DECIMAL(15,6) NOT NULL DEFAULT 0,
  `closing_meter` DECIMAL(15,6) NULL DEFAULT 0,
  `date_and_time` TIMESTAMP NULL DEFAULT NULL,
  `close_date_and_time` TIMESTAMP NULL DEFAULT NULL,
  `status` ENUM('open','close') NOT NULL DEFAULT 'open',
  `settlement_id` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `assigned_by` INT NULL,
  `is_confirmed` INT NOT NULL DEFAULT 0,
  `confirmed_at` TIMESTAMP NULL DEFAULT NULL,
  `is_manually_closed` INT NOT NULL DEFAULT 0,
  `pump_operator_other_sale_id` INT NULL,
  `closed_in_settlement` INT NOT NULL DEFAULT 0,
  `shift_id` INT NULL,
  `shift_number` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `business_id` (`business_id`),
  KEY `pump_id` (`pump_id`),
  KEY `pump_operator_id` (`pump_operator_id`),
  KEY `settlement_id` (`settlement_id`),
  KEY `pump_operator_other_sale_id` (`pump_operator_other_sale_id`),
  KEY `shift_id` (`shift_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 3) settlement_credit_sale_payments: add bill_number + index
-- ---------------------------------------------------------------------------
SELECT IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @db AND table_name = 'settlement_credit_sale_payments'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @db
          AND table_name = 'settlement_credit_sale_payments'
          AND column_name = 'bill_number'
    ),
    'ALTER TABLE `settlement_credit_sale_payments` ADD COLUMN `bill_number` VARCHAR(255) NULL AFTER `collection_form_no`',
    'SELECT ''skip: settlement_credit_sale_payments.bill_number'''
) INTO @sql;
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @db AND table_name = 'settlement_credit_sale_payments'
    )
    AND EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @db
          AND table_name = 'settlement_credit_sale_payments'
          AND column_name = 'bill_number'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = @db
          AND table_name = 'settlement_credit_sale_payments'
          AND index_name = 'settlement_credit_sale_payments_bill_number_index'
    ),
    'ALTER TABLE `settlement_credit_sale_payments` ADD INDEX `settlement_credit_sale_payments_bill_number_index` (`bill_number`)',
    'SELECT ''skip: settlement_credit_sale_payments.bill_number_index'''
) INTO @sql;
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- 4) subscription_bank_accounts: create table if missing (no FK by design)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `subscription_bank_accounts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `template_name` VARCHAR(255) NOT NULL,
  `ac_name` VARCHAR(255) NOT NULL,
  `ac_no` VARCHAR(255) NOT NULL,
  `bank` VARCHAR(255) NOT NULL,
  `branch` VARCHAR(255) NOT NULL,
  `status` ENUM('enabled','disabled') NOT NULL DEFAULT 'enabled',
  `created_by` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subscription_bank_accounts_created_by_index` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 5) subscription_payment_terms: create table if missing (no FK by design)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `subscription_payment_terms` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `terms` TEXT NOT NULL,
  `status` ENUM('enabled','disabled') NOT NULL DEFAULT 'enabled',
  `created_by` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subscription_payment_terms_created_by_index` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 6) subscription_banners: create table if missing (no FK by design)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `subscription_banners` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `file_path` VARCHAR(255) NOT NULL,
  `created_by` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subscription_banners_created_by_index` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 7) subscription_invoice_prefixes: create table if missing (no FK by design)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `subscription_invoice_prefixes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `prefix` VARCHAR(255) NOT NULL,
  `current_number` INT NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscription_invoice_prefixes_prefix_unique` (`prefix`),
  KEY `subscription_invoice_prefixes_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 8) subscription_invoices: create table if missing (no FK by design)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `subscription_invoices` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_no` VARCHAR(255) NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `customer_id` BIGINT UNSIGNED NOT NULL,
  `customer_code` VARCHAR(255) NULL,
  `from_date` DATE NOT NULL,
  `to_date` DATE NOT NULL,
  `bank_account_id` BIGINT UNSIGNED NULL,
  `payment_term_id` BIGINT UNSIGNED NULL,
  `banner_id` BIGINT UNSIGNED NULL,
  `payment_details` TEXT NULL,
  `total` DECIMAL(15,2) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subscription_invoices_user_id_index` (`user_id`),
  KEY `subscription_invoices_customer_id_index` (`customer_id`),
  KEY `subscription_invoices_bank_account_id_index` (`bank_account_id`),
  KEY `subscription_invoices_payment_term_id_index` (`payment_term_id`),
  KEY `subscription_invoices_banner_id_index` (`banner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 9) subscription_invoice_items: create table if missing (no FK by design)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `subscription_invoice_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_id` BIGINT UNSIGNED NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `qty` DECIMAL(10,2) NOT NULL DEFAULT 1.00,
  `price` DECIMAL(15,2) NOT NULL,
  `total` DECIMAL(15,2) NOT NULL,
  `source` ENUM('system','manual') NOT NULL DEFAULT 'system',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subscription_invoice_items_invoice_id_index` (`invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 10) vat_invoice2_prefixes: add Unit VAT decimal config columns
-- ---------------------------------------------------------------------------
SELECT IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @db AND table_name = 'vat_invoice2_prefixes'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @db
          AND table_name = 'vat_invoice2_prefixes'
          AND column_name = 'unit_vat_no_of_decimals'
    ),
    'ALTER TABLE `vat_invoice2_prefixes` ADD COLUMN `unit_vat_no_of_decimals` INT UNSIGNED NULL AFTER `starting_no`',
    'SELECT ''skip: vat_invoice2_prefixes.unit_vat_no_of_decimals'''
) INTO @sql;
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @db AND table_name = 'vat_invoice2_prefixes'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @db
          AND table_name = 'vat_invoice2_prefixes'
          AND column_name = 'unit_vat_rounding_off_required'
    ),
    'ALTER TABLE `vat_invoice2_prefixes` ADD COLUMN `unit_vat_rounding_off_required` TINYINT(1) NOT NULL DEFAULT 0 AFTER `unit_vat_no_of_decimals`',
    'SELECT ''skip: vat_invoice2_prefixes.unit_vat_rounding_off_required'''
) INTO @sql;
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Done.
