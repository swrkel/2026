-- ProductsNew_STAGE007_PRODUCTSNEW_007_BATCH_LOT_EXPIRY.sql
-- Run on every tenant database. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `products_new_batches` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `variation_id` INT UNSIGNED NULL,
  `location_id` INT UNSIGNED NOT NULL,
  `batch_no` VARCHAR(191) NOT NULL,
  `lot_no` VARCHAR(191) NULL,
  `supplier_batch_no` VARCHAR(191) NULL,
  `manufactured_at` DATE NULL,
  `expiry_at` DATE NULL,
  `opening_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `current_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `reserved_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `available_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `cost_price` DECIMAL(22,4) NULL,
  `selling_price` DECIMAL(22,4) NULL,
  `note` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED NULL,
  `updated_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_new_batches_unique` (`business_id`,`product_id`,`location_id`,`batch_no`,`lot_no`),
  KEY `products_new_batches_expiry_idx` (`business_id`,`expiry_at`),
  KEY `products_new_batches_product_idx` (`business_id`,`product_id`,`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products_new_batch_movements` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `variation_id` INT UNSIGNED NULL,
  `location_id` INT UNSIGNED NOT NULL,
  `batch_id` BIGINT UNSIGNED NOT NULL,
  `movement_type` VARCHAR(50) NOT NULL,
  `transaction_date` DATETIME NOT NULL,
  `qty_in` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `qty_out` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `balance_after` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `reference_type` VARCHAR(100) NULL,
  `reference_id` BIGINT UNSIGNED NULL,
  `note` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `products_new_batch_movements_batch_idx` (`batch_id`,`transaction_date`),
  KEY `products_new_batch_movements_product_idx` (`business_id`,`product_id`,`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products_new_expiry_alerts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `variation_id` INT UNSIGNED NULL,
  `location_id` INT UNSIGNED NOT NULL,
  `batch_id` BIGINT UNSIGNED NOT NULL,
  `batch_no` VARCHAR(191) NOT NULL,
  `expiry_at` DATE NOT NULL,
  `alert_type` VARCHAR(50) NOT NULL,
  `alert_date` DATE NOT NULL,
  `severity` VARCHAR(50) NOT NULL DEFAULT 'warning',
  `is_resolved` TINYINT(1) NOT NULL DEFAULT 0,
  `resolved_at` DATETIME NULL,
  `resolved_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_new_expiry_alerts_unique` (`business_id`,`batch_id`,`alert_type`),
  KEY `products_new_expiry_alerts_due_idx` (`business_id`,`expiry_at`,`is_resolved`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products_new_recalls` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `batch_id` BIGINT UNSIGNED NULL,
  `recall_no` VARCHAR(191) NOT NULL,
  `reason` TEXT NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'open',
  `started_at` DATETIME NULL,
  `closed_at` DATETIME NULL,
  `created_by` INT UNSIGNED NULL,
  `closed_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_new_recalls_unique` (`business_id`,`recall_no`),
  KEY `products_new_recalls_status_idx` (`business_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
('products_new.batch.view','web',NOW(),NOW()),
('products_new.batch.create','web',NOW(),NOW()),
('products_new.batch.adjust','web',NOW(),NOW()),
('products_new.expiry.view','web',NOW(),NOW()),
('products_new.expiry.resolve','web',NOW(),NOW()),
('products_new.recall.view','web',NOW(),NOW()),
('products_new.recall.create','web',NOW(),NOW()),
('products_new.recall.close','web',NOW(),NOW()),
('products_new.reports.batch','web',NOW(),NOW());
