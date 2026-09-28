-- Petro PD-New 1.0.0 Full Release
-- Standalone tenant schema paired exclusively with Pumper Dashboard-New.
-- Safe to rerun; creates missing tables, upgrades partial tables, inserts permissions, and applies pairing.
-- Install Pumper Dashboard-New first in the same tenant database.
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- 01_CREATE_CORE_AND_SOURCE_TABLES.sql
-- ============================================================================
-- Petro PD-New 1.0.0
-- Standalone tenant schema. Safe to run repeatedly.
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `pdnew_module_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `scope_key` VARCHAR(191) NOT NULL,
  `settlement_prefix` VARCHAR(30) NOT NULL DEFAULT 'PDN-SET-',
  `day_end_prefix` VARCHAR(30) NOT NULL DEFAULT 'PDN-DE-',
  `amount_decimals` TINYINT UNSIGNED NOT NULL DEFAULT 4,
  `quantity_decimals` TINYINT UNSIGNED NOT NULL DEFAULT 3,
  `require_review` TINYINT(1) NOT NULL DEFAULT 1,
  `require_approval` TINYINT(1) NOT NULL DEFAULT 1,
  `require_zero_variance` TINYINT(1) NOT NULL DEFAULT 1,
  `allow_reopen` TINYINT(1) NOT NULL DEFAULT 1,
  `auto_import_closed_shifts` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `settings` LONGTEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdnew_settings_scope_uq` (`scope_key`),
  KEY `pdnew_settings_business_idx` (`business_id`,`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_number_sequences` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `sequence_type` VARCHAR(50) NOT NULL,
  `scope_key` VARCHAR(191) NOT NULL,
  `prefix` VARCHAR(30) NOT NULL,
  `next_number` BIGINT UNSIGNED NOT NULL DEFAULT 1,
  `padding` TINYINT UNSIGNED NOT NULL DEFAULT 6,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdnew_sequences_scope_uq` (`scope_key`),
  KEY `pdnew_sequences_business_idx` (`business_id`,`sequence_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_operator_mappings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `pone_operator_profile_id` BIGINT UNSIGNED NOT NULL,
  `pone_pd_operator_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NULL,
  `display_name` VARCHAR(191) NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'active',
  `settings` LONGTEXT NULL,
  `last_synced_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdnew_operator_profile_uq` (`business_id`,`pone_operator_profile_id`),
  KEY `pdnew_operator_scope_idx` (`business_id`,`location_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_source_imports` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `pone_shift_id` BIGINT UNSIGNED NOT NULL,
  `pone_shift_uuid` CHAR(36) NULL,
  `pone_shift_number` VARCHAR(80) NOT NULL,
  `pone_operator_profile_id` BIGINT UNSIGNED NOT NULL,
  `pone_pd_operator_id` INT UNSIGNED NOT NULL,
  `source_status` VARCHAR(30) NOT NULL,
  `import_status` VARCHAR(30) NOT NULL DEFAULT 'available',
  `source_hash` CHAR(64) NOT NULL,
  `current_hash` CHAR(64) NULL,
  `snapshot_version` INT UNSIGNED NOT NULL DEFAULT 1,
  `source_closed_at` TIMESTAMP NULL DEFAULT NULL,
  `source_totals` LONGTEXT NULL,
  `settlement_id` BIGINT UNSIGNED NULL,
  `imported_at` TIMESTAMP NULL DEFAULT NULL,
  `verified_at` TIMESTAMP NULL DEFAULT NULL,
  `last_error` TEXT NULL,
  `imported_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdnew_source_shift_uq` (`business_id`,`pone_shift_id`),
  KEY `pdnew_source_status_idx` (`business_id`,`location_id`,`import_status`),
  KEY `pdnew_source_settlement_idx` (`settlement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_source_snapshots` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `source_import_id` BIGINT UNSIGNED NOT NULL,
  `pone_shift_id` BIGINT UNSIGNED NOT NULL,
  `version` INT UNSIGNED NOT NULL,
  `source_hash` CHAR(64) NOT NULL,
  `snapshot` LONGTEXT NOT NULL,
  `captured_at` TIMESTAMP NOT NULL,
  `captured_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdnew_snapshot_version_uq` (`source_import_id`,`version`),
  KEY `pdnew_snapshot_shift_idx` (`business_id`,`pone_shift_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- 02_CREATE_SETTLEMENT_TABLES.sql
-- ============================================================================
-- Petro PD-New 1.0.0
-- Standalone tenant schema. Safe to run repeatedly.
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `pdnew_settlements` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` CHAR(36) NOT NULL,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `source_import_id` BIGINT UNSIGNED NOT NULL,
  `pone_shift_id` BIGINT UNSIGNED NOT NULL,
  `pone_shift_number` VARCHAR(80) NOT NULL,
  `pone_operator_profile_id` BIGINT UNSIGNED NOT NULL,
  `pone_pd_operator_id` INT UNSIGNED NOT NULL,
  `operator_name` VARCHAR(191) NOT NULL,
  `settlement_number` VARCHAR(80) NOT NULL,
  `settlement_date` DATE NOT NULL,
  `source_closed_at` TIMESTAMP NULL DEFAULT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'draft',
  `source_hash` CHAR(64) NOT NULL,
  `meter_sales_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `other_sales_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `source_payments_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `manual_payments_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `source_declared_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `source_shortage_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `source_excess_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `manual_shortage_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `manual_excess_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `shortage_recovery_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `excess_commission_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `adjustments_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `expected_adjustments_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `received_adjustments_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `expected_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `received_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `operational_variance_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `variance_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `reconciliation_status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `notes` TEXT NULL,
  `submitted_at` TIMESTAMP NULL DEFAULT NULL,
  `submitted_by` INT UNSIGNED NULL,
  `reviewed_at` TIMESTAMP NULL DEFAULT NULL,
  `reviewed_by` INT UNSIGNED NULL,
  `approved_at` TIMESTAMP NULL DEFAULT NULL,
  `approved_by` INT UNSIGNED NULL,
  `finalized_at` TIMESTAMP NULL DEFAULT NULL,
  `finalized_by` INT UNSIGNED NULL,
  `cancelled_at` TIMESTAMP NULL DEFAULT NULL,
  `cancelled_by` INT UNSIGNED NULL,
  `created_by` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdnew_settlement_uuid_uq` (`uuid`),
  UNIQUE KEY `pdnew_settlement_number_uq` (`business_id`,`settlement_number`),
  UNIQUE KEY `pdnew_settlement_shift_uq` (`business_id`,`pone_shift_id`),
  KEY `pdnew_settlement_status_idx` (`business_id`,`location_id`,`status`,`settlement_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_settlement_sources` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `settlement_id` BIGINT UNSIGNED NOT NULL,
  `pone_shift_id` BIGINT UNSIGNED NOT NULL,
  `pone_shift_number` VARCHAR(80) NOT NULL,
  `source_hash` CHAR(64) NOT NULL,
  `source_closed_at` TIMESTAMP NULL DEFAULT NULL,
  `source_totals` LONGTEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdnew_settlement_source_uq` (`settlement_id`,`pone_shift_id`),
  KEY `pdnew_source_business_idx` (`business_id`,`pone_shift_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_settlement_pumps` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `settlement_id` BIGINT UNSIGNED NOT NULL,
  `pone_assignment_id` BIGINT UNSIGNED NOT NULL,
  `pump_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NULL,
  `opening_meter` DECIMAL(22,3) NOT NULL DEFAULT 0,
  `closing_meter` DECIMAL(22,3) NOT NULL DEFAULT 0,
  `testing_quantity` DECIMAL(22,3) NOT NULL DEFAULT 0,
  `sold_quantity` DECIMAL(22,3) NOT NULL DEFAULT 0,
  `unit_price` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `status` VARCHAR(30) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdnew_settlement_assignment_uq` (`settlement_id`,`pone_assignment_id`),
  KEY `pdnew_settlement_pump_idx` (`business_id`,`pump_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_settlement_meter_sales` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `settlement_id` BIGINT UNSIGNED NOT NULL,
  `pone_assignment_id` BIGINT UNSIGNED NOT NULL,
  `pone_meter_reading_id` BIGINT UNSIGNED NULL,
  `pump_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NULL,
  `reading_at` TIMESTAMP NULL DEFAULT NULL,
  `opening_meter` DECIMAL(22,3) NOT NULL DEFAULT 0,
  `closing_meter` DECIMAL(22,3) NOT NULL DEFAULT 0,
  `testing_quantity` DECIMAL(22,3) NOT NULL DEFAULT 0,
  `sold_quantity` DECIMAL(22,3) NOT NULL DEFAULT 0,
  `unit_price` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdnew_meter_assignment_uq` (`settlement_id`,`pone_assignment_id`),
  KEY `pdnew_meter_business_idx` (`business_id`,`pump_id`,`reading_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_settlement_payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` CHAR(36) NOT NULL,
  `business_id` INT UNSIGNED NOT NULL,
  `settlement_id` BIGINT UNSIGNED NOT NULL,
  `pone_payment_id` BIGINT UNSIGNED NULL,
  `payment_number` VARCHAR(80) NOT NULL,
  `payment_type` VARCHAR(40) NOT NULL,
  `gross_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `customer_id` INT UNSIGNED NULL,
  `reference_no` VARCHAR(191) NULL,
  `transaction_at` TIMESTAMP NULL DEFAULT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'active',
  `is_source` TINYINT(1) NOT NULL DEFAULT 0,
  `note` TEXT NULL,
  `metadata` LONGTEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `voided_by` INT UNSIGNED NULL,
  `voided_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdnew_payment_uuid_uq` (`uuid`),
  UNIQUE KEY `pdnew_source_payment_uq` (`settlement_id`,`pone_payment_id`),
  KEY `pdnew_payment_type_idx` (`business_id`,`settlement_id`,`payment_type`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_settlement_payment_details` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `settlement_id` BIGINT UNSIGNED NOT NULL,
  `payment_id` BIGINT UNSIGNED NOT NULL,
  `detail_type` VARCHAR(40) NOT NULL,
  `reference_no` VARCHAR(191) NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `detail` LONGTEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pdnew_payment_detail_idx` (`payment_id`,`detail_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_settlement_credit_sales` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `settlement_id` BIGINT UNSIGNED NOT NULL,
  `pone_credit_sale_id` BIGINT UNSIGNED NOT NULL,
  `pone_payment_id` BIGINT UNSIGNED NOT NULL,
  `customer_id` INT UNSIGNED NULL,
  `order_number` VARCHAR(191) NULL,
  `bill_number` VARCHAR(191) NULL,
  `vehicle_number` VARCHAR(191) NULL,
  `customer_reference` VARCHAR(191) NULL,
  `order_date` DATE NULL,
  `due_date` DATE NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `confirmed` TINYINT(1) NOT NULL DEFAULT 0,
  `metadata` LONGTEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdnew_credit_source_uq` (`settlement_id`,`pone_credit_sale_id`),
  KEY `pdnew_credit_customer_idx` (`business_id`,`customer_id`,`order_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_settlement_credit_sale_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `settlement_id` BIGINT UNSIGNED NOT NULL,
  `credit_sale_id` BIGINT UNSIGNED NOT NULL,
  `pone_credit_sale_line_id` BIGINT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NULL,
  `quantity` DECIMAL(22,3) NOT NULL DEFAULT 0,
  `unit_price` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdnew_credit_line_source_uq` (`credit_sale_id`,`pone_credit_sale_line_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_settlement_other_sales` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `settlement_id` BIGINT UNSIGNED NOT NULL,
  `pone_other_sale_id` BIGINT UNSIGNED NOT NULL,
  `sale_number` VARCHAR(80) NOT NULL,
  `store_id` INT UNSIGNED NULL,
  `sale_at` TIMESTAMP NULL DEFAULT NULL,
  `gross_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `net_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `status` VARCHAR(30) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdnew_other_sale_source_uq` (`settlement_id`,`pone_other_sale_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_settlement_other_sale_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `settlement_id` BIGINT UNSIGNED NOT NULL,
  `other_sale_id` BIGINT UNSIGNED NOT NULL,
  `pone_other_sale_line_id` BIGINT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NULL,
  `quantity` DECIMAL(22,3) NOT NULL DEFAULT 0,
  `unit_price` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdnew_other_line_source_uq` (`other_sale_id`,`pone_other_sale_line_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_settlement_unload_stocks` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `settlement_id` BIGINT UNSIGNED NOT NULL,
  `pone_unload_stock_id` BIGINT UNSIGNED NOT NULL,
  `receipt_number` VARCHAR(80) NOT NULL,
  `bill_number` VARCHAR(191) NULL,
  `store_id` INT UNSIGNED NULL,
  `unloaded_at` TIMESTAMP NULL DEFAULT NULL,
  `total_quantity` DECIMAL(22,3) NOT NULL DEFAULT 0,
  `total_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `status` VARCHAR(30) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdnew_unload_source_uq` (`settlement_id`,`pone_unload_stock_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_settlement_unload_stock_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `settlement_id` BIGINT UNSIGNED NOT NULL,
  `unload_stock_id` BIGINT UNSIGNED NOT NULL,
  `pone_unload_stock_line_id` BIGINT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NULL,
  `tank_id` INT UNSIGNED NULL,
  `quantity` DECIMAL(22,3) NOT NULL DEFAULT 0,
  `unit_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `dip_reading` DECIMAL(22,3) NOT NULL DEFAULT 0,
  `current_stock` DECIMAL(22,3) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdnew_unload_line_source_uq` (`unload_stock_id`,`pone_unload_stock_line_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_settlement_day_entries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `settlement_id` BIGINT UNSIGNED NOT NULL,
  `pone_day_entry_id` BIGINT UNSIGNED NOT NULL,
  `entry_type` VARCHAR(50) NOT NULL,
  `reference_no` VARCHAR(191) NULL,
  `pump_id` INT UNSIGNED NULL,
  `assignment_id` BIGINT UNSIGNED NULL,
  `quantity` DECIMAL(22,3) NOT NULL DEFAULT 0,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `starting_meter` DECIMAL(22,3) NOT NULL DEFAULT 0,
  `closing_meter` DECIMAL(22,3) NOT NULL DEFAULT 0,
  `testing_quantity` DECIMAL(22,3) NOT NULL DEFAULT 0,
  `entry_at` TIMESTAMP NULL DEFAULT NULL,
  `status` VARCHAR(30) NOT NULL,
  `note` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdnew_day_entry_source_uq` (`settlement_id`,`pone_day_entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_settlement_collections` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `settlement_id` BIGINT UNSIGNED NOT NULL,
  `pone_collection_id` BIGINT UNSIGNED NOT NULL,
  `collection_number` VARCHAR(80) NOT NULL,
  `collection_at` TIMESTAMP NULL DEFAULT NULL,
  `expected_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `cash_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `card_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `cheque_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `credit_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `other_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `declared_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `difference_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `status` VARCHAR(30) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdnew_collection_source_uq` (`settlement_id`,`pone_collection_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_settlement_ledger_entries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `settlement_id` BIGINT UNSIGNED NOT NULL,
  `pone_ledger_entry_id` BIGINT UNSIGNED NOT NULL,
  `entry_at` TIMESTAMP NULL DEFAULT NULL,
  `source_type` VARCHAR(50) NOT NULL,
  `source_id` BIGINT UNSIGNED NULL,
  `reference_no` VARCHAR(191) NULL,
  `description` TEXT NULL,
  `debit` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `credit` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `status` VARCHAR(30) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdnew_ledger_source_uq` (`settlement_id`,`pone_ledger_entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_settlement_recoveries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `settlement_id` BIGINT UNSIGNED NOT NULL,
  `pone_recovery_id` BIGINT UNSIGNED NOT NULL,
  `recovery_number` VARCHAR(80) NOT NULL,
  `recovery_date` DATE NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `payment_method` VARCHAR(40) NULL,
  `reference_no` VARCHAR(191) NULL,
  `status` VARCHAR(30) NOT NULL,
  `note` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdnew_recovery_source_uq` (`settlement_id`,`pone_recovery_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_settlement_commissions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `settlement_id` BIGINT UNSIGNED NOT NULL,
  `pone_commission_id` BIGINT UNSIGNED NOT NULL,
  `commission_number` VARCHAR(80) NOT NULL,
  `commission_date` DATE NULL,
  `base_excess_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `commission_type` VARCHAR(30) NULL,
  `commission_rate` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `commission_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `status` VARCHAR(30) NOT NULL,
  `note` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdnew_commission_source_uq` (`settlement_id`,`pone_commission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_settlement_adjustments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` CHAR(36) NOT NULL,
  `business_id` INT UNSIGNED NOT NULL,
  `settlement_id` BIGINT UNSIGNED NOT NULL,
  `adjustment_number` VARCHAR(80) NOT NULL,
  `adjustment_type` VARCHAR(40) NOT NULL,
  `field_name` VARCHAR(80) NOT NULL,
  `current_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `requested_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `approved_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `reason` TEXT NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'requested',
  `requested_by` INT UNSIGNED NOT NULL,
  `requested_at` TIMESTAMP NOT NULL,
  `approved_by` INT UNSIGNED NULL,
  `approved_at` TIMESTAMP NULL DEFAULT NULL,
  `rejected_by` INT UNSIGNED NULL,
  `rejected_at` TIMESTAMP NULL DEFAULT NULL,
  `decision_note` TEXT NULL,
  `metadata` LONGTEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdnew_adjustment_uuid_uq` (`uuid`),
  UNIQUE KEY `pdnew_adjustment_number_uq` (`business_id`,`adjustment_number`),
  KEY `pdnew_adjustment_status_idx` (`business_id`,`settlement_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- 03_CREATE_WORKFLOW_AND_DAY_END_TABLES.sql
-- ============================================================================
-- Petro PD-New 1.0.0
-- Standalone tenant schema. Safe to run repeatedly.
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `pdnew_settlement_approvals` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `settlement_id` BIGINT UNSIGNED NOT NULL,
  `action` VARCHAR(40) NOT NULL,
  `from_status` VARCHAR(30) NULL,
  `to_status` VARCHAR(30) NOT NULL,
  `note` TEXT NULL,
  `acted_by` INT UNSIGNED NOT NULL,
  `acted_at` TIMESTAMP NOT NULL,
  `metadata` LONGTEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pdnew_approval_settlement_idx` (`settlement_id`,`acted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_settlement_status_histories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `settlement_id` BIGINT UNSIGNED NOT NULL,
  `from_status` VARCHAR(30) NULL,
  `to_status` VARCHAR(30) NOT NULL,
  `reason` TEXT NULL,
  `changed_by` INT UNSIGNED NOT NULL,
  `changed_at` TIMESTAMP NOT NULL,
  `metadata` LONGTEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pdnew_status_history_idx` (`settlement_id`,`changed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_reconciliation_issues` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `settlement_id` BIGINT UNSIGNED NOT NULL,
  `issue_key` VARCHAR(191) NOT NULL,
  `issue_type` VARCHAR(50) NOT NULL,
  `severity` VARCHAR(20) NOT NULL DEFAULT 'error',
  `description` TEXT NOT NULL,
  `expected_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `actual_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `difference_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `status` VARCHAR(30) NOT NULL DEFAULT 'open',
  `resolution_note` TEXT NULL,
  `resolved_by` INT UNSIGNED NULL,
  `resolved_at` TIMESTAMP NULL DEFAULT NULL,
  `metadata` LONGTEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdnew_issue_key_uq` (`settlement_id`,`issue_key`),
  KEY `pdnew_issue_status_idx` (`business_id`,`status`,`severity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_day_ends` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` CHAR(36) NOT NULL,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `day_end_number` VARCHAR(80) NOT NULL,
  `day_end_date` DATE NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'draft',
  `settlement_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `settlements_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `payments_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `variance_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `note` TEXT NULL,
  `prepared_by` INT UNSIGNED NOT NULL,
  `prepared_at` TIMESTAMP NOT NULL,
  `finalized_by` INT UNSIGNED NULL,
  `finalized_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdnew_day_end_uuid_uq` (`uuid`),
  UNIQUE KEY `pdnew_day_end_number_uq` (`business_id`,`day_end_number`),
  UNIQUE KEY `pdnew_day_end_scope_uq` (`business_id`,`location_id`,`day_end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_day_end_settlements` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `day_end_id` BIGINT UNSIGNED NOT NULL,
  `settlement_id` BIGINT UNSIGNED NOT NULL,
  `settlement_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `payment_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `variance_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdnew_day_end_settlement_uq` (`settlement_id`),
  KEY `pdnew_day_end_items_idx` (`day_end_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_posting_batches` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` CHAR(36) NOT NULL,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `settlement_id` BIGINT UNSIGNED NULL,
  `day_end_id` BIGINT UNSIGNED NULL,
  `batch_number` VARCHAR(80) NOT NULL,
  `posting_date` DATE NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'prepared',
  `total_debit` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `total_credit` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `metadata` LONGTEXT NULL,
  `created_by` INT UNSIGNED NOT NULL,
  `posted_by` INT UNSIGNED NULL,
  `posted_at` TIMESTAMP NULL DEFAULT NULL,
  `reversed_by` INT UNSIGNED NULL,
  `reversed_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdnew_posting_uuid_uq` (`uuid`),
  UNIQUE KEY `pdnew_posting_number_uq` (`business_id`,`batch_number`),
  KEY `pdnew_posting_source_idx` (`settlement_id`,`day_end_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_posting_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `posting_batch_id` BIGINT UNSIGNED NOT NULL,
  `line_no` INT UNSIGNED NOT NULL,
  `account_id` INT UNSIGNED NULL,
  `account_code` VARCHAR(80) NULL,
  `description` TEXT NULL,
  `debit` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `credit` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `metadata` LONGTEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdnew_posting_line_uq` (`posting_batch_id`,`line_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- 04_CREATE_INTEGRATION_AND_SUPPORT_TABLES.sql
-- ============================================================================
-- Petro PD-New 1.0.0
-- Standalone tenant schema. Safe to run repeatedly.
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `pdnew_integration_outbox` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `aggregate_type` VARCHAR(50) NOT NULL,
  `aggregate_id` BIGINT UNSIGNED NOT NULL,
  `event_type` VARCHAR(80) NOT NULL,
  `idempotency_key` CHAR(64) NOT NULL,
  `payload` LONGTEXT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `attempts` INT UNSIGNED NOT NULL DEFAULT 0,
  `available_at` TIMESTAMP NULL DEFAULT NULL,
  `processed_at` TIMESTAMP NULL DEFAULT NULL,
  `last_error` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdnew_outbox_key_uq` (`idempotency_key`),
  KEY `pdnew_outbox_status_idx` (`business_id`,`status`,`available_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_integration_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `direction` VARCHAR(20) NOT NULL,
  `operation` VARCHAR(80) NOT NULL,
  `aggregate_type` VARCHAR(50) NULL,
  `aggregate_id` BIGINT UNSIGNED NULL,
  `status` VARCHAR(30) NOT NULL,
  `payload` LONGTEXT NULL,
  `response` LONGTEXT NULL,
  `error_message` TEXT NULL,
  `started_at` TIMESTAMP NULL DEFAULT NULL,
  `completed_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pdnew_integration_log_idx` (`business_id`,`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_notification_templates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `event_key` VARCHAR(80) NOT NULL,
  `name` VARCHAR(191) NOT NULL,
  `subject` VARCHAR(255) NULL,
  `body` LONGTEXT NOT NULL,
  `channels` LONGTEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdnew_notification_event_uq` (`business_id`,`event_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_notification_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `template_id` BIGINT UNSIGNED NULL,
  `settlement_id` BIGINT UNSIGNED NULL,
  `event_key` VARCHAR(80) NOT NULL,
  `channel` VARCHAR(30) NOT NULL,
  `recipient` VARCHAR(191) NULL,
  `status` VARCHAR(30) NOT NULL,
  `payload` LONGTEXT NULL,
  `response` TEXT NULL,
  `sent_at` TIMESTAMP NULL DEFAULT NULL,
  `failed_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pdnew_notification_log_idx` (`business_id`,`event_key`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_print_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `document_type` VARCHAR(50) NOT NULL,
  `document_id` BIGINT UNSIGNED NOT NULL,
  `print_type` VARCHAR(30) NOT NULL DEFAULT 'original',
  `printed_by` INT UNSIGNED NOT NULL,
  `printed_at` TIMESTAMP NOT NULL,
  `ip_address` VARCHAR(64) NULL,
  `metadata` LONGTEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pdnew_print_document_idx` (`business_id`,`document_type`,`document_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `user_id` INT UNSIGNED NULL,
  `action` VARCHAR(100) NOT NULL,
  `entity_type` VARCHAR(100) NULL,
  `entity_id` BIGINT UNSIGNED NULL,
  `old_values` LONGTEXT NULL,
  `new_values` LONGTEXT NULL,
  `ip_address` VARCHAR(64) NULL,
  `user_agent` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pdnew_audit_entity_idx` (`business_id`,`entity_type`,`entity_id`),
  KEY `pdnew_audit_date_idx` (`business_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_documents` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `settlement_id` BIGINT UNSIGNED NULL,
  `document_type` VARCHAR(50) NOT NULL,
  `title` VARCHAR(191) NOT NULL,
  `disk` VARCHAR(50) NOT NULL DEFAULT 'public',
  `path` TEXT NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `mime_type` VARCHAR(100) NULL,
  `size_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `metadata` LONGTEXT NULL,
  `uploaded_by` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pdnew_document_settlement_idx` (`business_id`,`settlement_id`,`document_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_saved_report_filters` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `report_key` VARCHAR(80) NOT NULL,
  `name` VARCHAR(191) NOT NULL,
  `filters` LONGTEXT NOT NULL,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdnew_saved_filter_uq` (`business_id`,`user_id`,`report_key`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- 07_UPGRADE_EXISTING_INSTALLATION.sql
-- ============================================================================
-- Petro PD-New idempotent upgrade for partially installed tenant databases.
-- Safe to rerun. New tables are created by the create scripts; this file adds missing columns and indexes.
SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS `pdnew_add_column_if_missing`;
DROP PROCEDURE IF EXISTS `pdnew_add_index_if_missing`;
DELIMITER $$
CREATE PROCEDURE `pdnew_add_column_if_missing`(
    IN p_table VARCHAR(128), IN p_column VARCHAR(128), IN p_definition LONGTEXT
)
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = p_table)
       AND NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = p_table AND column_name = p_column) THEN
        SET @pdnew_sql = CONCAT('ALTER TABLE `', REPLACE(p_table,'`','``'), '` ADD COLUMN ', p_definition);
        PREPARE pdnew_stmt FROM @pdnew_sql;
        EXECUTE pdnew_stmt;
        DEALLOCATE PREPARE pdnew_stmt;
    END IF;
END$$

CREATE PROCEDURE `pdnew_add_index_if_missing`(
    IN p_table VARCHAR(128), IN p_index VARCHAR(128), IN p_definition LONGTEXT
)
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = p_table)
       AND NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = p_table AND index_name = p_index) THEN
        SET @pdnew_sql = CONCAT('ALTER TABLE `', REPLACE(p_table,'`','``'), '` ADD ', p_definition);
        PREPARE pdnew_stmt FROM @pdnew_sql;
        EXECUTE pdnew_stmt;
        DEALLOCATE PREPARE pdnew_stmt;
    END IF;
END$$
DELIMITER ;

CALL `pdnew_add_column_if_missing`('pdnew_module_settings','business_id','`business_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_module_settings','location_id','`location_id` INT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_module_settings','scope_key','`scope_key` VARCHAR(191) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_module_settings','settlement_prefix','`settlement_prefix` VARCHAR(30) NOT NULL DEFAULT ''PDN-SET-''');
CALL `pdnew_add_column_if_missing`('pdnew_module_settings','day_end_prefix','`day_end_prefix` VARCHAR(30) NOT NULL DEFAULT ''PDN-DE-''');
CALL `pdnew_add_column_if_missing`('pdnew_module_settings','amount_decimals','`amount_decimals` TINYINT UNSIGNED NOT NULL DEFAULT 4');
CALL `pdnew_add_column_if_missing`('pdnew_module_settings','quantity_decimals','`quantity_decimals` TINYINT UNSIGNED NOT NULL DEFAULT 3');
CALL `pdnew_add_column_if_missing`('pdnew_module_settings','require_review','`require_review` TINYINT(1) NOT NULL DEFAULT 1');
CALL `pdnew_add_column_if_missing`('pdnew_module_settings','require_approval','`require_approval` TINYINT(1) NOT NULL DEFAULT 1');
CALL `pdnew_add_column_if_missing`('pdnew_module_settings','require_zero_variance','`require_zero_variance` TINYINT(1) NOT NULL DEFAULT 1');
CALL `pdnew_add_column_if_missing`('pdnew_module_settings','allow_reopen','`allow_reopen` TINYINT(1) NOT NULL DEFAULT 1');
CALL `pdnew_add_column_if_missing`('pdnew_module_settings','auto_import_closed_shifts','`auto_import_closed_shifts` TINYINT(1) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_module_settings','is_active','`is_active` TINYINT(1) NOT NULL DEFAULT 1');
CALL `pdnew_add_column_if_missing`('pdnew_module_settings','settings','`settings` LONGTEXT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_module_settings','created_at','`created_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_module_settings','updated_at','`updated_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_index_if_missing`('pdnew_module_settings','pdnew_settings_scope_uq','UNIQUE KEY `pdnew_settings_scope_uq` (`scope_key`)');
CALL `pdnew_add_index_if_missing`('pdnew_module_settings','pdnew_settings_business_idx','KEY `pdnew_settings_business_idx` (`business_id`,`location_id`)');
CALL `pdnew_add_column_if_missing`('pdnew_number_sequences','business_id','`business_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_number_sequences','location_id','`location_id` INT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_number_sequences','sequence_type','`sequence_type` VARCHAR(50) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_number_sequences','scope_key','`scope_key` VARCHAR(191) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_number_sequences','prefix','`prefix` VARCHAR(30) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_number_sequences','next_number','`next_number` BIGINT UNSIGNED NOT NULL DEFAULT 1');
CALL `pdnew_add_column_if_missing`('pdnew_number_sequences','padding','`padding` TINYINT UNSIGNED NOT NULL DEFAULT 6');
CALL `pdnew_add_column_if_missing`('pdnew_number_sequences','created_at','`created_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_number_sequences','updated_at','`updated_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_index_if_missing`('pdnew_number_sequences','pdnew_sequences_scope_uq','UNIQUE KEY `pdnew_sequences_scope_uq` (`scope_key`)');
CALL `pdnew_add_index_if_missing`('pdnew_number_sequences','pdnew_sequences_business_idx','KEY `pdnew_sequences_business_idx` (`business_id`,`sequence_type`)');
CALL `pdnew_add_column_if_missing`('pdnew_operator_mappings','business_id','`business_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_operator_mappings','location_id','`location_id` INT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_operator_mappings','pone_operator_profile_id','`pone_operator_profile_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_operator_mappings','pone_pd_operator_id','`pone_pd_operator_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_operator_mappings','user_id','`user_id` INT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_operator_mappings','display_name','`display_name` VARCHAR(191) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_operator_mappings','status','`status` VARCHAR(30) NOT NULL DEFAULT ''active''');
CALL `pdnew_add_column_if_missing`('pdnew_operator_mappings','settings','`settings` LONGTEXT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_operator_mappings','last_synced_at','`last_synced_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_operator_mappings','created_at','`created_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_operator_mappings','updated_at','`updated_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_index_if_missing`('pdnew_operator_mappings','pdnew_operator_profile_uq','UNIQUE KEY `pdnew_operator_profile_uq` (`business_id`,`pone_operator_profile_id`)');
CALL `pdnew_add_index_if_missing`('pdnew_operator_mappings','pdnew_operator_scope_idx','KEY `pdnew_operator_scope_idx` (`business_id`,`location_id`,`status`)');
CALL `pdnew_add_column_if_missing`('pdnew_source_imports','business_id','`business_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_source_imports','location_id','`location_id` INT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_source_imports','pone_shift_id','`pone_shift_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_source_imports','pone_shift_uuid','`pone_shift_uuid` CHAR(36) NULL');
CALL `pdnew_add_column_if_missing`('pdnew_source_imports','pone_shift_number','`pone_shift_number` VARCHAR(80) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_source_imports','pone_operator_profile_id','`pone_operator_profile_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_source_imports','pone_pd_operator_id','`pone_pd_operator_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_source_imports','source_status','`source_status` VARCHAR(30) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_source_imports','import_status','`import_status` VARCHAR(30) NOT NULL DEFAULT ''available''');
CALL `pdnew_add_column_if_missing`('pdnew_source_imports','source_hash','`source_hash` CHAR(64) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_source_imports','current_hash','`current_hash` CHAR(64) NULL');
CALL `pdnew_add_column_if_missing`('pdnew_source_imports','snapshot_version','`snapshot_version` INT UNSIGNED NOT NULL DEFAULT 1');
CALL `pdnew_add_column_if_missing`('pdnew_source_imports','source_closed_at','`source_closed_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_source_imports','source_totals','`source_totals` LONGTEXT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_source_imports','settlement_id','`settlement_id` BIGINT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_source_imports','imported_at','`imported_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_source_imports','verified_at','`verified_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_source_imports','last_error','`last_error` TEXT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_source_imports','imported_by','`imported_by` INT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_source_imports','created_at','`created_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_source_imports','updated_at','`updated_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_index_if_missing`('pdnew_source_imports','pdnew_source_shift_uq','UNIQUE KEY `pdnew_source_shift_uq` (`business_id`,`pone_shift_id`)');
CALL `pdnew_add_index_if_missing`('pdnew_source_imports','pdnew_source_status_idx','KEY `pdnew_source_status_idx` (`business_id`,`location_id`,`import_status`)');
CALL `pdnew_add_index_if_missing`('pdnew_source_imports','pdnew_source_settlement_idx','KEY `pdnew_source_settlement_idx` (`settlement_id`)');
CALL `pdnew_add_column_if_missing`('pdnew_source_snapshots','business_id','`business_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_source_snapshots','source_import_id','`source_import_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_source_snapshots','pone_shift_id','`pone_shift_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_source_snapshots','version','`version` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_source_snapshots','source_hash','`source_hash` CHAR(64) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_source_snapshots','snapshot','`snapshot` LONGTEXT NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_source_snapshots','captured_at','`captured_at` TIMESTAMP NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_source_snapshots','captured_by','`captured_by` INT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_source_snapshots','created_at','`created_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_source_snapshots','updated_at','`updated_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_index_if_missing`('pdnew_source_snapshots','pdnew_snapshot_version_uq','UNIQUE KEY `pdnew_snapshot_version_uq` (`source_import_id`,`version`)');
CALL `pdnew_add_index_if_missing`('pdnew_source_snapshots','pdnew_snapshot_shift_idx','KEY `pdnew_snapshot_shift_idx` (`business_id`,`pone_shift_id`)');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','uuid','`uuid` CHAR(36) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','business_id','`business_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','location_id','`location_id` INT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','source_import_id','`source_import_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','pone_shift_id','`pone_shift_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','pone_shift_number','`pone_shift_number` VARCHAR(80) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','pone_operator_profile_id','`pone_operator_profile_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','pone_pd_operator_id','`pone_pd_operator_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','operator_name','`operator_name` VARCHAR(191) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','settlement_number','`settlement_number` VARCHAR(80) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','settlement_date','`settlement_date` DATE NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','source_closed_at','`source_closed_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','status','`status` VARCHAR(30) NOT NULL DEFAULT ''draft''');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','source_hash','`source_hash` CHAR(64) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','meter_sales_total','`meter_sales_total` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','other_sales_total','`other_sales_total` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','source_payments_total','`source_payments_total` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','manual_payments_total','`manual_payments_total` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','source_declared_total','`source_declared_total` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','source_shortage_total','`source_shortage_total` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','source_excess_total','`source_excess_total` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','manual_shortage_total','`manual_shortage_total` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','manual_excess_total','`manual_excess_total` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','shortage_recovery_total','`shortage_recovery_total` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','excess_commission_total','`excess_commission_total` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','adjustments_total','`adjustments_total` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','expected_adjustments_total','`expected_adjustments_total` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','received_adjustments_total','`received_adjustments_total` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','expected_total','`expected_total` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','received_total','`received_total` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','operational_variance_amount','`operational_variance_amount` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','variance_amount','`variance_amount` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','reconciliation_status','`reconciliation_status` VARCHAR(30) NOT NULL DEFAULT ''pending''');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','notes','`notes` TEXT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','submitted_at','`submitted_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','submitted_by','`submitted_by` INT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','reviewed_at','`reviewed_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','reviewed_by','`reviewed_by` INT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','approved_at','`approved_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','approved_by','`approved_by` INT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','finalized_at','`finalized_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','finalized_by','`finalized_by` INT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','cancelled_at','`cancelled_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','cancelled_by','`cancelled_by` INT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','created_by','`created_by` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','created_at','`created_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','updated_at','`updated_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlements','deleted_at','`deleted_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_index_if_missing`('pdnew_settlements','pdnew_settlement_uuid_uq','UNIQUE KEY `pdnew_settlement_uuid_uq` (`uuid`)');
CALL `pdnew_add_index_if_missing`('pdnew_settlements','pdnew_settlement_number_uq','UNIQUE KEY `pdnew_settlement_number_uq` (`business_id`,`settlement_number`)');
CALL `pdnew_add_index_if_missing`('pdnew_settlements','pdnew_settlement_shift_uq','UNIQUE KEY `pdnew_settlement_shift_uq` (`business_id`,`pone_shift_id`)');
CALL `pdnew_add_index_if_missing`('pdnew_settlements','pdnew_settlement_status_idx','KEY `pdnew_settlement_status_idx` (`business_id`,`location_id`,`status`,`settlement_date`)');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_sources','business_id','`business_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_sources','settlement_id','`settlement_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_sources','pone_shift_id','`pone_shift_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_sources','pone_shift_number','`pone_shift_number` VARCHAR(80) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_sources','source_hash','`source_hash` CHAR(64) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_sources','source_closed_at','`source_closed_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_sources','source_totals','`source_totals` LONGTEXT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_sources','created_at','`created_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_sources','updated_at','`updated_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_index_if_missing`('pdnew_settlement_sources','pdnew_settlement_source_uq','UNIQUE KEY `pdnew_settlement_source_uq` (`settlement_id`,`pone_shift_id`)');
CALL `pdnew_add_index_if_missing`('pdnew_settlement_sources','pdnew_source_business_idx','KEY `pdnew_source_business_idx` (`business_id`,`pone_shift_id`)');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_pumps','business_id','`business_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_pumps','settlement_id','`settlement_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_pumps','pone_assignment_id','`pone_assignment_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_pumps','pump_id','`pump_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_pumps','product_id','`product_id` INT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_pumps','opening_meter','`opening_meter` DECIMAL(22,3) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_pumps','closing_meter','`closing_meter` DECIMAL(22,3) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_pumps','testing_quantity','`testing_quantity` DECIMAL(22,3) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_pumps','sold_quantity','`sold_quantity` DECIMAL(22,3) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_pumps','unit_price','`unit_price` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_pumps','amount','`amount` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_pumps','status','`status` VARCHAR(30) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_pumps','created_at','`created_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_pumps','updated_at','`updated_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_index_if_missing`('pdnew_settlement_pumps','pdnew_settlement_assignment_uq','UNIQUE KEY `pdnew_settlement_assignment_uq` (`settlement_id`,`pone_assignment_id`)');
CALL `pdnew_add_index_if_missing`('pdnew_settlement_pumps','pdnew_settlement_pump_idx','KEY `pdnew_settlement_pump_idx` (`business_id`,`pump_id`)');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_meter_sales','business_id','`business_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_meter_sales','settlement_id','`settlement_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_meter_sales','pone_assignment_id','`pone_assignment_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_meter_sales','pone_meter_reading_id','`pone_meter_reading_id` BIGINT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_meter_sales','pump_id','`pump_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_meter_sales','product_id','`product_id` INT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_meter_sales','reading_at','`reading_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_meter_sales','opening_meter','`opening_meter` DECIMAL(22,3) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_meter_sales','closing_meter','`closing_meter` DECIMAL(22,3) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_meter_sales','testing_quantity','`testing_quantity` DECIMAL(22,3) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_meter_sales','sold_quantity','`sold_quantity` DECIMAL(22,3) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_meter_sales','unit_price','`unit_price` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_meter_sales','amount','`amount` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_meter_sales','created_at','`created_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_meter_sales','updated_at','`updated_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_index_if_missing`('pdnew_settlement_meter_sales','pdnew_meter_assignment_uq','UNIQUE KEY `pdnew_meter_assignment_uq` (`settlement_id`,`pone_assignment_id`)');
CALL `pdnew_add_index_if_missing`('pdnew_settlement_meter_sales','pdnew_meter_business_idx','KEY `pdnew_meter_business_idx` (`business_id`,`pump_id`,`reading_at`)');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_payments','uuid','`uuid` CHAR(36) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_payments','business_id','`business_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_payments','settlement_id','`settlement_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_payments','pone_payment_id','`pone_payment_id` BIGINT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_payments','payment_number','`payment_number` VARCHAR(80) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_payments','payment_type','`payment_type` VARCHAR(40) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_payments','gross_amount','`gross_amount` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_payments','discount_amount','`discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_payments','amount','`amount` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_payments','customer_id','`customer_id` INT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_payments','reference_no','`reference_no` VARCHAR(191) NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_payments','transaction_at','`transaction_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_payments','status','`status` VARCHAR(30) NOT NULL DEFAULT ''active''');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_payments','is_source','`is_source` TINYINT(1) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_payments','note','`note` TEXT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_payments','metadata','`metadata` LONGTEXT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_payments','created_by','`created_by` INT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_payments','voided_by','`voided_by` INT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_payments','voided_at','`voided_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_payments','created_at','`created_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_payments','updated_at','`updated_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_payments','deleted_at','`deleted_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_index_if_missing`('pdnew_settlement_payments','pdnew_payment_uuid_uq','UNIQUE KEY `pdnew_payment_uuid_uq` (`uuid`)');
CALL `pdnew_add_index_if_missing`('pdnew_settlement_payments','pdnew_source_payment_uq','UNIQUE KEY `pdnew_source_payment_uq` (`settlement_id`,`pone_payment_id`)');
CALL `pdnew_add_index_if_missing`('pdnew_settlement_payments','pdnew_payment_type_idx','KEY `pdnew_payment_type_idx` (`business_id`,`settlement_id`,`payment_type`,`status`)');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_payment_details','business_id','`business_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_payment_details','settlement_id','`settlement_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_payment_details','payment_id','`payment_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_payment_details','detail_type','`detail_type` VARCHAR(40) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_payment_details','reference_no','`reference_no` VARCHAR(191) NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_payment_details','amount','`amount` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_payment_details','detail','`detail` LONGTEXT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_payment_details','created_at','`created_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_payment_details','updated_at','`updated_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_index_if_missing`('pdnew_settlement_payment_details','pdnew_payment_detail_idx','KEY `pdnew_payment_detail_idx` (`payment_id`,`detail_type`)');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_credit_sales','business_id','`business_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_credit_sales','settlement_id','`settlement_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_credit_sales','pone_credit_sale_id','`pone_credit_sale_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_credit_sales','pone_payment_id','`pone_payment_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_credit_sales','customer_id','`customer_id` INT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_credit_sales','order_number','`order_number` VARCHAR(191) NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_credit_sales','bill_number','`bill_number` VARCHAR(191) NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_credit_sales','vehicle_number','`vehicle_number` VARCHAR(191) NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_credit_sales','customer_reference','`customer_reference` VARCHAR(191) NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_credit_sales','order_date','`order_date` DATE NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_credit_sales','due_date','`due_date` DATE NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_credit_sales','amount','`amount` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_credit_sales','confirmed','`confirmed` TINYINT(1) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_credit_sales','metadata','`metadata` LONGTEXT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_credit_sales','created_at','`created_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_credit_sales','updated_at','`updated_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_index_if_missing`('pdnew_settlement_credit_sales','pdnew_credit_source_uq','UNIQUE KEY `pdnew_credit_source_uq` (`settlement_id`,`pone_credit_sale_id`)');
CALL `pdnew_add_index_if_missing`('pdnew_settlement_credit_sales','pdnew_credit_customer_idx','KEY `pdnew_credit_customer_idx` (`business_id`,`customer_id`,`order_date`)');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_credit_sale_lines','business_id','`business_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_credit_sale_lines','settlement_id','`settlement_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_credit_sale_lines','credit_sale_id','`credit_sale_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_credit_sale_lines','pone_credit_sale_line_id','`pone_credit_sale_line_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_credit_sale_lines','product_id','`product_id` INT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_credit_sale_lines','quantity','`quantity` DECIMAL(22,3) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_credit_sale_lines','unit_price','`unit_price` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_credit_sale_lines','discount_amount','`discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_credit_sale_lines','amount','`amount` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_credit_sale_lines','created_at','`created_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_credit_sale_lines','updated_at','`updated_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_index_if_missing`('pdnew_settlement_credit_sale_lines','pdnew_credit_line_source_uq','UNIQUE KEY `pdnew_credit_line_source_uq` (`credit_sale_id`,`pone_credit_sale_line_id`)');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_other_sales','business_id','`business_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_other_sales','settlement_id','`settlement_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_other_sales','pone_other_sale_id','`pone_other_sale_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_other_sales','sale_number','`sale_number` VARCHAR(80) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_other_sales','store_id','`store_id` INT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_other_sales','sale_at','`sale_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_other_sales','gross_amount','`gross_amount` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_other_sales','discount_amount','`discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_other_sales','net_amount','`net_amount` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_other_sales','status','`status` VARCHAR(30) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_other_sales','created_at','`created_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_other_sales','updated_at','`updated_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_index_if_missing`('pdnew_settlement_other_sales','pdnew_other_sale_source_uq','UNIQUE KEY `pdnew_other_sale_source_uq` (`settlement_id`,`pone_other_sale_id`)');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_other_sale_lines','business_id','`business_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_other_sale_lines','settlement_id','`settlement_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_other_sale_lines','other_sale_id','`other_sale_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_other_sale_lines','pone_other_sale_line_id','`pone_other_sale_line_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_other_sale_lines','product_id','`product_id` INT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_other_sale_lines','quantity','`quantity` DECIMAL(22,3) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_other_sale_lines','unit_price','`unit_price` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_other_sale_lines','discount_amount','`discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_other_sale_lines','amount','`amount` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_other_sale_lines','created_at','`created_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_other_sale_lines','updated_at','`updated_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_index_if_missing`('pdnew_settlement_other_sale_lines','pdnew_other_line_source_uq','UNIQUE KEY `pdnew_other_line_source_uq` (`other_sale_id`,`pone_other_sale_line_id`)');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_unload_stocks','business_id','`business_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_unload_stocks','settlement_id','`settlement_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_unload_stocks','pone_unload_stock_id','`pone_unload_stock_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_unload_stocks','receipt_number','`receipt_number` VARCHAR(80) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_unload_stocks','bill_number','`bill_number` VARCHAR(191) NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_unload_stocks','store_id','`store_id` INT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_unload_stocks','unloaded_at','`unloaded_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_unload_stocks','total_quantity','`total_quantity` DECIMAL(22,3) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_unload_stocks','total_amount','`total_amount` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_unload_stocks','status','`status` VARCHAR(30) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_unload_stocks','created_at','`created_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_unload_stocks','updated_at','`updated_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_index_if_missing`('pdnew_settlement_unload_stocks','pdnew_unload_source_uq','UNIQUE KEY `pdnew_unload_source_uq` (`settlement_id`,`pone_unload_stock_id`)');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_unload_stock_lines','business_id','`business_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_unload_stock_lines','settlement_id','`settlement_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_unload_stock_lines','unload_stock_id','`unload_stock_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_unload_stock_lines','pone_unload_stock_line_id','`pone_unload_stock_line_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_unload_stock_lines','product_id','`product_id` INT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_unload_stock_lines','tank_id','`tank_id` INT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_unload_stock_lines','quantity','`quantity` DECIMAL(22,3) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_unload_stock_lines','unit_cost','`unit_cost` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_unload_stock_lines','amount','`amount` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_unload_stock_lines','dip_reading','`dip_reading` DECIMAL(22,3) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_unload_stock_lines','current_stock','`current_stock` DECIMAL(22,3) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_unload_stock_lines','created_at','`created_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_unload_stock_lines','updated_at','`updated_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_index_if_missing`('pdnew_settlement_unload_stock_lines','pdnew_unload_line_source_uq','UNIQUE KEY `pdnew_unload_line_source_uq` (`unload_stock_id`,`pone_unload_stock_line_id`)');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_day_entries','business_id','`business_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_day_entries','settlement_id','`settlement_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_day_entries','pone_day_entry_id','`pone_day_entry_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_day_entries','entry_type','`entry_type` VARCHAR(50) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_day_entries','reference_no','`reference_no` VARCHAR(191) NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_day_entries','pump_id','`pump_id` INT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_day_entries','assignment_id','`assignment_id` BIGINT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_day_entries','quantity','`quantity` DECIMAL(22,3) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_day_entries','amount','`amount` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_day_entries','starting_meter','`starting_meter` DECIMAL(22,3) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_day_entries','closing_meter','`closing_meter` DECIMAL(22,3) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_day_entries','testing_quantity','`testing_quantity` DECIMAL(22,3) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_day_entries','entry_at','`entry_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_day_entries','status','`status` VARCHAR(30) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_day_entries','note','`note` TEXT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_day_entries','created_at','`created_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_day_entries','updated_at','`updated_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_index_if_missing`('pdnew_settlement_day_entries','pdnew_day_entry_source_uq','UNIQUE KEY `pdnew_day_entry_source_uq` (`settlement_id`,`pone_day_entry_id`)');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_collections','business_id','`business_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_collections','settlement_id','`settlement_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_collections','pone_collection_id','`pone_collection_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_collections','collection_number','`collection_number` VARCHAR(80) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_collections','collection_at','`collection_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_collections','expected_amount','`expected_amount` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_collections','cash_amount','`cash_amount` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_collections','card_amount','`card_amount` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_collections','cheque_amount','`cheque_amount` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_collections','credit_amount','`credit_amount` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_collections','other_amount','`other_amount` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_collections','declared_amount','`declared_amount` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_collections','difference_amount','`difference_amount` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_collections','status','`status` VARCHAR(30) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_collections','created_at','`created_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_collections','updated_at','`updated_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_index_if_missing`('pdnew_settlement_collections','pdnew_collection_source_uq','UNIQUE KEY `pdnew_collection_source_uq` (`settlement_id`,`pone_collection_id`)');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_ledger_entries','business_id','`business_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_ledger_entries','settlement_id','`settlement_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_ledger_entries','pone_ledger_entry_id','`pone_ledger_entry_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_ledger_entries','entry_at','`entry_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_ledger_entries','source_type','`source_type` VARCHAR(50) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_ledger_entries','source_id','`source_id` BIGINT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_ledger_entries','reference_no','`reference_no` VARCHAR(191) NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_ledger_entries','description','`description` TEXT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_ledger_entries','debit','`debit` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_ledger_entries','credit','`credit` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_ledger_entries','status','`status` VARCHAR(30) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_ledger_entries','created_at','`created_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_ledger_entries','updated_at','`updated_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_index_if_missing`('pdnew_settlement_ledger_entries','pdnew_ledger_source_uq','UNIQUE KEY `pdnew_ledger_source_uq` (`settlement_id`,`pone_ledger_entry_id`)');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_recoveries','business_id','`business_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_recoveries','settlement_id','`settlement_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_recoveries','pone_recovery_id','`pone_recovery_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_recoveries','recovery_number','`recovery_number` VARCHAR(80) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_recoveries','recovery_date','`recovery_date` DATE NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_recoveries','amount','`amount` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_recoveries','payment_method','`payment_method` VARCHAR(40) NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_recoveries','reference_no','`reference_no` VARCHAR(191) NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_recoveries','status','`status` VARCHAR(30) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_recoveries','note','`note` TEXT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_recoveries','created_at','`created_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_recoveries','updated_at','`updated_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_index_if_missing`('pdnew_settlement_recoveries','pdnew_recovery_source_uq','UNIQUE KEY `pdnew_recovery_source_uq` (`settlement_id`,`pone_recovery_id`)');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_commissions','business_id','`business_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_commissions','settlement_id','`settlement_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_commissions','pone_commission_id','`pone_commission_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_commissions','commission_number','`commission_number` VARCHAR(80) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_commissions','commission_date','`commission_date` DATE NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_commissions','base_excess_amount','`base_excess_amount` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_commissions','commission_type','`commission_type` VARCHAR(30) NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_commissions','commission_rate','`commission_rate` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_commissions','commission_amount','`commission_amount` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_commissions','status','`status` VARCHAR(30) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_commissions','note','`note` TEXT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_commissions','created_at','`created_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_commissions','updated_at','`updated_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_index_if_missing`('pdnew_settlement_commissions','pdnew_commission_source_uq','UNIQUE KEY `pdnew_commission_source_uq` (`settlement_id`,`pone_commission_id`)');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_adjustments','uuid','`uuid` CHAR(36) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_adjustments','business_id','`business_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_adjustments','settlement_id','`settlement_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_adjustments','adjustment_number','`adjustment_number` VARCHAR(80) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_adjustments','adjustment_type','`adjustment_type` VARCHAR(40) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_adjustments','field_name','`field_name` VARCHAR(80) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_adjustments','current_amount','`current_amount` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_adjustments','requested_amount','`requested_amount` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_adjustments','approved_amount','`approved_amount` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_adjustments','reason','`reason` TEXT NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_adjustments','status','`status` VARCHAR(30) NOT NULL DEFAULT ''requested''');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_adjustments','requested_by','`requested_by` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_adjustments','requested_at','`requested_at` TIMESTAMP NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_adjustments','approved_by','`approved_by` INT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_adjustments','approved_at','`approved_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_adjustments','rejected_by','`rejected_by` INT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_adjustments','rejected_at','`rejected_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_adjustments','decision_note','`decision_note` TEXT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_adjustments','metadata','`metadata` LONGTEXT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_adjustments','created_at','`created_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_adjustments','updated_at','`updated_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_index_if_missing`('pdnew_settlement_adjustments','pdnew_adjustment_uuid_uq','UNIQUE KEY `pdnew_adjustment_uuid_uq` (`uuid`)');
CALL `pdnew_add_index_if_missing`('pdnew_settlement_adjustments','pdnew_adjustment_number_uq','UNIQUE KEY `pdnew_adjustment_number_uq` (`business_id`,`adjustment_number`)');
CALL `pdnew_add_index_if_missing`('pdnew_settlement_adjustments','pdnew_adjustment_status_idx','KEY `pdnew_adjustment_status_idx` (`business_id`,`settlement_id`,`status`)');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_approvals','business_id','`business_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_approvals','settlement_id','`settlement_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_approvals','action','`action` VARCHAR(40) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_approvals','from_status','`from_status` VARCHAR(30) NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_approvals','to_status','`to_status` VARCHAR(30) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_approvals','note','`note` TEXT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_approvals','acted_by','`acted_by` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_approvals','acted_at','`acted_at` TIMESTAMP NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_approvals','metadata','`metadata` LONGTEXT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_approvals','created_at','`created_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_approvals','updated_at','`updated_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_index_if_missing`('pdnew_settlement_approvals','pdnew_approval_settlement_idx','KEY `pdnew_approval_settlement_idx` (`settlement_id`,`acted_at`)');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_status_histories','business_id','`business_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_status_histories','settlement_id','`settlement_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_status_histories','from_status','`from_status` VARCHAR(30) NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_status_histories','to_status','`to_status` VARCHAR(30) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_status_histories','reason','`reason` TEXT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_status_histories','changed_by','`changed_by` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_status_histories','changed_at','`changed_at` TIMESTAMP NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_status_histories','metadata','`metadata` LONGTEXT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_status_histories','created_at','`created_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_settlement_status_histories','updated_at','`updated_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_index_if_missing`('pdnew_settlement_status_histories','pdnew_status_history_idx','KEY `pdnew_status_history_idx` (`settlement_id`,`changed_at`)');
CALL `pdnew_add_column_if_missing`('pdnew_reconciliation_issues','business_id','`business_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_reconciliation_issues','settlement_id','`settlement_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_reconciliation_issues','issue_key','`issue_key` VARCHAR(191) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_reconciliation_issues','issue_type','`issue_type` VARCHAR(50) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_reconciliation_issues','severity','`severity` VARCHAR(20) NOT NULL DEFAULT ''error''');
CALL `pdnew_add_column_if_missing`('pdnew_reconciliation_issues','description','`description` TEXT NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_reconciliation_issues','expected_amount','`expected_amount` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_reconciliation_issues','actual_amount','`actual_amount` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_reconciliation_issues','difference_amount','`difference_amount` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_reconciliation_issues','status','`status` VARCHAR(30) NOT NULL DEFAULT ''open''');
CALL `pdnew_add_column_if_missing`('pdnew_reconciliation_issues','resolution_note','`resolution_note` TEXT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_reconciliation_issues','resolved_by','`resolved_by` INT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_reconciliation_issues','resolved_at','`resolved_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_reconciliation_issues','metadata','`metadata` LONGTEXT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_reconciliation_issues','created_at','`created_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_reconciliation_issues','updated_at','`updated_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_index_if_missing`('pdnew_reconciliation_issues','pdnew_issue_key_uq','UNIQUE KEY `pdnew_issue_key_uq` (`settlement_id`,`issue_key`)');
CALL `pdnew_add_index_if_missing`('pdnew_reconciliation_issues','pdnew_issue_status_idx','KEY `pdnew_issue_status_idx` (`business_id`,`status`,`severity`)');
CALL `pdnew_add_column_if_missing`('pdnew_day_ends','uuid','`uuid` CHAR(36) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_day_ends','business_id','`business_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_day_ends','location_id','`location_id` INT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_day_ends','day_end_number','`day_end_number` VARCHAR(80) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_day_ends','day_end_date','`day_end_date` DATE NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_day_ends','status','`status` VARCHAR(30) NOT NULL DEFAULT ''draft''');
CALL `pdnew_add_column_if_missing`('pdnew_day_ends','settlement_count','`settlement_count` INT UNSIGNED NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_day_ends','settlements_total','`settlements_total` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_day_ends','payments_total','`payments_total` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_day_ends','variance_total','`variance_total` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_day_ends','note','`note` TEXT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_day_ends','prepared_by','`prepared_by` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_day_ends','prepared_at','`prepared_at` TIMESTAMP NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_day_ends','finalized_by','`finalized_by` INT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_day_ends','finalized_at','`finalized_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_day_ends','created_at','`created_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_day_ends','updated_at','`updated_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_index_if_missing`('pdnew_day_ends','pdnew_day_end_uuid_uq','UNIQUE KEY `pdnew_day_end_uuid_uq` (`uuid`)');
CALL `pdnew_add_index_if_missing`('pdnew_day_ends','pdnew_day_end_number_uq','UNIQUE KEY `pdnew_day_end_number_uq` (`business_id`,`day_end_number`)');
CALL `pdnew_add_index_if_missing`('pdnew_day_ends','pdnew_day_end_scope_uq','UNIQUE KEY `pdnew_day_end_scope_uq` (`business_id`,`location_id`,`day_end_date`)');
CALL `pdnew_add_column_if_missing`('pdnew_day_end_settlements','business_id','`business_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_day_end_settlements','day_end_id','`day_end_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_day_end_settlements','settlement_id','`settlement_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_day_end_settlements','settlement_amount','`settlement_amount` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_day_end_settlements','payment_amount','`payment_amount` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_day_end_settlements','variance_amount','`variance_amount` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_day_end_settlements','created_at','`created_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_day_end_settlements','updated_at','`updated_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_index_if_missing`('pdnew_day_end_settlements','pdnew_day_end_settlement_uq','UNIQUE KEY `pdnew_day_end_settlement_uq` (`settlement_id`)');
CALL `pdnew_add_index_if_missing`('pdnew_day_end_settlements','pdnew_day_end_items_idx','KEY `pdnew_day_end_items_idx` (`day_end_id`)');
CALL `pdnew_add_column_if_missing`('pdnew_posting_batches','uuid','`uuid` CHAR(36) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_posting_batches','business_id','`business_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_posting_batches','location_id','`location_id` INT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_posting_batches','settlement_id','`settlement_id` BIGINT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_posting_batches','day_end_id','`day_end_id` BIGINT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_posting_batches','batch_number','`batch_number` VARCHAR(80) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_posting_batches','posting_date','`posting_date` DATE NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_posting_batches','status','`status` VARCHAR(30) NOT NULL DEFAULT ''prepared''');
CALL `pdnew_add_column_if_missing`('pdnew_posting_batches','total_debit','`total_debit` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_posting_batches','total_credit','`total_credit` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_posting_batches','metadata','`metadata` LONGTEXT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_posting_batches','created_by','`created_by` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_posting_batches','posted_by','`posted_by` INT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_posting_batches','posted_at','`posted_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_posting_batches','reversed_by','`reversed_by` INT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_posting_batches','reversed_at','`reversed_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_posting_batches','created_at','`created_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_posting_batches','updated_at','`updated_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_index_if_missing`('pdnew_posting_batches','pdnew_posting_uuid_uq','UNIQUE KEY `pdnew_posting_uuid_uq` (`uuid`)');
CALL `pdnew_add_index_if_missing`('pdnew_posting_batches','pdnew_posting_number_uq','UNIQUE KEY `pdnew_posting_number_uq` (`business_id`,`batch_number`)');
CALL `pdnew_add_index_if_missing`('pdnew_posting_batches','pdnew_posting_source_idx','KEY `pdnew_posting_source_idx` (`settlement_id`,`day_end_id`,`status`)');
CALL `pdnew_add_column_if_missing`('pdnew_posting_lines','business_id','`business_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_posting_lines','posting_batch_id','`posting_batch_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_posting_lines','line_no','`line_no` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_posting_lines','account_id','`account_id` INT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_posting_lines','account_code','`account_code` VARCHAR(80) NULL');
CALL `pdnew_add_column_if_missing`('pdnew_posting_lines','description','`description` TEXT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_posting_lines','debit','`debit` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_posting_lines','credit','`credit` DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_posting_lines','metadata','`metadata` LONGTEXT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_posting_lines','created_at','`created_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_posting_lines','updated_at','`updated_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_index_if_missing`('pdnew_posting_lines','pdnew_posting_line_uq','UNIQUE KEY `pdnew_posting_line_uq` (`posting_batch_id`,`line_no`)');
CALL `pdnew_add_column_if_missing`('pdnew_integration_outbox','business_id','`business_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_integration_outbox','aggregate_type','`aggregate_type` VARCHAR(50) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_integration_outbox','aggregate_id','`aggregate_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_integration_outbox','event_type','`event_type` VARCHAR(80) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_integration_outbox','idempotency_key','`idempotency_key` CHAR(64) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_integration_outbox','payload','`payload` LONGTEXT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_integration_outbox','status','`status` VARCHAR(30) NOT NULL DEFAULT ''pending''');
CALL `pdnew_add_column_if_missing`('pdnew_integration_outbox','attempts','`attempts` INT UNSIGNED NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_integration_outbox','available_at','`available_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_integration_outbox','processed_at','`processed_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_integration_outbox','last_error','`last_error` TEXT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_integration_outbox','created_at','`created_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_integration_outbox','updated_at','`updated_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_index_if_missing`('pdnew_integration_outbox','pdnew_outbox_key_uq','UNIQUE KEY `pdnew_outbox_key_uq` (`idempotency_key`)');
CALL `pdnew_add_index_if_missing`('pdnew_integration_outbox','pdnew_outbox_status_idx','KEY `pdnew_outbox_status_idx` (`business_id`,`status`,`available_at`)');
CALL `pdnew_add_column_if_missing`('pdnew_integration_logs','business_id','`business_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_integration_logs','direction','`direction` VARCHAR(20) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_integration_logs','operation','`operation` VARCHAR(80) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_integration_logs','aggregate_type','`aggregate_type` VARCHAR(50) NULL');
CALL `pdnew_add_column_if_missing`('pdnew_integration_logs','aggregate_id','`aggregate_id` BIGINT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_integration_logs','status','`status` VARCHAR(30) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_integration_logs','payload','`payload` LONGTEXT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_integration_logs','response','`response` LONGTEXT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_integration_logs','error_message','`error_message` TEXT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_integration_logs','started_at','`started_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_integration_logs','completed_at','`completed_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_integration_logs','created_at','`created_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_integration_logs','updated_at','`updated_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_index_if_missing`('pdnew_integration_logs','pdnew_integration_log_idx','KEY `pdnew_integration_log_idx` (`business_id`,`status`,`created_at`)');
CALL `pdnew_add_column_if_missing`('pdnew_notification_templates','business_id','`business_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_notification_templates','event_key','`event_key` VARCHAR(80) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_notification_templates','name','`name` VARCHAR(191) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_notification_templates','subject','`subject` VARCHAR(255) NULL');
CALL `pdnew_add_column_if_missing`('pdnew_notification_templates','body','`body` LONGTEXT NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_notification_templates','channels','`channels` LONGTEXT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_notification_templates','is_active','`is_active` TINYINT(1) NOT NULL DEFAULT 1');
CALL `pdnew_add_column_if_missing`('pdnew_notification_templates','created_at','`created_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_notification_templates','updated_at','`updated_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_index_if_missing`('pdnew_notification_templates','pdnew_notification_event_uq','UNIQUE KEY `pdnew_notification_event_uq` (`business_id`,`event_key`)');
CALL `pdnew_add_column_if_missing`('pdnew_notification_logs','business_id','`business_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_notification_logs','template_id','`template_id` BIGINT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_notification_logs','settlement_id','`settlement_id` BIGINT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_notification_logs','event_key','`event_key` VARCHAR(80) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_notification_logs','channel','`channel` VARCHAR(30) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_notification_logs','recipient','`recipient` VARCHAR(191) NULL');
CALL `pdnew_add_column_if_missing`('pdnew_notification_logs','status','`status` VARCHAR(30) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_notification_logs','payload','`payload` LONGTEXT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_notification_logs','response','`response` TEXT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_notification_logs','sent_at','`sent_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_notification_logs','failed_at','`failed_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_notification_logs','created_at','`created_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_notification_logs','updated_at','`updated_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_index_if_missing`('pdnew_notification_logs','pdnew_notification_log_idx','KEY `pdnew_notification_log_idx` (`business_id`,`event_key`,`status`)');
CALL `pdnew_add_column_if_missing`('pdnew_print_logs','business_id','`business_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_print_logs','document_type','`document_type` VARCHAR(50) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_print_logs','document_id','`document_id` BIGINT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_print_logs','print_type','`print_type` VARCHAR(30) NOT NULL DEFAULT ''original''');
CALL `pdnew_add_column_if_missing`('pdnew_print_logs','printed_by','`printed_by` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_print_logs','printed_at','`printed_at` TIMESTAMP NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_print_logs','ip_address','`ip_address` VARCHAR(64) NULL');
CALL `pdnew_add_column_if_missing`('pdnew_print_logs','metadata','`metadata` LONGTEXT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_print_logs','created_at','`created_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_print_logs','updated_at','`updated_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_index_if_missing`('pdnew_print_logs','pdnew_print_document_idx','KEY `pdnew_print_document_idx` (`business_id`,`document_type`,`document_id`)');
CALL `pdnew_add_column_if_missing`('pdnew_audit_logs','business_id','`business_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_audit_logs','location_id','`location_id` INT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_audit_logs','user_id','`user_id` INT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_audit_logs','action','`action` VARCHAR(100) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_audit_logs','entity_type','`entity_type` VARCHAR(100) NULL');
CALL `pdnew_add_column_if_missing`('pdnew_audit_logs','entity_id','`entity_id` BIGINT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_audit_logs','old_values','`old_values` LONGTEXT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_audit_logs','new_values','`new_values` LONGTEXT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_audit_logs','ip_address','`ip_address` VARCHAR(64) NULL');
CALL `pdnew_add_column_if_missing`('pdnew_audit_logs','user_agent','`user_agent` TEXT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_audit_logs','created_at','`created_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_audit_logs','updated_at','`updated_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_index_if_missing`('pdnew_audit_logs','pdnew_audit_entity_idx','KEY `pdnew_audit_entity_idx` (`business_id`,`entity_type`,`entity_id`)');
CALL `pdnew_add_index_if_missing`('pdnew_audit_logs','pdnew_audit_date_idx','KEY `pdnew_audit_date_idx` (`business_id`,`created_at`)');
CALL `pdnew_add_column_if_missing`('pdnew_documents','business_id','`business_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_documents','settlement_id','`settlement_id` BIGINT UNSIGNED NULL');
CALL `pdnew_add_column_if_missing`('pdnew_documents','document_type','`document_type` VARCHAR(50) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_documents','title','`title` VARCHAR(191) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_documents','disk','`disk` VARCHAR(50) NOT NULL DEFAULT ''public''');
CALL `pdnew_add_column_if_missing`('pdnew_documents','path','`path` TEXT NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_documents','original_name','`original_name` VARCHAR(255) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_documents','mime_type','`mime_type` VARCHAR(100) NULL');
CALL `pdnew_add_column_if_missing`('pdnew_documents','size_bytes','`size_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_documents','metadata','`metadata` LONGTEXT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_documents','uploaded_by','`uploaded_by` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_documents','created_at','`created_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_documents','updated_at','`updated_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_documents','deleted_at','`deleted_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_index_if_missing`('pdnew_documents','pdnew_document_settlement_idx','KEY `pdnew_document_settlement_idx` (`business_id`,`settlement_id`,`document_type`)');
CALL `pdnew_add_column_if_missing`('pdnew_saved_report_filters','business_id','`business_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_saved_report_filters','user_id','`user_id` INT UNSIGNED NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_saved_report_filters','report_key','`report_key` VARCHAR(80) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_saved_report_filters','name','`name` VARCHAR(191) NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_saved_report_filters','filters','`filters` LONGTEXT NOT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_saved_report_filters','is_default','`is_default` TINYINT(1) NOT NULL DEFAULT 0');
CALL `pdnew_add_column_if_missing`('pdnew_saved_report_filters','created_at','`created_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_column_if_missing`('pdnew_saved_report_filters','updated_at','`updated_at` TIMESTAMP NULL DEFAULT NULL');
CALL `pdnew_add_index_if_missing`('pdnew_saved_report_filters','pdnew_saved_filter_uq','UNIQUE KEY `pdnew_saved_filter_uq` (`business_id`,`user_id`,`report_key`,`name`)');

DROP PROCEDURE IF EXISTS `pdnew_add_column_if_missing`;
DROP PROCEDURE IF EXISTS `pdnew_add_index_if_missing`;

-- ============================================================================
-- 08_INSERT_PERMISSIONS.sql
-- ============================================================================
-- Petro PD-New permissions. Safe to rerun.
SET NAMES utf8mb4;
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.access','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.access' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.dashboard.view','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.dashboard.view' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.sources.view','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.sources.view' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.sources.import','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.sources.import' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.sources.refresh','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.sources.refresh' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.settlements.view','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.settlements.view' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.settlements.create','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.settlements.create' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.settlements.edit','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.settlements.edit' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.settlements.cancel','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.settlements.cancel' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.settlements.reopen','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.settlements.reopen' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.payments.view','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.payments.view' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.payments.manage','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.payments.manage' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.adjustments.view','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.adjustments.view' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.adjustments.request','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.adjustments.request' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.adjustments.approve','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.adjustments.approve' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reconciliation.view','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reconciliation.view' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reconciliation.manage','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reconciliation.manage' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.workflow.review','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.workflow.review' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.workflow.approve','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.workflow.approve' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.workflow.finalize','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.workflow.finalize' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.day_end.view','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.day_end.view' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.day_end.manage','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.day_end.manage' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.day_end.finalize','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.day_end.finalize' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.operators.view','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.operators.view' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.operators.manage','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.operators.manage' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.view','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.view' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.export','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.export' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.print','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.print' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.settlements','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.settlements' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.shifts','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.shifts' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.operators','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.operators' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.payments','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.payments' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.meters','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.meters' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.other_sales','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.other_sales' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.unloads','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.unloads' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.day_entries','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.day_entries' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.collections','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.collections' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.ledger','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.ledger' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.shortages','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.shortages' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.commissions','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.commissions' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.day_end','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.day_end' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.reconciliation','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.reconciliation' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.adjustments','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.adjustments' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.integrity','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.integrity' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.activity','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.activity' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.integration','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.integration' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.print_history','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.print_history' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.integration.view','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.integration.view' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.integration.manage','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.integration.manage' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.notifications.manage','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.notifications.manage' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.audit.view','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.audit.view' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.print','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.print' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.settings.manage','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.settings.manage' AND `guard_name`='web');

-- ============================================================================
-- 05_PATCH_PUMPER_DASHBOARD_NEW_PAIRING.sql
-- ============================================================================
-- Petro PD-New / Pumper Dashboard-New exclusive pairing patch
-- Safe to rerun after both modules are installed in each tenant database.
SET NAMES utf8mb4;


-- Enforce one finalized Petro PD-New reference for each PONE shift.  Older
-- Pumper Dashboard-New migrations did not include this business/shift key.
-- When historical duplicates exist, the installer leaves the data untouched;
-- the verification script reports the duplicate so it can be resolved safely.
DROP PROCEDURE IF EXISTS `pdnew_harden_pone_settlement_reference`;
DELIMITER $$
CREATE PROCEDURE `pdnew_harden_pone_settlement_reference`()
BEGIN
    DECLARE duplicate_groups BIGINT DEFAULT 0;

    IF EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = 'pone_shift_settlement_references'
    ) AND NOT EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = 'pone_shift_settlement_references'
          AND index_name = 'pone_settlement_business_shift_uq'
    ) THEN
        SELECT COUNT(*) INTO duplicate_groups
        FROM (
            SELECT `business_id`, `shift_id`
            FROM `pone_shift_settlement_references`
            GROUP BY `business_id`, `shift_id`
            HAVING COUNT(*) > 1
        ) AS duplicate_reference_groups;

        IF duplicate_groups = 0 THEN
            ALTER TABLE `pone_shift_settlement_references`
              ADD UNIQUE KEY `pone_settlement_business_shift_uq` (`business_id`,`shift_id`);
        END IF;
    END IF;
END$$
DELIMITER ;

CALL `pdnew_harden_pone_settlement_reference`();
DROP PROCEDURE IF EXISTS `pdnew_harden_pone_settlement_reference`;

-- Keep the legacy enum values only for data compatibility; the application fixes
-- all new settings to petro_pd_new and never invokes the legacy bridges.
ALTER TABLE `pone_module_settings`
  MODIFY COLUMN `integration_mode`
    ENUM('petro_pd_new','petropd','local_only')
    NOT NULL DEFAULT 'petro_pd_new';

UPDATE `pone_module_settings`
SET `integration_enabled` = 1,
    `integration_mode` = 'petro_pd_new',
    `sync_during_operation` = 1,
    `require_clean_sync_before_close` = 1,
    `updated_at` = CURRENT_TIMESTAMP
WHERE `integration_mode` <> 'petro_pd_new'
   OR `integration_enabled` <> 1
   OR `sync_during_operation` <> 1
   OR `require_clean_sync_before_close` <> 1;

-- Historical push-link rows are retained for audit but are inactive. Petro
-- PD-New reads immutable snapshots directly and never invokes a target table.
UPDATE `pone_integration_links`
SET `status` = 'retired',
    `last_error` = 'Retired: Pumper Dashboard-New is paired exclusively with Petro PD-New.',
    `updated_at` = CURRENT_TIMESTAMP
WHERE `status` <> 'retired';

-- Pending legacy publication states become source-ready. Petro PD-New pulls the
-- complete source graph after a shift is closed.
UPDATE `pone_shifts`
SET `integration_status` = CASE WHEN `status` = 'closed' THEN 'synced' ELSE `integration_status` END,
    `integration_error` = NULL,
    `updated_at` = CURRENT_TIMESTAMP
WHERE `status` = 'closed'
  AND `integration_status` IN ('pending','failed');

UPDATE `pone_pump_assignments`
SET `integration_status` = 'synced',
    `integration_error` = NULL,
    `updated_at` = CURRENT_TIMESTAMP
WHERE `integration_status` IN ('pending','failed');

UPDATE `pone_payments`
SET `integration_status` = 'synced',
    `integration_error` = NULL,
    `updated_at` = CURRENT_TIMESTAMP
WHERE `integration_status` IN ('pending','failed');

UPDATE `pone_other_sales`
SET `integration_status` = 'synced',
    `integration_error` = NULL,
    `updated_at` = CURRENT_TIMESTAMP
WHERE `integration_status` IN ('pending','failed');

UPDATE `pone_unload_stocks`
SET `integration_status` = 'synced',
    `integration_error` = NULL,
    `updated_at` = CURRENT_TIMESTAMP
WHERE `integration_status` IN ('pending','failed');

UPDATE `pone_day_entries`
SET `integration_status` = 'synced',
    `integration_error` = NULL,
    `updated_at` = CURRENT_TIMESTAMP
WHERE `integration_status` IN ('pending','failed');

SET FOREIGN_KEY_CHECKS = 1;
