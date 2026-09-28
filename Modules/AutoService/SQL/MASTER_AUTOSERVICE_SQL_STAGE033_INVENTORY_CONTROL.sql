-- Master SQL - AutoService Stage 033

-- AutoService Stage 033 - Inventory Control, Reorder Alerts and Parts Profitability
-- Run this SQL on each tenant database. It is idempotent as far as MySQL allows.

CREATE TABLE IF NOT EXISTS `auto_service_parts_stock` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `product_id` BIGINT UNSIGNED NULL,
  `part_name` VARCHAR(255) NOT NULL,
  `sku` VARCHAR(100) NULL,
  `qty_available` DECIMAL(22,3) NOT NULL DEFAULT 0.000,
  `qty_reserved` DECIMAL(22,3) NOT NULL DEFAULT 0.000,
  `reorder_level` DECIMAL(22,3) NOT NULL DEFAULT 0.000,
  `last_purchase_price` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `selling_price` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `preferred_supplier_id` BIGINT UNSIGNED NULL,
  `preferred_supplier_name` VARCHAR(255) NULL,
  `notes` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `as_parts_stock_business_location_idx` (`business_id`,`location_id`),
  KEY `as_parts_stock_product_idx` (`product_id`),
  KEY `as_parts_stock_sku_idx` (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `auto_service_reorder_requests` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `part_stock_id` BIGINT UNSIGNED NULL,
  `part_name` VARCHAR(255) NOT NULL,
  `sku` VARCHAR(100) NULL,
  `current_qty` DECIMAL(22,3) NOT NULL DEFAULT 0.000,
  `reorder_level` DECIMAL(22,3) NOT NULL DEFAULT 0.000,
  `required_qty` DECIMAL(22,3) NOT NULL DEFAULT 0.000,
  `preferred_supplier_id` BIGINT UNSIGNED NULL,
  `preferred_supplier_name` VARCHAR(255) NULL,
  `priority` VARCHAR(30) NOT NULL DEFAULT 'normal',
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `approved_by` BIGINT UNSIGNED NULL,
  `approved_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `as_reorder_business_location_idx` (`business_id`,`location_id`),
  KEY `as_reorder_status_idx` (`status`),
  KEY `as_reorder_part_stock_idx` (`part_stock_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add costing columns used by the profitability report. Ignore duplicate-column errors if already added.
ALTER TABLE `auto_service_job_lines` ADD COLUMN `cost_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000 AFTER `unit_price`;
ALTER TABLE `auto_service_job_lines` ADD COLUMN `supplier_id` BIGINT UNSIGNED NULL AFTER `product_id`;
ALTER TABLE `auto_service_job_lines` ADD COLUMN `supplier_name` VARCHAR(255) NULL AFTER `supplier_id`;

-- Optional permission seed. Adjust table name if your system stores permissions differently.
INSERT IGNORE INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
('autoservice.inventory_control.view', 'web', NOW(), NOW()),
('autoservice.inventory_control.manage', 'web', NOW(), NOW()),
('autoservice.parts_profitability.view', 'web', NOW(), NOW()),
('autoservice.reorder_requests.manage', 'web', NOW(), NOW());
