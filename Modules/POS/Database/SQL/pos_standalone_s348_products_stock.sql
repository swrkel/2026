-- POS Standalone S348 - Products & Stock
-- Run this inside each tenant database. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `pos_categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `short_name` VARCHAR(50) NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`), KEY `pos_categories_deleted_at_index` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_brands` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `short_name` VARCHAR(50) NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`), KEY `pos_brands_deleted_at_index` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_units` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `short_name` VARCHAR(50) NULL,
  `allow_decimal` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`), KEY `pos_units_deleted_at_index` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_products` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `sku` VARCHAR(100) NULL,
  `barcode` VARCHAR(100) NULL,
  `category_id` BIGINT UNSIGNED NULL,
  `brand_id` BIGINT UNSIGNED NULL,
  `unit_id` BIGINT UNSIGNED NULL,
  `cost_price` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `selling_price` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `tax_rate` DECIMAL(8,4) NOT NULL DEFAULT 0.0000,
  `current_stock` DECIMAL(22,3) NOT NULL DEFAULT 0.000,
  `alert_quantity` DECIMAL(22,3) NOT NULL DEFAULT 0.000,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `description` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pos_products_sku_unique` (`sku`),
  KEY `pos_products_barcode_index` (`barcode`),
  KEY `pos_products_category_index` (`category_id`),
  KEY `pos_products_brand_index` (`brand_id`),
  KEY `pos_products_unit_index` (`unit_id`),
  KEY `pos_products_deleted_at_index` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_stock_movements` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `movement_type` VARCHAR(50) NOT NULL,
  `reference_type` VARCHAR(100) NULL,
  `reference_id` BIGINT UNSIGNED NULL,
  `quantity` DECIMAL(22,3) NOT NULL DEFAULT 0.000,
  `stock_before` DECIMAL(22,3) NOT NULL DEFAULT 0.000,
  `stock_after` DECIMAL(22,3) NOT NULL DEFAULT 0.000,
  `unit_cost` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_stock_movements_product_index` (`product_id`),
  KEY `pos_stock_movements_type_index` (`movement_type`),
  KEY `pos_stock_movements_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `pos_units` (`name`, `short_name`, `allow_decimal`, `created_at`, `updated_at`)
SELECT 'Pieces', 'Pcs', 0, NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM `pos_units` WHERE `short_name` = 'Pcs');
INSERT INTO `pos_units` (`name`, `short_name`, `allow_decimal`, `created_at`, `updated_at`)
SELECT 'Kilogram', 'Kg', 1, NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM `pos_units` WHERE `short_name` = 'Kg');
INSERT INTO `pos_categories` (`name`, `short_name`, `created_at`, `updated_at`)
SELECT 'General', 'GEN', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM `pos_categories` WHERE `name` = 'General');
