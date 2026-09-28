-- POS Standalone S349 - Customers, Credit Sales, Ledger, Statements
-- Database agnostic: run after selecting the correct tenant database. No hard-coded DB name.

CREATE TABLE IF NOT EXISTS `pos_customers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `customer_code` VARCHAR(50) NULL,
  `name` VARCHAR(191) NOT NULL,
  `mobile` VARCHAR(50) NULL,
  `email` VARCHAR(191) NULL,
  `nic_no` VARCHAR(100) NULL,
  `address` TEXT NULL,
  `customer_type` ENUM('walk_in','credit','loyalty') NOT NULL DEFAULT 'walk_in',
  `credit_limit` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `opening_balance` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `balance_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `pos_customers_business_idx` (`business_id`),
  KEY `pos_customers_code_idx` (`customer_code`),
  KEY `pos_customers_mobile_idx` (`mobile`),
  KEY `pos_customers_type_idx` (`customer_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_customer_ledgers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `customer_id` BIGINT UNSIGNED NOT NULL,
  `sale_id` BIGINT UNSIGNED NULL,
  `transaction_date` DATETIME NULL,
  `entry_type` VARCHAR(60) NOT NULL,
  `debit` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `credit` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `balance` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `reference_no` VARCHAR(191) NULL,
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `pos_customer_ledgers_business_idx` (`business_id`),
  KEY `pos_customer_ledgers_customer_idx` (`customer_id`),
  KEY `pos_customer_ledgers_sale_idx` (`sale_id`),
  KEY `pos_customer_ledgers_date_idx` (`transaction_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ensure existing POS sales/cart tables can link POS standalone customers.
ALTER TABLE `pos_carts` ADD COLUMN IF NOT EXISTS `customer_id` BIGINT UNSIGNED NULL AFTER `session_id`;
ALTER TABLE `pos_carts` ADD COLUMN IF NOT EXISTS `customer_name` VARCHAR(191) NULL AFTER `customer_id`;
ALTER TABLE `pos_sales` ADD COLUMN IF NOT EXISTS `customer_id` BIGINT UNSIGNED NULL AFTER `invoice_no`;
ALTER TABLE `pos_sales` ADD COLUMN IF NOT EXISTS `customer_name` VARCHAR(191) NULL AFTER `customer_id`;
ALTER TABLE `pos_payments` ADD COLUMN IF NOT EXISTS `customer_id` BIGINT UNSIGNED NULL AFTER `sale_id`;
