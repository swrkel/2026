-- Pumper Dashboard-New source schema repair for Petro PD-New
-- Version: 2026-08-04
-- Purpose: Upgrade older/hybrid PONE tenant schemas without deleting or renaming existing tables.
-- MySQL 5.7+/MariaDB compatible. Safe to run repeatedly in each affected TENANT database.
-- This file does not delete business data.

SET NAMES utf8mb4;
SET time_zone = '+05:30';

SELECT DATABASE() AS `active_tenant_database`;


-- Create source tables required by Petro PD-New when they do not exist.

CREATE TABLE IF NOT EXISTS `pone_meter_readings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `shift_id` BIGINT UNSIGNED NOT NULL,
  `assignment_id` BIGINT UNSIGNED NOT NULL,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `operator_profile_id` BIGINT UNSIGNED NOT NULL,
  `pump_id` INT UNSIGNED NOT NULL,
  `reading_type` ENUM('opening','current','closing','testing') NOT NULL,
  `meter_value` DECIMAL(22,6) NULL,
  `testing_quantity` DECIMAL(22,6) NOT NULL DEFAULT 0,
  `source` ENUM('manual','imported','system') NOT NULL DEFAULT 'manual',
  `recorded_at` TIMESTAMP NOT NULL,
  `recorded_by` INT UNSIGNED NULL,
  `note` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pone_reading_assignment_type_idx` (`assignment_id`,`reading_type`,`recorded_at`),
  KEY `pone_reading_business_date_idx` (`business_id`,`recorded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `pone_payment_cash_denominations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_id` BIGINT UNSIGNED NOT NULL,
  `denomination` DECIMAL(16,2) NOT NULL,
  `quantity` INT UNSIGNED NOT NULL DEFAULT 0,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pone_cash_denom_payment_value_uq` (`payment_id`,`denomination`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `pone_payment_card_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_id` BIGINT UNSIGNED NOT NULL,
  `card_type` VARCHAR(80) NULL,
  `last_four` VARCHAR(4) NULL,
  `slip_no` VARCHAR(100) NULL,
  `account_id` INT UNSIGNED NULL,
  `reference_no` VARCHAR(191) NULL,
  `amount` DECIMAL(22,4) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pone_card_line_payment_slip_idx` (`payment_id`,`slip_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `pone_credit_sales` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_id` BIGINT UNSIGNED NOT NULL,
  `business_id` INT UNSIGNED NOT NULL,
  `customer_id` INT UNSIGNED NOT NULL,
  `order_number` VARCHAR(191) NOT NULL,
  `bill_number` VARCHAR(191) NULL,
  `vehicle_number` VARCHAR(100) NOT NULL,
  `customer_reference` VARCHAR(191) NULL,
  `order_date` DATE NOT NULL,
  `due_date` DATE NULL,
  `customer_confirmed` TINYINT(1) NOT NULL DEFAULT 0,
  `order_confirmed` TINYINT(1) NOT NULL DEFAULT 0,
  `vehicle_confirmed` TINYINT(1) NOT NULL DEFAULT 0,
  `customer_confirmed_at` TIMESTAMP NULL DEFAULT NULL,
  `order_confirmed_at` TIMESTAMP NULL DEFAULT NULL,
  `vehicle_confirmed_at` TIMESTAMP NULL DEFAULT NULL,
  `confirmation_rounds` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `locked_at` TIMESTAMP NULL DEFAULT NULL,
  `confirmed_by` INT UNSIGNED NULL,
  `note` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pone_credit_sales_payment_id_unique` (`payment_id`),
  KEY `pone_credit_customer_date_idx` (`business_id`,`customer_id`,`order_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `pone_credit_sale_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `credit_sale_id` BIGINT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `quantity` DECIMAL(22,6) NOT NULL,
  `unit_price` DECIMAL(22,6) NOT NULL,
  `discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `amount` DECIMAL(22,4) NOT NULL,
  `petropd_credit_detail_id` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pone_credit_line_sale_product_idx` (`credit_sale_id`,`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `pone_other_sale_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `other_sale_id` BIGINT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `quantity` DECIMAL(22,6) NOT NULL,
  `unit_price` DECIMAL(22,6) NOT NULL,
  `discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `amount` DECIMAL(22,4) NOT NULL,
  `balance_stock_snapshot` DECIMAL(22,6) NULL,
  `petropd_other_sale_id` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pone_other_line_sale_product_idx` (`other_sale_id`,`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `pone_unload_stock_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `unload_stock_id` BIGINT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `tank_id` INT UNSIGNED NULL,
  `quantity` DECIMAL(22,6) NOT NULL,
  `unit_cost` DECIMAL(22,6) NOT NULL DEFAULT 0,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `dip_reading` DECIMAL(22,6) NULL,
  `current_stock` DECIMAL(22,6) NULL,
  `petropd_unload_stock_id` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pone_unload_line_product_idx` (`unload_stock_id`,`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `pone_day_entries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `shift_id` BIGINT UNSIGNED NOT NULL,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `operator_profile_id` BIGINT UNSIGNED NOT NULL,
  `pd_operator_id` INT UNSIGNED NOT NULL,
  `assignment_id` BIGINT UNSIGNED NULL,
  `pump_id` INT UNSIGNED NULL,
  `entry_type` ENUM('note','incident','expense','deposit','meter','testing','other') NOT NULL,
  `reference_no` VARCHAR(191) NULL,
  `quantity` DECIMAL(22,6) NOT NULL DEFAULT 0,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `starting_meter` DECIMAL(22,6) NULL,
  `closing_meter` DECIMAL(22,6) NULL,
  `testing_quantity` DECIMAL(22,6) NOT NULL DEFAULT 0,
  `entry_at` TIMESTAMP NOT NULL,
  `note` TEXT NULL,
  `status` ENUM('active','void') NOT NULL DEFAULT 'active',
  `petropd_day_entry_id` BIGINT UNSIGNED NULL,
  `integration_status` ENUM('not_required','pending','synced','failed') NOT NULL DEFAULT 'pending',
  `integration_error` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pone_day_entry_shift_type_idx` (`shift_id`,`entry_type`,`entry_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `pone_daily_collections` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` VARCHAR(36) NOT NULL,
  `shift_id` BIGINT UNSIGNED NOT NULL,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `operator_profile_id` BIGINT UNSIGNED NOT NULL,
  `pd_operator_id` INT UNSIGNED NOT NULL,
  `collection_number` VARCHAR(80) NOT NULL,
  `collection_at` TIMESTAMP NOT NULL,
  `expected_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `cash_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `card_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `cheque_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `credit_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `other_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `declared_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `difference_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `status` ENUM('draft','confirmed','void') NOT NULL DEFAULT 'confirmed',
  `note` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `confirmed_by` INT UNSIGNED NULL,
  `voided_by` INT UNSIGNED NULL,
  `voided_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pone_daily_collections_uuid_unique` (`uuid`),
  UNIQUE KEY `pone_collection_business_number_uq` (`business_id`,`collection_number`),
  KEY `pone_collection_shift_idx` (`shift_id`,`status`,`collection_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `pone_shortage_recoveries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `shift_id` BIGINT UNSIGNED NULL,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `operator_profile_id` BIGINT UNSIGNED NOT NULL,
  `pd_operator_id` INT UNSIGNED NOT NULL,
  `recovery_number` VARCHAR(80) NOT NULL,
  `recovery_date` DATE NOT NULL,
  `amount` DECIMAL(22,4) NOT NULL,
  `payment_method` VARCHAR(40) NOT NULL DEFAULT 'cash',
  `reference_no` VARCHAR(191) NULL,
  `note` TEXT NULL,
  `status` ENUM('confirmed','void') NOT NULL DEFAULT 'confirmed',
  `created_by` INT UNSIGNED NULL,
  `voided_by` INT UNSIGNED NULL,
  `voided_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pone_recovery_business_number_uq` (`business_id`,`recovery_number`),
  KEY `pone_recovery_operator_idx` (`operator_profile_id`,`recovery_date`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `pone_excess_commissions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `shift_id` BIGINT UNSIGNED NULL,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `operator_profile_id` BIGINT UNSIGNED NOT NULL,
  `pd_operator_id` INT UNSIGNED NOT NULL,
  `commission_number` VARCHAR(80) NOT NULL,
  `commission_date` DATE NOT NULL,
  `base_excess_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `commission_type` ENUM('fixed','percentage') NOT NULL DEFAULT 'percentage',
  `commission_rate` DECIMAL(14,6) NOT NULL DEFAULT 0,
  `commission_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `note` TEXT NULL,
  `status` ENUM('confirmed','void') NOT NULL DEFAULT 'confirmed',
  `created_by` INT UNSIGNED NULL,
  `voided_by` INT UNSIGNED NULL,
  `voided_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pone_commission_business_number_uq` (`business_id`,`commission_number`),
  KEY `pone_commission_operator_idx` (`operator_profile_id`,`commission_date`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `pone_operator_ledger_entries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `entry_key` VARCHAR(191) NOT NULL,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `operator_profile_id` BIGINT UNSIGNED NOT NULL,
  `pd_operator_id` INT UNSIGNED NOT NULL,
  `shift_id` BIGINT UNSIGNED NULL,
  `source_type` VARCHAR(80) NOT NULL,
  `source_id` BIGINT UNSIGNED NULL,
  `entry_at` TIMESTAMP NOT NULL,
  `reference_no` VARCHAR(100) NULL,
  `description` VARCHAR(500) NOT NULL,
  `debit` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `credit` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `status` ENUM('active','void') NOT NULL DEFAULT 'active',
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pone_operator_ledger_entries_entry_key_unique` (`entry_key`),
  KEY `pone_ledger_operator_date_idx` (`operator_profile_id`,`entry_at`,`status`),
  KEY `pone_ledger_source_idx` (`source_type`,`source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `pone_shift_settlement_references` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `shift_id` BIGINT UNSIGNED NOT NULL,
  `business_id` INT UNSIGNED NOT NULL,
  `settlement_no` VARCHAR(100) NOT NULL,
  `settlement_date` DATE NOT NULL,
  `note` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pone_settlement_business_shift_uq` (`business_id`,`shift_id`),
  UNIQUE KEY `pone_settlement_shift_number_uq` (`shift_id`,`settlement_no`),
  KEY `pone_settlement_business_date_idx` (`business_id`,`settlement_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Canonical columns for `pone_shifts`.

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_shifts')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND column_name = 'uuid'),
  'ALTER TABLE `pone_shifts` ADD COLUMN `uuid` VARCHAR(36) NULL',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_shifts')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND column_name = 'pd_operator_id'),
  'ALTER TABLE `pone_shifts` ADD COLUMN `pd_operator_id` INT UNSIGNED NULL',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_shifts')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND column_name = 'user_id'),
  'ALTER TABLE `pone_shifts` ADD COLUMN `user_id` INT UNSIGNED NULL',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_shifts')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND column_name = 'opened_at'),
  'ALTER TABLE `pone_shifts` ADD COLUMN `opened_at` TIMESTAMP NULL DEFAULT NULL',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_shifts')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND column_name = 'meter_sales_total'),
  'ALTER TABLE `pone_shifts` ADD COLUMN `meter_sales_total` DECIMAL(22,4) NOT NULL DEFAULT 0',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_shifts')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND column_name = 'other_sales_total'),
  'ALTER TABLE `pone_shifts` ADD COLUMN `other_sales_total` DECIMAL(22,4) NOT NULL DEFAULT 0',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_shifts')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND column_name = 'payments_total'),
  'ALTER TABLE `pone_shifts` ADD COLUMN `payments_total` DECIMAL(22,4) NOT NULL DEFAULT 0',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_shifts')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND column_name = 'expected_total'),
  'ALTER TABLE `pone_shifts` ADD COLUMN `expected_total` DECIMAL(22,4) NOT NULL DEFAULT 0',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_shifts')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND column_name = 'declared_total'),
  'ALTER TABLE `pone_shifts` ADD COLUMN `declared_total` DECIMAL(22,4) NOT NULL DEFAULT 0',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_shifts')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND column_name = 'shortage_amount'),
  'ALTER TABLE `pone_shifts` ADD COLUMN `shortage_amount` DECIMAL(22,4) NOT NULL DEFAULT 0',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_shifts')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND column_name = 'excess_amount'),
  'ALTER TABLE `pone_shifts` ADD COLUMN `excess_amount` DECIMAL(22,4) NOT NULL DEFAULT 0',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_shifts')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND column_name = 'reconciliation_status'),
  'ALTER TABLE `pone_shifts` ADD COLUMN `reconciliation_status` VARCHAR(20) NOT NULL DEFAULT ''pending''',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_shifts')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND column_name = 'collection_form_no'),
  'ALTER TABLE `pone_shifts` ADD COLUMN `collection_form_no` VARCHAR(100) NULL',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_shifts')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND column_name = 'notes'),
  'ALTER TABLE `pone_shifts` ADD COLUMN `notes` TEXT NULL',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;


-- Canonical columns for `pone_pd_operators`.

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_pd_operators')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_pd_operators' AND column_name = 'pd_operator_id'),
  'ALTER TABLE `pone_pd_operators` ADD COLUMN `pd_operator_id` INT UNSIGNED NULL',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_pd_operators')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_pd_operators' AND column_name = 'display_name'),
  'ALTER TABLE `pone_pd_operators` ADD COLUMN `display_name` VARCHAR(191) NULL',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_pd_operators')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_pd_operators' AND column_name = 'login_enabled'),
  'ALTER TABLE `pone_pd_operators` ADD COLUMN `login_enabled` TINYINT(1) NOT NULL DEFAULT 1',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_pd_operators')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_pd_operators' AND column_name = 'status'),
  'ALTER TABLE `pone_pd_operators` ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT ''active''',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_pd_operators')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_pd_operators' AND column_name = 'last_login_at'),
  'ALTER TABLE `pone_pd_operators` ADD COLUMN `last_login_at` TIMESTAMP NULL DEFAULT NULL',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_pd_operators')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_pd_operators' AND column_name = 'last_login_ip'),
  'ALTER TABLE `pone_pd_operators` ADD COLUMN `last_login_ip` VARCHAR(64) NULL',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;


-- Canonical columns for `pone_pump_assignments`.

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_pump_assignments')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_pump_assignments' AND column_name = 'pd_operator_id'),
  'ALTER TABLE `pone_pump_assignments` ADD COLUMN `pd_operator_id` INT UNSIGNED NULL',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_pump_assignments')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_pump_assignments' AND column_name = 'opening_meter'),
  'ALTER TABLE `pone_pump_assignments` ADD COLUMN `opening_meter` DECIMAL(22,6) NOT NULL DEFAULT 0',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_pump_assignments')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_pump_assignments' AND column_name = 'testing_quantity'),
  'ALTER TABLE `pone_pump_assignments` ADD COLUMN `testing_quantity` DECIMAL(22,6) NOT NULL DEFAULT 0',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_pump_assignments')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_pump_assignments' AND column_name = 'sold_quantity'),
  'ALTER TABLE `pone_pump_assignments` ADD COLUMN `sold_quantity` DECIMAL(22,6) NOT NULL DEFAULT 0',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_pump_assignments')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_pump_assignments' AND column_name = 'amount'),
  'ALTER TABLE `pone_pump_assignments` ADD COLUMN `amount` DECIMAL(22,4) NOT NULL DEFAULT 0',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_pump_assignments')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_pump_assignments' AND column_name = 'accepted_at'),
  'ALTER TABLE `pone_pump_assignments` ADD COLUMN `accepted_at` TIMESTAMP NULL DEFAULT NULL',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_pump_assignments')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_pump_assignments' AND column_name = 'confirmed_at'),
  'ALTER TABLE `pone_pump_assignments` ADD COLUMN `confirmed_at` TIMESTAMP NULL DEFAULT NULL',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;


