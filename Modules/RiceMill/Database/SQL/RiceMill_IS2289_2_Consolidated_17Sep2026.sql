-- Rice Mill IS2289-2 - 17 Sep 2026
-- Tenant database SQL. Safe to run once per tenant database.
-- Product Category Mapping values are stored in rcm_settings.settings JSON,
-- so no separate mapping table is required.

CREATE TABLE IF NOT EXISTS `rcm_purchase_payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `purchase_id` BIGINT UNSIGNED NOT NULL,
  `payment_date` DATE NOT NULL,
  `payment_method` VARCHAR(60) NOT NULL,
  `payment_method_label` VARCHAR(120) NULL,
  `payable_account_id` BIGINT UNSIGNED NOT NULL,
  `payment_account_id` BIGINT UNSIGNED NOT NULL,
  `amount` DECIMAL(20,4) NOT NULL,
  `cheque_number` VARCHAR(120) NULL,
  `note` TEXT NULL,
  `debit_account_transaction_id` BIGINT UNSIGNED NULL,
  `credit_account_transaction_id` BIGINT UNSIGNED NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rcm_purchase_payment_unique` (`business_id`,`purchase_id`),
  KEY `rcm_purchase_payment_business_idx` (`business_id`),
  KEY `rcm_purchase_payment_date_idx` (`payment_date`),
  KEY `rcm_purchase_payment_payable_idx` (`payable_account_id`),
  KEY `rcm_purchase_payment_account_idx` (`payment_account_id`),
  KEY `rcm_purchase_payment_debit_tx_idx` (`debit_account_transaction_id`),
  KEY `rcm_purchase_payment_credit_tx_idx` (`credit_account_transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Rice Mill - IS2289-2 follow-up - 17 Sep 2026
-- Standard Purchase payment flow + deferred Purchase Tax at Purchase Order stage.
-- Tenant DB incremental SQL. Safe to run after the previous IS2289-2 SQL.
-- It is also safe when rcm_purchase_payments does not yet exist.

CREATE TABLE IF NOT EXISTS `rcm_purchase_payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `purchase_id` BIGINT UNSIGNED NOT NULL,
  `payment_date` DATE NOT NULL,
  `payment_method` VARCHAR(60) NOT NULL,
  `payment_method_label` VARCHAR(120) NULL,
  `payable_account_id` BIGINT UNSIGNED NOT NULL,
  `payment_account_id` BIGINT UNSIGNED NOT NULL,
  `amount` DECIMAL(20,4) NOT NULL DEFAULT 0,
  `cheque_number` VARCHAR(120) NULL,
  `note` TEXT NULL,
  `debit_account_transaction_id` BIGINT UNSIGNED NULL,
  `credit_account_transaction_id` BIGINT UNSIGNED NULL,
  `transaction_id` BIGINT UNSIGNED NULL,
  `transaction_payment_id` BIGINT UNSIGNED NULL,
  `payment_status` VARCHAR(30) NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rcm_purchase_payment_unique` (`business_id`,`purchase_id`),
  KEY `rcm_purchase_payment_business_idx` (`business_id`),
  KEY `rcm_purchase_payment_date_idx` (`payment_date`),
  KEY `rcm_purchase_payment_payable_idx` (`payable_account_id`),
  KEY `rcm_purchase_payment_account_idx` (`payment_account_id`),
  KEY `rcm_purchase_payment_transaction_idx` (`transaction_id`),
  KEY `rcm_purchase_payment_transaction_payment_idx` (`transaction_payment_id`),
  KEY `rcm_purchase_payment_status_idx` (`payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add missing columns without relying on ALTER ... IF NOT EXISTS syntax.
SET @db := DATABASE();

SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='rcm_paddy_purchases' AND COLUMN_NAME='purchase_tax_id')=0,
 'ALTER TABLE `rcm_paddy_purchases` ADD COLUMN `purchase_tax_id` BIGINT UNSIGNED NULL, ADD INDEX `rcm_paddy_purchase_tax_idx` (`purchase_tax_id`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='rcm_paddy_purchases' AND COLUMN_NAME='purchase_tax_percent')=0,
 'ALTER TABLE `rcm_paddy_purchases` ADD COLUMN `purchase_tax_percent` DECIMAL(10,4) NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='rcm_paddy_purchases' AND COLUMN_NAME='purchase_tax_amount')=0,
 'ALTER TABLE `rcm_paddy_purchases` ADD COLUMN `purchase_tax_amount` DECIMAL(20,4) NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='rcm_paddy_purchases' AND COLUMN_NAME='core_transaction_id')=0,
 'ALTER TABLE `rcm_paddy_purchases` ADD COLUMN `core_transaction_id` BIGINT UNSIGNED NULL, ADD INDEX `rcm_paddy_purchase_core_tx_idx` (`core_transaction_id`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='rcm_paddy_purchases' AND COLUMN_NAME='payment_status')=0,
 'ALTER TABLE `rcm_paddy_purchases` ADD COLUMN `payment_status` VARCHAR(30) NOT NULL DEFAULT ''due'', ADD INDEX `rcm_paddy_purchase_payment_status_idx` (`payment_status`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='rcm_purchase_payments' AND COLUMN_NAME='transaction_id')=0,
 'ALTER TABLE `rcm_purchase_payments` ADD COLUMN `transaction_id` BIGINT UNSIGNED NULL, ADD INDEX `rcm_purchase_payment_transaction_idx` (`transaction_id`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='rcm_purchase_payments' AND COLUMN_NAME='transaction_payment_id')=0,
 'ALTER TABLE `rcm_purchase_payments` ADD COLUMN `transaction_payment_id` BIGINT UNSIGNED NULL, ADD INDEX `rcm_purchase_payment_transaction_payment_idx` (`transaction_payment_id`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='rcm_purchase_payments' AND COLUMN_NAME='payment_status')=0,
 'ALTER TABLE `rcm_purchase_payments` ADD COLUMN `payment_status` VARCHAR(30) NULL, ADD INDEX `rcm_purchase_payment_status_idx` (`payment_status`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- No tax ledger rows are created by this SQL. Purchase Tax is only retained
-- on rcm_paddy_purchases at Purchase Order stage; actual tax posting is deferred
-- until the future actual-purchase/receipt flow.
