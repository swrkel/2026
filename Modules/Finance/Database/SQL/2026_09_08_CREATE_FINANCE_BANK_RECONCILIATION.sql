-- Finance Bank Reconciliation - 08 Sep 2026
-- Run once in EACH tenant database if migrations are not used.

CREATE TABLE IF NOT EXISTS `finance_bank_reconciliations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `account_id` BIGINT UNSIGNED NOT NULL,
  `reconciliation_no` VARCHAR(50) NOT NULL,
  `statement_date` DATE NOT NULL,
  `statement_ending_balance` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `book_ending_balance` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `outstanding_deposits` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `outstanding_payments` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `adjusted_bank_balance` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `difference` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
  `notes` TEXT NULL,
  `created_by` BIGINT UNSIGNED NOT NULL,
  `reconciled_by` BIGINT UNSIGNED NULL,
  `reconciled_at` DATETIME NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `finance_bank_rec_business_no_uq` (`business_id`,`reconciliation_no`),
  KEY `finance_bank_rec_account_date_idx` (`business_id`,`account_id`,`statement_date`),
  KEY `finance_bank_rec_status_idx` (`business_id`,`status`),
  KEY `finance_bank_rec_location_idx` (`business_id`,`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `finance_bank_reconciliation_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reconciliation_id` BIGINT UNSIGNED NOT NULL,
  `account_transaction_id` BIGINT UNSIGNED NULL,
  `transaction_date` DATETIME NULL,
  `reference` VARCHAR(191) NULL,
  `description` TEXT NULL,
  `transaction_type` VARCHAR(20) NOT NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `is_cleared` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `finance_bank_rec_lines_status_idx` (`reconciliation_id`,`is_cleared`),
  KEY `finance_bank_rec_lines_tx_idx` (`account_transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