-- Canonical columns for `pone_payments`.

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_payments')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_payments' AND column_name = 'uuid'),
  'ALTER TABLE `pone_payments` ADD COLUMN `uuid` VARCHAR(36) NULL',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_payments')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_payments' AND column_name = 'pd_operator_id'),
  'ALTER TABLE `pone_payments` ADD COLUMN `pd_operator_id` INT UNSIGNED NULL',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_payments')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_payments' AND column_name = 'gross_amount'),
  'ALTER TABLE `pone_payments` ADD COLUMN `gross_amount` DECIMAL(22,4) NOT NULL DEFAULT 0',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_payments')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_payments' AND column_name = 'discount_amount'),
  'ALTER TABLE `pone_payments` ADD COLUMN `discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_payments')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_payments' AND column_name = 'customer_id'),
  'ALTER TABLE `pone_payments` ADD COLUMN `customer_id` INT UNSIGNED NULL',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_payments')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_payments' AND column_name = 'slip_no'),
  'ALTER TABLE `pone_payments` ADD COLUMN `slip_no` VARCHAR(100) NULL',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_payments')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_payments' AND column_name = 'cheque_no'),
  'ALTER TABLE `pone_payments` ADD COLUMN `cheque_no` VARCHAR(100) NULL',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_payments')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_payments' AND column_name = 'transaction_at'),
  'ALTER TABLE `pone_payments` ADD COLUMN `transaction_at` TIMESTAMP NULL DEFAULT NULL',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;


