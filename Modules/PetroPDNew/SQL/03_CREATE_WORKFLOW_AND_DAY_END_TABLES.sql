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
