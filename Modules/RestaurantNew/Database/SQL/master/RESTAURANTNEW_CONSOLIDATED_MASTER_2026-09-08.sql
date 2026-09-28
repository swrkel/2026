-- ============================================================================
-- RestaurantNew - CONSOLIDATED MASTER SQL
-- Date: 2026-09-08
-- Consolidates supplied stages 001-030 and 041-046.
-- Target: tenant database for the RestaurantNew module.
-- IMPORTANT: Take a full database backup before import.
-- This master is intended for ONE-TIME installation on a database that has not
-- already had these RestaurantNew ALTER scripts applied.
-- Core application tables such as `permissions` are expected to already exist.
-- Host-specific module activation (`modules_statuses`) is intentionally excluded.
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;


-- ============================================================================
-- SECTION 1 - CREATE TABLES / VIEWS
-- ============================================================================


-- ---- BEGIN: create/RESTAURANTNEW_STAGE001_CREATE.sql ----
CREATE TABLE IF NOT EXISTS `rn_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `key` VARCHAR(191) NOT NULL,
  `value` LONGTEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rn_settings_scope_key_unique` (`business_id`, `location_id`, `key`),
  KEY `rn_settings_business_id_index` (`business_id`),
  KEY `rn_settings_location_id_index` (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rn_dining_areas` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `name` VARCHAR(191) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rn_dining_areas_business_id_index` (`business_id`),
  KEY `rn_dining_areas_location_id_index` (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rn_tables` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `dining_area_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NOT NULL,
  `capacity` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` VARCHAR(50) NOT NULL DEFAULT 'available',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rn_tables_business_id_index` (`business_id`),
  KEY `rn_tables_location_id_index` (`location_id`),
  KEY `rn_tables_dining_area_id_index` (`dining_area_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- ---- END: create/RESTAURANTNEW_STAGE001_CREATE.sql ----

-- ---- BEGIN: create/RESTAURANTNEW_STAGE002_CREATE.sql ----
CREATE TABLE IF NOT EXISTS `rn_kitchen_sections` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `name` VARCHAR(191) NOT NULL,
  `code` VARCHAR(191) NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `print_kot` TINYINT(1) NOT NULL DEFAULT 1,
  `show_on_kds` TINYINT(1) NOT NULL DEFAULT 1,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rn_kitchen_sections_business_id_index` (`business_id`),
  KEY `rn_kitchen_sections_location_id_index` (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rn_order_types` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `name` VARCHAR(191) NOT NULL,
  `slug` VARCHAR(191) NOT NULL,
  `requires_table` TINYINT(1) NOT NULL DEFAULT 0,
  `requires_customer` TINYINT(1) NOT NULL DEFAULT 0,
  `allow_delivery` TINYINT(1) NOT NULL DEFAULT 0,
  `default_service_charge_percent` DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
  `default_delivery_charge` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rn_order_types_business_id_index` (`business_id`),
  KEY `rn_order_types_location_id_index` (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rn_numbering_sequences` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `document_type` VARCHAR(191) NOT NULL,
  `prefix` VARCHAR(191) NULL,
  `next_number` INT UNSIGNED NOT NULL DEFAULT 1,
  `padding` INT UNSIGNED NOT NULL DEFAULT 5,
  `suffix` VARCHAR(191) NULL,
  `reset_yearly` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rn_numbering_sequences_business_id_index` (`business_id`),
  KEY `rn_numbering_sequences_location_id_index` (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- ---- END: create/RESTAURANTNEW_STAGE002_CREATE.sql ----

-- ---- BEGIN: create/RESTAURANTNEW_STAGE003_CREATE.sql ----
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
-- ---- END: create/RESTAURANTNEW_STAGE003_CREATE.sql ----

-- ---- BEGIN: create/RESTAURANTNEW_STAGE004_CREATE.sql ----
CREATE TABLE IF NOT EXISTS `restaurant_new_orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `dining_area_id` BIGINT UNSIGNED NULL,
  `restaurant_table_id` BIGINT UNSIGNED NULL,
  `customer_id` INT UNSIGNED NULL,
  `order_no` VARCHAR(50) NOT NULL,
  `order_type` ENUM('dine_in','takeaway','delivery') NOT NULL DEFAULT 'dine_in',
  `order_status` VARCHAR(50) NOT NULL DEFAULT 'open',
  `kot_status` VARCHAR(50) NOT NULL DEFAULT 'pending',
  `payment_status` VARCHAR(50) NOT NULL DEFAULT 'due',
  `guest_count` INT UNSIGNED NOT NULL DEFAULT 1,
  `waiter_id` INT UNSIGNED NULL,
  `cashier_id` INT UNSIGNED NULL,
  `subtotal` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `discount_type` VARCHAR(20) NULL,
  `discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `service_charge_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `tax_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `delivery_charge` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `round_off` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `grand_total` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `paid_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `balance_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `order_note` TEXT NULL,
  `opened_at` TIMESTAMP NULL,
  `completed_at` TIMESTAMP NULL,
  `cancelled_at` TIMESTAMP NULL,
  `created_by` INT UNSIGNED NULL,
  `updated_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restaurant_new_orders_business_order_unique` (`business_id`, `order_no`),
  KEY `restaurant_new_orders_lookup_idx` (`business_id`, `location_id`, `order_status`, `payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restaurant_new_order_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `menu_item_id` BIGINT UNSIGNED NULL,
  `variant_id` BIGINT UNSIGNED NULL,
  `kitchen_section_id` BIGINT UNSIGNED NULL,
  `item_name` VARCHAR(191) NOT NULL,
  `variant_name` VARCHAR(191) NULL,
  `qty` DECIMAL(22,4) NOT NULL DEFAULT 1.0000,
  `unit_price` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `tax_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `line_total` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `kot_status` VARCHAR(50) NOT NULL DEFAULT 'pending',
  `is_printed_to_kitchen` TINYINT(1) NOT NULL DEFAULT 0,
  `line_note` TEXT NULL,
  `cancel_reason` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `updated_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `restaurant_new_order_lines_order_idx` (`order_id`),
  KEY `restaurant_new_order_lines_kot_idx` (`business_id`, `location_id`, `kot_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restaurant_new_order_payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `payment_method` VARCHAR(50) NOT NULL,
  `payment_account_id` INT UNSIGNED NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `reference_no` VARCHAR(191) NULL,
  `paid_on` TIMESTAMP NULL,
  `payment_note` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `restaurant_new_order_payments_order_idx` (`order_id`),
  KEY `restaurant_new_order_payments_method_idx` (`business_id`, `location_id`, `payment_method`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- ---- END: create/RESTAURANTNEW_STAGE004_CREATE.sql ----

-- ---- BEGIN: create/RESTAURANTNEW_STAGE005_CREATE.sql ----
CREATE TABLE IF NOT EXISTS `restaurant_new_kitchen_tickets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `ticket_no` VARCHAR(50) NOT NULL,
  `kitchen_section_id` BIGINT UNSIGNED NULL,
  `ticket_type` VARCHAR(30) NOT NULL DEFAULT 'kot',
  `status` VARCHAR(30) NOT NULL DEFAULT 'new',
  `printed_at` TIMESTAMP NULL,
  `started_at` TIMESTAMP NULL,
  `completed_at` TIMESTAMP NULL,
  `cancelled_at` TIMESTAMP NULL,
  `cancel_reason` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `updated_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restaurant_new_kot_unique` (`business_id`,`business_location_id`,`ticket_no`),
  KEY `restaurant_new_kot_business_idx` (`business_id`),
  KEY `restaurant_new_kot_location_idx` (`business_location_id`),
  KEY `restaurant_new_kot_order_idx` (`order_id`),
  KEY `restaurant_new_kot_section_idx` (`kitchen_section_id`),
  KEY `restaurant_new_kot_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restaurant_new_kitchen_ticket_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `kitchen_ticket_id` BIGINT UNSIGNED NOT NULL,
  `order_line_id` BIGINT UNSIGNED NULL,
  `menu_item_id` BIGINT UNSIGNED NULL,
  `item_name` VARCHAR(255) NOT NULL,
  `quantity` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `modifiers_text` TEXT NULL,
  `special_instruction` TEXT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'new',
  `started_at` TIMESTAMP NULL,
  `completed_at` TIMESTAMP NULL,
  `cancelled_at` TIMESTAMP NULL,
  `cancel_reason` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `updated_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `restaurant_new_kotl_business_idx` (`business_id`),
  KEY `restaurant_new_kotl_location_idx` (`business_location_id`),
  KEY `restaurant_new_kotl_ticket_idx` (`kitchen_ticket_id`),
  KEY `restaurant_new_kotl_order_line_idx` (`order_line_id`),
  KEY `restaurant_new_kotl_menu_item_idx` (`menu_item_id`),
  KEY `restaurant_new_kotl_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- ---- END: create/RESTAURANTNEW_STAGE005_CREATE.sql ----

-- ---- BEGIN: create/RESTAURANTNEW_STAGE006_CREATE.sql ----
CREATE TABLE IF NOT EXISTS `restaurant_new_bills` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `restaurant_new_order_id` BIGINT UNSIGNED NULL,
  `bill_no` VARCHAR(191) NOT NULL,
  `bill_date` DATETIME NOT NULL,
  `customer_id` BIGINT UNSIGNED NULL,
  `cashier_id` BIGINT UNSIGNED NULL,
  `waiter_id` BIGINT UNSIGNED NULL,
  `subtotal` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `discount_type` VARCHAR(20) NOT NULL DEFAULT 'fixed',
  `discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `tax_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `service_charge_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `round_off_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `grand_total` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `paid_total` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `balance_due` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `change_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `payment_status` VARCHAR(20) NOT NULL DEFAULT 'due',
  `bill_status` VARCHAR(20) NOT NULL DEFAULT 'open',
  `notes` TEXT NULL,
  `void_reason` TEXT NULL,
  `voided_by` BIGINT UNSIGNED NULL,
  `voided_at` DATETIME NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restaurant_new_bills_unique_no` (`business_id`,`location_id`,`bill_no`),
  KEY `restaurant_new_bills_business_id_index` (`business_id`),
  KEY `restaurant_new_bills_location_id_index` (`location_id`),
  KEY `restaurant_new_bills_order_id_index` (`restaurant_new_order_id`),
  KEY `restaurant_new_bills_bill_date_index` (`bill_date`),
  KEY `restaurant_new_bills_payment_status_index` (`payment_status`),
  KEY `restaurant_new_bills_bill_status_index` (`bill_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restaurant_new_bill_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `restaurant_new_bill_id` BIGINT UNSIGNED NOT NULL,
  `restaurant_new_order_line_id` BIGINT UNSIGNED NULL,
  `menu_item_id` BIGINT UNSIGNED NULL,
  `item_name` VARCHAR(191) NOT NULL,
  `variant_name` VARCHAR(191) NULL,
  `quantity` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `unit_price` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `tax_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `service_charge_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `line_total` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `line_status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `restaurant_new_bill_lines_business_id_index` (`business_id`),
  KEY `restaurant_new_bill_lines_bill_id_index` (`restaurant_new_bill_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restaurant_new_bill_payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `restaurant_new_bill_id` BIGINT UNSIGNED NOT NULL,
  `payment_date` DATETIME NOT NULL,
  `payment_method` VARCHAR(50) NOT NULL,
  `account_id` BIGINT UNSIGNED NULL,
  `reference_no` VARCHAR(191) NULL,
  `card_type` VARCHAR(191) NULL,
  `card_last_four` VARCHAR(4) NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `payment_status` VARCHAR(20) NOT NULL DEFAULT 'posted',
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `voided_by` BIGINT UNSIGNED NULL,
  `voided_at` DATETIME NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `restaurant_new_bill_payments_bill_id_index` (`restaurant_new_bill_id`),
  KEY `restaurant_new_bill_payments_method_index` (`payment_method`),
  KEY `restaurant_new_bill_payments_date_index` (`payment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restaurant_new_refunds` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `restaurant_new_bill_id` BIGINT UNSIGNED NOT NULL,
  `refund_no` VARCHAR(191) NOT NULL,
  `refund_date` DATETIME NOT NULL,
  `refund_method` VARCHAR(50) NOT NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `reason` TEXT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'posted',
  `approved_by` BIGINT UNSIGNED NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `restaurant_new_refunds_bill_id_index` (`restaurant_new_bill_id`),
  KEY `restaurant_new_refunds_date_index` (`refund_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- ---- END: create/RESTAURANTNEW_STAGE006_CREATE.sql ----

-- ---- BEGIN: create/007_inventory_recipe_tables.sql ----
CREATE TABLE IF NOT EXISTS `restaurant_new_ingredient_categories` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` INT UNSIGNED NOT NULL,`location_id` INT UNSIGNED NULL,`name` VARCHAR(191) NOT NULL,`code` VARCHAR(191) NULL,`is_active` TINYINT(1) NOT NULL DEFAULT 1,`created_by` INT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,PRIMARY KEY (`id`),UNIQUE KEY `restnew_ing_cat_unique` (`business_id`,`location_id`,`name`),KEY `restaurant_new_ingredient_categories_business_id_index` (`business_id`),KEY `restaurant_new_ingredient_categories_location_id_index` (`location_id`));
CREATE TABLE IF NOT EXISTS `restaurant_new_ingredients` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` INT UNSIGNED NOT NULL,`location_id` INT UNSIGNED NULL,`category_id` BIGINT UNSIGNED NULL,`name` VARCHAR(191) NOT NULL,`sku` VARCHAR(191) NULL,`unit` VARCHAR(30) NOT NULL DEFAULT 'unit',`purchase_price` DECIMAL(22,4) NOT NULL DEFAULT 0,`reorder_level` DECIMAL(22,4) NOT NULL DEFAULT 0,`is_active` TINYINT(1) NOT NULL DEFAULT 1,`created_by` INT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,PRIMARY KEY (`id`),KEY `restaurant_new_ingredients_business_id_index` (`business_id`),KEY `restaurant_new_ingredients_location_id_index` (`location_id`),KEY `restaurant_new_ingredients_category_id_index` (`category_id`),KEY `restaurant_new_ingredients_business_id_location_id_sku_index` (`business_id`,`location_id`,`sku`));
CREATE TABLE IF NOT EXISTS `restaurant_new_ingredient_stocks` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` INT UNSIGNED NOT NULL,`location_id` INT UNSIGNED NOT NULL,`ingredient_id` BIGINT UNSIGNED NOT NULL,`quantity` DECIMAL(22,4) NOT NULL DEFAULT 0,`average_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,`stock_value` DECIMAL(22,4) NOT NULL DEFAULT 0,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,PRIMARY KEY (`id`),UNIQUE KEY `restnew_ing_stock_unique` (`business_id`,`location_id`,`ingredient_id`),KEY `restaurant_new_ingredient_stocks_business_id_index` (`business_id`),KEY `restaurant_new_ingredient_stocks_location_id_index` (`location_id`),KEY `restaurant_new_ingredient_stocks_ingredient_id_index` (`ingredient_id`));
CREATE TABLE IF NOT EXISTS `restaurant_new_recipes` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` INT UNSIGNED NOT NULL,`location_id` INT UNSIGNED NULL,`menu_item_id` BIGINT UNSIGNED NOT NULL,`name` VARCHAR(191) NOT NULL,`yield_qty` DECIMAL(22,4) NOT NULL DEFAULT 1,`yield_unit` VARCHAR(30) NOT NULL DEFAULT 'portion',`estimated_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,`preparation_notes` TEXT NULL,`is_active` TINYINT(1) NOT NULL DEFAULT 1,`created_by` INT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,PRIMARY KEY (`id`),KEY `restaurant_new_recipes_business_id_index` (`business_id`),KEY `restaurant_new_recipes_location_id_index` (`location_id`),KEY `restaurant_new_recipes_menu_item_id_index` (`menu_item_id`));
CREATE TABLE IF NOT EXISTS `restaurant_new_recipe_lines` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` INT UNSIGNED NOT NULL,`recipe_id` BIGINT UNSIGNED NOT NULL,`ingredient_id` BIGINT UNSIGNED NOT NULL,`quantity` DECIMAL(22,4) NOT NULL DEFAULT 0,`unit` VARCHAR(30) NOT NULL DEFAULT 'unit',`unit_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,`line_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,`is_optional` TINYINT(1) NOT NULL DEFAULT 0,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,PRIMARY KEY (`id`),KEY `restaurant_new_recipe_lines_business_id_index` (`business_id`),KEY `restaurant_new_recipe_lines_recipe_id_index` (`recipe_id`),KEY `restaurant_new_recipe_lines_ingredient_id_index` (`ingredient_id`));
CREATE TABLE IF NOT EXISTS `restaurant_new_stock_movements` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` INT UNSIGNED NOT NULL,`location_id` INT UNSIGNED NOT NULL,`ingredient_id` BIGINT UNSIGNED NOT NULL,`movement_type` VARCHAR(40) NOT NULL,`reference_type` VARCHAR(80) NULL,`reference_id` BIGINT UNSIGNED NULL,`quantity_in` DECIMAL(22,4) NOT NULL DEFAULT 0,`quantity_out` DECIMAL(22,4) NOT NULL DEFAULT 0,`balance_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,`unit_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,`total_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,`notes` VARCHAR(191) NULL,`created_by` INT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,PRIMARY KEY (`id`),KEY `restaurant_new_stock_movements_business_id_index` (`business_id`),KEY `restaurant_new_stock_movements_location_id_index` (`location_id`),KEY `restaurant_new_stock_movements_ingredient_id_index` (`ingredient_id`),KEY `restaurant_new_stock_movements_movement_type_index` (`movement_type`));
CREATE TABLE IF NOT EXISTS `restaurant_new_wastages` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,`business_id` INT UNSIGNED NOT NULL,`location_id` INT UNSIGNED NOT NULL,`ingredient_id` BIGINT UNSIGNED NOT NULL,`wastage_date` DATE NOT NULL,`quantity` DECIMAL(22,4) NOT NULL DEFAULT 0,`unit` VARCHAR(30) NOT NULL DEFAULT 'unit',`unit_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,`total_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,`reason` VARCHAR(191) NULL,`notes` TEXT NULL,`created_by` INT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,PRIMARY KEY (`id`),KEY `restaurant_new_wastages_business_id_index` (`business_id`),KEY `restaurant_new_wastages_location_id_index` (`location_id`),KEY `restaurant_new_wastages_ingredient_id_index` (`ingredient_id`),KEY `restaurant_new_wastages_wastage_date_index` (`wastage_date`));
-- ---- END: create/007_inventory_recipe_tables.sql ----

-- ---- BEGIN: create/008_staff_shift_tables.sql ----
CREATE TABLE IF NOT EXISTS `rn_staff_members` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `business_id` INT UNSIGNED NOT NULL, `location_id` INT UNSIGNED NULL, `staff_code` VARCHAR(191) NULL, `name` VARCHAR(191) NOT NULL, `mobile` VARCHAR(50) NULL, `email` VARCHAR(191) NULL, `role` VARCHAR(50) NOT NULL DEFAULT 'waiter', `service_charge_share_percent` DECIMAL(8,4) NOT NULL DEFAULT 0, `can_take_orders` TINYINT(1) NOT NULL DEFAULT 0, `can_cashier` TINYINT(1) NOT NULL DEFAULT 0, `is_active` TINYINT(1) NOT NULL DEFAULT 1, `created_at` TIMESTAMP NULL DEFAULT NULL, `updated_at` TIMESTAMP NULL DEFAULT NULL, PRIMARY KEY (`id`), UNIQUE KEY `rn_staff_business_code_unique` (`business_id`,`staff_code`), KEY `rn_staff_members_business_id_index` (`business_id`), KEY `rn_staff_members_location_id_index` (`location_id`), KEY `rn_staff_members_role_index` (`role`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `rn_cashier_shifts` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `business_id` INT UNSIGNED NOT NULL, `location_id` INT UNSIGNED NULL, `staff_member_id` BIGINT UNSIGNED NULL, `user_id` INT UNSIGNED NULL, `shift_no` VARCHAR(191) NOT NULL, `opened_at` DATETIME NULL, `closed_at` DATETIME NULL, `opening_cash` DECIMAL(22,4) NOT NULL DEFAULT 0, `cash_sales` DECIMAL(22,4) NOT NULL DEFAULT 0, `card_sales` DECIMAL(22,4) NOT NULL DEFAULT 0, `other_sales` DECIMAL(22,4) NOT NULL DEFAULT 0, `cash_in` DECIMAL(22,4) NOT NULL DEFAULT 0, `cash_out` DECIMAL(22,4) NOT NULL DEFAULT 0, `expected_cash` DECIMAL(22,4) NOT NULL DEFAULT 0, `counted_cash` DECIMAL(22,4) NULL, `shortage_excess` DECIMAL(22,4) NOT NULL DEFAULT 0, `status` VARCHAR(50) NOT NULL DEFAULT 'open', `opening_note` TEXT NULL, `closing_note` TEXT NULL, `created_at` TIMESTAMP NULL DEFAULT NULL, `updated_at` TIMESTAMP NULL DEFAULT NULL, PRIMARY KEY (`id`), UNIQUE KEY `rn_cashier_shift_scope_no_unique` (`business_id`,`location_id`,`shift_no`), KEY `rn_cashier_shifts_business_id_index` (`business_id`), KEY `rn_cashier_shifts_location_id_index` (`location_id`), KEY `rn_cashier_shifts_status_index` (`status`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `rn_cash_movements` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `business_id` INT UNSIGNED NOT NULL, `location_id` INT UNSIGNED NULL, `cashier_shift_id` BIGINT UNSIGNED NOT NULL, `user_id` INT UNSIGNED NULL, `movement_type` VARCHAR(50) NOT NULL, `amount` DECIMAL(22,4) NOT NULL DEFAULT 0, `reference_no` VARCHAR(191) NULL, `note` TEXT NULL, `created_at` TIMESTAMP NULL DEFAULT NULL, `updated_at` TIMESTAMP NULL DEFAULT NULL, PRIMARY KEY (`id`), KEY `rn_cash_movements_shift_index` (`cashier_shift_id`), KEY `rn_cash_movements_business_id_index` (`business_id`), KEY `rn_cash_movements_location_id_index` (`location_id`), KEY `rn_cash_movements_type_index` (`movement_type`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `rn_tip_entries` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `business_id` INT UNSIGNED NOT NULL, `location_id` INT UNSIGNED NULL, `cashier_shift_id` BIGINT UNSIGNED NULL, `staff_member_id` BIGINT UNSIGNED NULL, `bill_id` BIGINT UNSIGNED NULL, `amount` DECIMAL(22,4) NOT NULL DEFAULT 0, `payment_method` VARCHAR(50) NULL, `note` TEXT NULL, `created_at` TIMESTAMP NULL DEFAULT NULL, `updated_at` TIMESTAMP NULL DEFAULT NULL, PRIMARY KEY (`id`), KEY `rn_tip_entries_business_id_index` (`business_id`), KEY `rn_tip_entries_location_id_index` (`location_id`), KEY `rn_tip_entries_staff_index` (`staff_member_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `rn_service_charge_distributions` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `business_id` INT UNSIGNED NOT NULL, `location_id` INT UNSIGNED NULL, `cashier_shift_id` BIGINT UNSIGNED NULL, `staff_member_id` BIGINT UNSIGNED NOT NULL, `base_amount` DECIMAL(22,4) NOT NULL DEFAULT 0, `share_percent` DECIMAL(8,4) NOT NULL DEFAULT 0, `distributed_amount` DECIMAL(22,4) NOT NULL DEFAULT 0, `status` VARCHAR(50) NOT NULL DEFAULT 'pending', `distribution_date` DATE NULL, `created_at` TIMESTAMP NULL DEFAULT NULL, `updated_at` TIMESTAMP NULL DEFAULT NULL, PRIMARY KEY (`id`), KEY `rn_service_charge_business_id_index` (`business_id`), KEY `rn_service_charge_location_id_index` (`location_id`), KEY `rn_service_charge_staff_index` (`staff_member_id`), KEY `rn_service_charge_status_index` (`status`), KEY `rn_service_charge_date_index` (`distribution_date`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- ---- END: create/008_staff_shift_tables.sql ----

-- ---- BEGIN: create/009_delivery_tables.sql ----
CREATE TABLE IF NOT EXISTS rn_delivery_zones (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  business_location_id BIGINT UNSIGNED NULL,
  zone_name VARCHAR(191) NOT NULL,
  base_delivery_charge DECIMAL(22,4) NOT NULL DEFAULT 0,
  free_delivery_minimum DECIMAL(22,4) NOT NULL DEFAULT 0,
  estimated_minutes INT UNSIGNED NOT NULL DEFAULT 30,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  deleted_at TIMESTAMP NULL,
  INDEX rn_delivery_zones_business_idx (business_id),
  INDEX rn_delivery_zones_location_idx (business_location_id)
);

CREATE TABLE IF NOT EXISTS rn_delivery_riders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  business_location_id BIGINT UNSIGNED NULL,
  rider_name VARCHAR(191) NOT NULL,
  mobile VARCHAR(191) NULL,
  vehicle_no VARCHAR(191) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  is_available TINYINT(1) NOT NULL DEFAULT 1,
  last_assigned_at TIMESTAMP NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  deleted_at TIMESTAMP NULL,
  INDEX rn_delivery_riders_business_idx (business_id),
  INDEX rn_delivery_riders_location_idx (business_location_id)
);

CREATE TABLE IF NOT EXISTS rn_customer_addresses (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  business_location_id BIGINT UNSIGNED NULL,
  customer_id BIGINT UNSIGNED NULL,
  contact_name VARCHAR(191) NULL,
  mobile VARCHAR(191) NULL,
  address_line_1 VARCHAR(191) NOT NULL,
  address_line_2 VARCHAR(191) NULL,
  city VARCHAR(191) NULL,
  landmark VARCHAR(191) NULL,
  delivery_zone_id BIGINT UNSIGNED NULL,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  deleted_at TIMESTAMP NULL,
  INDEX rn_customer_addresses_business_idx (business_id),
  INDEX rn_customer_addresses_customer_idx (customer_id),
  INDEX rn_customer_addresses_zone_idx (delivery_zone_id)
);

CREATE TABLE IF NOT EXISTS rn_delivery_orders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  business_location_id BIGINT UNSIGNED NULL,
  restaurant_order_id BIGINT UNSIGNED NULL,
  customer_id BIGINT UNSIGNED NULL,
  customer_address_id BIGINT UNSIGNED NULL,
  delivery_zone_id BIGINT UNSIGNED NULL,
  delivery_rider_id BIGINT UNSIGNED NULL,
  delivery_charge DECIMAL(22,4) NOT NULL DEFAULT 0,
  cod_amount DECIMAL(22,4) NOT NULL DEFAULT 0,
  card_amount DECIMAL(22,4) NOT NULL DEFAULT 0,
  delivery_status VARCHAR(50) NOT NULL DEFAULT 'pending',
  payment_collection_status VARCHAR(50) NOT NULL DEFAULT 'pending',
  special_instructions TEXT NULL,
  assigned_at TIMESTAMP NULL,
  dispatched_at TIMESTAMP NULL,
  delivered_at TIMESTAMP NULL,
  cancelled_at TIMESTAMP NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  deleted_at TIMESTAMP NULL,
  INDEX rn_delivery_orders_business_idx (business_id),
  INDEX rn_delivery_orders_order_idx (restaurant_order_id),
  INDEX rn_delivery_orders_status_idx (delivery_status),
  INDEX rn_delivery_orders_collection_idx (payment_collection_status)
);

CREATE TABLE IF NOT EXISTS rn_delivery_status_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  business_location_id BIGINT UNSIGNED NULL,
  delivery_order_id BIGINT UNSIGNED NOT NULL,
  status VARCHAR(50) NOT NULL,
  note TEXT NULL,
  changed_by BIGINT UNSIGNED NULL,
  changed_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX rn_delivery_status_logs_business_idx (business_id),
  INDEX rn_delivery_status_logs_order_idx (delivery_order_id),
  INDEX rn_delivery_status_logs_status_idx (status)
);
-- ---- END: create/009_delivery_tables.sql ----

-- ---- BEGIN: create/010_reports_views.sql ----
-- Stage 010: Reporting suite does not require new physical tables.
-- Reports read from RestaurantNew standalone tables only:
-- restaurantnew_orders, restaurantnew_order_lines, restaurantnew_payments,
-- restaurantnew_tables, restaurantnew_staff_members, restaurantnew_menu_items,
-- restaurantnew_menu_categories, restaurantnew_kitchen_tickets, restaurantnew_kitchen_ticket_lines.
-- ---- END: create/010_reports_views.sql ----

-- ---- BEGIN: create/011_sale_kitchen_tables.sql ----
CREATE TABLE IF NOT EXISTS rn_sale_orders (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 location_id BIGINT UNSIGNED NULL,
 order_no VARCHAR(191) NOT NULL UNIQUE,
 order_type VARCHAR(50) NOT NULL DEFAULT 'dine_in',
 table_id BIGINT UNSIGNED NULL,
 customer_id BIGINT UNSIGNED NULL,
 waiter_id BIGINT UNSIGNED NULL,
 cashier_id BIGINT UNSIGNED NULL,
 status VARCHAR(50) NOT NULL DEFAULT 'received',
 kitchen_status VARCHAR(50) NOT NULL DEFAULT 'received',
 payment_status VARCHAR(50) NOT NULL DEFAULT 'pending',
 sub_total DECIMAL(22,4) NOT NULL DEFAULT 0,
 discount_amount DECIMAL(22,4) NOT NULL DEFAULT 0,
 tax_amount DECIMAL(22,4) NOT NULL DEFAULT 0,
 service_charge_amount DECIMAL(22,4) NOT NULL DEFAULT 0,
 grand_total DECIMAL(22,4) NOT NULL DEFAULT 0,
 note TEXT NULL,
 created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL,
 updated_at TIMESTAMP NULL,
 INDEX rn_sale_orders_business_location_status_idx (business_id, location_id, status)
);
CREATE TABLE IF NOT EXISTS rn_sale_order_lines (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id BIGINT UNSIGNED NOT NULL,
 menu_item_id BIGINT UNSIGNED NULL,
 menu_item_name VARCHAR(191) NOT NULL,
 kitchen_section_id BIGINT UNSIGNED NULL,
 quantity DECIMAL(22,4) NOT NULL,
 unit_price DECIMAL(22,4) NOT NULL DEFAULT 0,
 line_total DECIMAL(22,4) NOT NULL DEFAULT 0,
 status VARCHAR(50) NOT NULL DEFAULT 'received',
 note TEXT NULL,
 created_at TIMESTAMP NULL,
 updated_at TIMESTAMP NULL,
 INDEX rn_sale_order_lines_order_section_status_idx (order_id, kitchen_section_id, status)
);
CREATE TABLE IF NOT EXISTS rn_kitchen_queue (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 location_id BIGINT UNSIGNED NULL,
 order_id BIGINT UNSIGNED NOT NULL UNIQUE,
 queue_no VARCHAR(191) NOT NULL,
 status VARCHAR(50) NOT NULL DEFAULT 'received',
 received_at TIMESTAMP NULL,
 started_at TIMESTAMP NULL,
 ready_at TIMESTAMP NULL,
 served_at TIMESTAMP NULL,
 print_count INT UNSIGNED NOT NULL DEFAULT 0,
 last_printed_at TIMESTAMP NULL,
 created_at TIMESTAMP NULL,
 updated_at TIMESTAMP NULL,
 INDEX rn_kitchen_queue_business_location_status_idx (business_id, location_id, status)
);
-- ---- END: create/011_sale_kitchen_tables.sql ----

-- ---- BEGIN: create/012_create_advanced_pos_tables.sql ----
CREATE TABLE IF NOT EXISTS `rn_table_operations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `order_id` BIGINT UNSIGNED NULL,
  `operation_type` VARCHAR(40) NOT NULL,
  `from_table_id` BIGINT UNSIGNED NULL,
  `to_table_id` BIGINT UNSIGNED NULL,
  `from_waiter_id` BIGINT UNSIGNED NULL,
  `to_waiter_id` BIGINT UNSIGNED NULL,
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `rn_table_ops_business_location_idx` (`business_id`, `location_id`),
  KEY `rn_table_ops_order_type_idx` (`order_id`, `operation_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rn_held_orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `hold_no` VARCHAR(191) NOT NULL,
  `reason` TEXT NULL,
  `snapshot` JSON NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'held',
  `held_by` BIGINT UNSIGNED NULL,
  `held_at` TIMESTAMP NULL,
  `resumed_by` BIGINT UNSIGNED NULL,
  `resumed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rn_held_orders_hold_no_unique` (`hold_no`),
  KEY `rn_held_orders_status_idx` (`business_id`, `location_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rn_split_bills` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `split_no` VARCHAR(191) NOT NULL,
  `guest_name` VARCHAR(191) NULL,
  `seat_no` VARCHAR(191) NULL,
  `sub_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `tax_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `service_charge_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `grand_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `payment_status` VARCHAR(20) NOT NULL DEFAULT 'pending',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `rn_split_bills_business_location_idx` (`business_id`, `location_id`),
  KEY `rn_split_bills_order_payment_idx` (`order_id`, `payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rn_split_bill_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `split_bill_id` BIGINT UNSIGNED NOT NULL,
  `order_line_id` BIGINT UNSIGNED NULL,
  `menu_item_name` VARCHAR(191) NOT NULL,
  `quantity` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `unit_price` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `line_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `rn_split_bill_lines_split_idx` (`split_bill_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rn_multi_payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `split_bill_id` BIGINT UNSIGNED NULL,
  `payment_method` VARCHAR(30) NOT NULL,
  `account_id` BIGINT UNSIGNED NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `reference_no` VARCHAR(191) NULL,
  `card_type` VARCHAR(191) NULL,
  `paid_at` TIMESTAMP NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `remarks` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `rn_multi_payments_business_location_idx` (`business_id`, `location_id`),
  KEY `rn_multi_payments_order_split_idx` (`order_id`, `split_bill_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- ---- END: create/012_create_advanced_pos_tables.sql ----

-- ---- BEGIN: create/013_create_kitchen_production_tables.sql ----
CREATE TABLE IF NOT EXISTS `resnew_kitchen_queues` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NOT NULL,
  `kitchen_section_id` BIGINT UNSIGNED NULL,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `order_item_id` BIGINT UNSIGNED NULL,
  `kot_id` BIGINT UNSIGNED NULL,
  `queue_no` VARCHAR(40) NOT NULL,
  `priority` ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `status` ENUM('received','preparing','ready','served','cancelled') NOT NULL DEFAULT 'received',
  `received_at` DATETIME NULL,
  `started_at` DATETIME NULL,
  `ready_at` DATETIME NULL,
  `served_at` DATETIME NULL,
  `estimated_minutes` INT NOT NULL DEFAULT 15,
  `actual_minutes` INT NULL,
  `notes` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `rn_kq_business_location_status_idx` (`business_id`,`location_id`,`status`),
  KEY `rn_kq_priority_idx` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `resnew_kitchen_timers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NOT NULL,
  `queue_id` BIGINT UNSIGNED NOT NULL,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `order_item_id` BIGINT UNSIGNED NULL,
  `timer_type` VARCHAR(30) NOT NULL,
  `started_at` DATETIME NULL,
  `paused_at` DATETIME NULL,
  `ended_at` DATETIME NULL,
  `total_seconds` INT NOT NULL DEFAULT 0,
  `status` VARCHAR(20) NOT NULL DEFAULT 'running',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `rn_kt_queue_status_idx` (`queue_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `resnew_kitchen_route_rules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NOT NULL,
  `menu_category_id` BIGINT UNSIGNED NULL,
  `menu_item_id` BIGINT UNSIGNED NULL,
  `kitchen_section_id` BIGINT UNSIGNED NOT NULL,
  `order_type` VARCHAR(30) NULL,
  `priority` INT NOT NULL DEFAULT 10,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `rn_krr_active_idx` (`business_id`,`location_id`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `resnew_kitchen_performance_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NOT NULL,
  `kitchen_section_id` BIGINT UNSIGNED NULL,
  `queue_id` BIGINT UNSIGNED NOT NULL,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `order_item_id` BIGINT UNSIGNED NULL,
  `staff_id` BIGINT UNSIGNED NULL,
  `metric_date` DATE NOT NULL,
  `target_minutes` INT NOT NULL DEFAULT 0,
  `actual_minutes` INT NULL,
  `delay_minutes` INT NOT NULL DEFAULT 0,
  `status` VARCHAR(30) NOT NULL,
  `remarks` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `rn_kpl_metric_idx` (`business_id`,`location_id`,`metric_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- ---- END: create/013_create_kitchen_production_tables.sql ----

-- ---- BEGIN: create/014_create_customer_experience_tables.sql ----
CREATE TABLE IF NOT EXISTS `restaurant_new_qr_menus` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `title` VARCHAR(191) NOT NULL,
  `public_token` VARCHAR(80) NOT NULL,
  `allow_self_order` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `settings` JSON NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rn_qr_public_token_unique` (`public_token`),
  KEY `rn_qr_business_location_index` (`business_id`, `location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restaurant_new_customer_order_links` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `restaurant_order_id` BIGINT UNSIGNED NOT NULL,
  `purpose` VARCHAR(40) NOT NULL DEFAULT 'status',
  `public_token` VARCHAR(100) NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'active',
  `expires_at` TIMESTAMP NULL,
  `last_accessed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rn_customer_link_token_unique` (`public_token`),
  KEY `rn_customer_link_order_index` (`business_id`, `restaurant_order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restaurant_new_digital_receipts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `restaurant_order_id` BIGINT UNSIGNED NOT NULL,
  `restaurant_bill_id` BIGINT UNSIGNED NULL,
  `receipt_token` VARCHAR(100) NOT NULL,
  `sent_to` VARCHAR(191) NULL,
  `delivery_channel` VARCHAR(40) NULL,
  `delivery_status` VARCHAR(40) NOT NULL DEFAULT 'pending',
  `sent_at` TIMESTAMP NULL,
  `payload` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rn_receipt_token_unique` (`receipt_token`),
  KEY `rn_receipt_order_index` (`business_id`, `restaurant_order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restaurant_new_customer_feedback` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `restaurant_order_id` BIGINT UNSIGNED NULL,
  `customer_id` BIGINT UNSIGNED NULL,
  `customer_name` VARCHAR(191) NULL,
  `customer_mobile` VARCHAR(50) NULL,
  `rating_food` TINYINT UNSIGNED NULL,
  `rating_service` TINYINT UNSIGNED NULL,
  `rating_overall` TINYINT UNSIGNED NULL,
  `comments` TEXT NULL,
  `metadata` JSON NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `rn_feedback_business_location_index` (`business_id`, `location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- ---- END: create/014_create_customer_experience_tables.sql ----

-- ---- BEGIN: create/015_create_restaurant_administration_tables.sql ----
CREATE TABLE IF NOT EXISTS restaurant_new_promotions (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,business_id BIGINT UNSIGNED NOT NULL,location_id BIGINT UNSIGNED NULL,name VARCHAR(191) NOT NULL,promotion_type VARCHAR(50) NOT NULL DEFAULT 'discount',discount_value DECIMAL(22,4) NOT NULL DEFAULT 0,start_date DATE NULL,end_date DATE NULL,priority INT UNSIGNED NOT NULL DEFAULT 100,is_active TINYINT(1) NOT NULL DEFAULT 1,created_by BIGINT UNSIGNED NULL,created_at TIMESTAMP NULL,updated_at TIMESTAMP NULL);
CREATE TABLE IF NOT EXISTS restaurant_new_promotion_rules (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,promotion_id BIGINT UNSIGNED NOT NULL,rule_type VARCHAR(100) NOT NULL,rule_payload JSON NULL,created_at TIMESTAMP NULL,updated_at TIMESTAMP NULL);
CREATE TABLE IF NOT EXISTS restaurant_new_combo_meals (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,business_id BIGINT UNSIGNED NOT NULL,location_id BIGINT UNSIGNED NULL,name VARCHAR(191) NOT NULL,combo_price DECIMAL(22,4) NOT NULL DEFAULT 0,is_active TINYINT(1) NOT NULL DEFAULT 1,created_by BIGINT UNSIGNED NULL,created_at TIMESTAMP NULL,updated_at TIMESTAMP NULL);
CREATE TABLE IF NOT EXISTS restaurant_new_combo_meal_items (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,combo_meal_id BIGINT UNSIGNED NOT NULL,menu_item_id BIGINT UNSIGNED NOT NULL,quantity DECIMAL(22,4) NOT NULL DEFAULT 1,created_at TIMESTAMP NULL,updated_at TIMESTAMP NULL);
CREATE TABLE IF NOT EXISTS restaurant_new_happy_hours (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,business_id BIGINT UNSIGNED NOT NULL,location_id BIGINT UNSIGNED NULL,name VARCHAR(191) NOT NULL,start_time TIME NOT NULL,end_time TIME NOT NULL,days_of_week JSON NULL,discount_value DECIMAL(22,4) NOT NULL DEFAULT 0,is_active TINYINT(1) NOT NULL DEFAULT 1,created_at TIMESTAMP NULL,updated_at TIMESTAMP NULL);
CREATE TABLE IF NOT EXISTS restaurant_new_buffet_packages (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,business_id BIGINT UNSIGNED NOT NULL,location_id BIGINT UNSIGNED NULL,name VARCHAR(191) NOT NULL,adult_price DECIMAL(22,4) NOT NULL DEFAULT 0,child_price DECIMAL(22,4) NOT NULL DEFAULT 0,description TEXT NULL,is_active TINYINT(1) NOT NULL DEFAULT 1,created_at TIMESTAMP NULL,updated_at TIMESTAMP NULL);
CREATE TABLE IF NOT EXISTS restaurant_new_banquet_events (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,business_id BIGINT UNSIGNED NOT NULL,location_id BIGINT UNSIGNED NULL,event_name VARCHAR(191) NOT NULL,event_datetime DATETIME NOT NULL,guest_count INT UNSIGNED NOT NULL DEFAULT 0,estimated_amount DECIMAL(22,4) NOT NULL DEFAULT 0,status VARCHAR(50) NOT NULL DEFAULT 'booked',created_at TIMESTAMP NULL,updated_at TIMESTAMP NULL);
CREATE TABLE IF NOT EXISTS restaurant_new_catering_orders (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,business_id BIGINT UNSIGNED NOT NULL,location_id BIGINT UNSIGNED NULL,order_no VARCHAR(100) NOT NULL UNIQUE,delivery_datetime DATETIME NOT NULL,delivery_address TEXT NULL,total_amount DECIMAL(22,4) NOT NULL DEFAULT 0,status VARCHAR(50) NOT NULL DEFAULT 'pending',created_at TIMESTAMP NULL,updated_at TIMESTAMP NULL);
-- ---- END: create/015_create_restaurant_administration_tables.sql ----

-- ---- BEGIN: create/016_create_procurement_production_tables.sql ----
CREATE TABLE IF NOT EXISTS restaurant_new_supplier_quotations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, business_id BIGINT UNSIGNED NOT NULL, location_id BIGINT UNSIGNED NULL,
 quotation_no VARCHAR(50) NOT NULL, supplier_name VARCHAR(255) NOT NULL, quotation_date DATE NOT NULL, valid_until DATE NULL,
 subtotal DECIMAL(22,4) DEFAULT 0, discount_amount DECIMAL(22,4) DEFAULT 0, tax_amount DECIMAL(22,4) DEFAULT 0, total_amount DECIMAL(22,4) DEFAULT 0,
 status VARCHAR(30) DEFAULT 'draft', created_by BIGINT UNSIGNED NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 INDEX rn_sq_business_idx (business_id), INDEX rn_sq_location_idx (location_id), INDEX rn_sq_status_idx (status)
);
CREATE TABLE IF NOT EXISTS restaurant_new_supplier_quotation_lines (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, quotation_id BIGINT UNSIGNED NOT NULL, ingredient_id BIGINT UNSIGNED NULL,
 item_name VARCHAR(255) NOT NULL, quantity DECIMAL(22,4) DEFAULT 0, unit VARCHAR(40) NULL, unit_price DECIMAL(22,4) DEFAULT 0, line_total DECIMAL(22,4) DEFAULT 0,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, INDEX rn_sql_quote_idx (quotation_id)
);
CREATE TABLE IF NOT EXISTS restaurant_new_purchase_orders (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, business_id BIGINT UNSIGNED NOT NULL, location_id BIGINT UNSIGNED NULL, quotation_id BIGINT UNSIGNED NULL,
 po_no VARCHAR(50) NOT NULL, supplier_name VARCHAR(255) NOT NULL, order_date DATE NOT NULL, expected_date DATE NULL,
 subtotal DECIMAL(22,4) DEFAULT 0, tax_amount DECIMAL(22,4) DEFAULT 0, total_amount DECIMAL(22,4) DEFAULT 0, status VARCHAR(30) DEFAULT 'draft', created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, INDEX rn_po_business_idx (business_id), INDEX rn_po_location_idx (location_id), INDEX rn_po_status_idx (status)
);
CREATE TABLE IF NOT EXISTS restaurant_new_purchase_order_lines (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, purchase_order_id BIGINT UNSIGNED NOT NULL, ingredient_id BIGINT UNSIGNED NULL,
 item_name VARCHAR(255) NOT NULL, ordered_qty DECIMAL(22,4) DEFAULT 0, received_qty DECIMAL(22,4) DEFAULT 0, unit VARCHAR(40) NULL, unit_cost DECIMAL(22,4) DEFAULT 0, line_total DECIMAL(22,4) DEFAULT 0,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, INDEX rn_pol_po_idx (purchase_order_id)
);
CREATE TABLE IF NOT EXISTS restaurant_new_goods_receipts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, business_id BIGINT UNSIGNED NOT NULL, location_id BIGINT UNSIGNED NULL, purchase_order_id BIGINT UNSIGNED NULL,
 grn_no VARCHAR(50) NOT NULL, received_date DATE NOT NULL, supplier_name VARCHAR(255) NULL, total_amount DECIMAL(22,4) DEFAULT 0, status VARCHAR(30) DEFAULT 'received', received_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, INDEX rn_grn_business_idx (business_id), INDEX rn_grn_location_idx (location_id)
);
CREATE TABLE IF NOT EXISTS restaurant_new_goods_receipt_lines (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, goods_receipt_id BIGINT UNSIGNED NOT NULL, ingredient_id BIGINT UNSIGNED NULL,
 item_name VARCHAR(255) NOT NULL, received_qty DECIMAL(22,4) DEFAULT 0, unit VARCHAR(40) NULL, unit_cost DECIMAL(22,4) DEFAULT 0, line_total DECIMAL(22,4) DEFAULT 0, expiry_date DATE NULL, batch_no VARCHAR(255) NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, INDEX rn_grnl_grn_idx (goods_receipt_id)
);
CREATE TABLE IF NOT EXISTS restaurant_new_production_batches (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, business_id BIGINT UNSIGNED NOT NULL, location_id BIGINT UNSIGNED NULL, batch_no VARCHAR(50) NOT NULL, production_type VARCHAR(50) DEFAULT 'kitchen', item_name VARCHAR(255) NOT NULL,
 planned_qty DECIMAL(22,4) DEFAULT 0, produced_qty DECIMAL(22,4) DEFAULT 0, wastage_qty DECIMAL(22,4) DEFAULT 0, total_cost DECIMAL(22,4) DEFAULT 0, status VARCHAR(30) DEFAULT 'planned', created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, INDEX rn_pb_business_idx (business_id), INDEX rn_pb_status_idx (status)
);
CREATE TABLE IF NOT EXISTS restaurant_new_commissary_transfers (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, business_id BIGINT UNSIGNED NOT NULL, from_location_id BIGINT UNSIGNED NULL, to_location_id BIGINT UNSIGNED NULL, transfer_no VARCHAR(50) NOT NULL, transfer_date DATE NOT NULL, status VARCHAR(30) DEFAULT 'draft', created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, INDEX rn_ct_business_idx (business_id), INDEX rn_ct_status_idx (status)
);
CREATE TABLE IF NOT EXISTS restaurant_new_commissary_transfer_lines (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, transfer_id BIGINT UNSIGNED NOT NULL, ingredient_id BIGINT UNSIGNED NULL, item_name VARCHAR(255) NOT NULL, quantity DECIMAL(22,4) DEFAULT 0, unit VARCHAR(40) NULL, unit_cost DECIMAL(22,4) DEFAULT 0,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, INDEX rn_ctl_transfer_idx (transfer_id)
);
-- ---- END: create/016_create_procurement_production_tables.sql ----

-- ---- BEGIN: create/017_create_command_center_tables.sql ----
CREATE TABLE IF NOT EXISTS restaurant_new_command_center_snapshots (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 location_id BIGINT UNSIGNED NULL,
 snapshot_date DATE NOT NULL,
 gross_sales DECIMAL(22,4) NOT NULL DEFAULT 0,
 net_sales DECIMAL(22,4) NOT NULL DEFAULT 0,
 active_tables INT NOT NULL DEFAULT 0,
 available_tables INT NOT NULL DEFAULT 0,
 running_orders INT NOT NULL DEFAULT 0,
 waiting_orders INT NOT NULL DEFAULT 0,
 ready_orders INT NOT NULL DEFAULT 0,
 delivery_orders INT NOT NULL DEFAULT 0,
 takeaway_orders INT NOT NULL DEFAULT 0,
 delayed_kots INT NOT NULL DEFAULT 0,
 low_stock_alerts INT NOT NULL DEFAULT 0,
 meta JSON NULL,
 created_at TIMESTAMP NULL,
 updated_at TIMESTAMP NULL,
 INDEX rn_ccs_business_idx (business_id),
 INDEX rn_ccs_location_idx (location_id),
 INDEX rn_ccs_date_idx (snapshot_date)
);
CREATE TABLE IF NOT EXISTS restaurant_new_dashboard_alerts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 location_id BIGINT UNSIGNED NULL,
 alert_type VARCHAR(50) NOT NULL,
 severity VARCHAR(20) NOT NULL DEFAULT 'info',
 title VARCHAR(255) NOT NULL,
 message TEXT NULL,
 source_screen VARCHAR(50) NULL,
 is_read TINYINT(1) NOT NULL DEFAULT 0,
 created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL,
 updated_at TIMESTAMP NULL,
 INDEX rn_da_business_idx (business_id),
 INDEX rn_da_location_idx (location_id),
 INDEX rn_da_type_idx (alert_type),
 INDEX rn_da_read_idx (is_read)
);
CREATE TABLE IF NOT EXISTS restaurant_new_kpi_daily_summaries (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 location_id BIGINT UNSIGNED NULL,
 summary_date DATE NOT NULL,
 sales_total DECIMAL(22,4) NOT NULL DEFAULT 0,
 payment_total DECIMAL(22,4) NOT NULL DEFAULT 0,
 food_cost_total DECIMAL(22,4) NOT NULL DEFAULT 0,
 gross_profit DECIMAL(22,4) NOT NULL DEFAULT 0,
 food_cost_percent DECIMAL(8,4) NOT NULL DEFAULT 0,
 bills_count INT NOT NULL DEFAULT 0,
 kot_count INT NOT NULL DEFAULT 0,
 void_count INT NOT NULL DEFAULT 0,
 refund_count INT NOT NULL DEFAULT 0,
 feedback_count INT NOT NULL DEFAULT 0,
 average_rating DECIMAL(8,4) NOT NULL DEFAULT 0,
 created_at TIMESTAMP NULL,
 updated_at TIMESTAMP NULL,
 INDEX rn_kpi_business_idx (business_id),
 INDEX rn_kpi_location_idx (location_id),
 INDEX rn_kpi_date_idx (summary_date)
);
-- ---- END: create/017_create_command_center_tables.sql ----

-- ---- BEGIN: create/018_admin_security_tables.sql ----
CREATE TABLE restaurant_new_feature_settings (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 location_id BIGINT UNSIGNED NULL,
 feature_key VARCHAR(100) NOT NULL,
 is_enabled TINYINT(1) NOT NULL DEFAULT 1,
 settings JSON NULL,
 created_by BIGINT UNSIGNED NULL,
 updated_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL,
 updated_at TIMESTAMP NULL,
 UNIQUE KEY restnew_feature_scope_unique (business_id, location_id, feature_key),
 INDEX rn_feature_business_idx (business_id), INDEX rn_feature_location_idx (location_id), INDEX rn_feature_enabled_idx (is_enabled)
);

CREATE TABLE restaurant_new_user_access_rules (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 location_id BIGINT UNSIGNED NULL,
 user_id BIGINT UNSIGNED NOT NULL,
 access_area VARCHAR(80) NOT NULL,
 allowed_actions JSON NULL,
 is_allowed TINYINT(1) NOT NULL DEFAULT 1,
 created_by BIGINT UNSIGNED NULL,
 updated_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL,
 updated_at TIMESTAMP NULL,
 UNIQUE KEY restnew_user_access_unique (business_id, location_id, user_id, access_area)
);

CREATE TABLE restaurant_new_audit_logs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 location_id BIGINT UNSIGNED NULL,
 user_id BIGINT UNSIGNED NULL,
 module_area VARCHAR(80) NOT NULL,
 action VARCHAR(80) NOT NULL,
 entity_type VARCHAR(120) NULL,
 entity_id BIGINT UNSIGNED NULL,
 old_values JSON NULL,
 new_values JSON NULL,
 ip_address VARCHAR(64) NULL,
 user_agent VARCHAR(500) NULL,
 created_at TIMESTAMP NULL,
 updated_at TIMESTAMP NULL
);

CREATE TABLE restaurant_new_direct_url_blocks (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NULL,
 location_id BIGINT UNSIGNED NULL,
 user_id BIGINT UNSIGNED NULL,
 route_name VARCHAR(160) NULL,
 url_path VARCHAR(255) NULL,
 required_permission VARCHAR(160) NULL,
 block_reason VARCHAR(255) NULL,
 created_at TIMESTAMP NULL,
 updated_at TIMESTAMP NULL
);
-- ---- END: create/018_admin_security_tables.sql ----

-- ---- BEGIN: create/019_hardening_tables.sql ----
CREATE TABLE IF NOT EXISTS restaurant_new_integrity_checks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NULL,
  location_id BIGINT UNSIGNED NULL,
  check_code VARCHAR(120) NOT NULL,
  check_group VARCHAR(80) NOT NULL,
  status ENUM('passed','warning','failed') NOT NULL DEFAULT 'passed',
  message TEXT NULL,
  details JSON NULL,
  checked_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX restnew_integrity_business_idx (business_id),
  INDEX restnew_integrity_location_idx (location_id),
  INDEX restnew_integrity_code_idx (check_code),
  INDEX restnew_integrity_group_idx (check_group),
  INDEX restnew_integrity_status_idx (status),
  INDEX restnew_integrity_checked_idx (checked_at)
);

CREATE TABLE IF NOT EXISTS restaurant_new_tenant_scope_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NULL,
  location_id BIGINT UNSIGNED NULL,
  user_id BIGINT UNSIGNED NULL,
  route_name VARCHAR(160) NULL,
  model_name VARCHAR(180) NULL,
  operation VARCHAR(80) NULL,
  status ENUM('allowed','blocked') NOT NULL DEFAULT 'allowed',
  reason VARCHAR(255) NULL,
  payload JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX restnew_scope_business_idx (business_id),
  INDEX restnew_scope_location_idx (location_id),
  INDEX restnew_scope_user_idx (user_id),
  INDEX restnew_scope_route_idx (route_name),
  INDEX restnew_scope_model_idx (model_name),
  INDEX restnew_scope_operation_idx (operation),
  INDEX restnew_scope_status_idx (status)
);
-- ---- END: create/019_hardening_tables.sql ----

-- ---- BEGIN: create/021_no_create_required.sql ----
-- Stage 021 contains support/readiness files only. No new create table SQL required.
-- ---- END: create/021_no_create_required.sql ----

-- ---- BEGIN: create/022_create_online_ordering_tables.sql ----
CREATE TABLE IF NOT EXISTS restaurant_new_online_channels (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  channel_name VARCHAR(255) NOT NULL,
  channel_code VARCHAR(50) NOT NULL,
  allow_delivery TINYINT(1) NOT NULL DEFAULT 1,
  allow_pickup TINYINT(1) NOT NULL DEFAULT 1,
  allow_dine_in TINYINT(1) NOT NULL DEFAULT 1,
  accept_scheduled_orders TINYINT(1) NOT NULL DEFAULT 1,
  min_preparation_minutes INT NOT NULL DEFAULT 20,
  minimum_order_amount DECIMAL(22,4) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  settings JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX rn_online_channels_business_idx (business_id),
  INDEX rn_online_channels_location_idx (location_id),
  INDEX rn_online_channels_code_idx (channel_code)
);
CREATE TABLE IF NOT EXISTS restaurant_new_online_customers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  customer_name VARCHAR(255) NOT NULL,
  mobile VARCHAR(50) NOT NULL,
  email VARCHAR(255) NULL,
  default_address TEXT NULL,
  city VARCHAR(255) NULL,
  landmark VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX rn_online_customers_business_idx (business_id),
  INDEX rn_online_customers_mobile_idx (mobile)
);
CREATE TABLE IF NOT EXISTS restaurant_new_online_orders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  online_channel_id BIGINT UNSIGNED NULL,
  online_customer_id BIGINT UNSIGNED NULL,
  online_order_no VARCHAR(80) NOT NULL,
  order_type VARCHAR(30) NOT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'received',
  payment_status VARCHAR(40) NOT NULL DEFAULT 'pending',
  scheduled_at TIMESTAMP NULL,
  accepted_at TIMESTAMP NULL,
  ready_at TIMESTAMP NULL,
  completed_at TIMESTAMP NULL,
  subtotal DECIMAL(22,4) NOT NULL DEFAULT 0,
  discount_amount DECIMAL(22,4) NOT NULL DEFAULT 0,
  tax_amount DECIMAL(22,4) NOT NULL DEFAULT 0,
  delivery_charge DECIMAL(22,4) NOT NULL DEFAULT 0,
  total_amount DECIMAL(22,4) NOT NULL DEFAULT 0,
  customer_note TEXT NULL,
  delivery_address TEXT NULL,
  meta JSON NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX rn_online_orders_business_idx (business_id),
  INDEX rn_online_orders_location_idx (location_id),
  INDEX rn_online_orders_no_idx (online_order_no),
  INDEX rn_online_orders_status_idx (status)
);
CREATE TABLE IF NOT EXISTS restaurant_new_online_order_lines (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  online_order_id BIGINT UNSIGNED NOT NULL,
  menu_item_id BIGINT UNSIGNED NULL,
  item_name VARCHAR(255) NOT NULL,
  quantity DECIMAL(22,4) NOT NULL DEFAULT 0,
  unit_price DECIMAL(22,4) NOT NULL DEFAULT 0,
  discount_amount DECIMAL(22,4) NOT NULL DEFAULT 0,
  tax_amount DECIMAL(22,4) NOT NULL DEFAULT 0,
  line_total DECIMAL(22,4) NOT NULL DEFAULT 0,
  item_note TEXT NULL,
  modifiers JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX rn_online_lines_business_idx (business_id),
  INDEX rn_online_lines_order_idx (online_order_id)
);
CREATE TABLE IF NOT EXISTS restaurant_new_online_order_status_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  online_order_id BIGINT UNSIGNED NOT NULL,
  from_status VARCHAR(40) NULL,
  to_status VARCHAR(40) NOT NULL,
  remarks TEXT NULL,
  changed_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX rn_online_logs_business_idx (business_id),
  INDEX rn_online_logs_order_idx (online_order_id),
  INDEX rn_online_logs_status_idx (to_status)
);
-- ---- END: create/022_create_online_ordering_tables.sql ----

-- ---- BEGIN: create/023_customer_experience_create.sql ----
CREATE TABLE restaurant_new_table_service_requests (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  table_id BIGINT UNSIGNED NULL,
  order_id BIGINT UNSIGNED NULL,
  request_no VARCHAR(80) NOT NULL,
  request_type VARCHAR(50) NOT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'open',
  customer_note TEXT NULL,
  acknowledged_at TIMESTAMP NULL,
  completed_at TIMESTAMP NULL,
  assigned_staff_id BIGINT UNSIGNED NULL,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX rn_tsr_business (business_id),
  INDEX rn_tsr_status (status)
);

CREATE TABLE restaurant_new_customer_order_tracking (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  order_id BIGINT UNSIGNED NOT NULL,
  tracking_token VARCHAR(120) NOT NULL UNIQUE,
  customer_mobile VARCHAR(50) NULL,
  current_status VARCHAR(50) NOT NULL DEFAULT 'received',
  last_status_at TIMESTAMP NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  public_payload JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX rn_cot_business (business_id),
  INDEX rn_cot_order (order_id)
);

CREATE TABLE restaurant_new_customer_experience_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  order_id BIGINT UNSIGNED NULL,
  table_id BIGINT UNSIGNED NULL,
  event_type VARCHAR(80) NOT NULL,
  description TEXT NULL,
  meta JSON NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX rn_cel_business (business_id),
  INDEX rn_cel_event (event_type)
);
-- ---- END: create/023_customer_experience_create.sql ----

-- ---- BEGIN: create/024_enterprise_kds_tables.sql ----
CREATE TABLE IF NOT EXISTS restaurant_new_kds_screens (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  screen_name VARCHAR(120) NOT NULL,
  screen_code VARCHAR(80) NOT NULL,
  kitchen_section VARCHAR(80) NULL,
  visible_order_types JSON NULL,
  status_filter JSON NULL,
  sound_enabled TINYINT(1) NOT NULL DEFAULT 1,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX rn_kds_screen_business_idx (business_id),
  INDEX rn_kds_screen_location_idx (location_id),
  INDEX rn_kds_screen_code_idx (screen_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS restaurant_new_kds_queue_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  order_id BIGINT UNSIGNED NOT NULL,
  order_item_id BIGINT UNSIGNED NULL,
  kot_id BIGINT UNSIGNED NULL,
  order_no VARCHAR(80) NULL,
  item_name VARCHAR(191) NOT NULL,
  quantity DECIMAL(18,4) NOT NULL DEFAULT 1,
  order_type VARCHAR(40) NOT NULL DEFAULT 'dine_in',
  kitchen_section VARCHAR(80) NULL,
  priority VARCHAR(40) NOT NULL DEFAULT 'normal',
  current_status VARCHAR(50) NOT NULL DEFAULT 'received',
  expected_prep_minutes INT NOT NULL DEFAULT 0,
  received_at TIMESTAMP NULL,
  accepted_at TIMESTAMP NULL,
  started_at TIMESTAMP NULL,
  ready_at TIMESTAMP NULL,
  collected_at TIMESTAMP NULL,
  served_at TIMESTAMP NULL,
  assigned_chef_id BIGINT UNSIGNED NULL,
  kitchen_note TEXT NULL,
  meta JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX rn_kds_queue_business_idx (business_id),
  INDEX rn_kds_queue_location_idx (location_id),
  INDEX rn_kds_queue_order_idx (order_id),
  INDEX rn_kds_queue_status_idx (current_status),
  INDEX rn_kds_queue_section_idx (kitchen_section),
  INDEX rn_kds_queue_priority_idx (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS restaurant_new_kds_status_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  queue_item_id BIGINT UNSIGNED NOT NULL,
  order_id BIGINT UNSIGNED NOT NULL,
  from_status VARCHAR(50) NULL,
  to_status VARCHAR(50) NOT NULL,
  remarks TEXT NULL,
  changed_by BIGINT UNSIGNED NULL,
  changed_at TIMESTAMP NULL,
  meta JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX rn_kds_log_business_idx (business_id),
  INDEX rn_kds_log_queue_idx (queue_item_id),
  INDEX rn_kds_log_order_idx (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS restaurant_new_kds_notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  order_id BIGINT UNSIGNED NULL,
  queue_item_id BIGINT UNSIGNED NULL,
  notification_type VARCHAR(80) NOT NULL,
  title VARCHAR(191) NOT NULL,
  message TEXT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  target_user_id BIGINT UNSIGNED NULL,
  payload JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX rn_kds_notification_business_idx (business_id),
  INDEX rn_kds_notification_type_idx (notification_type),
  INDEX rn_kds_notification_read_idx (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- ---- END: create/024_enterprise_kds_tables.sql ----

-- ---- BEGIN: create/025_multi_branch_operations_tables.sql ----
CREATE TABLE IF NOT EXISTS restaurant_new_branch_operation_profiles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  business_location_id BIGINT UNSIGNED NOT NULL,
  is_central_kitchen TINYINT(1) NOT NULL DEFAULT 0,
  is_commissary TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  operating_days JSON NULL,
  production_start_time TIME NULL,
  production_end_time TIME NULL,
  meta JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX rn_branch_profile_business_idx (business_id),
  INDEX rn_branch_profile_location_idx (business_location_id)
);

CREATE TABLE IF NOT EXISTS restaurant_new_branch_recipe_policies (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  business_location_id BIGINT UNSIGNED NULL,
  restaurant_new_menu_item_id BIGINT UNSIGNED NULL,
  allow_local_override TINYINT(1) NOT NULL DEFAULT 0,
  approval_required TINYINT(1) NOT NULL DEFAULT 1,
  effective_from DATE NULL,
  meta JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX rn_branch_recipe_policy_business_idx (business_id),
  INDEX rn_branch_recipe_policy_location_idx (business_location_id)
);

CREATE TABLE IF NOT EXISTS restaurant_new_branch_transfers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  transfer_no VARCHAR(191) NOT NULL,
  transfer_date DATE NULL,
  from_business_location_id BIGINT UNSIGNED NOT NULL,
  to_business_location_id BIGINT UNSIGNED NOT NULL,
  transfer_type VARCHAR(80) NOT NULL DEFAULT 'branch_transfer',
  status VARCHAR(80) NOT NULL DEFAULT 'draft',
  requested_by BIGINT UNSIGNED NULL,
  requested_at TIMESTAMP NULL,
  approved_by BIGINT UNSIGNED NULL,
  approved_at TIMESTAMP NULL,
  dispatched_by BIGINT UNSIGNED NULL,
  dispatched_at TIMESTAMP NULL,
  received_by BIGINT UNSIGNED NULL,
  received_at TIMESTAMP NULL,
  cancelled_by BIGINT UNSIGNED NULL,
  cancelled_at TIMESTAMP NULL,
  totals JSON NULL,
  meta JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX rn_branch_transfers_business_idx (business_id),
  INDEX rn_branch_transfers_no_idx (transfer_no),
  INDEX rn_branch_transfers_status_idx (status)
);

CREATE TABLE IF NOT EXISTS restaurant_new_branch_transfer_lines (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  restaurant_new_branch_transfer_id BIGINT UNSIGNED NOT NULL,
  ingredient_id BIGINT UNSIGNED NULL,
  menu_item_id BIGINT UNSIGNED NULL,
  line_type VARCHAR(80) NOT NULL DEFAULT 'ingredient',
  requested_qty DECIMAL(20,4) NOT NULL DEFAULT 0,
  approved_qty DECIMAL(20,4) NOT NULL DEFAULT 0,
  dispatched_qty DECIMAL(20,4) NOT NULL DEFAULT 0,
  received_qty DECIMAL(20,4) NOT NULL DEFAULT 0,
  unit_cost DECIMAL(20,4) NOT NULL DEFAULT 0,
  line_total DECIMAL(20,4) NOT NULL DEFAULT 0,
  uom VARCHAR(80) NULL,
  meta JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX rn_branch_transfer_lines_transfer_idx (restaurant_new_branch_transfer_id)
);

CREATE TABLE IF NOT EXISTS restaurant_new_branch_comparison_snapshots (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  business_location_id BIGINT UNSIGNED NOT NULL,
  snapshot_date DATE NOT NULL,
  sales_total DECIMAL(20,4) NOT NULL DEFAULT 0,
  food_cost_total DECIMAL(20,4) NOT NULL DEFAULT 0,
  gross_profit_total DECIMAL(20,4) NOT NULL DEFAULT 0,
  wastage_total DECIMAL(20,4) NOT NULL DEFAULT 0,
  kpi_payload JSON NULL,
  meta JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX rn_branch_snapshot_business_idx (business_id),
  INDEX rn_branch_snapshot_location_date_idx (business_location_id, snapshot_date)
);
-- ---- END: create/025_multi_branch_operations_tables.sql ----

-- ---- BEGIN: create/026_crm_loyalty_tables.sql ----
CREATE TABLE restaurant_new_customer_profiles (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, business_id BIGINT UNSIGNED NOT NULL, location_id BIGINT UNSIGNED NULL, contact_id BIGINT UNSIGNED NULL, customer_code VARCHAR(80) NULL, customer_name VARCHAR(191) NOT NULL, mobile VARCHAR(50) NULL, email VARCHAR(191) NULL, preferred_table VARCHAR(80) NULL, preferred_waiter_id BIGINT UNSIGNED NULL, favourite_items JSON NULL, dietary_preferences JSON NULL, allergies JSON NULL, lifetime_spend DECIMAL(22,4) NOT NULL DEFAULT 0, average_bill_value DECIMAL(22,4) NOT NULL DEFAULT 0, visit_count INT NOT NULL DEFAULT 0, last_visit_at TIMESTAMP NULL, crm_status VARCHAR(40) NOT NULL DEFAULT 'active', vip_level VARCHAR(40) NULL, created_by BIGINT UNSIGNED NULL, updated_by BIGINT UNSIGNED NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, INDEX rn_crm_profile_business_idx (business_id), INDEX rn_crm_profile_location_idx (location_id), INDEX rn_crm_profile_mobile_idx (mobile));
CREATE TABLE restaurant_new_customer_visits (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, business_id BIGINT UNSIGNED NOT NULL, location_id BIGINT UNSIGNED NULL, customer_profile_id BIGINT UNSIGNED NOT NULL, order_id BIGINT UNSIGNED NULL, visit_type VARCHAR(40) NOT NULL DEFAULT 'dine_in', table_no VARCHAR(80) NULL, waiter_id BIGINT UNSIGNED NULL, guest_count INT NOT NULL DEFAULT 1, gross_total DECIMAL(22,4) NOT NULL DEFAULT 0, discount_total DECIMAL(22,4) NOT NULL DEFAULT 0, net_total DECIMAL(22,4) NOT NULL DEFAULT 0, visited_at TIMESTAMP NULL, ordered_items_summary JSON NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, INDEX rn_crm_visit_customer_idx (customer_profile_id), INDEX rn_crm_visit_business_idx (business_id));
CREATE TABLE restaurant_new_loyalty_links (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, business_id BIGINT UNSIGNED NOT NULL, location_id BIGINT UNSIGNED NULL, customer_profile_id BIGINT UNSIGNED NOT NULL, membership_member_id BIGINT UNSIGNED NULL, loyalty_number VARCHAR(100) NULL, tier_name VARCHAR(100) NULL, available_points DECIMAL(22,4) NOT NULL DEFAULT 0, lifetime_points DECIMAL(22,4) NOT NULL DEFAULT 0, is_active TINYINT(1) NOT NULL DEFAULT 1, linked_at TIMESTAMP NULL, integration_meta JSON NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, INDEX rn_loyalty_customer_idx (customer_profile_id));
CREATE TABLE restaurant_new_crm_campaigns (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, business_id BIGINT UNSIGNED NOT NULL, location_id BIGINT UNSIGNED NULL, campaign_name VARCHAR(191) NOT NULL, campaign_type VARCHAR(60) NOT NULL DEFAULT 'sms', segment_code VARCHAR(80) NULL, start_date DATE NULL, end_date DATE NULL, status VARCHAR(40) NOT NULL DEFAULT 'draft', message_body TEXT NULL, filters JSON NULL, communication_meta JSON NULL, created_by BIGINT UNSIGNED NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, INDEX rn_campaign_business_idx (business_id));
CREATE TABLE restaurant_new_crm_feedback_cases (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, business_id BIGINT UNSIGNED NOT NULL, location_id BIGINT UNSIGNED NULL, customer_profile_id BIGINT UNSIGNED NULL, order_id BIGINT UNSIGNED NULL, food_rating TINYINT NULL, service_rating TINYINT NULL, waiter_rating TINYINT NULL, kitchen_rating TINYINT NULL, delivery_rating TINYINT NULL, case_status VARCHAR(40) NOT NULL DEFAULT 'open', priority VARCHAR(40) NOT NULL DEFAULT 'normal', customer_comments TEXT NULL, resolution_note TEXT NULL, resolved_by BIGINT UNSIGNED NULL, resolved_at TIMESTAMP NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, INDEX rn_feedback_business_idx (business_id));
-- ---- END: create/026_crm_loyalty_tables.sql ----

-- ---- BEGIN: create/027_create_restaurant_new_analytics_tables.sql ----
CREATE TABLE IF NOT EXISTS restaurant_new_analytics_snapshots (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  snapshot_date DATE NOT NULL,
  gross_sales DECIMAL(22,4) NOT NULL DEFAULT 0,
  net_sales DECIMAL(22,4) NOT NULL DEFAULT 0,
  discount_total DECIMAL(22,4) NOT NULL DEFAULT 0,
  tax_total DECIMAL(22,4) NOT NULL DEFAULT 0,
  service_charge_total DECIMAL(22,4) NOT NULL DEFAULT 0,
  food_cost_total DECIMAL(22,4) NOT NULL DEFAULT 0,
  gross_profit DECIMAL(22,4) NOT NULL DEFAULT 0,
  food_cost_percentage DECIMAL(12,4) NOT NULL DEFAULT 0,
  order_count INT NOT NULL DEFAULT 0,
  guest_count INT NOT NULL DEFAULT 0,
  table_turns INT NOT NULL DEFAULT 0,
  kpi_payload JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY rn_analytics_snapshot_unique (business_id, location_id, snapshot_date),
  KEY rn_analytics_business_idx (business_id),
  KEY rn_analytics_location_idx (location_id)
);

CREATE TABLE IF NOT EXISTS restaurant_new_menu_profitability (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  menu_item_id BIGINT UNSIGNED NOT NULL,
  period_date DATE NOT NULL,
  qty_sold DECIMAL(22,4) NOT NULL DEFAULT 0,
  sales_total DECIMAL(22,4) NOT NULL DEFAULT 0,
  recipe_cost_total DECIMAL(22,4) NOT NULL DEFAULT 0,
  gross_margin DECIMAL(22,4) NOT NULL DEFAULT 0,
  margin_percentage DECIMAL(12,4) NOT NULL DEFAULT 0,
  void_count INT NOT NULL DEFAULT 0,
  complaint_count INT NOT NULL DEFAULT 0,
  performance_band VARCHAR(40) NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  KEY rn_menu_profit_business_idx (business_id),
  KEY rn_menu_profit_location_idx (location_id),
  KEY rn_menu_profit_item_idx (menu_item_id)
);

CREATE TABLE IF NOT EXISTS restaurant_new_hourly_sales_trends (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  trend_date DATE NOT NULL,
  hour_no TINYINT UNSIGNED NOT NULL,
  net_sales DECIMAL(22,4) NOT NULL DEFAULT 0,
  order_count INT NOT NULL DEFAULT 0,
  guest_count INT NOT NULL DEFAULT 0,
  average_bill_value DECIMAL(22,4) NOT NULL DEFAULT 0,
  order_type_breakdown JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  KEY rn_hourly_business_idx (business_id),
  KEY rn_hourly_location_idx (location_id),
  KEY rn_hourly_date_idx (trend_date)
);

CREATE TABLE IF NOT EXISTS restaurant_new_table_utilization (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  table_id BIGINT UNSIGNED NULL,
  utilization_date DATE NOT NULL,
  turn_count INT NOT NULL DEFAULT 0,
  guest_count INT NOT NULL DEFAULT 0,
  occupied_minutes INT NOT NULL DEFAULT 0,
  sales_total DECIMAL(22,4) NOT NULL DEFAULT 0,
  revenue_per_seat DECIMAL(22,4) NOT NULL DEFAULT 0,
  hourly_usage JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  KEY rn_table_util_business_idx (business_id),
  KEY rn_table_util_location_idx (location_id),
  KEY rn_table_util_table_idx (table_id)
);

CREATE TABLE IF NOT EXISTS restaurant_new_forecast_runs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  forecast_date DATE NOT NULL,
  forecast_type VARCHAR(60) NOT NULL,
  forecast_sales DECIMAL(22,4) NOT NULL DEFAULT 0,
  forecast_orders INT NOT NULL DEFAULT 0,
  forecast_payload JSON NULL,
  confidence_score DECIMAL(8,4) NOT NULL DEFAULT 0,
  status VARCHAR(40) NOT NULL DEFAULT 'generated',
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  KEY rn_forecast_business_idx (business_id),
  KEY rn_forecast_location_idx (location_id),
  KEY rn_forecast_date_idx (forecast_date)
);
-- ---- END: create/027_create_restaurant_new_analytics_tables.sql ----

-- ---- BEGIN: create/028_create_restaurant_new_ai_operations_tables.sql ----
CREATE TABLE IF NOT EXISTS restaurant_new_ai_recommendations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  recommendation_type VARCHAR(80) NOT NULL,
  priority VARCHAR(30) NOT NULL DEFAULT 'normal',
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  source_payload JSON NULL,
  action_payload JSON NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'open',
  assigned_to BIGINT UNSIGNED NULL,
  resolved_by BIGINT UNSIGNED NULL,
  resolved_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  KEY rn_ai_rec_business_idx (business_id), KEY rn_ai_rec_location_idx (location_id), KEY rn_ai_rec_type_idx (recommendation_type)
);
CREATE TABLE IF NOT EXISTS restaurant_new_ai_forecasts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  forecast_date DATE NOT NULL,
  forecast_area VARCHAR(80) NOT NULL,
  predicted_sales DECIMAL(22,4) NOT NULL DEFAULT 0,
  predicted_orders INT NOT NULL DEFAULT 0,
  predicted_guests INT NOT NULL DEFAULT 0,
  forecast_payload JSON NULL,
  confidence_score DECIMAL(8,4) NOT NULL DEFAULT 0,
  method VARCHAR(80) NOT NULL DEFAULT 'rule_based_foundation',
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  KEY rn_ai_forecast_business_idx (business_id), KEY rn_ai_forecast_location_idx (location_id), KEY rn_ai_forecast_date_idx (forecast_date)
);
CREATE TABLE IF NOT EXISTS restaurant_new_ai_inventory_signals (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  ingredient_id BIGINT UNSIGNED NOT NULL,
  signal_type VARCHAR(60) NOT NULL,
  current_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
  predicted_consumption_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
  recommended_reorder_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
  expected_shortage_date DATE NULL,
  priority VARCHAR(30) NOT NULL DEFAULT 'normal',
  calculation_payload JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  KEY rn_ai_inv_business_idx (business_id), KEY rn_ai_inv_location_idx (location_id), KEY rn_ai_inv_ingredient_idx (ingredient_id)
);
CREATE TABLE IF NOT EXISTS restaurant_new_ai_anomaly_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  anomaly_area VARCHAR(80) NOT NULL,
  severity VARCHAR(30) NOT NULL DEFAULT 'info',
  title VARCHAR(255) NOT NULL,
  details TEXT NULL,
  metric_payload JSON NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'new',
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  KEY rn_ai_anomaly_business_idx (business_id), KEY rn_ai_anomaly_location_idx (location_id), KEY rn_ai_anomaly_area_idx (anomaly_area)
);
-- ---- END: create/028_create_restaurant_new_ai_operations_tables.sql ----

-- ---- BEGIN: create/029_create_production_tables.sql ----
-- RestaurantNew Stage 029 CREATE SQL - corrected full production schema
-- Mirrors 2026_07_08_029000_create_restaurant_new_production_tables.php
-- Run in each tenant database after taking a backup.

CREATE TABLE IF NOT EXISTS `restaurant_new_production_plans` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `plan_no` VARCHAR(60) NOT NULL,
  `plan_date` DATE NOT NULL,
  `production_type` VARCHAR(40) NOT NULL DEFAULT 'central_kitchen',
  `status` VARCHAR(40) NOT NULL DEFAULT 'draft',
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `approved_by` BIGINT UNSIGNED NULL,
  `approved_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `rn_prod_plan_business_idx` (`business_id`),
  KEY `rn_prod_plan_location_idx` (`location_id`),
  KEY `rn_prod_plan_no_idx` (`plan_no`),
  KEY `rn_prod_plan_date_idx` (`plan_date`),
  KEY `rn_prod_plan_type_idx` (`production_type`),
  KEY `rn_prod_plan_status_idx` (`status`),
  KEY `rn_prod_plan_created_by_idx` (`created_by`),
  KEY `rn_prod_plan_approved_by_idx` (`approved_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restaurant_new_production_plan_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `production_plan_id` BIGINT UNSIGNED NOT NULL,
  `menu_item_id` BIGINT UNSIGNED NULL,
  `semi_finished_item_id` BIGINT UNSIGNED NULL,
  `planned_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `produced_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `yield_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `wastage_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `estimated_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `actual_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `status` VARCHAR(40) NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `rn_prod_item_plan_idx` (`production_plan_id`),
  KEY `rn_prod_item_menu_idx` (`menu_item_id`),
  KEY `rn_prod_item_semi_idx` (`semi_finished_item_id`),
  KEY `rn_prod_item_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restaurant_new_semi_finished_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NOT NULL,
  `sku` VARCHAR(80) NULL,
  `unit` VARCHAR(30) NULL,
  `standard_yield_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `standard_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `rn_semi_business_idx` (`business_id`),
  KEY `rn_semi_location_idx` (`location_id`),
  KEY `rn_semi_sku_idx` (`sku`),
  KEY `rn_semi_active_idx` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restaurant_new_batch_productions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `production_plan_id` BIGINT UNSIGNED NULL,
  `batch_no` VARCHAR(80) NOT NULL,
  `started_at` DATETIME NULL,
  `completed_at` DATETIME NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'open',
  `input_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `output_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `wastage_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `yield_payload` JSON NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `rn_batch_business_idx` (`business_id`),
  KEY `rn_batch_location_idx` (`location_id`),
  KEY `rn_batch_plan_idx` (`production_plan_id`),
  KEY `rn_batch_no_idx` (`batch_no`),
  KEY `rn_batch_status_idx` (`status`),
  KEY `rn_batch_created_by_idx` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restaurant_new_branch_distributions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `from_location_id` BIGINT UNSIGNED NOT NULL,
  `to_location_id` BIGINT UNSIGNED NOT NULL,
  `batch_production_id` BIGINT UNSIGNED NULL,
  `distribution_no` VARCHAR(80) NOT NULL,
  `distribution_date` DATE NOT NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'draft',
  `total_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `total_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `rn_dist_business_idx` (`business_id`),
  KEY `rn_dist_from_idx` (`from_location_id`),
  KEY `rn_dist_to_idx` (`to_location_id`),
  KEY `rn_dist_batch_idx` (`batch_production_id`),
  KEY `rn_dist_no_idx` (`distribution_no`),
  KEY `rn_dist_date_idx` (`distribution_date`),
  KEY `rn_dist_status_idx` (`status`),
  KEY `rn_dist_created_by_idx` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- ---- END: create/029_create_production_tables.sql ----

-- ---- BEGIN: CREATE/041_create_reservation_floor_tables.sql ----
CREATE TABLE IF NOT EXISTS restaurant_new_floor_plans (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,business_id BIGINT UNSIGNED NOT NULL,location_id BIGINT UNSIGNED NULL,name VARCHAR(191) NOT NULL,floor_type VARCHAR(60) NOT NULL DEFAULT 'indoor',layout_payload JSON NULL,is_active TINYINT(1) NOT NULL DEFAULT 1,created_by BIGINT UNSIGNED NULL,created_at TIMESTAMP NULL,updated_at TIMESTAMP NULL,INDEX rn_fp_business_idx (business_id),INDEX rn_fp_location_idx (location_id),INDEX rn_fp_active_idx (is_active));
CREATE TABLE IF NOT EXISTS restaurant_new_floor_tables (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,business_id BIGINT UNSIGNED NOT NULL,location_id BIGINT UNSIGNED NULL,floor_plan_id BIGINT UNSIGNED NOT NULL,table_no VARCHAR(60) NOT NULL,table_name VARCHAR(191) NULL,shape VARCHAR(30) NOT NULL DEFAULT 'rectangle',min_guests INT UNSIGNED NOT NULL DEFAULT 1,capacity INT UNSIGNED NOT NULL DEFAULT 2,max_guests INT UNSIGNED NOT NULL DEFAULT 2,status VARCHAR(40) NOT NULL DEFAULT 'available',assigned_waiter_id BIGINT UNSIGNED NULL,assigned_cashier_id BIGINT UNSIGNED NULL,merge_group VARCHAR(80) NULL,position_payload JSON NULL,created_at TIMESTAMP NULL,updated_at TIMESTAMP NULL,INDEX rn_ft_business_idx (business_id),INDEX rn_ft_location_idx (location_id),INDEX rn_ft_floor_idx (floor_plan_id),INDEX rn_ft_status_idx (status));
CREATE TABLE IF NOT EXISTS restaurant_new_reservations (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,business_id BIGINT UNSIGNED NOT NULL,location_id BIGINT UNSIGNED NULL,reservation_no VARCHAR(80) NOT NULL,customer_id BIGINT UNSIGNED NULL,customer_name VARCHAR(191) NOT NULL,mobile VARCHAR(40) NULL,email VARCHAR(191) NULL,reservation_date DATE NOT NULL,reservation_time TIME NOT NULL,guest_count INT UNSIGNED NOT NULL DEFAULT 1,floor_table_id BIGINT UNSIGNED NULL,preferred_area VARCHAR(191) NULL,is_vip TINYINT(1) NOT NULL DEFAULT 0,special_requests TEXT NULL,allergy_notes TEXT NULL,status VARCHAR(40) NOT NULL DEFAULT 'booked',deposit_amount DECIMAL(22,4) NOT NULL DEFAULT 0,deposit_status VARCHAR(40) NOT NULL DEFAULT 'not_required',qr_token VARCHAR(120) NULL,created_by BIGINT UNSIGNED NULL,created_at TIMESTAMP NULL,updated_at TIMESTAMP NULL,UNIQUE KEY rn_res_qr_unique (qr_token),INDEX rn_res_business_idx (business_id),INDEX rn_res_location_idx (location_id),INDEX rn_res_date_time_idx (reservation_date,reservation_time),INDEX rn_res_status_idx (status));
CREATE TABLE IF NOT EXISTS restaurant_new_waitlists (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,business_id BIGINT UNSIGNED NOT NULL,location_id BIGINT UNSIGNED NULL,queue_no VARCHAR(80) NOT NULL,customer_name VARCHAR(191) NOT NULL,mobile VARCHAR(40) NULL,guest_count INT UNSIGNED NOT NULL DEFAULT 1,estimated_wait_minutes INT UNSIGNED NOT NULL DEFAULT 0,priority VARCHAR(30) NOT NULL DEFAULT 'normal',status VARCHAR(40) NOT NULL DEFAULT 'waiting',assigned_table_id BIGINT UNSIGNED NULL,seated_at TIMESTAMP NULL,created_at TIMESTAMP NULL,updated_at TIMESTAMP NULL,INDEX rn_wl_business_idx (business_id),INDEX rn_wl_status_idx (status));
CREATE TABLE IF NOT EXISTS restaurant_new_table_status_logs (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,business_id BIGINT UNSIGNED NOT NULL,location_id BIGINT UNSIGNED NULL,floor_table_id BIGINT UNSIGNED NOT NULL,old_status VARCHAR(40) NULL,new_status VARCHAR(40) NOT NULL,reservation_id BIGINT UNSIGNED NULL,order_id BIGINT UNSIGNED NULL,note TEXT NULL,changed_by BIGINT UNSIGNED NULL,created_at TIMESTAMP NULL,updated_at TIMESTAMP NULL,INDEX rn_tsl_business_idx (business_id),INDEX rn_tsl_table_idx (floor_table_id),INDEX rn_tsl_status_idx (new_status));
-- ---- END: CREATE/041_create_reservation_floor_tables.sql ----

-- ---- BEGIN: CREATE/042_create_gift_voucher_tables.sql ----
CREATE TABLE IF NOT EXISTS `rn_gift_vouchers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `voucher_no` VARCHAR(40) NOT NULL,
  `voucher_type` VARCHAR(30) NOT NULL DEFAULT 'gift_card',
  `customer_name` VARCHAR(255) NULL,
  `customer_mobile` VARCHAR(30) NULL,
  `customer_email` VARCHAR(255) NULL,
  `issue_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `balance_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `issued_on` DATE NULL,
  `expires_on` DATE NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rn_gift_vouchers_business_voucher_unique` (`business_id`,`voucher_no`),
  KEY `rn_gift_vouchers_business_id_index` (`business_id`),
  KEY `rn_gift_vouchers_location_index` (`business_location_id`),
  KEY `rn_gift_vouchers_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `rn_gift_voucher_transactions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `gift_voucher_id` BIGINT UNSIGNED NOT NULL,
  `transaction_type` VARCHAR(30) NOT NULL,
  `reference_type` VARCHAR(60) NULL,
  `reference_id` BIGINT UNSIGNED NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `balance_after` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `rn_gift_voucher_txn_business_index` (`business_id`),
  KEY `rn_gift_voucher_txn_location_index` (`business_location_id`),
  KEY `rn_gift_voucher_txn_voucher_index` (`gift_voucher_id`),
  KEY `rn_gift_voucher_txn_ref_index` (`reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- ---- END: CREATE/042_create_gift_voucher_tables.sql ----

-- ---- BEGIN: CREATE/043_create_corporate_account_tables.sql ----
-- RestaurantNew Stage 043 Corporate Accounts - CREATE tables
-- Run on each tenant database.
CREATE TABLE IF NOT EXISTS restaurant_new_corporate_accounts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  account_code VARCHAR(50) NOT NULL,
  company_name VARCHAR(255) NOT NULL,
  contact_person VARCHAR(255) NULL,
  mobile VARCHAR(30) NULL,
  email VARCHAR(255) NULL,
  credit_limit DECIMAL(22,4) NOT NULL DEFAULT 0,
  current_balance DECIMAL(22,4) NOT NULL DEFAULT 0,
  credit_days INT NOT NULL DEFAULT 0,
  status ENUM('active','hold','closed') NOT NULL DEFAULT 'active',
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY restnew_corp_account_code_unique (business_id, account_code),
  KEY restnew_corp_account_scope_idx (business_id, location_id, status)
);
CREATE TABLE IF NOT EXISTS restaurant_new_corporate_contract_prices (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  corporate_account_id BIGINT UNSIGNED NOT NULL,
  menu_item_id BIGINT UNSIGNED NULL,
  menu_category_id BIGINT UNSIGNED NULL,
  contract_price DECIMAL(22,4) NULL,
  discount_percent DECIMAL(8,4) NOT NULL DEFAULT 0,
  effective_from DATE NULL,
  effective_to DATE NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  KEY restnew_corp_price_scope_idx (business_id, corporate_account_id, status)
);
CREATE TABLE IF NOT EXISTS restaurant_new_corporate_invoices (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  corporate_account_id BIGINT UNSIGNED NOT NULL,
  invoice_no VARCHAR(80) NOT NULL,
  invoice_date DATE NOT NULL,
  due_date DATE NULL,
  subtotal DECIMAL(22,4) NOT NULL DEFAULT 0,
  tax_total DECIMAL(22,4) NOT NULL DEFAULT 0,
  discount_total DECIMAL(22,4) NOT NULL DEFAULT 0,
  grand_total DECIMAL(22,4) NOT NULL DEFAULT 0,
  paid_amount DECIMAL(22,4) NOT NULL DEFAULT 0,
  balance_amount DECIMAL(22,4) NOT NULL DEFAULT 0,
  status ENUM('draft','issued','partially_paid','paid','cancelled') NOT NULL DEFAULT 'draft',
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY restnew_corp_invoice_no_unique (business_id, invoice_no),
  KEY restnew_corp_invoice_scope_idx (business_id, location_id, status, invoice_date)
);
CREATE TABLE IF NOT EXISTS restaurant_new_corporate_invoice_lines (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  corporate_invoice_id BIGINT UNSIGNED NOT NULL,
  restaurant_order_id BIGINT UNSIGNED NULL,
  description VARCHAR(255) NOT NULL,
  qty DECIMAL(22,4) NOT NULL DEFAULT 1,
  unit_price DECIMAL(22,4) NOT NULL DEFAULT 0,
  line_total DECIMAL(22,4) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  KEY restnew_corp_invoice_line_idx (business_id, corporate_invoice_id)
);
CREATE TABLE IF NOT EXISTS restaurant_new_corporate_payments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  corporate_account_id BIGINT UNSIGNED NOT NULL,
  corporate_invoice_id BIGINT UNSIGNED NULL,
  payment_date DATE NOT NULL,
  method VARCHAR(50) NULL,
  reference_no VARCHAR(120) NULL,
  amount DECIMAL(22,4) NOT NULL DEFAULT 0,
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  KEY restnew_corp_payment_scope_idx (business_id, corporate_account_id, payment_date)
);
-- ---- END: CREATE/043_create_corporate_account_tables.sql ----

-- ---- BEGIN: CREATE/044_equipment_assets_create.sql ----
-- RestaurantNew Stage 044 Equipment / Assets - corrected CREATE SQL
-- Generated from 2026_07_08_044000_create_restaurant_new_equipment_asset_tables.php

CREATE TABLE IF NOT EXISTS `restaurant_new_equipment_categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NOT NULL,
  `code` VARCHAR(80) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `rn_eq_cat_business_idx` (`business_id`),
  KEY `rn_eq_cat_location_idx` (`location_id`),
  KEY `rn_eq_cat_code_idx` (`code`),
  KEY `rn_eq_cat_active_idx` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restaurant_new_equipment_assets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `category_id` BIGINT UNSIGNED NULL,
  `asset_code` VARCHAR(80) NOT NULL,
  `asset_name` VARCHAR(191) NOT NULL,
  `brand` VARCHAR(191) NULL,
  `model` VARCHAR(191) NULL,
  `serial_no` VARCHAR(191) NULL,
  `purchase_date` DATE NULL,
  `purchase_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `warranty_expiry` DATE NULL,
  `supplier_id` BIGINT UNSIGNED NULL,
  `kitchen_section` VARCHAR(191) NULL,
  `assigned_employee_id` BIGINT UNSIGNED NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'working',
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rn_asset_code_unique` (`business_id`,`asset_code`),
  KEY `rn_eq_asset_business_idx` (`business_id`),
  KEY `rn_eq_asset_location_idx` (`location_id`),
  KEY `rn_eq_asset_category_idx` (`category_id`),
  KEY `rn_eq_asset_code_idx` (`asset_code`),
  KEY `rn_eq_asset_serial_idx` (`serial_no`),
  KEY `rn_eq_asset_warranty_idx` (`warranty_expiry`),
  KEY `rn_eq_asset_supplier_idx` (`supplier_id`),
  KEY `rn_eq_asset_kitchen_idx` (`kitchen_section`),
  KEY `rn_eq_asset_employee_idx` (`assigned_employee_id`),
  KEY `rn_eq_asset_status_idx` (`status`),
  KEY `rn_eq_asset_created_by_idx` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restaurant_new_equipment_maintenance_schedules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `asset_id` BIGINT UNSIGNED NOT NULL,
  `schedule_type` VARCHAR(50) NOT NULL DEFAULT 'preventive',
  `frequency` VARCHAR(50) NOT NULL DEFAULT 'monthly',
  `next_due_date` DATE NULL,
  `running_hours_due` INT NULL,
  `usage_count_due` INT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `checklist` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `rn_eq_ms_business_idx` (`business_id`),
  KEY `rn_eq_ms_location_idx` (`location_id`),
  KEY `rn_eq_ms_asset_idx` (`asset_id`),
  KEY `rn_eq_ms_type_idx` (`schedule_type`),
  KEY `rn_eq_ms_frequency_idx` (`frequency`),
  KEY `rn_eq_ms_due_idx` (`next_due_date`),
  KEY `rn_eq_ms_active_idx` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restaurant_new_equipment_work_orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `work_order_no` VARCHAR(80) NOT NULL,
  `asset_id` BIGINT UNSIGNED NOT NULL,
  `work_type` VARCHAR(50) NOT NULL DEFAULT 'repair',
  `priority` VARCHAR(30) NOT NULL DEFAULT 'normal',
  `status` VARCHAR(50) NOT NULL DEFAULT 'open',
  `assigned_technician_id` BIGINT UNSIGNED NULL,
  `requested_at` DATETIME NULL,
  `expected_completion_at` DATETIME NULL,
  `completed_at` DATETIME NULL,
  `labour_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `parts_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `downtime_hours` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `problem_description` TEXT NULL,
  `resolution_note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `rn_eq_wo_business_idx` (`business_id`),
  KEY `rn_eq_wo_location_idx` (`location_id`),
  KEY `rn_eq_wo_no_idx` (`work_order_no`),
  KEY `rn_eq_wo_asset_idx` (`asset_id`),
  KEY `rn_eq_wo_type_idx` (`work_type`),
  KEY `rn_eq_wo_priority_idx` (`priority`),
  KEY `rn_eq_wo_status_idx` (`status`),
  KEY `rn_eq_wo_tech_idx` (`assigned_technician_id`),
  KEY `rn_eq_wo_requested_idx` (`requested_at`),
  KEY `rn_eq_wo_completed_idx` (`completed_at`),
  KEY `rn_eq_wo_created_by_idx` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restaurant_new_equipment_spare_parts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `part_code` VARCHAR(80) NOT NULL,
  `part_name` VARCHAR(191) NOT NULL,
  `unit` VARCHAR(30) NOT NULL DEFAULT 'nos',
  `current_stock` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `minimum_stock` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `reorder_level` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `last_purchase_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rn_part_code_unique` (`business_id`,`part_code`),
  KEY `rn_eq_sp_business_idx` (`business_id`),
  KEY `rn_eq_sp_location_idx` (`location_id`),
  KEY `rn_eq_sp_code_idx` (`part_code`),
  KEY `rn_eq_sp_active_idx` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restaurant_new_equipment_work_order_parts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `work_order_id` BIGINT UNSIGNED NOT NULL,
  `spare_part_id` BIGINT UNSIGNED NOT NULL,
  `quantity` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `unit_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `total_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `rn_eq_wop_business_idx` (`business_id`),
  KEY `rn_eq_wop_work_order_idx` (`work_order_id`),
  KEY `rn_eq_wop_spare_idx` (`spare_part_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restaurant_new_equipment_alerts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `asset_id` BIGINT UNSIGNED NULL,
  `spare_part_id` BIGINT UNSIGNED NULL,
  `alert_type` VARCHAR(60) NOT NULL,
  `title` VARCHAR(191) NOT NULL,
  `message` TEXT NULL,
  `severity` VARCHAR(30) NOT NULL DEFAULT 'info',
  `status` VARCHAR(30) NOT NULL DEFAULT 'open',
  `resolved_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `rn_eq_alert_business_idx` (`business_id`),
  KEY `rn_eq_alert_location_idx` (`location_id`),
  KEY `rn_eq_alert_asset_idx` (`asset_id`),
  KEY `rn_eq_alert_spare_idx` (`spare_part_id`),
  KEY `rn_eq_alert_type_idx` (`alert_type`),
  KEY `rn_eq_alert_severity_idx` (`severity`),
  KEY `rn_eq_alert_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- ---- END: CREATE/044_equipment_assets_create.sql ----

-- ---- BEGIN: CREATE/045_create_nutrition_allergen_tables.sql ----
CREATE TABLE IF NOT EXISTS restaurant_new_allergens (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, business_id BIGINT UNSIGNED NOT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(80) NULL, severity_level VARCHAR(30) NOT NULL DEFAULT 'warning', requires_customer_warning TINYINT(1) NOT NULL DEFAULT 1, is_active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, UNIQUE KEY rn_allergen_name_unique (business_id, name), KEY restaurant_new_allergens_business_id_index (business_id));
CREATE TABLE IF NOT EXISTS restaurant_new_dietary_tags (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, business_id BIGINT UNSIGNED NOT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(80) NULL, tag_type VARCHAR(50) NOT NULL DEFAULT 'dietary', is_active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, UNIQUE KEY rn_dietary_tag_name_unique (business_id, name), KEY restaurant_new_dietary_tags_business_id_index (business_id));
CREATE TABLE IF NOT EXISTS restaurant_new_nutrition_profiles (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, business_id BIGINT UNSIGNED NOT NULL, location_id BIGINT UNSIGNED NULL, menu_item_id BIGINT UNSIGNED NOT NULL, serving_size DECIMAL(22,4) NULL, serving_unit VARCHAR(30) NULL, calories DECIMAL(22,4) NOT NULL DEFAULT 0, protein_g DECIMAL(22,4) NOT NULL DEFAULT 0, carbohydrate_g DECIMAL(22,4) NOT NULL DEFAULT 0, fat_g DECIMAL(22,4) NOT NULL DEFAULT 0, sugar_g DECIMAL(22,4) NOT NULL DEFAULT 0, fiber_g DECIMAL(22,4) NOT NULL DEFAULT 0, sodium_mg DECIMAL(22,4) NOT NULL DEFAULT 0, cholesterol_mg DECIMAL(22,4) NOT NULL DEFAULT 0, nutrition_note TEXT NULL, approved_by BIGINT UNSIGNED NULL, approved_at TIMESTAMP NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, UNIQUE KEY rn_nutrition_menu_unique (business_id, menu_item_id));
CREATE TABLE IF NOT EXISTS restaurant_new_menu_item_allergens (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, business_id BIGINT UNSIGNED NOT NULL, menu_item_id BIGINT UNSIGNED NOT NULL, allergen_id BIGINT UNSIGNED NOT NULL, may_contain TINYINT(1) NOT NULL DEFAULT 0, warning_note TEXT NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, UNIQUE KEY rn_menu_allergen_unique (menu_item_id, allergen_id));
CREATE TABLE IF NOT EXISTS restaurant_new_menu_item_dietary_tags (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, business_id BIGINT UNSIGNED NOT NULL, menu_item_id BIGINT UNSIGNED NOT NULL, dietary_tag_id BIGINT UNSIGNED NOT NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, UNIQUE KEY rn_menu_diet_tag_unique (menu_item_id, dietary_tag_id));
CREATE TABLE IF NOT EXISTS restaurant_new_recipe_compliance_checks (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, business_id BIGINT UNSIGNED NOT NULL, location_id BIGINT UNSIGNED NULL, menu_item_id BIGINT UNSIGNED NULL, recipe_id BIGINT UNSIGNED NULL, check_type VARCHAR(60) NOT NULL, status VARCHAR(40) NOT NULL DEFAULT 'pending', finding TEXT NULL, corrective_action TEXT NULL, checked_by BIGINT UNSIGNED NULL, checked_at TIMESTAMP NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL);
CREATE TABLE IF NOT EXISTS restaurant_new_customer_allergy_warnings (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, business_id BIGINT UNSIGNED NOT NULL, location_id BIGINT UNSIGNED NULL, order_id BIGINT UNSIGNED NULL, customer_id BIGINT UNSIGNED NULL, menu_item_id BIGINT UNSIGNED NULL, allergen_id BIGINT UNSIGNED NULL, warning_status VARCHAR(40) NOT NULL DEFAULT 'shown', acknowledged_by BIGINT UNSIGNED NULL, acknowledged_at TIMESTAMP NULL, warning_message TEXT NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL);
-- ---- END: CREATE/045_create_nutrition_allergen_tables.sql ----

-- ---- BEGIN: CREATE/046_create_haccp_tables.sql ----
CREATE TABLE IF NOT EXISTS restnew_haccp_temperature_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  business_location_id BIGINT UNSIGNED NULL,
  asset_name VARCHAR(191) NOT NULL,
  check_type VARCHAR(100) NOT NULL,
  temperature DECIMAL(10,3) NOT NULL,
  min_temperature DECIMAL(10,3) NULL,
  max_temperature DECIMAL(10,3) NULL,
  status VARCHAR(50) NOT NULL DEFAULT 'ok',
  checked_at DATETIME NOT NULL,
  remarks TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX restnew_haccp_temp_business_idx (business_id),
  INDEX restnew_haccp_temp_location_idx (business_location_id),
  INDEX restnew_haccp_temp_status_idx (status),
  INDEX restnew_haccp_temp_checked_idx (checked_at)
);

CREATE TABLE IF NOT EXISTS restnew_haccp_corrective_actions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  business_location_id BIGINT UNSIGNED NULL,
  source_type VARCHAR(191) NULL,
  source_id BIGINT UNSIGNED NULL,
  title VARCHAR(191) NOT NULL,
  description TEXT NULL,
  priority VARCHAR(50) NOT NULL DEFAULT 'normal',
  status VARCHAR(50) NOT NULL DEFAULT 'open',
  assigned_to BIGINT UNSIGNED NULL,
  due_at DATETIME NULL,
  completed_at DATETIME NULL,
  verified_by BIGINT UNSIGNED NULL,
  verified_at DATETIME NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX restnew_haccp_ca_business_idx (business_id),
  INDEX restnew_haccp_ca_location_idx (business_location_id),
  INDEX restnew_haccp_ca_status_idx (status),
  INDEX restnew_haccp_ca_due_idx (due_at)
);
-- ---- END: CREATE/046_create_haccp_tables.sql ----

-- ============================================================================
-- SECTION 2 - REQUIRED ALTER / INDEX UPDATES
-- ============================================================================


-- ---- BEGIN: alter/RESTAURANTNEW_STAGE002_ALTER.sql ----
ALTER TABLE `rn_dining_areas` ADD COLUMN `code` VARCHAR(191) NULL AFTER `name`;
ALTER TABLE `rn_dining_areas` ADD COLUMN `sort_order` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `code`;
ALTER TABLE `rn_tables` ADD COLUMN `table_code` VARCHAR(191) NULL AFTER `name`;
ALTER TABLE `rn_tables` ADD COLUMN `qr_code` VARCHAR(191) NULL AFTER `table_code`;
ALTER TABLE `rn_tables` ADD COLUMN `sort_order` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `status`;
ALTER TABLE `rn_tables` ADD COLUMN `notes` TEXT NULL AFTER `sort_order`;
-- ---- END: alter/RESTAURANTNEW_STAGE002_ALTER.sql ----

-- ---- BEGIN: alter/012_alter_sale_orders_for_advanced_pos.sql ----
ALTER TABLE `rn_sale_orders`
  ADD COLUMN `merged_into_order_id` BIGINT UNSIGNED NULL AFTER `id`,
  ADD COLUMN `guest_count` INT UNSIGNED NULL AFTER `table_id`,
  ADD COLUMN `seat_label` VARCHAR(191) NULL AFTER `guest_count`,
  ADD COLUMN `is_split_bill` TINYINT(1) NOT NULL DEFAULT 0 AFTER `payment_status`,
  ADD COLUMN `paid_amount` DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `grand_total`;
-- ---- END: alter/012_alter_sale_orders_for_advanced_pos.sql ----

-- ---- BEGIN: alter/014_alter_orders_for_customer_experience.sql ----
ALTER TABLE `restaurant_new_orders`
  ADD COLUMN `customer_status_token` VARCHAR(100) NULL AFTER `order_status`,
  ADD COLUMN `customer_visible_status` VARCHAR(50) NULL AFTER `customer_status_token`;
-- ---- END: alter/014_alter_orders_for_customer_experience.sql ----

-- ---- BEGIN: alter/015_alter_orders_for_promotions_and_packages.sql ----
-- RestaurantNew Stage 015 ALTER SQL - fresh consolidated install version
ALTER TABLE `restaurant_new_orders`
  ADD COLUMN `promotion_id` BIGINT UNSIGNED NULL AFTER `order_type`,
  ADD COLUMN `combo_meal_id` BIGINT UNSIGNED NULL AFTER `promotion_id`,
  ADD COLUMN `banquet_event_id` BIGINT UNSIGNED NULL AFTER `combo_meal_id`,
  ADD COLUMN `catering_order_id` BIGINT UNSIGNED NULL AFTER `banquet_event_id`;
-- ---- END: alter/015_alter_orders_for_promotions_and_packages.sql ----

-- ---- BEGIN: alter/016_alter_inventory_for_procurement_links.sql ----
ALTER TABLE `restaurant_new_stock_movements`
  ADD COLUMN `source_module` VARCHAR(80) NULL,
  ADD COLUMN `source_reference` VARCHAR(80) NULL;
ALTER TABLE `restaurant_new_ingredients`
  ADD COLUMN `default_supplier_name` VARCHAR(255) NULL AFTER `name`,
  ADD COLUMN `last_purchase_cost` DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `default_supplier_name`;
-- ---- END: alter/016_alter_inventory_for_procurement_links.sql ----

-- ---- BEGIN: alter/017_alter_command_center_optional_indexes.sql ----
CREATE INDEX `rn_orders_cmd_status_idx` ON `restaurant_new_orders` (`business_id`, `location_id`, `order_status`);
CREATE INDEX `rn_kot_cmd_status_idx` ON `restaurant_new_kitchen_tickets` (`business_id`, `business_location_id`, `status`);
CREATE INDEX `rn_bills_cmd_status_idx` ON `restaurant_new_bills` (`business_id`, `location_id`, `bill_status`);
-- ---- END: alter/017_alter_command_center_optional_indexes.sql ----

-- ---- BEGIN: alter/019_order_hardening_flag.sql ----
ALTER TABLE `restaurant_new_orders` ADD COLUMN `is_hardened` TINYINT(1) NOT NULL DEFAULT 1 AFTER `order_status`;
CREATE INDEX `restaurant_new_orders_is_hardened_idx` ON `restaurant_new_orders` (`is_hardened`);
-- ---- END: alter/019_order_hardening_flag.sql ----

-- ---- BEGIN: alter/RESTNEW_020_FINAL_ALTER.sql ----
-- RESTNEW 020 corrected final safeguards for a fresh consolidated install.
ALTER TABLE `restaurant_new_orders`
  ADD COLUMN `kitchen_print_count` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `order_status`;
ALTER TABLE `restaurant_new_kitchen_tickets`
  ADD COLUMN `bill_printed_at` TIMESTAMP NULL AFTER `printed_at`,
  ADD COLUMN `last_status_changed_at` TIMESTAMP NULL AFTER `status`;
-- ---- END: alter/RESTNEW_020_FINAL_ALTER.sql ----

-- ---- BEGIN: ALTER/044_equipment_assets_indexes.sql ----
ALTER TABLE restaurant_new_equipment_assets ADD INDEX rn_eq_status_idx (business_id, location_id, status);
ALTER TABLE restaurant_new_equipment_work_orders ADD INDEX rn_wo_status_idx (business_id, location_id, status, priority);
ALTER TABLE restaurant_new_equipment_spare_parts ADD INDEX rn_spare_reorder_idx (business_id, location_id, current_stock, reorder_level);
-- ---- END: ALTER/044_equipment_assets_indexes.sql ----

-- ============================================================================
-- SECTION 3 - PERMISSIONS / DEFAULT REFERENCE DATA
-- ============================================================================


-- ---- BEGIN: permissions/RESTAURANTNEW_STAGE001_PERMISSIONS.sql ----
-- Idempotent permission insert template for systems using the permissions table.
INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT p.name, 'web', NOW(), NOW()
FROM (
    SELECT 'restaurant_new.view' AS name UNION ALL
    SELECT 'restaurant_new.settings.view' UNION ALL
    SELECT 'restaurant_new.settings.update' UNION ALL
    SELECT 'restaurant_new.dining_area.manage' UNION ALL
    SELECT 'restaurant_new.table.manage' UNION ALL
    SELECT 'restaurant_new.menu.manage' UNION ALL
    SELECT 'restaurant_new.order.view' UNION ALL
    SELECT 'restaurant_new.order.create' UNION ALL
    SELECT 'restaurant_new.order.update' UNION ALL
    SELECT 'restaurant_new.order.cancel' UNION ALL
    SELECT 'restaurant_new.kot.view' UNION ALL
    SELECT 'restaurant_new.kot.print' UNION ALL
    SELECT 'restaurant_new.bill.view' UNION ALL
    SELECT 'restaurant_new.bill.finalize' UNION ALL
    SELECT 'restaurant_new.report.view'
) p
WHERE NOT EXISTS (SELECT 1 FROM permissions existing WHERE existing.name = p.name AND existing.guard_name = 'web');
-- ---- END: permissions/RESTAURANTNEW_STAGE001_PERMISSIONS.sql ----

-- ---- BEGIN: permissions/RESTAURANTNEW_STAGE002_PERMISSIONS.sql ----
INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT p.name, 'web', NOW(), NOW()
FROM (
    SELECT 'restaurant_new.settings.view' AS name UNION ALL
    SELECT 'restaurant_new.settings.update' UNION ALL
    SELECT 'restaurant_new.dining_area.manage' UNION ALL
    SELECT 'restaurant_new.table.manage' UNION ALL
    SELECT 'restaurant_new.kitchen_section.manage' UNION ALL
    SELECT 'restaurant_new.order_type.manage' UNION ALL
    SELECT 'restaurant_new.numbering.manage'
) p
WHERE NOT EXISTS (SELECT 1 FROM permissions existing WHERE existing.name = p.name AND existing.guard_name = 'web');
-- ---- END: permissions/RESTAURANTNEW_STAGE002_PERMISSIONS.sql ----

-- ---- BEGIN: permissions/RESTAURANTNEW_STAGE003_PERMISSIONS.sql ----
-- RestaurantNew Stage 003 PERMISSIONS SQL
-- Idempotent permission insert pattern; adjust column names if the base permissions table differs.
INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurantnew.menu.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.menu.view');
INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurantnew.menu.create', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.menu.create');
INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurantnew.menu.update', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.menu.update');
INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurantnew.menu.delete', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.menu.delete');
-- ---- END: permissions/RESTAURANTNEW_STAGE003_PERMISSIONS.sql ----

-- ---- BEGIN: permissions/RESTAURANTNEW_STAGE004_PERMISSIONS.sql ----
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.pos.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.pos.view');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.pos.create_order', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.pos.create_order');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.pos.add_payment', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.pos.add_payment');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.pos.close_order', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.pos.close_order');
-- ---- END: permissions/RESTAURANTNEW_STAGE004_PERMISSIONS.sql ----

-- ---- BEGIN: permissions/RESTAURANTNEW_STAGE005_PERMISSIONS.sql ----
INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurantnew.kitchen.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurantnew.kitchen.view');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurantnew.kitchen.update_status', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurantnew.kitchen.update_status');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurantnew.kitchen.print', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurantnew.kitchen.print');
-- ---- END: permissions/RESTAURANTNEW_STAGE005_PERMISSIONS.sql ----

-- ---- BEGIN: permissions/RESTAURANTNEW_STAGE006_PERMISSIONS.sql ----
INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurantnew.billing.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.billing.view');
INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurantnew.billing.create', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.billing.create');
INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurantnew.billing.payment', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.billing.payment');
INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurantnew.billing.void', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.billing.void');
INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurantnew.billing.refund', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.billing.refund');
-- ---- END: permissions/RESTAURANTNEW_STAGE006_PERMISSIONS.sql ----

-- ---- BEGIN: insert/007_inventory_recipe_permissions.sql ----
INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) SELECT 'restaurantnew.inventory.view', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='restaurantnew.inventory.view');
INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) SELECT 'restaurantnew.inventory.create', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='restaurantnew.inventory.create');
INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) SELECT 'restaurantnew.recipes.view', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='restaurantnew.recipes.view');
INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) SELECT 'restaurantnew.recipes.create', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='restaurantnew.recipes.create');
INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) SELECT 'restaurantnew.wastage.manage', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='restaurantnew.wastage.manage');
-- ---- END: insert/007_inventory_recipe_permissions.sql ----

-- ---- BEGIN: insert/008_staff_shift_permissions.sql ----
INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT p.name, 'web', NOW(), NOW()
FROM (
    SELECT 'restaurant_new.staff.view' AS name UNION ALL
    SELECT 'restaurant_new.staff.create' UNION ALL
    SELECT 'restaurant_new.staff.update' UNION ALL
    SELECT 'restaurant_new.shift.view' UNION ALL
    SELECT 'restaurant_new.shift.open' UNION ALL
    SELECT 'restaurant_new.shift.close' UNION ALL
    SELECT 'restaurant_new.shift.cash_movement' UNION ALL
    SELECT 'restaurant_new.tips.manage' UNION ALL
    SELECT 'restaurant_new.service_charge.distribute' UNION ALL
    SELECT 'restaurant_new.report.staff_performance'
) p
WHERE NOT EXISTS (SELECT 1 FROM permissions existing WHERE existing.name = p.name AND existing.guard_name = 'web');
-- ---- END: insert/008_staff_shift_permissions.sql ----

-- ---- BEGIN: insert/009_delivery_permissions.sql ----
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.delivery.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.delivery.view');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.delivery.manage', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.delivery.manage');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.delivery.reports', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.delivery.reports');
-- ---- END: insert/009_delivery_permissions.sql ----

-- ---- BEGIN: insert/010_report_permissions.sql ----
INSERT INTO permissions (name, guard_name, created_at, updated_at) VALUES
('restaurantnew.reports.view', 'web', NOW(), NOW()),
('restaurantnew.reports.sales', 'web', NOW(), NOW()),
('restaurantnew.reports.items', 'web', NOW(), NOW()),
('restaurantnew.reports.operations', 'web', NOW(), NOW()),
('restaurantnew.reports.payments', 'web', NOW(), NOW()),
('restaurantnew.reports.tax_service', 'web', NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);
-- ---- END: insert/010_report_permissions.sql ----

-- ---- BEGIN: insert/011_sale_kitchen_permissions.sql ----
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.sale.create', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.sale.create');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.sale.print_bill', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.sale.print_bill');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.kitchen.screen', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.kitchen.screen');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.kitchen.print_kot', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.kitchen.print_kot');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.kitchen.update_status', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.kitchen.update_status');
-- ---- END: insert/011_sale_kitchen_permissions.sql ----

-- ---- BEGIN: insert/012_insert_advanced_pos_permissions.sql ----
INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurantnew.advanced_pos.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurantnew.advanced_pos.view');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurantnew.table.transfer', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurantnew.table.transfer');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurantnew.bill.split', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurantnew.bill.split');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurantnew.payment.multiple', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurantnew.payment.multiple');
-- ---- END: insert/012_insert_advanced_pos_permissions.sql ----

-- ---- BEGIN: insert/013_insert_kitchen_production_permissions.sql ----
INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurantnew.kitchen.production.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurantnew.kitchen.production.view');
INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurantnew.kitchen.production.update_status', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurantnew.kitchen.production.update_status');
INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurantnew.kitchen.routing.manage', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurantnew.kitchen.routing.manage');
INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurantnew.kitchen.performance.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurantnew.kitchen.performance.view');
-- ---- END: insert/013_insert_kitchen_production_permissions.sql ----

-- ---- BEGIN: insert/014_insert_customer_experience_permissions.sql ----
INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurantnew.qr_menu.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurantnew.qr_menu.view');
INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurantnew.qr_menu.create', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurantnew.qr_menu.create');
INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurantnew.digital_receipt.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurantnew.digital_receipt.view');
INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurantnew.feedback.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurantnew.feedback.view');
-- ---- END: insert/014_insert_customer_experience_permissions.sql ----

-- ---- BEGIN: insert/015_insert_restaurant_administration_permissions.sql ----
INSERT INTO permissions (name, guard_name, created_at, updated_at) SELECT 'restaurantnew.promotions.view', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.promotions.view');
INSERT INTO permissions (name, guard_name, created_at, updated_at) SELECT 'restaurantnew.promotions.manage', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.promotions.manage');
INSERT INTO permissions (name, guard_name, created_at, updated_at) SELECT 'restaurantnew.combo_meals.manage', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.combo_meals.manage');
INSERT INTO permissions (name, guard_name, created_at, updated_at) SELECT 'restaurantnew.happy_hours.manage', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.happy_hours.manage');
INSERT INTO permissions (name, guard_name, created_at, updated_at) SELECT 'restaurantnew.buffet_packages.manage', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.buffet_packages.manage');
INSERT INTO permissions (name, guard_name, created_at, updated_at) SELECT 'restaurantnew.banquets.manage', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.banquets.manage');
INSERT INTO permissions (name, guard_name, created_at, updated_at) SELECT 'restaurantnew.catering.manage', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.catering.manage');
-- ---- END: insert/015_insert_restaurant_administration_permissions.sql ----

-- ---- BEGIN: insert/016_insert_procurement_production_permissions.sql ----
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.procurement.view', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.procurement.view');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.purchase_orders.manage', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.purchase_orders.manage');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.goods_receipts.manage', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.goods_receipts.manage');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.production_batches.manage', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.production_batches.manage');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.commissary_transfers.manage', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.commissary_transfers.manage');
-- ---- END: insert/016_insert_procurement_production_permissions.sql ----

-- ---- BEGIN: insert/017_insert_command_center_permissions.sql ----
INSERT IGNORE INTO permissions (name, guard_name, created_at, updated_at) VALUES
('restaurantnew.command_center.view', 'web', NOW(), NOW()),
('restaurantnew.command_center.restaurant', 'web', NOW(), NOW()),
('restaurantnew.command_center.kitchen', 'web', NOW(), NOW()),
('restaurantnew.command_center.cashier', 'web', NOW(), NOW()),
('restaurantnew.command_center.waiter', 'web', NOW(), NOW()),
('restaurantnew.command_center.manager', 'web', NOW(), NOW()),
('restaurantnew.command_center.executive', 'web', NOW(), NOW()),
('restaurantnew.dashboard_alerts.manage', 'web', NOW(), NOW());
-- ---- END: insert/017_insert_command_center_permissions.sql ----

-- ---- BEGIN: insert/018_default_features_permissions.sql ----
INSERT INTO permissions (name, guard_name, created_at, updated_at) VALUES
('restaurantnew.super_admin.features', 'web', NOW(), NOW()),
('restaurantnew.super_admin.user_access', 'web', NOW(), NOW()),
('restaurantnew.super_admin.audit_logs', 'web', NOW(), NOW()),
('restaurantnew.security.direct_url_protection', 'web', NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);
-- ---- END: insert/018_default_features_permissions.sql ----

-- ---- BEGIN: insert/019_hardening_permissions.sql ----
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.hardening.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.hardening.view');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.hardening.run', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.hardening.run');
-- ---- END: insert/019_hardening_permissions.sql ----

-- ---- BEGIN: permissions/RESTNEW_020_PERMISSIONS.sql ----
-- RestaurantNew RESTNEW_020 permissions
-- Use INSERT IGNORE to avoid duplicate permission rows.

INSERT IGNORE INTO permissions (name, guard_name, created_at, updated_at) VALUES
('restaurantnew.view', 'web', NOW(), NOW()),
('restaurantnew.setup.manage', 'web', NOW(), NOW()),
('restaurantnew.menu.manage', 'web', NOW(), NOW()),
('restaurantnew.pos.create_sale', 'web', NOW(), NOW()),
('restaurantnew.waiter.create_order', 'web', NOW(), NOW()),
('restaurantnew.cashier.create_sale', 'web', NOW(), NOW()),
('restaurantnew.kitchen.view_received_orders', 'web', NOW(), NOW()),
('restaurantnew.kitchen.print_kot', 'web', NOW(), NOW()),
('restaurantnew.kitchen.print_bill', 'web', NOW(), NOW()),
('restaurantnew.billing.manage', 'web', NOW(), NOW()),
('restaurantnew.inventory.manage', 'web', NOW(), NOW()),
('restaurantnew.reports.view', 'web', NOW(), NOW()),
('restaurantnew.command_center.view', 'web', NOW(), NOW()),
('restaurantnew.superadmin.manage', 'web', NOW(), NOW());
-- ---- END: permissions/RESTNEW_020_PERMISSIONS.sql ----

-- ---- BEGIN: insert/021_support_permissions.sql ----
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.support.readiness', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.support.readiness');
-- ---- END: insert/021_support_permissions.sql ----

-- ---- BEGIN: insert/022_online_ordering_permissions.sql ----
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.online_ordering.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.online_ordering.view');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.online_ordering.create', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.online_ordering.create');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.online_kitchen_queue.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.online_kitchen_queue.view');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.online_ordering.change_status', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.online_ordering.change_status');
-- ---- END: insert/022_online_ordering_permissions.sql ----

-- ---- BEGIN: insert/023_customer_experience_permissions.sql ----
INSERT INTO permissions (name, guard_name, created_at, updated_at) VALUES
('restaurantnew.customer_experience.view', 'web', NOW(), NOW()),
('restaurantnew.customer_experience.create_request', 'web', NOW(), NOW()),
('restaurantnew.customer_experience.update_status', 'web', NOW(), NOW()),
('restaurantnew.customer_experience.public_tracking', 'web', NOW(), NOW());
-- ---- END: insert/023_customer_experience_permissions.sql ----

-- ---- BEGIN: insert/024_enterprise_kds_permissions.sql ----
INSERT INTO permissions (name, guard_name, created_at, updated_at) VALUES
('restaurantnew.kds_enterprise.view', 'web', NOW(), NOW()),
('restaurantnew.kds_enterprise.manage', 'web', NOW(), NOW()),
('restaurantnew.kds_enterprise.status', 'web', NOW(), NOW()),
('restaurantnew.kds_enterprise.reprint_kot', 'web', NOW(), NOW()),
('restaurantnew.kds_enterprise.cancel_item', 'web', NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);
-- ---- END: insert/024_enterprise_kds_permissions.sql ----

-- ---- BEGIN: insert/025_multi_branch_permissions.sql ----
INSERT INTO permissions (name, guard_name, created_at, updated_at) VALUES
('restaurantnew.multi_branch.view', 'web', NOW(), NOW()),
('restaurantnew.multi_branch.manage', 'web', NOW(), NOW()),
('restaurantnew.branch_transfer.create', 'web', NOW(), NOW()),
('restaurantnew.branch_transfer.approve', 'web', NOW(), NOW()),
('restaurantnew.branch_transfer.dispatch', 'web', NOW(), NOW()),
('restaurantnew.branch_transfer.receive', 'web', NOW(), NOW()),
('restaurantnew.branch_comparison.view', 'web', NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);
-- ---- END: insert/025_multi_branch_permissions.sql ----

-- ---- BEGIN: insert/026_crm_loyalty_permissions.sql ----
INSERT INTO permissions (name, guard_name, created_at, updated_at) VALUES
('restaurantnew.crm.view', 'web', NOW(), NOW()),
('restaurantnew.crm.customer_360', 'web', NOW(), NOW()),
('restaurantnew.crm.campaigns', 'web', NOW(), NOW()),
('restaurantnew.crm.feedback', 'web', NOW(), NOW()),
('restaurantnew.loyalty.view', 'web', NOW(), NOW()),
('restaurantnew.loyalty.redeem', 'web', NOW(), NOW());
-- ---- END: insert/026_crm_loyalty_permissions.sql ----

-- ---- BEGIN: insert/027_insert_restaurant_new_analytics_permissions.sql ----
INSERT INTO permissions (name, guard_name, created_at, updated_at) VALUES
('restaurantnew.analytics.view', 'web', NOW(), NOW()),
('restaurantnew.analytics.forecast', 'web', NOW(), NOW()),
('restaurantnew.analytics.export', 'web', NOW(), NOW()),
('restaurantnew.analytics.menu_profitability', 'web', NOW(), NOW()),
('restaurantnew.analytics.table_utilization', 'web', NOW(), NOW()),
('restaurantnew.analytics.food_cost', 'web', NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);
-- ---- END: insert/027_insert_restaurant_new_analytics_permissions.sql ----

-- ---- BEGIN: insert/028_insert_restaurant_new_ai_permissions.sql ----
INSERT INTO permissions (name, guard_name, created_at, updated_at) VALUES
('restaurantnew.ai.view', 'web', NOW(), NOW()),
('restaurantnew.ai.forecast', 'web', NOW(), NOW()),
('restaurantnew.ai.recommendations', 'web', NOW(), NOW()),
('restaurantnew.ai.inventory_signals', 'web', NOW(), NOW()),
('restaurantnew.ai.anomaly_logs', 'web', NOW(), NOW()),
('restaurantnew.ai.resolve', 'web', NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);
-- ---- END: insert/028_insert_restaurant_new_ai_permissions.sql ----

-- ---- BEGIN: insert/029_insert_production_permissions.sql ----
-- RestaurantNew Stage 029 INSERT SQL - idempotent permissions
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.production.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.production.view');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.production.create', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.production.create');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.production.approve', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.production.approve');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.production.distribute', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.production.distribute');
-- ---- END: insert/029_insert_production_permissions.sql ----

-- ---- BEGIN: INSERT/041_insert_reservation_permissions.sql ----
INSERT INTO permissions (name, guard_name, created_at, updated_at) VALUES
('restaurantnew.reservations.view','web',NOW(),NOW()),
('restaurantnew.reservations.create','web',NOW(),NOW()),
('restaurantnew.reservations.update','web',NOW(),NOW()),
('restaurantnew.reservations.cancel','web',NOW(),NOW()),
('restaurantnew.reservations.check_in','web',NOW(),NOW()),
('restaurantnew.floor_plans.manage','web',NOW(),NOW()),
('restaurantnew.waitlist.manage','web',NOW(),NOW()),
('restaurantnew.reservation_reports.view','web',NOW(),NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);
-- ---- END: INSERT/041_insert_reservation_permissions.sql ----

-- ---- BEGIN: INSERT/042_insert_gift_voucher_permissions.sql ----
INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
('restaurantnew.gift_vouchers.view', 'web', NOW(), NOW()),
('restaurantnew.gift_vouchers.issue', 'web', NOW(), NOW()),
('restaurantnew.gift_vouchers.redeem', 'web', NOW(), NOW()),
('restaurantnew.gift_vouchers.top_up', 'web', NOW(), NOW()),
('restaurantnew.gift_vouchers.cancel', 'web', NOW(), NOW()),
('restaurantnew.gift_vouchers.reports', 'web', NOW(), NOW())
ON DUPLICATE KEY UPDATE `updated_at` = VALUES(`updated_at`);
-- ---- END: INSERT/042_insert_gift_voucher_permissions.sql ----

-- ---- BEGIN: INSERT/043_insert_corporate_permissions.sql ----
-- RestaurantNew Stage 043 Corporate Account permissions
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.corporate.view','web',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.corporate.view');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.corporate.create','web',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.corporate.create');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.corporate.invoice','web',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.corporate.invoice');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.corporate.payment','web',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.corporate.payment');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.corporate.statement','web',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.corporate.statement');
-- ---- END: INSERT/043_insert_corporate_permissions.sql ----

-- ---- BEGIN: INSERT/044_equipment_assets_permissions.sql ----
INSERT INTO permissions (name, guard_name, created_at, updated_at) VALUES
('restaurantnew.equipment.view','web',NOW(),NOW()),
('restaurantnew.equipment.create','web',NOW(),NOW()),
('restaurantnew.equipment.update','web',NOW(),NOW()),
('restaurantnew.equipment.work_orders','web',NOW(),NOW()),
('restaurantnew.equipment.schedules','web',NOW(),NOW()),
('restaurantnew.equipment.spare_parts','web',NOW(),NOW()),
('restaurantnew.equipment.alerts','web',NOW(),NOW()),
('restaurantnew.equipment.reports','web',NOW(),NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);
-- ---- END: INSERT/044_equipment_assets_permissions.sql ----

-- ---- BEGIN: INSERT/045_insert_compliance_permissions.sql ----
INSERT INTO permissions (name, guard_name, created_at, updated_at) SELECT 'restaurantnew.compliance.view', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.compliance.view');
INSERT INTO permissions (name, guard_name, created_at, updated_at) SELECT 'restaurantnew.compliance.allergens.manage', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.compliance.allergens.manage');
INSERT INTO permissions (name, guard_name, created_at, updated_at) SELECT 'restaurantnew.compliance.dietary_tags.manage', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.compliance.dietary_tags.manage');
INSERT INTO permissions (name, guard_name, created_at, updated_at) SELECT 'restaurantnew.compliance.nutrition.manage', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.compliance.nutrition.manage');
INSERT INTO permissions (name, guard_name, created_at, updated_at) SELECT 'restaurantnew.compliance.checks.manage', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.compliance.checks.manage');
INSERT INTO permissions (name, guard_name, created_at, updated_at) SELECT 'restaurantnew.compliance.warnings.view', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.compliance.warnings.view');
INSERT INTO permissions (name, guard_name, created_at, updated_at) SELECT 'restaurantnew.compliance.reports.view', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.compliance.reports.view');
-- ---- END: INSERT/045_insert_compliance_permissions.sql ----

-- ---- BEGIN: INSERT/045_insert_default_allergens_and_tags.sql ----
-- Run per business by replacing :business_id if your SQL client does not support variables.
INSERT INTO restaurant_new_allergens (business_id, name, code, severity_level, requires_customer_warning, is_active, created_at, updated_at)
SELECT :business_id, 'Peanuts', 'PEANUTS', 'critical', 1, 1, NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM restaurant_new_allergens WHERE business_id=:business_id AND name='Peanuts');
INSERT INTO restaurant_new_allergens (business_id, name, code, severity_level, requires_customer_warning, is_active, created_at, updated_at)
SELECT :business_id, 'Dairy', 'DAIRY', 'warning', 1, 1, NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM restaurant_new_allergens WHERE business_id=:business_id AND name='Dairy');
INSERT INTO restaurant_new_allergens (business_id, name, code, severity_level, requires_customer_warning, is_active, created_at, updated_at)
SELECT :business_id, 'Gluten', 'GLUTEN', 'warning', 1, 1, NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM restaurant_new_allergens WHERE business_id=:business_id AND name='Gluten');
INSERT INTO restaurant_new_dietary_tags (business_id, name, code, tag_type, is_active, created_at, updated_at)
SELECT :business_id, 'Vegetarian', 'VEG', 'dietary', 1, NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM restaurant_new_dietary_tags WHERE business_id=:business_id AND name='Vegetarian');
INSERT INTO restaurant_new_dietary_tags (business_id, name, code, tag_type, is_active, created_at, updated_at)
SELECT :business_id, 'Vegan', 'VEGAN', 'dietary', 1, NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM restaurant_new_dietary_tags WHERE business_id=:business_id AND name='Vegan');
-- ---- END: INSERT/045_insert_default_allergens_and_tags.sql ----

-- ---- BEGIN: INSERT/046_insert_haccp_permissions.sql ----
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.haccp.view', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.haccp.view');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.haccp.temperature', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.haccp.temperature');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.haccp.corrective_actions', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.haccp.corrective_actions');
-- ---- END: INSERT/046_insert_haccp_permissions.sql ----

-- ---- BEGIN: SQL/PERMISSIONS/030_restaurant_new_final_permissions.sql ----
-- RESTNEW 030 FINAL PERMISSIONS
-- Use INSERT IGNORE / ON DUPLICATE KEY according to your permissions table structure.

INSERT IGNORE INTO permissions (name, guard_name, created_at, updated_at) VALUES
('restaurantnew.view', 'web', NOW(), NOW()),
('restaurantnew.create', 'web', NOW(), NOW()),
('restaurantnew.update', 'web', NOW(), NOW()),
('restaurantnew.delete', 'web', NOW(), NOW()),
('restaurantnew.pos.create_sale', 'web', NOW(), NOW()),
('restaurantnew.kitchen.view_received_orders', 'web', NOW(), NOW()),
('restaurantnew.kitchen.print_kot', 'web', NOW(), NOW()),
('restaurantnew.billing.finalize', 'web', NOW(), NOW()),
('restaurantnew.reports.view', 'web', NOW(), NOW()),
('restaurantnew.superadmin.manage', 'web', NOW(), NOW());
-- ---- END: SQL/PERMISSIONS/030_restaurant_new_final_permissions.sql ----

-- ============================================================================
-- SECTION 4 - FINAL PERFORMANCE INDEXES
-- ============================================================================

-- RESTNEW 030 corrected index script. Run once after CREATE/ALTER scripts.
CREATE INDEX `idx_restnew_orders_business_location_status` ON `restaurant_new_orders` (`business_id`, `location_id`, `order_status`);
CREATE INDEX `idx_restnew_orders_business_date` ON `restaurant_new_orders` (`business_id`, `created_at`);
CREATE INDEX `idx_restnew_kitchen_tickets_business_status` ON `restaurant_new_kitchen_tickets` (`business_id`, `status`);
CREATE INDEX `idx_restnew_bills_business_date` ON `restaurant_new_bills` (`business_id`, `bill_date`);
CREATE INDEX `idx_restnew_order_payments_business_date` ON `restaurant_new_order_payments` (`business_id`, `paid_on`);
CREATE INDEX `idx_restnew_stock_movements_business_item` ON `restaurant_new_stock_movements` (`business_id`, `ingredient_id`);

SET FOREIGN_KEY_CHECKS=1;
-- END OF RESTAURANTNEW CONSOLIDATED MASTER SQL
