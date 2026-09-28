-- Products New Stage 004: Stock & Price Center
-- Run inside each tenant database. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `products_new_inventory_movements` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `variation_id` INT UNSIGNED NULL,
  `location_id` INT UNSIGNED NULL,
  `movement_type` VARCHAR(50) NOT NULL,
  `movement_date` DATETIME NOT NULL,
  `qty` DECIMAL(22,3) NOT NULL DEFAULT 0.000,
  `unit_cost` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `total_cost` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `reference_no` VARCHAR(100) NULL,
  `notes` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pn_im_business_idx` (`business_id`),
  KEY `pn_im_product_idx` (`product_id`),
  KEY `pn_im_variation_location_idx` (`variation_id`,`location_id`),
  KEY `pn_im_type_date_idx` (`movement_type`,`movement_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products_new_opening_stock_sessions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `reference_no` VARCHAR(100) NOT NULL,
  `session_date` DATE NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'draft',
  `notes` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `posted_by` INT UNSIGNED NULL,
  `posted_at` DATETIME NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pn_os_business_idx` (`business_id`),
  KEY `pn_os_location_idx` (`location_id`),
  UNIQUE KEY `pn_os_reference_unique` (`business_id`,`reference_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products_new_price_tiers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `variation_id` INT UNSIGNED NULL,
  `location_id` INT UNSIGNED NULL,
  `price_type` VARCHAR(50) NOT NULL,
  `price` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `currency` VARCHAR(10) NULL,
  `starts_at` DATETIME NULL,
  `ends_at` DATETIME NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pn_pt_business_idx` (`business_id`),
  KEY `pn_pt_product_idx` (`product_id`),
  KEY `pn_pt_price_type_idx` (`price_type`),
  KEY `pn_pt_location_idx` (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products_new_barcode_queue` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `variation_id` INT UNSIGNED NULL,
  `location_id` INT UNSIGNED NULL,
  `template_code` VARCHAR(100) NULL,
  `qty` INT NOT NULL DEFAULT 1,
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pn_bq_business_idx` (`business_id`),
  KEY `pn_bq_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
('products_new.inventory.view', 'web', NOW(), NOW()),
('products_new.inventory.create', 'web', NOW(), NOW()),
('products_new.opening_stock.view', 'web', NOW(), NOW()),
('products_new.opening_stock.create', 'web', NOW(), NOW()),
('products_new.price_center.view', 'web', NOW(), NOW()),
('products_new.price_center.create', 'web', NOW(), NOW());
