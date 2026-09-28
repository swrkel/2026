-- Distribution optimized raw SQL
-- Direct import script for MySQL 8+
-- Tailored to this project table names:
-- business, routes, users, distribution_sales_orders, distribution_sales_order_lines, distribution_route_user_maps

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- ------------------------------------------------------------
-- A) distribution_sales_order_lines
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `distribution_sales_order_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sales_order_id` BIGINT UNSIGNED NOT NULL,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `variation_id` BIGINT UNSIGNED NULL,
  `unit_id` BIGINT UNSIGNED NULL,
  `qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `quantity` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `unit_price` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `discount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `discount_type` VARCHAR(20) NOT NULL DEFAULT 'fixed',
  `final_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `line_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `is_free` TINYINT(1) NOT NULL DEFAULT 0,
  `is_free_bottles` TINYINT(1) NOT NULL DEFAULT 0,
  `is_free_auto` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `distribution_sales_order_lines_sales_order_id_index` (`sales_order_id`),
  KEY `distribution_sales_order_lines_product_id_index` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add any missing columns safely
ALTER TABLE `distribution_sales_order_lines`
  ADD COLUMN IF NOT EXISTS `variation_id` BIGINT UNSIGNED NULL AFTER `product_id`,
  ADD COLUMN IF NOT EXISTS `unit_id` BIGINT UNSIGNED NULL AFTER `variation_id`,
  ADD COLUMN IF NOT EXISTS `qty` DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `unit_id`,
  ADD COLUMN IF NOT EXISTS `quantity` DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `qty`,
  ADD COLUMN IF NOT EXISTS `unit_price` DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `quantity`,
  ADD COLUMN IF NOT EXISTS `amount` DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `unit_price`,
  ADD COLUMN IF NOT EXISTS `discount` DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `amount`,
  ADD COLUMN IF NOT EXISTS `discount_type` VARCHAR(20) NOT NULL DEFAULT 'fixed' AFTER `discount`,
  ADD COLUMN IF NOT EXISTS `final_amount` DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `discount_type`,
  ADD COLUMN IF NOT EXISTS `line_total` DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `final_amount`,
  ADD COLUMN IF NOT EXISTS `is_free` TINYINT(1) NOT NULL DEFAULT 0 AFTER `line_total`,
  ADD COLUMN IF NOT EXISTS `is_free_bottles` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_free`,
  ADD COLUMN IF NOT EXISTS `is_free_auto` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_free_bottles`;

-- Add missing indexes safely (information_schema checks)
SET @db_name = DATABASE();