-- Canonical columns for `pone_other_sales`.

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_other_sales')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_other_sales' AND column_name = 'uuid'),
  'ALTER TABLE `pone_other_sales` ADD COLUMN `uuid` VARCHAR(36) NULL',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_other_sales')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_other_sales' AND column_name = 'pd_operator_id'),
  'ALTER TABLE `pone_other_sales` ADD COLUMN `pd_operator_id` INT UNSIGNED NULL',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_other_sales')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_other_sales' AND column_name = 'store_id'),
  'ALTER TABLE `pone_other_sales` ADD COLUMN `store_id` INT UNSIGNED NULL',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_other_sales')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_other_sales' AND column_name = 'sale_at'),
  'ALTER TABLE `pone_other_sales` ADD COLUMN `sale_at` TIMESTAMP NULL DEFAULT NULL',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_other_sales')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_other_sales' AND column_name = 'gross_amount'),
  'ALTER TABLE `pone_other_sales` ADD COLUMN `gross_amount` DECIMAL(22,4) NOT NULL DEFAULT 0',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_other_sales')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_other_sales' AND column_name = 'net_amount'),
  'ALTER TABLE `pone_other_sales` ADD COLUMN `net_amount` DECIMAL(22,4) NOT NULL DEFAULT 0',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;


-- Canonical columns for `pone_unload_stocks`.

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_unload_stocks')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_unload_stocks' AND column_name = 'uuid'),
  'ALTER TABLE `pone_unload_stocks` ADD COLUMN `uuid` VARCHAR(36) NULL',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_unload_stocks')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_unload_stocks' AND column_name = 'pd_operator_id'),
  'ALTER TABLE `pone_unload_stocks` ADD COLUMN `pd_operator_id` INT UNSIGNED NULL',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_unload_stocks')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_unload_stocks' AND column_name = 'store_id'),
  'ALTER TABLE `pone_unload_stocks` ADD COLUMN `store_id` INT UNSIGNED NULL',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_unload_stocks')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_unload_stocks' AND column_name = 'receipt_number'),
  'ALTER TABLE `pone_unload_stocks` ADD COLUMN `receipt_number` VARCHAR(80) NULL',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_unload_stocks')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_unload_stocks' AND column_name = 'bill_number'),
  'ALTER TABLE `pone_unload_stocks` ADD COLUMN `bill_number` VARCHAR(191) NULL',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_unload_stocks')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_unload_stocks' AND column_name = 'supplier_reference'),
  'ALTER TABLE `pone_unload_stocks` ADD COLUMN `supplier_reference` VARCHAR(191) NULL',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_unload_stocks')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_unload_stocks' AND column_name = 'total_quantity'),
  'ALTER TABLE `pone_unload_stocks` ADD COLUMN `total_quantity` DECIMAL(22,6) NOT NULL DEFAULT 0',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_unload_stocks')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_unload_stocks' AND column_name = 'total_amount'),
  'ALTER TABLE `pone_unload_stocks` ADD COLUMN `total_amount` DECIMAL(22,4) NOT NULL DEFAULT 0',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;


