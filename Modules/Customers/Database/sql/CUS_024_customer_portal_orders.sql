-- CUS-024 Distribution Dealer Ordering Portal
-- Run this once in each tenant database before testing dealer ordering.

CREATE TABLE IF NOT EXISTS `customer_portal_orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `contact_id` INT UNSIGNED NOT NULL,
  `order_no` VARCHAR(50) NOT NULL,
  `order_date` DATE NOT NULL,
  `required_date` DATE NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'submitted',
  `remarks` TEXT NULL,
  `total_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `total_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `created_by_customer` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_portal_orders_order_no_unique` (`business_id`, `order_no`),
  KEY `customer_portal_orders_business_contact_index` (`business_id`, `contact_id`),
  KEY `customer_portal_orders_status_index` (`business_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `customer_portal_order_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `customer_portal_order_id` BIGINT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `product_name` VARCHAR(255) NULL,
  `sku` VARCHAR(191) NULL,
  `unit` VARCHAR(50) NULL,
  `quantity` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `unit_price` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `line_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `remarks` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_portal_order_lines_order_index` (`customer_portal_order_id`),
  KEY `customer_portal_order_lines_product_index` (`business_id`, `product_id`),
  CONSTRAINT `cpol_order_fk` FOREIGN KEY (`customer_portal_order_id`) REFERENCES `customer_portal_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
