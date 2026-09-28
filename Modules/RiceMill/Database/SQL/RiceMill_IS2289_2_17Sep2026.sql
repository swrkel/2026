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
