-- HOTELMGT_034_SQL.sql
-- Hotel Management Parcel 034: Cashier Control / Shift Reconciliation
-- Execute inside each tenant database. No database names are hardcoded.

CREATE TABLE IF NOT EXISTS `hm_cashier_shifts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `shift_no` VARCHAR(60) NOT NULL,
  `cashier_user_id` BIGINT UNSIGNED NULL,
  `counter_name` VARCHAR(120) NULL,
  `opening_float` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `expected_cash` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `declared_cash` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `variance_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `opened_at` DATETIME NULL,
  `closed_at` DATETIME NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'open',
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_cashier_shifts_shift_no_unique` (`business_id`,`shift_no`),
  KEY `hm_cashier_shifts_scope_idx` (`business_id`,`business_location_id`,`status`),
  KEY `hm_cashier_shifts_cashier_idx` (`cashier_user_id`,`opened_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_cashier_safe_drops` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `shift_id` BIGINT UNSIGNED NOT NULL,
  `drop_no` VARCHAR(60) NOT NULL,
  `drop_datetime` DATETIME NULL,
  `drop_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `received_by` VARCHAR(120) NULL,
  `safe_bag_no` VARCHAR(120) NULL,
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_cashier_safe_drops_drop_no_unique` (`business_id`,`drop_no`),
  KEY `hm_cashier_safe_drops_shift_idx` (`shift_id`),
  KEY `hm_cashier_safe_drops_scope_idx` (`business_id`,`business_location_id`,`drop_datetime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_cashier_cash_counts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `shift_id` BIGINT UNSIGNED NOT NULL,
  `denomination` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `quantity` INT UNSIGNED NOT NULL DEFAULT 0,
  `line_total` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_cashier_cash_counts_shift_idx` (`shift_id`),
  KEY `hm_cashier_cash_counts_scope_idx` (`business_id`,`business_location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_cashier_variances` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `shift_id` BIGINT UNSIGNED NOT NULL,
  `variance_no` VARCHAR(60) NOT NULL,
  `variance_type` VARCHAR(40) NOT NULL,
  `variance_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `reason` TEXT NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'pending_review',
  `review_note` TEXT NULL,
  `reviewed_by` BIGINT UNSIGNED NULL,
  `reviewed_at` DATETIME NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_cashier_variances_no_unique` (`business_id`,`variance_no`),
  KEY `hm_cashier_variances_shift_idx` (`shift_id`),
  KEY `hm_cashier_variances_scope_idx` (`business_id`,`business_location_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
