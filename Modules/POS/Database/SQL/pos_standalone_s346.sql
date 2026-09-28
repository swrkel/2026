CREATE TABLE IF NOT EXISTS `pos_categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `name` VARCHAR(191) NOT NULL,
  `code` VARCHAR(191) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_categories_business_id_index` (`business_id`),
  KEY `pos_categories_name_index` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_brands` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `name` VARCHAR(191) NOT NULL,
  `code` VARCHAR(191) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_brands_business_id_index` (`business_id`),
  KEY `pos_brands_name_index` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_products` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `category_id` BIGINT UNSIGNED NULL,
  `brand_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NOT NULL,
  `sku` VARCHAR(191) NULL,
  `barcode` VARCHAR(191) NULL,
  `sell_price` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `stock_qty` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `enable_stock` TINYINT(1) NOT NULL DEFAULT 1,
  `image` VARCHAR(191) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_products_business_id_index` (`business_id`),
  KEY `pos_products_category_id_index` (`category_id`),
  KEY `pos_products_brand_id_index` (`brand_id`),
  KEY `pos_products_sku_index` (`sku`),
  KEY `pos_products_barcode_index` (`barcode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_customers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `name` VARCHAR(191) NOT NULL,
  `mobile` VARCHAR(191) NULL,
  `customer_code` VARCHAR(191) NULL,
  `email` VARCHAR(191) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_customers_business_id_index` (`business_id`),
  KEY `pos_customers_mobile_index` (`mobile`),
  KEY `pos_customers_customer_code_index` (`customer_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
