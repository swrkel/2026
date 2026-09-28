-- POS Standalone S351 - Returns, Void Bills and Cash Register Hardening
-- Run after selecting the tenant database. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `pos_sale_returns` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `sale_id` BIGINT UNSIGNED NOT NULL,
  `return_no` VARCHAR(191) NOT NULL,
  `return_date` DATETIME NULL,
  `refund_method` VARCHAR(50) NOT NULL DEFAULT 'cash',
  `total_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(40) NOT NULL DEFAULT 'final',
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `pos_sale_returns_business_idx` (`business_id`),
  KEY `pos_sale_returns_sale_idx` (`sale_id`),
  KEY `pos_sale_returns_no_idx` (`return_no`),
  KEY `pos_sale_returns_date_idx` (`return_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_sale_return_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `return_id` BIGINT UNSIGNED NOT NULL,
  `sale_line_id` BIGINT UNSIGNED NULL,
  `product_id` BIGINT UNSIGNED NULL,
  `product_name` VARCHAR(191) NULL,
  `quantity` DECIMAL(22,3) NOT NULL DEFAULT 0.000,
  `unit_price` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `line_total` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `pos_sale_return_lines_return_idx` (`return_id`),
  KEY `pos_sale_return_lines_product_idx` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_cash_movements` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `register_session_id` BIGINT UNSIGNED NULL,
  `movement_type` VARCHAR(50) NOT NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `transaction_date` DATETIME NULL,
  `reference_no` VARCHAR(191) NULL,
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `pos_cash_movements_business_idx` (`business_id`),
  KEY `pos_cash_movements_session_idx` (`register_session_id`),
  KEY `pos_cash_movements_type_idx` (`movement_type`),
  KEY `pos_cash_movements_date_idx` (`transaction_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `pos_sales` ADD COLUMN IF NOT EXISTS `void_reason` TEXT NULL;
ALTER TABLE `pos_sales` ADD COLUMN IF NOT EXISTS `voided_at` DATETIME NULL;
ALTER TABLE `pos_sales` ADD COLUMN IF NOT EXISTS `voided_by` BIGINT UNSIGNED NULL;
ALTER TABLE `pos_sales` ADD COLUMN IF NOT EXISTS `status` VARCHAR(40) NOT NULL DEFAULT 'final';
ALTER TABLE `pos_cash_movements` ADD COLUMN IF NOT EXISTS `reference_no` VARCHAR(191) NULL;
ALTER TABLE `pos_cash_movements` ADD COLUMN IF NOT EXISTS `business_location_id` BIGINT UNSIGNED NULL;
