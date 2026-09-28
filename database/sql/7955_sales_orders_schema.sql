-- 7955 Sales Orders: schema changes
-- MySQL 8+ compatible

START TRANSACTION;

-- 1) Extend distribution prefix numbering type
ALTER TABLE `distribution_prefix_settings`
MODIFY COLUMN `numbering_type` ENUM('sales_invoice', 'sales_order', 'daily_summary_sheet', 'loading_sheet') NOT NULL;

-- 2) Add invoice fields needed for Sales Orders/Invoice enhancements
ALTER TABLE `distribution_invoices`
ADD COLUMN `delivery_date` DATE NULL AFTER `date`,
ADD COLUMN `invoice_note` TEXT NULL AFTER `loading_sheet_no`,
ADD COLUMN `shipping_note` TEXT NULL AFTER `invoice_note`,
ADD COLUMN `shipping_details` TEXT NULL AFTER `shipping_note`,
ADD COLUMN `shipping_status` ENUM('ordered', 'packed', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'ordered' AFTER `shipping_details`,
ADD COLUMN `status` VARCHAR(30) NOT NULL DEFAULT 'active' AFTER `shipping_status`,
ADD COLUMN `sales_order_id` BIGINT UNSIGNED NULL AFTER `status`,
ADD COLUMN `added_by` BIGINT UNSIGNED NULL AFTER `sales_order_id`,
ADD COLUMN `updated_by` BIGINT UNSIGNED NULL AFTER `added_by`;

-- 3) Create sales order header table
CREATE TABLE IF NOT EXISTS `distribution_sales_orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `customer_id` BIGINT UNSIGNED NOT NULL,
  `customer_name` VARCHAR(255) NULL,
  `customer_contact` VARCHAR(255) NULL,
  `customer_address` TEXT NULL,
  `date` DATETIME NOT NULL,
  `delivery_date` DATE NULL,
  `sales_rep_id` BIGINT UNSIGNED NULL,
  `route_id` BIGINT UNSIGNED NULL,
  `vehicle_id` BIGINT UNSIGNED NULL,
  `category_id` BIGINT UNSIGNED NULL,
  `sales_order_no` VARCHAR(255) NOT NULL,
  `loading_sheet_no` VARCHAR(255) NULL,
  `invoice_note` TEXT NULL,
  `shipping_note` TEXT NULL,
  `shipping_details` TEXT NULL,
  `shipping_status` ENUM('ordered', 'packed', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'ordered',
  `status` VARCHAR(30) NOT NULL DEFAULT 'active',
  `total` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `discount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `grand_total` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `distribution_sales_orders_business_id_index` (`business_id`),
  KEY `distribution_sales_orders_sales_order_no_index` (`sales_order_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4) Create sales order line table
CREATE TABLE IF NOT EXISTS `distribution_sales_order_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sales_order_id` BIGINT UNSIGNED NOT NULL,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `unit_id` BIGINT UNSIGNED NULL,
  `qty` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `unit_price` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `discount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `discount_type` VARCHAR(20) NOT NULL DEFAULT 'fixed',
  `final_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `is_free` TINYINT(1) NOT NULL DEFAULT 0,
  `is_free_bottles` TINYINT(1) NOT NULL DEFAULT 0,
  `is_free_auto` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `distribution_sales_order_lines_sales_order_id_index` (`sales_order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5) Create route-user mapping table
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
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `distribution_route_user_maps_business_id_index` (`business_id`),
  KEY `distribution_route_user_maps_route_id_index` (`route_id`),
  KEY `distribution_route_user_maps_sales_rep_id_index` (`sales_rep_id`),
  UNIQUE KEY `distribution_route_user_map_unique` (`business_id`, `route_id`, `sales_rep_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