SET @idx_exists = (
  SELECT COUNT(1) FROM information_schema.statistics
  WHERE table_schema = @db_name
    AND table_name = 'distribution_sales_order_lines'
    AND index_name = 'distribution_sales_order_lines_sales_order_id_index'
);
SET @sql = IF(@idx_exists = 0,
  'CREATE INDEX `distribution_sales_order_lines_sales_order_id_index` ON `distribution_sales_order_lines` (`sales_order_id`)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (
  SELECT COUNT(1) FROM information_schema.statistics
  WHERE table_schema = @db_name
    AND table_name = 'distribution_sales_order_lines'
    AND index_name = 'distribution_sales_order_lines_product_id_index'
);
SET @sql = IF(@idx_exists = 0,
  'CREATE INDEX `distribution_sales_order_lines_product_id_index` ON `distribution_sales_order_lines` (`product_id`)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (
  SELECT COUNT(1) FROM information_schema.statistics
  WHERE table_schema = @db_name
    AND table_name = 'distribution_sales_order_lines'
    AND index_name = 'distribution_sales_order_lines_variation_id_index'
);
SET @sql = IF(@idx_exists = 0,
  'CREATE INDEX `distribution_sales_order_lines_variation_id_index` ON `distribution_sales_order_lines` (`variation_id`)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (
  SELECT COUNT(1) FROM information_schema.statistics
  WHERE table_schema = @db_name
    AND table_name = 'distribution_sales_order_lines'
    AND index_name = 'idx_dsol_sales_order_product'
);
SET @sql = IF(@idx_exists = 0,
  'CREATE INDEX `idx_dsol_sales_order_product` ON `distribution_sales_order_lines` (`sales_order_id`, `product_id`)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (
  SELECT COUNT(1) FROM information_schema.statistics
  WHERE table_schema = @db_name
    AND table_name = 'distribution_sales_order_lines'
    AND index_name = 'idx_dsol_sales_order_created'
);
SET @sql = IF(@idx_exists = 0,
  'CREATE INDEX `idx_dsol_sales_order_created` ON `distribution_sales_order_lines` (`sales_order_id`, `created_at`)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- B) distribution_route_user_maps
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `distribution_route_user_maps` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `route_id` BIGINT UNSIGNED NOT NULL,
  `sales_rep_id` BIGINT UNSIGNED NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `last_status_from` VARCHAR(20) NULL,
  `last_status_to` VARCHAR(20) NULL,
  `status_changed_at` TIMESTAMP NULL,
  `added_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `distribution_route_user_map_unique` (`business_id`, `route_id`, `sales_rep_id`),
  KEY `distribution_route_user_maps_business_id_index` (`business_id`),
  KEY `distribution_route_user_maps_route_id_index` (`route_id`),
  KEY `distribution_route_user_maps_sales_rep_id_index` (`sales_rep_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `distribution_route_user_maps`
  ADD COLUMN IF NOT EXISTS `last_status_from` VARCHAR(20) NULL AFTER `status`,
  ADD COLUMN IF NOT EXISTS `last_status_to` VARCHAR(20) NULL AFTER `last_status_from`,
  ADD COLUMN IF NOT EXISTS `status_changed_at` TIMESTAMP NULL AFTER `last_status_to`;

SET @idx_exists = (
  SELECT COUNT(1) FROM information_schema.statistics
  WHERE table_schema = @db_name
    AND table_name = 'distribution_route_user_maps'
    AND index_name = 'distribution_route_user_maps_business_id_index'
);
SET @sql = IF(@idx_exists = 0,
  'CREATE INDEX `distribution_route_user_maps_business_id_index` ON `distribution_route_user_maps` (`business_id`)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (
  SELECT COUNT(1) FROM information_schema.statistics
  WHERE table_schema = @db_name
    AND table_name = 'distribution_route_user_maps'
    AND index_name = 'distribution_route_user_maps_route_id_index'
);
SET @sql = IF(@idx_exists = 0,
  'CREATE INDEX `distribution_route_user_maps_route_id_index` ON `distribution_route_user_maps` (`route_id`)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (
  SELECT COUNT(1) FROM information_schema.statistics
  WHERE table_schema = @db_name
    AND table_name = 'distribution_route_user_maps'
    AND index_name = 'distribution_route_user_maps_sales_rep_id_index'
);
SET @sql = IF(@idx_exists = 0,
  'CREATE INDEX `distribution_route_user_maps_sales_rep_id_index` ON `distribution_route_user_maps` (`sales_rep_id`)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (
  SELECT COUNT(1) FROM information_schema.statistics
  WHERE table_schema = @db_name
    AND table_name = 'distribution_route_user_maps'
    AND index_name = 'idx_route_user_maps_status_changed_at'
);
SET @sql = IF(@idx_exists = 0,
  'CREATE INDEX `idx_route_user_maps_status_changed_at` ON `distribution_route_user_maps` (`status_changed_at`)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (
  SELECT COUNT(1) FROM information_schema.statistics
  WHERE table_schema = @db_name
    AND table_name = 'distribution_route_user_maps'
    AND index_name = 'idx_route_user_maps_status_time'
);
SET @sql = IF(@idx_exists = 0,
  'CREATE INDEX `idx_route_user_maps_status_time` ON `distribution_route_user_maps` (`status`, `status_changed_at`)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (
  SELECT COUNT(1) FROM information_schema.statistics
  WHERE table_schema = @db_name
    AND table_name = 'distribution_route_user_maps'
    AND index_name = 'idx_route_user_maps_status_route'
);
SET @sql = IF(@idx_exists = 0,
  'CREATE INDEX `idx_route_user_maps_status_route` ON `distribution_route_user_maps` (`status`, `status_changed_at`, `route_id`)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (
  SELECT COUNT(1) FROM information_schema.statistics
  WHERE table_schema = @db_name
    AND table_name = 'distribution_route_user_maps'
    AND index_name = 'idx_route_user_maps_status_sales_rep'
);
SET @sql = IF(@idx_exists = 0,
  'CREATE INDEX `idx_route_user_maps_status_sales_rep` ON `distribution_route_user_maps` (`status`, `status_changed_at`, `sales_rep_id`)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Optional: strict FK constraints are intentionally excluded here
-- because legacy parent PK types in this project are often INT UNSIGNED
-- while these distribution tables use BIGINT UNSIGNED.
-- Add FKs later only after full type alignment.

