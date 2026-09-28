-- =====================================================================
-- HOTELMGT_008 - Hotel POS menu and order posting
-- Specific SQL for HOTELMGT_008 only.
-- Execute this on every tenant database that uses the Hotel Management module.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `hm_pos_categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NOT NULL,
  `code` VARCHAR(50) NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_pos_categories_business_idx` (`business_id`),
  KEY `hm_pos_categories_location_idx` (`business_location_id`),
  KEY `hm_pos_categories_code_idx` (`code`),
  KEY `hm_pos_categories_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_pos_menu_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `category_id` BIGINT UNSIGNED NULL,
  `item_code` VARCHAR(50) NULL,
  `name` VARCHAR(191) NOT NULL,
  `price` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `unit` VARCHAR(30) NOT NULL DEFAULT 'unit',
  `status` VARCHAR(30) NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_pos_menu_items_business_idx` (`business_id`),
  KEY `hm_pos_menu_items_location_idx` (`business_location_id`),
  KEY `hm_pos_menu_items_category_idx` (`category_id`),
  KEY `hm_pos_menu_items_code_idx` (`item_code`),
  KEY `hm_pos_menu_items_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_pos_orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `folio_id` BIGINT UNSIGNED NULL,
  `room_id` BIGINT UNSIGNED NULL,
  `order_no` VARCHAR(50) NULL,
  `order_date` DATE NULL,
  `guest_name` VARCHAR(191) NULL,
  `subtotal` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `service_charge` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `tax_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `grand_total` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `payment_mode` VARCHAR(40) NOT NULL DEFAULT 'cash',
  `status` VARCHAR(30) NOT NULL DEFAULT 'open',
  `note` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_pos_orders_business_idx` (`business_id`),
  KEY `hm_pos_orders_location_idx` (`business_location_id`),
  KEY `hm_pos_orders_folio_idx` (`folio_id`),
  KEY `hm_pos_orders_room_idx` (`room_id`),
  KEY `hm_pos_orders_no_idx` (`order_no`),
  KEY `hm_pos_orders_date_idx` (`order_date`),
  KEY `hm_pos_orders_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_pos_order_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `pos_order_id` BIGINT UNSIGNED NOT NULL,
  `menu_item_id` BIGINT UNSIGNED NULL,
  `description` VARCHAR(191) NOT NULL,
  `quantity` DECIMAL(22,4) NOT NULL DEFAULT 1.0000,
  `unit_price` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `line_total` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_pos_order_lines_business_idx` (`business_id`),
  KEY `hm_pos_order_lines_location_idx` (`business_location_id`),
  KEY `hm_pos_order_lines_order_idx` (`pos_order_id`),
  KEY `hm_pos_order_lines_item_idx` (`menu_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