-- Backfill canonical columns from the earlier PONE column names when available.

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND column_name = 'uuid')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND column_name = 'shift_uuid'),
  'UPDATE `pone_shifts` SET `uuid` = `shift_uuid` WHERE (`uuid` IS NULL OR `uuid` = '''')',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND column_name = 'pd_operator_id')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND column_name = 'petro_pd_operator_id'),
  'UPDATE `pone_shifts` SET `pd_operator_id` = `petro_pd_operator_id` WHERE (`pd_operator_id` IS NULL OR `pd_operator_id` = 0)',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND column_name = 'opened_at')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND column_name = 'started_at'),
  'UPDATE `pone_shifts` SET `opened_at` = `started_at` WHERE `opened_at` IS NULL',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND column_name = 'meter_sales_total')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND column_name = 'total_meter_sales'),
  'UPDATE `pone_shifts` SET `meter_sales_total` = `total_meter_sales` WHERE `meter_sales_total` = 0',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND column_name = 'other_sales_total')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND column_name = 'total_other_sales'),
  'UPDATE `pone_shifts` SET `other_sales_total` = `total_other_sales` WHERE `other_sales_total` = 0',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND column_name = 'payments_total')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND column_name = 'total_payments'),
  'UPDATE `pone_shifts` SET `payments_total` = `total_payments` WHERE `payments_total` = 0',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_pd_operators' AND column_name = 'pd_operator_id')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_pd_operators' AND column_name = 'petro_pd_operator_id'),
  'UPDATE `pone_pd_operators` SET `pd_operator_id` = `petro_pd_operator_id` WHERE (`pd_operator_id` IS NULL OR `pd_operator_id` = 0)',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_pd_operators' AND column_name = 'display_name')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_pd_operators' AND column_name = 'operator_code'),
  'UPDATE `pone_pd_operators` SET `display_name` = `operator_code` WHERE (`display_name` IS NULL OR TRIM(`display_name`) = '''')',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_pd_operators' AND column_name = 'login_enabled')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_pd_operators' AND column_name = 'can_login'),
  'UPDATE `pone_pd_operators` SET `login_enabled` = `can_login` WHERE 1=1',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_pump_assignments' AND column_name = 'pd_operator_id')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_pump_assignments' AND column_name = 'petro_pd_operator_id'),
  'UPDATE `pone_pump_assignments` SET `pd_operator_id` = `petro_pd_operator_id` WHERE (`pd_operator_id` IS NULL OR `pd_operator_id` = 0)',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_pump_assignments' AND column_name = 'opening_meter')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_pump_assignments' AND column_name = 'starting_meter'),
  'UPDATE `pone_pump_assignments` SET `opening_meter` = `starting_meter` WHERE `opening_meter` = 0',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_pump_assignments' AND column_name = 'testing_quantity')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_pump_assignments' AND column_name = 'testing_qty'),
  'UPDATE `pone_pump_assignments` SET `testing_quantity` = `testing_qty` WHERE `testing_quantity` = 0',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_pump_assignments' AND column_name = 'sold_quantity')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_pump_assignments' AND column_name = 'sold_qty'),
  'UPDATE `pone_pump_assignments` SET `sold_quantity` = `sold_qty` WHERE `sold_quantity` = 0',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_pump_assignments' AND column_name = 'amount')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_pump_assignments' AND column_name = 'gross_amount'),
  'UPDATE `pone_pump_assignments` SET `amount` = `gross_amount` WHERE `amount` = 0',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_pump_assignments' AND column_name = 'accepted_at')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_pump_assignments' AND column_name = 'received_at'),
  'UPDATE `pone_pump_assignments` SET `accepted_at` = `received_at` WHERE `accepted_at` IS NULL',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_pump_assignments' AND column_name = 'confirmed_at')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_pump_assignments' AND column_name = 'received_at'),
  'UPDATE `pone_pump_assignments` SET `confirmed_at` = `received_at` WHERE `confirmed_at` IS NULL',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_payments' AND column_name = 'uuid')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_payments' AND column_name = 'payment_uuid'),
  'UPDATE `pone_payments` SET `uuid` = `payment_uuid` WHERE (`uuid` IS NULL OR `uuid` = '''')',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_payments' AND column_name = 'pd_operator_id')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_payments' AND column_name = 'petro_pd_operator_id'),
  'UPDATE `pone_payments` SET `pd_operator_id` = `petro_pd_operator_id` WHERE (`pd_operator_id` IS NULL OR `pd_operator_id` = 0)',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_payments' AND column_name = 'gross_amount')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_payments' AND column_name = 'amount'),
  'UPDATE `pone_payments` SET `gross_amount` = `amount` WHERE `gross_amount` = 0',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_payments' AND column_name = 'customer_id')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_payments' AND column_name = 'contact_id'),
  'UPDATE `pone_payments` SET `customer_id` = `contact_id` WHERE `customer_id` IS NULL',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_payments' AND column_name = 'cheque_no')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_payments' AND column_name = 'cheque_number'),
  'UPDATE `pone_payments` SET `cheque_no` = `cheque_number` WHERE `cheque_no` IS NULL',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_payments' AND column_name = 'transaction_at')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_payments' AND column_name = 'paid_at'),
  'UPDATE `pone_payments` SET `transaction_at` = `paid_at` WHERE `transaction_at` IS NULL',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_other_sales' AND column_name = 'uuid')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_other_sales' AND column_name = 'sale_uuid'),
  'UPDATE `pone_other_sales` SET `uuid` = `sale_uuid` WHERE (`uuid` IS NULL OR `uuid` = '''')',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_other_sales' AND column_name = 'pd_operator_id')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_other_sales' AND column_name = 'petro_pd_operator_id'),
  'UPDATE `pone_other_sales` SET `pd_operator_id` = `petro_pd_operator_id` WHERE (`pd_operator_id` IS NULL OR `pd_operator_id` = 0)',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_other_sales' AND column_name = 'sale_at')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_other_sales' AND column_name = 'sold_at'),
  'UPDATE `pone_other_sales` SET `sale_at` = `sold_at` WHERE `sale_at` IS NULL',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_other_sales' AND column_name = 'gross_amount')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_other_sales' AND column_name = 'subtotal'),
  'UPDATE `pone_other_sales` SET `gross_amount` = `subtotal` WHERE `gross_amount` = 0',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_other_sales' AND column_name = 'net_amount')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_other_sales' AND column_name = 'total_amount'),
  'UPDATE `pone_other_sales` SET `net_amount` = `total_amount` WHERE `net_amount` = 0',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_unload_stocks' AND column_name = 'uuid')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_unload_stocks' AND column_name = 'unload_uuid'),
  'UPDATE `pone_unload_stocks` SET `uuid` = `unload_uuid` WHERE (`uuid` IS NULL OR `uuid` = '''')',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_unload_stocks' AND column_name = 'pd_operator_id')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_unload_stocks' AND column_name = 'petro_pd_operator_id'),
  'UPDATE `pone_unload_stocks` SET `pd_operator_id` = `petro_pd_operator_id` WHERE (`pd_operator_id` IS NULL OR `pd_operator_id` = 0)',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_unload_stocks' AND column_name = 'receipt_number')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_unload_stocks' AND column_name = 'unload_number'),
  'UPDATE `pone_unload_stocks` SET `receipt_number` = `unload_number` WHERE (`receipt_number` IS NULL OR `receipt_number` = '''')',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_unload_stocks' AND column_name = 'supplier_reference')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_unload_stocks' AND column_name = 'reference_no'),
  'UPDATE `pone_unload_stocks` SET `supplier_reference` = `reference_no` WHERE `supplier_reference` IS NULL',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_unload_stocks' AND column_name = 'total_quantity')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_unload_stocks' AND column_name = 'quantity'),
  'UPDATE `pone_unload_stocks` SET `total_quantity` = `quantity` WHERE `total_quantity` = 0',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;


