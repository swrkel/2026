-- ============================================================================
-- Products New Reports Centre - Required / Supporting Tables
-- Date: 27 July 2026
-- Run this file separately inside EACH tenant database.
-- No database name is hardcoded.
-- All CREATE and INSERT operations are idempotent.
-- ============================================================================

SET NAMES utf8mb4;
SET @pn_database_name = DATABASE();

-- --------------------------------------------------------------------------
-- 1. Product metadata used by Product Master and common report datasets
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `products_new_product_meta` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `business_id` INT UNSIGNED NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `primary_image` VARCHAR(191) NULL,
    `gallery` JSON NULL,
    `attachments` JSON NULL,
    `health_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `health_payload` JSON NULL,
    `settings` JSON NULL,
    `created_by` INT UNSIGNED NULL,
    `updated_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `products_new_meta_product_unique` (`product_id`),
    KEY `products_new_meta_business_idx` (`business_id`),
    KEY `products_new_meta_health_idx` (`health_score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add missing compatibility columns when an older product_meta table exists.
SET @pn_sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @pn_database_name AND table_name = 'products_new_product_meta'
    )
    AND NOT EXISTS(
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @pn_database_name
          AND table_name = 'products_new_product_meta'
          AND column_name = 'primary_image'
    ),
    'ALTER TABLE `products_new_product_meta` ADD COLUMN `primary_image` VARCHAR(191) NULL AFTER `product_id`',
    'SELECT 1'
);
PREPARE pn_stmt FROM @pn_sql;
EXECUTE pn_stmt;
DEALLOCATE PREPARE pn_stmt;

SET @pn_sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @pn_database_name AND table_name = 'products_new_product_meta'
    )
    AND NOT EXISTS(
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @pn_database_name
          AND table_name = 'products_new_product_meta'
          AND column_name = 'health_score'
    ),
    'ALTER TABLE `products_new_product_meta` ADD COLUMN `health_score` TINYINT UNSIGNED NOT NULL DEFAULT 0',
    'SELECT 1'
);
PREPARE pn_stmt FROM @pn_sql;
EXECUTE pn_stmt;
DEALLOCATE PREPARE pn_stmt;

-- --------------------------------------------------------------------------
-- 2. Inventory movements used by low-stock and movement reports
-- --------------------------------------------------------------------------
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
    KEY `pn_im_variation_location_idx` (`variation_id`, `location_id`),
    KEY `pn_im_type_date_idx` (`movement_type`, `movement_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 3. Product price history used by Price Change and Price History reports
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `products_new_price_history` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `business_id` INT UNSIGNED NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `variation_id` INT UNSIGNED NULL,
    `price_type` VARCHAR(80) NOT NULL DEFAULT 'selling',
    `old_price` DECIMAL(22,4) NULL,
    `new_price` DECIMAL(22,4) NULL,
    `currency` VARCHAR(10) NULL,
    `effective_from` DATE NULL,
    `created_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `products_new_price_product_idx` (`product_id`),
    KEY `products_new_price_business_idx` (`business_id`),
    KEY `products_new_price_effective_idx` (`effective_from`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 4. Batch / Lot and expiry support
-- --------------------------------------------------------------------------
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
    `opening_qty` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    `current_qty` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    `reserved_qty` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    `available_qty` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    `cost_price` DECIMAL(22,4) NULL,
    `selling_price` DECIMAL(22,4) NULL,
    `note` TEXT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` INT UNSIGNED NULL,
    `updated_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `products_new_batches_unique` (`business_id`, `product_id`, `location_id`, `batch_no`, `lot_no`),
    KEY `products_new_batches_expiry_idx` (`business_id`, `expiry_at`),
    KEY `products_new_batches_product_idx` (`business_id`, `product_id`, `location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Existing older tenant tables may use expiry_date. Add expiry_at only if absent.
SET @pn_sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @pn_database_name AND table_name = 'products_new_batches'
    )
    AND NOT EXISTS(
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @pn_database_name
          AND table_name = 'products_new_batches'
          AND column_name = 'expiry_at'
    ),
    'ALTER TABLE `products_new_batches` ADD COLUMN `expiry_at` DATE NULL AFTER `manufactured_at`',
    'SELECT 1'
);
PREPARE pn_stmt FROM @pn_sql;
EXECUTE pn_stmt;
DEALLOCATE PREPARE pn_stmt;

-- Copy older expiry_date values into expiry_at when both columns exist.
SET @pn_sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @pn_database_name
          AND table_name = 'products_new_batches'
          AND column_name = 'expiry_at'
    )
    AND EXISTS(
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @pn_database_name
          AND table_name = 'products_new_batches'
          AND column_name = 'expiry_date'
    ),
    'UPDATE `products_new_batches` SET `expiry_at` = `expiry_date` WHERE `expiry_at` IS NULL AND `expiry_date` IS NOT NULL',
    'SELECT 1'
);
PREPARE pn_stmt FROM @pn_sql;
EXECUTE pn_stmt;
DEALLOCATE PREPARE pn_stmt;

