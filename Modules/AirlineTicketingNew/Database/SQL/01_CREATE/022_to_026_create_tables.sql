-- ATN-022 TO ATN-026
CREATE TABLE IF NOT EXISTS `atn_account_mappings` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`event_code` VARCHAR(80) NOT NULL,`debit_account_id` BIGINT UNSIGNED NOT NULL,`credit_account_id` BIGINT UNSIGNED NOT NULL,
`description_template` VARCHAR(500) NULL,`is_active` TINYINT(1) NOT NULL DEFAULT 1,`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,
`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,UNIQUE KEY `atn_account_mapping_uq` (`business_id`,`event_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_journal_entries` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`journal_no` VARCHAR(40) NOT NULL,`journal_date` DATE NOT NULL,`reference_type` VARCHAR(80) NULL,`reference_id` BIGINT UNSIGNED NULL,`reference_no` VARCHAR(80) NULL,
`description` TEXT NOT NULL,`currency_code` VARCHAR(3) NOT NULL,`exchange_rate` DECIMAL(20,8) NOT NULL DEFAULT 1,
`total_debit` DECIMAL(22,4) NOT NULL DEFAULT 0,`total_credit` DECIMAL(22,4) NOT NULL DEFAULT 0,`status` VARCHAR(30) NOT NULL DEFAULT 'draft',
`posted_by` BIGINT UNSIGNED NULL,`posted_at` DATETIME NULL,`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,
`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,UNIQUE KEY `atn_journal_no_uq` (`business_id`,`journal_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_journal_lines` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`journal_entry_id` BIGINT UNSIGNED NOT NULL,`account_id` BIGINT UNSIGNED NOT NULL,`line_description` VARCHAR(500) NULL,
`debit` DECIMAL(22,4) NOT NULL DEFAULT 0,`credit` DECIMAL(22,4) NOT NULL DEFAULT 0,`currency_code` VARCHAR(3) NOT NULL,
`base_debit` DECIMAL(22,4) NOT NULL DEFAULT 0,`base_credit` DECIMAL(22,4) NOT NULL DEFAULT 0,
`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_exchange_rates` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`rate_date` DATE NOT NULL,`from_currency` VARCHAR(3) NOT NULL,`to_currency` VARCHAR(3) NOT NULL,`rate` DECIMAL(20,8) NOT NULL,
`source` VARCHAR(100) NULL,`is_locked` TINYINT(1) NOT NULL DEFAULT 0,`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,
`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,UNIQUE KEY `atn_exchange_rate_uq` (`business_id`,`rate_date`,`from_currency`,`to_currency`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_currency_revaluations` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`revaluation_no` VARCHAR(40) NOT NULL,`revaluation_date` DATE NOT NULL,`currency_code` VARCHAR(3) NOT NULL,
`old_rate` DECIMAL(20,8) NOT NULL,`new_rate` DECIMAL(20,8) NOT NULL,`foreign_balance` DECIMAL(22,4) NOT NULL,
`old_base_value` DECIMAL(22,4) NOT NULL,`new_base_value` DECIMAL(22,4) NOT NULL,`gain_loss` DECIMAL(22,4) NOT NULL,
`status` VARCHAR(30) NOT NULL DEFAULT 'draft',`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,
`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_bsp_periods` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`period_code` VARCHAR(40) NOT NULL,`period_from` DATE NOT NULL,`period_to` DATE NOT NULL,`payment_due_date` DATE NOT NULL,
`status` VARCHAR(30) NOT NULL DEFAULT 'open',`closed_by` BIGINT UNSIGNED NULL,`closed_at` DATETIME NULL,
`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_bsp_adjustments` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`adjustment_no` VARCHAR(40) NOT NULL,`adjustment_type` VARCHAR(20) NOT NULL,`airline_id` BIGINT UNSIGNED NULL,`ticket_id` BIGINT UNSIGNED NULL,
`remittance_id` BIGINT UNSIGNED NULL,`adjustment_date` DATE NOT NULL,`currency_code` VARCHAR(3) NOT NULL,`amount` DECIMAL(22,4) NOT NULL,
`reason` TEXT NOT NULL,`status` VARCHAR(30) NOT NULL DEFAULT 'open',`document_reference` VARCHAR(100) NULL,
`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_crm_interactions` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`customer_type` VARCHAR(30) NOT NULL,`passenger_id` BIGINT UNSIGNED NULL,`corporate_customer_id` BIGINT UNSIGNED NULL,
`interaction_type` VARCHAR(50) NOT NULL,`interaction_at` DATETIME NOT NULL,`subject` VARCHAR(190) NOT NULL,`notes` TEXT NULL,
`follow_up_at` DATETIME NULL,`assigned_to` BIGINT UNSIGNED NULL,`status` VARCHAR(30) NOT NULL DEFAULT 'open',
`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `atn_loyalty_transactions` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`passenger_id` BIGINT UNSIGNED NOT NULL,`transaction_date` DATE NOT NULL,`transaction_type` VARCHAR(30) NOT NULL,
`reference_type` VARCHAR(80) NULL,`reference_id` BIGINT UNSIGNED NULL,`points` DECIMAL(18,2) NOT NULL,`balance_after` DECIMAL(18,2) NOT NULL,
`description` VARCHAR(500) NULL,`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,
`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