-- pone_pd_operators status from enabled.
SET @pone_sql = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_pd_operators' AND column_name = 'status') AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_pd_operators' AND column_name = 'enabled'), 'UPDATE `pone_pd_operators`
SET `status` = CASE WHEN COALESCE(`enabled`,1) = 1 THEN ''active'' ELSE ''inactive'' END', 'SELECT 1');
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;


-- shift expected total.
SET @pone_sql = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND column_name = 'expected_total') AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND column_name = 'meter_sales_total') AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND column_name = 'other_sales_total'), 'UPDATE `pone_shifts`
SET `expected_total` = COALESCE(`meter_sales_total`,0) + COALESCE(`other_sales_total`,0)
WHERE `expected_total` = 0', 'SELECT 1');
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;


-- shift declared total.
SET @pone_sql = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND column_name = 'declared_total') AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND column_name = 'payments_total'), 'UPDATE `pone_shifts`
SET `declared_total` = COALESCE(`payments_total`,0)
WHERE `declared_total` = 0', 'SELECT 1');
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;


-- shift shortage amount.
SET @pone_sql = IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND column_name = 'shortage_amount') AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND column_name = 'excess_amount') AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND column_name = 'shortage_excess'), 'UPDATE `pone_shifts`
SET `shortage_amount` = CASE WHEN `shortage_excess` < 0 THEN ABS(`shortage_excess`) ELSE 0 END,
    `excess_amount` = CASE WHEN `shortage_excess` > 0 THEN `shortage_excess` ELSE 0 END
