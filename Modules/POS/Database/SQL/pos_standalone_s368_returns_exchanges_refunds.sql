-- POS Standalone S368 - Returns, Refunds & Exchanges
-- Run this against each tenant database. No hardcoded database name is used.

CREATE TABLE IF NOT EXISTS `pos_sale_exchanges` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `sale_id` BIGINT UNSIGNED NOT NULL,
  `exchange_no` VARCHAR(100) NOT NULL,
  `exchange_date` DATETIME NULL,
  `return_total` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `new_total` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `difference_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `payment_method` VARCHAR(50) NULL,
  `note` TEXT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'final',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pos_sale_exchanges_exchange_no_unique` (`exchange_no`),
  KEY `pos_sale_exchanges_sale_id_index` (`sale_id`),
  KEY `pos_sale_exchanges_business_date_index` (`business_id`, `exchange_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_sale_exchange_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `exchange_id` BIGINT UNSIGNED NOT NULL,
  `line_type` VARCHAR(20) NOT NULL DEFAULT 'return',
  `sale_line_id` BIGINT UNSIGNED NULL,
  `product_id` BIGINT UNSIGNED NULL,
  `product_name` VARCHAR(255) NULL,
  `quantity` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `unit_price` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `line_total` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `pos_sale_exchange_lines_exchange_id_index` (`exchange_id`),
  KEY `pos_sale_exchange_lines_product_id_index` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `pos_sale_returns`
  ADD COLUMN IF NOT EXISTS `approval_status` VARCHAR(30) NOT NULL DEFAULT 'approved' AFTER `status`,
  ADD COLUMN IF NOT EXISTS `approval_note` TEXT NULL AFTER `approval_status`,
  ADD COLUMN IF NOT EXISTS `approved_by` BIGINT UNSIGNED NULL AFTER `approval_note`,
  ADD COLUMN IF NOT EXISTS `approved_at` DATETIME NULL AFTER `approved_by`;

ALTER TABLE `pos_sale_returns`
  ADD INDEX IF NOT EXISTS `pos_sale_returns_approval_status_index` (`approval_status`),
  ADD INDEX IF NOT EXISTS `pos_sale_returns_business_date_index` (`business_id`, `return_date`);

-- Compatibility note:
-- If your MySQL/MariaDB version does not support ADD COLUMN IF NOT EXISTS or ADD INDEX IF NOT EXISTS,
-- check the column/index first and run the ALTER statements manually only when missing.
