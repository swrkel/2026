-- RestaurantNew Stage 003 CREATE SQL
-- Run inside each tenant database that will use RestaurantNew.

CREATE TABLE IF NOT EXISTS `rn_menu_categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `parent_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NOT NULL,
  `code` VARCHAR(50) NULL,
  `description` TEXT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `available_for_dine_in` TINYINT(1) NOT NULL DEFAULT 1,
  `available_for_takeaway` TINYINT(1) NOT NULL DEFAULT 1,
  `available_for_delivery` TINYINT(1) NOT NULL DEFAULT 1,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `rn_menu_categories_business_location_index` (`business_id`, `location_id`),
  KEY `rn_menu_categories_business_parent_index` (`business_id`, `parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rn_menu_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `menu_category_id` BIGINT UNSIGNED NOT NULL,
  `kitchen_section_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NOT NULL,
  `sku` VARCHAR(80) NULL,
  `description` TEXT NULL,
  `price` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `cost_price` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `tax_percent` DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
  `preparation_time_minutes` INT NOT NULL DEFAULT 0,
  `image_path` VARCHAR(255) NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_modifier_required` TINYINT(1) NOT NULL DEFAULT 0,
  `allow_discount` TINYINT(1) NOT NULL DEFAULT 1,
  `track_recipe_stock` TINYINT(1) NOT NULL DEFAULT 0,
  `available_for_dine_in` TINYINT(1) NOT NULL DEFAULT 1,
  `available_for_takeaway` TINYINT(1) NOT NULL DEFAULT 1,
  `available_for_delivery` TINYINT(1) NOT NULL DEFAULT 1,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `rn_menu_items_business_location_index` (`business_id`, `location_id`),
  KEY `rn_menu_items_business_category_index` (`business_id`, `menu_category_id`),
  KEY `rn_menu_items_business_sku_index` (`business_id`, `sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rn_menu_item_variants` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `menu_item_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(191) NOT NULL,
  `sku` VARCHAR(80) NULL,
  `price` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `cost_price` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `rn_menu_item_variants_business_item_index` (`business_id`, `menu_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rn_menu_modifiers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `menu_item_id` BIGINT UNSIGNED NOT NULL,
  `group_name` VARCHAR(191) NULL,
  `name` VARCHAR(191) NOT NULL,
  `price` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `rn_menu_modifiers_business_item_index` (`business_id`, `menu_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rn_menu_recipes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `menu_item_id` BIGINT UNSIGNED NOT NULL,
  `ingredient_name` VARCHAR(191) NOT NULL,
  `ingredient_sku` VARCHAR(80) NULL,
  `unit` VARCHAR(50) NULL,
  `quantity` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `wastage_percent` DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `rn_menu_recipes_business_item_index` (`business_id`, `menu_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