WHERE (`shortage_amount` = 0 AND `excess_amount` = 0)', 'SELECT 1');
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;


-- migrate legacy meter entries.
SET @pone_sql = IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_meter_entries') AND EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_meter_readings'), 'INSERT IGNORE INTO `pone_meter_readings`
(`id`,`shift_id`,`assignment_id`,`business_id`,`location_id`,`operator_profile_id`,`pump_id`,`reading_type`,`meter_value`,`testing_quantity`,`source`,`recorded_at`,`recorded_by`,`note`,`created_at`,`updated_at`)
SELECT `id`,`shift_id`,`assignment_id`,`business_id`,`location_id`,`operator_profile_id`,`pump_id`,
CASE WHEN `entry_type` IN (''opening'',''current'',''closing'',''testing'') THEN `entry_type` ELSE ''current'' END,
`meter_reading`,COALESCE(`testing_qty`,0),''manual'',`entered_at`,`created_by`,`note`,`created_at`,`updated_at`
FROM `pone_meter_entries`', 'SELECT 1');
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;


-- migrate legacy other-sale items.
SET @pone_sql = IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_other_sale_items') AND EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_other_sale_lines'), 'INSERT IGNORE INTO `pone_other_sale_lines`
(`id`,`other_sale_id`,`product_id`,`quantity`,`unit_price`,`discount_amount`,`amount`,`balance_stock_snapshot`,`created_at`,`updated_at`)
SELECT `id`,`other_sale_id`,`product_id`,`quantity`,`unit_price`,`discount_amount`,`line_total`,NULL,`created_at`,`updated_at`
FROM `pone_other_sale_items`', 'SELECT 1');
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;