CREATE TABLE IF NOT EXISTS `products_new_batch_movements` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `business_id` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `variation_id` INT UNSIGNED NULL,
    `location_id` INT UNSIGNED NOT NULL,
    `batch_id` BIGINT UNSIGNED NOT NULL,
    `movement_type` VARCHAR(50) NOT NULL,
    `transaction_date` DATETIME NOT NULL,
    `qty_in` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    `qty_out` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    `balance_after` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    `reference_type` VARCHAR(100) NULL,
    `reference_id` BIGINT UNSIGNED NULL,
    `note` TEXT NULL,
    `created_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `products_new_batch_movements_batch_idx` (`batch_id`, `transaction_date`),
    KEY `products_new_batch_movements_product_idx` (`business_id`, `product_id`, `location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 5. Serial-number support
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `products_new_serial_numbers` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `business_id` INT UNSIGNED NOT NULL,
    `product_id` BIGINT UNSIGNED NOT NULL,
    `variation_id` BIGINT UNSIGNED NULL,
    `location_id` INT UNSIGNED NULL,
    `batch_id` BIGINT UNSIGNED NULL,
    `serial_no` VARCHAR(191) NOT NULL,
    `imei_no` VARCHAR(191) NULL,
    `asset_tag` VARCHAR(191) NULL,
    `purchase_reference` VARCHAR(191) NULL,
    `purchase_date` DATE NULL,
    `cost_price` DECIMAL(22,4) NULL,
    `selling_price` DECIMAL(22,4) NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'available',
    `note` TEXT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` INT UNSIGNED NULL,
    `updated_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `products_new_serial_unique` (`business_id`, `serial_no`),
    KEY `products_new_serial_product_idx` (`business_id`, `product_id`),
    KEY `products_new_serial_location_idx` (`business_id`, `location_id`),
    KEY `products_new_serial_status_idx` (`business_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 6. Reports Centre presets and snapshots
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `products_new_report_presets` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `business_id` BIGINT UNSIGNED NOT NULL,
    `location_id` BIGINT UNSIGNED NULL,
    `user_id` BIGINT UNSIGNED NULL,
    `report_key` VARCHAR(120) NOT NULL,
    `name` VARCHAR(191) NOT NULL,
    `filters` LONGTEXT NULL,
    `is_default` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `products_new_report_presets_business_report_idx` (`business_id`, `report_key`),
    KEY `products_new_report_presets_location_idx` (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products_new_report_snapshots` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `business_id` BIGINT UNSIGNED NOT NULL,
    `location_id` BIGINT UNSIGNED NULL,
    `report_key` VARCHAR(120) NOT NULL,
    `snapshot_date` DATE NOT NULL,
    `metric_key` VARCHAR(120) NOT NULL,
    `metric_value` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    `extra_data` LONGTEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `products_new_report_snapshots_business_report_idx` (`business_id`, `report_key`, `snapshot_date`),
    KEY `products_new_report_snapshots_metric_idx` (`metric_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- 7. Report permissions - duplicate-safe
-- --------------------------------------------------------------------------
INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'products_new.reports.index', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'products_new.reports.index' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'products_new.reports.product_master', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'products_new.reports.product_master' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'products_new.reports.stock_valuation', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'products_new.reports.stock_valuation' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'products_new.reports.low_stock', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'products_new.reports.low_stock' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'products_new.reports.price_changes', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'products_new.reports.price_changes' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'products_new.reports.batch', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'products_new.reports.batch' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'products_new.reports.movement', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'products_new.reports.movement' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'products_new.reports.profitability', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'products_new.reports.profitability' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'products_new.reports.aging', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'products_new.reports.aging' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'products_new.reports.fast_slow_dead', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'products_new.reports.fast_slow_dead' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'products_new.reports.negative_overstock', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'products_new.reports.negative_overstock' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'products_new.reports.expiry', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'products_new.reports.expiry' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'products_new.reports.serial', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'products_new.reports.serial' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'products_new.reports.category_brand', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'products_new.reports.category_brand' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'products_new.reports.price_history', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'products_new.reports.price_history' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'products_new.reports.inventory_turnover', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'products_new.reports.inventory_turnover' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'products_new.reports.abc_xyz', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'products_new.reports.abc_xyz' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'products_new.reports.reorder_recommendation', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'products_new.reports.reorder_recommendation' AND `guard_name` = 'web');

-- End of file.