-- create unload lines from legacy unload headers.
SET @pone_sql = IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_unload_stocks') AND EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_unload_stock_lines') AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_unload_stocks' AND column_name = 'product_id') AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_unload_stocks' AND column_name = 'fuel_tank_id') AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_unload_stocks' AND column_name = 'quantity'), 'INSERT INTO `pone_unload_stock_lines`
(`unload_stock_id`,`product_id`,`tank_id`,`quantity`,`unit_cost`,`amount`,`dip_reading`,`current_stock`,`created_at`,`updated_at`)
SELECT u.`id`,u.`product_id`,u.`fuel_tank_id`,u.`quantity`,0,0,NULL,NULL,u.`created_at`,u.`updated_at`
FROM `pone_unload_stocks` u
WHERE NOT EXISTS (SELECT 1 FROM `pone_unload_stock_lines` l WHERE l.`unload_stock_id` = u.`id`)', 'SELECT 1');
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;


-- Add read-performance indexes only when all referenced columns exist.

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_shifts')
  AND NOT EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND index_name = 'pone_shift_pdnew_source_idx')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND column_name = 'business_id') AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND column_name = 'location_id') AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND column_name = 'status') AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND column_name = 'closed_at'),
  'ALTER TABLE `pone_shifts` ADD INDEX `pone_shift_pdnew_source_idx` (`business_id`,`location_id`,`status`,`closed_at`)',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_shifts')
  AND NOT EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND index_name = 'pone_shift_pd_operator_idx')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND column_name = 'business_id') AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND column_name = 'pd_operator_id') AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_shifts' AND column_name = 'status'),
  'ALTER TABLE `pone_shifts` ADD INDEX `pone_shift_pd_operator_idx` (`business_id`,`pd_operator_id`,`status`)',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_pump_assignments')
  AND NOT EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'pone_pump_assignments' AND index_name = 'pone_assignment_pdnew_source_idx')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_pump_assignments' AND column_name = 'shift_id') AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_pump_assignments' AND column_name = 'status') AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_pump_assignments' AND column_name = 'pump_id'),
  'ALTER TABLE `pone_pump_assignments` ADD INDEX `pone_assignment_pdnew_source_idx` (`shift_id`,`status`,`pump_id`)',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_payments')
  AND NOT EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'pone_payments' AND index_name = 'pone_payment_pdnew_source_idx')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_payments' AND column_name = 'shift_id') AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_payments' AND column_name = 'status') AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_payments' AND column_name = 'payment_type'),
  'ALTER TABLE `pone_payments` ADD INDEX `pone_payment_pdnew_source_idx` (`shift_id`,`status`,`payment_type`)',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_other_sales')
  AND NOT EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'pone_other_sales' AND index_name = 'pone_other_sale_pdnew_source_idx')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_other_sales' AND column_name = 'shift_id') AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_other_sales' AND column_name = 'status'),
  'ALTER TABLE `pone_other_sales` ADD INDEX `pone_other_sale_pdnew_source_idx` (`shift_id`,`status`)',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;

SET @pone_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pone_unload_stocks')
  AND NOT EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'pone_unload_stocks' AND index_name = 'pone_unload_pdnew_source_idx')
  AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_unload_stocks' AND column_name = 'shift_id') AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pone_unload_stocks' AND column_name = 'status'),
  'ALTER TABLE `pone_unload_stocks` ADD INDEX `pone_unload_pdnew_source_idx` (`shift_id`,`status`)',
  'SELECT 1'
);
PREPARE pone_stmt FROM @pone_sql;
EXECUTE pone_stmt;
DEALLOCATE PREPARE pone_stmt;


SELECT 'Pumper Dashboard-New source schema repair completed. Run 06_VERIFY_PETRO_PD_NEW_SOURCE_SCHEMA.sql next.' AS `message`;
