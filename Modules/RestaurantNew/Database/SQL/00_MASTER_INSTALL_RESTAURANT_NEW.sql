-- Restaurant-New complete fresh tenant-database installation
-- Includes Stage 1 core + Stage 2 advanced operations.
-- Do not run in the central database.

-- Restaurant-New standalone module
-- Laravel multi-tenant tenant-database installation
-- Generated: 29 July 2026
-- All module-owned tables use the restnew_ prefix.
-- Safe to run repeatedly: all tables use CREATE TABLE IF NOT EXISTS.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
CREATE TABLE IF NOT EXISTS `restnew_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `setting_key` VARCHAR(120) NOT NULL,
  `setting_value` LONGTEXT NULL,
  `value_json` JSON NULL,
  `is_encrypted` TINYINT(1) NOT NULL DEFAULT 0,
  `updated_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_setting_business_key_uq` (`business_id`, `setting_key`),
  KEY `rn_settings_business_id_idx` (`business_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_number_sequences` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `sequence_key` VARCHAR(80) NOT NULL,
  `last_number` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_sequence_business_key_uq` (`business_id`, `sequence_key`),
  KEY `rn_number_sequences_business_id_idx` (`business_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_floors` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `name` VARCHAR(120) NOT NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_floor_scope_name_uq` (`business_id`, `location_id`, `name`),
  KEY `rn_floors_business_id_idx` (`business_id`),
  KEY `rn_floors_location_id_idx` (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_tables` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `floor_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `table_code` VARCHAR(50) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `capacity` INT UNSIGNED NOT NULL DEFAULT 4,
  `shape` VARCHAR(30) NOT NULL DEFAULT 'square',
  `status` VARCHAR(30) NOT NULL DEFAULT 'available',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_table_scope_code_uq` (`business_id`, `location_id`, `table_code`),
  KEY `rn_tables_business_id_idx` (`business_id`),
  KEY `rn_tables_location_id_idx` (`location_id`),
  KEY `rn_tables_floor_id_idx` (`floor_id`),
  KEY `rn_tables_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_kitchen_stations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `station_code` VARCHAR(50) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `screen_colour` VARCHAR(30) NOT NULL DEFAULT 'blue',
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_station_scope_code_uq` (`business_id`, `location_id`, `station_code`),
  KEY `rn_kitchen_stations_business_id_idx` (`business_id`),
  KEY `rn_kitchen_stations_location_id_idx` (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_printers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `station_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `name` VARCHAR(120) NOT NULL,
  `printer_type` VARCHAR(30) NOT NULL DEFAULT 'browser',
  `paper_size` VARCHAR(20) NOT NULL DEFAULT '80mm',
  `connection_type` VARCHAR(30) NOT NULL DEFAULT 'browser',
  `connection_value` VARCHAR(255) NULL DEFAULT NULL,
  `settings_json` JSON NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rn_printers_business_id_idx` (`business_id`),
  KEY `rn_printers_location_id_idx` (`location_id`),
  KEY `rn_printers_station_id_idx` (`station_id`),
  KEY `restnew_printer_scope_active_idx` (`business_id`, `location_id`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_user_screen_assignments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `screen_role` VARCHAR(40) NOT NULL,
  `station_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `settings_json` JSON NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_user_screen_scope_uq` (`business_id`, `location_id`, `user_id`, `screen_role`),
  KEY `rn_user_screen_assignments_business_id_idx` (`business_id`),
  KEY `rn_user_screen_assignments_location_id_idx` (`location_id`),
  KEY `rn_user_screen_assignments_user_id_idx` (`user_id`),
  KEY `rn_user_screen_assignments_screen_role_idx` (`screen_role`),
  KEY `rn_user_screen_assignments_station_id_idx` (`station_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `name` VARCHAR(120) NOT NULL,
  `colour` VARCHAR(30) NOT NULL DEFAULT 'blue',
  `icon` VARCHAR(80) NULL DEFAULT NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_category_scope_name_uq` (`business_id`, `location_id`, `name`),
  KEY `rn_categories_business_id_idx` (`business_id`),
  KEY `rn_categories_location_id_idx` (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_menu_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `category_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `station_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `item_code` VARCHAR(60) NOT NULL,
  `name` VARCHAR(180) NOT NULL,
  `description` TEXT NULL,
  `selling_price` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `takeaway_price` DECIMAL(22,4) NULL DEFAULT NULL,
  `cost_price` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `tax_rate` DECIMAL(8,4) NOT NULL DEFAULT 0,
  `service_charge_rate` DECIMAL(8,4) NOT NULL DEFAULT 0,
  `unit` VARCHAR(40) NOT NULL DEFAULT 'Item',
  `image_path` VARCHAR(255) NULL DEFAULT NULL,
  `is_dine_in` TINYINT(1) NOT NULL DEFAULT 1,
  `is_takeaway` TINYINT(1) NOT NULL DEFAULT 1,
  `is_available` TINYINT(1) NOT NULL DEFAULT 1,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `preparation_minutes` INT UNSIGNED NOT NULL DEFAULT 10,
  `metadata` JSON NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_menu_scope_code_uq` (`business_id`, `location_id`, `item_code`),
  KEY `rn_menu_items_business_id_idx` (`business_id`),
  KEY `rn_menu_items_location_id_idx` (`location_id`),
  KEY `rn_menu_items_category_id_idx` (`category_id`),
  KEY `rn_menu_items_station_id_idx` (`station_id`),
  KEY `rn_menu_items_is_available_idx` (`is_available`),
  KEY `rn_menu_items_is_active_idx` (`is_active`),
  KEY `restnew_menu_filter_idx` (`business_id`, `location_id`, `category_id`, `is_active`, `is_available`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_modifier_groups` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `is_required` TINYINT(1) NOT NULL DEFAULT 0,
  `min_select` INT UNSIGNED NOT NULL DEFAULT 0,
  `max_select` INT UNSIGNED NOT NULL DEFAULT 1,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_modifier_group_name_uq` (`business_id`, `name`),
  KEY `rn_modifier_groups_business_id_idx` (`business_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_modifiers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `modifier_group_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `price_delta` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_modifier_group_name_uq` (`modifier_group_id`, `name`),
  KEY `rn_modifiers_business_id_idx` (`business_id`),
  KEY `rn_modifiers_modifier_group_id_idx` (`modifier_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_menu_item_modifier_groups` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `menu_item_id` BIGINT UNSIGNED NOT NULL,
  `modifier_group_id` BIGINT UNSIGNED NOT NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_item_modifier_group_uq` (`menu_item_id`, `modifier_group_id`),
  KEY `rn_menu_item_modifier_groups_business_id_idx` (`business_id`),
  KEY `rn_menu_item_modifier_groups_menu_item_id_idx` (`menu_item_id`),
  KEY `rn_menu_item_modifier_groups_modifier_group_id_idx` (`modifier_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_ingredients` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `ingredient_code` VARCHAR(60) NOT NULL,
  `name` VARCHAR(160) NOT NULL,
  `unit` VARCHAR(40) NOT NULL DEFAULT 'Nos',
  `unit_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `reorder_level` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_ingredient_business_code_uq` (`business_id`, `ingredient_code`),
  KEY `rn_ingredients_business_id_idx` (`business_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_recipes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `menu_item_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(160) NOT NULL,
  `yield_qty` DECIMAL(22,4) NOT NULL DEFAULT 1,
  `yield_unit` VARCHAR(40) NOT NULL DEFAULT 'Item',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `instructions` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_recipe_menu_item_uq` (`business_id`, `menu_item_id`),
  KEY `rn_recipes_business_id_idx` (`business_id`),
  KEY `rn_recipes_menu_item_id_idx` (`menu_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_recipe_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `recipe_id` BIGINT UNSIGNED NOT NULL,
  `ingredient_id` BIGINT UNSIGNED NOT NULL,
  `quantity` DECIMAL(22,4) NOT NULL,
  `waste_percent` DECIMAL(8,4) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_recipe_ingredient_uq` (`recipe_id`, `ingredient_id`),
  KEY `rn_recipe_lines_business_id_idx` (`business_id`),
  KEY `rn_recipe_lines_recipe_id_idx` (`recipe_id`),
  KEY `rn_recipe_lines_ingredient_id_idx` (`ingredient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_inventory_balances` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `ingredient_id` BIGINT UNSIGNED NOT NULL,
  `quantity` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `average_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_inventory_scope_ingredient_uq` (`business_id`, `location_id`, `ingredient_id`),
  KEY `rn_inventory_balances_business_id_idx` (`business_id`),
  KEY `rn_inventory_balances_location_id_idx` (`location_id`),
  KEY `rn_inventory_balances_ingredient_id_idx` (`ingredient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_stock_movements` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `ingredient_id` BIGINT UNSIGNED NOT NULL,
  `movement_type` VARCHAR(40) NOT NULL,
  `quantity` DECIMAL(22,4) NOT NULL,
  `unit_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `value` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `source_type` VARCHAR(80) NULL DEFAULT NULL,
  `source_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `reference_no` VARCHAR(100) NULL DEFAULT NULL,
  `notes` TEXT NULL,
  `metadata` JSON NULL,
  `created_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_stock_source_ingredient_uq` (`source_type`, `source_id`, `ingredient_id`),
  KEY `rn_stock_movements_business_id_idx` (`business_id`),
  KEY `rn_stock_movements_location_id_idx` (`location_id`),
  KEY `rn_stock_movements_ingredient_id_idx` (`ingredient_id`),
  KEY `rn_stock_movements_movement_type_idx` (`movement_type`),
  KEY `restnew_stock_scope_date_idx` (`business_id`, `location_id`, `ingredient_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_shifts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `shift_no` VARCHAR(80) NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'open',
  `opening_cash` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `expected_cash` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `closing_cash` DECIMAL(22,4) NULL DEFAULT NULL,
  `cash_variance` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `opening_note` TEXT NULL,
  `closing_note` TEXT NULL,
  `opened_at` DATETIME NOT NULL,
  `closed_at` DATETIME NULL,
  `closed_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_shift_business_no_uq` (`business_id`, `shift_no`),
  KEY `rn_shifts_business_id_idx` (`business_id`),
  KEY `rn_shifts_location_id_idx` (`location_id`),
  KEY `rn_shifts_user_id_idx` (`user_id`),
  KEY `rn_shifts_status_idx` (`status`),
  KEY `restnew_shift_scope_status_idx` (`business_id`, `location_id`, `user_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `shift_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `table_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `waiter_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `cashier_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `order_no` VARCHAR(80) NOT NULL,
  `bill_no` VARCHAR(80) NULL DEFAULT NULL,
  `receipt_no` VARCHAR(80) NULL DEFAULT NULL,
  `order_type` VARCHAR(30) NOT NULL DEFAULT 'dine_in',
  `source` VARCHAR(30) NOT NULL DEFAULT 'waiter',
  `customer_name` VARCHAR(160) NULL DEFAULT NULL,
  `customer_phone` VARCHAR(60) NULL DEFAULT NULL,
  `customer_email` VARCHAR(160) NULL DEFAULT NULL,
  `guest_count` INT UNSIGNED NOT NULL DEFAULT 1,
  `notes` TEXT NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'draft',
  `payment_status` VARCHAR(30) NOT NULL DEFAULT 'unpaid',
  `kitchen_status` VARCHAR(30) NOT NULL DEFAULT 'not_sent',
  `subtotal` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `discount_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `tax_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `service_charge_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `rounding_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `total_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `paid_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `balance_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `opened_at` DATETIME NULL,
  `sent_at` DATETIME NULL,
  `ready_at` DATETIME NULL,
  `paid_at` DATETIME NULL,
  `cancelled_at` DATETIME NULL,
  `completed_at` DATETIME NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_order_business_no_uq` (`business_id`, `order_no`),
  KEY `rn_orders_business_id_idx` (`business_id`),
  KEY `rn_orders_location_id_idx` (`location_id`),
  KEY `rn_orders_shift_id_idx` (`shift_id`),
  KEY `rn_orders_table_id_idx` (`table_id`),
  KEY `rn_orders_waiter_id_idx` (`waiter_id`),
  KEY `rn_orders_cashier_id_idx` (`cashier_id`),
  KEY `rn_orders_order_type_idx` (`order_type`),
  KEY `rn_orders_customer_phone_idx` (`customer_phone`),
  KEY `rn_orders_status_idx` (`status`),
  KEY `rn_orders_payment_status_idx` (`payment_status`),
  KEY `rn_orders_kitchen_status_idx` (`kitchen_status`),
  KEY `restnew_order_scope_status_date_idx` (`business_id`, `location_id`, `order_type`, `status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_order_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `menu_item_id` BIGINT UNSIGNED NOT NULL,
  `station_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `item_code` VARCHAR(60) NULL DEFAULT NULL,
  `item_name` VARCHAR(180) NOT NULL,
  `quantity` DECIMAL(22,4) NOT NULL,
  `unit_price` DECIMAL(22,4) NOT NULL,
  `modifier_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `tax_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `service_charge_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `line_total` DECIMAL(22,4) NOT NULL,
  `notes` TEXT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `sent_at` DATETIME NULL,
  `started_at` DATETIME NULL,
  `ready_at` DATETIME NULL,
  `stock_posted_at` DATETIME NULL,
  `voided_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `voided_at` DATETIME NULL,
  `void_reason` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rn_order_items_business_id_idx` (`business_id`),
  KEY `rn_order_items_order_id_idx` (`order_id`),
  KEY `rn_order_items_menu_item_id_idx` (`menu_item_id`),
  KEY `rn_order_items_station_id_idx` (`station_id`),
  KEY `rn_order_items_status_idx` (`status`),
  KEY `restnew_order_item_order_status_idx` (`order_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_order_item_modifiers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `order_item_id` BIGINT UNSIGNED NOT NULL,
  `modifier_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `modifier_name` VARCHAR(120) NOT NULL,
  `quantity` DECIMAL(22,4) NOT NULL DEFAULT 1,
  `price_delta` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `line_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rn_order_item_modifiers_business_id_idx` (`business_id`),
  KEY `rn_order_item_modifiers_order_item_id_idx` (`order_item_id`),
  KEY `rn_order_item_modifiers_modifier_id_idx` (`modifier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_kitchen_tickets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `station_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `ticket_no` VARCHAR(80) NOT NULL,
  `ticket_type` VARCHAR(30) NOT NULL DEFAULT 'dine_in',
  `status` VARCHAR(30) NOT NULL DEFAULT 'new',
  `priority` INT UNSIGNED NOT NULL DEFAULT 0,
  `printed_at` DATETIME NULL,
  `accepted_at` DATETIME NULL,
  `started_at` DATETIME NULL,
  `ready_at` DATETIME NULL,
  `accepted_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `ready_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_ticket_business_no_uq` (`business_id`, `ticket_no`),
  KEY `rn_kitchen_tickets_business_id_idx` (`business_id`),
  KEY `rn_kitchen_tickets_location_id_idx` (`location_id`),
  KEY `rn_kitchen_tickets_order_id_idx` (`order_id`),
  KEY `rn_kitchen_tickets_station_id_idx` (`station_id`),
  KEY `rn_kitchen_tickets_status_idx` (`status`),
  KEY `restnew_ticket_queue_idx` (`business_id`, `location_id`, `station_id`, `status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_kitchen_ticket_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `ticket_id` BIGINT UNSIGNED NOT NULL,
  `order_item_id` BIGINT UNSIGNED NOT NULL,
  `item_name` VARCHAR(180) NOT NULL,
  `quantity` DECIMAL(22,4) NOT NULL,
  `notes` TEXT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'new',
  `started_at` DATETIME NULL,
  `ready_at` DATETIME NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_ticket_order_item_uq` (`ticket_id`, `order_item_id`),
  KEY `rn_kitchen_ticket_items_business_id_idx` (`business_id`),
  KEY `rn_kitchen_ticket_items_ticket_id_idx` (`ticket_id`),
  KEY `rn_kitchen_ticket_items_order_item_id_idx` (`order_item_id`),
  KEY `rn_kitchen_ticket_items_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_order_status_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `from_status` VARCHAR(40) NULL DEFAULT NULL,
  `to_status` VARCHAR(40) NOT NULL,
  `remarks` TEXT NULL,
  `metadata` JSON NULL,
  `created_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rn_order_status_logs_business_id_idx` (`business_id`),
  KEY `rn_order_status_logs_order_id_idx` (`order_id`),
  KEY `restnew_order_status_date_idx` (`order_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_collection_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `token_no` VARCHAR(40) NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'queued',
  `call_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `called_at` DATETIME NULL,
  `ready_at` DATETIME NULL,
  `collected_at` DATETIME NULL,
  `collected_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rn_collection_tokens_order_id_uq` (`order_id`),
  UNIQUE KEY `restnew_collection_scope_token_uq` (`business_id`, `location_id`, `token_no`),
  KEY `rn_collection_tokens_business_id_idx` (`business_id`),
  KEY `rn_collection_tokens_location_id_idx` (`location_id`),
  KEY `rn_collection_tokens_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_reservations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `table_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `reservation_no` VARCHAR(80) NOT NULL,
  `customer_name` VARCHAR(160) NOT NULL,
  `customer_phone` VARCHAR(60) NOT NULL,
  `guest_count` INT UNSIGNED NOT NULL DEFAULT 1,
  `reserved_at` DATETIME NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'booked',
  `notes` TEXT NULL,
  `metadata` JSON NULL,
  `created_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_reservation_business_no_uq` (`business_id`, `reservation_no`),
  KEY `rn_reservations_business_id_idx` (`business_id`),
  KEY `rn_reservations_location_id_idx` (`location_id`),
  KEY `rn_reservations_table_id_idx` (`table_id`),
  KEY `rn_reservations_reserved_at_idx` (`reserved_at`),
  KEY `rn_reservations_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_print_jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `printer_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `order_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `ticket_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `document_type` VARCHAR(40) NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `attempt_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `payload` JSON NULL,
  `error_message` TEXT NULL,
  `printed_at` DATETIME NULL,
  `failed_at` DATETIME NULL,
  `created_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rn_print_jobs_business_id_idx` (`business_id`),
  KEY `rn_print_jobs_location_id_idx` (`location_id`),
  KEY `rn_print_jobs_printer_id_idx` (`printer_id`),
  KEY `rn_print_jobs_order_id_idx` (`order_id`),
  KEY `rn_print_jobs_ticket_id_idx` (`ticket_id`),
  KEY `rn_print_jobs_document_type_idx` (`document_type`),
  KEY `rn_print_jobs_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `shift_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `payment_no` VARCHAR(80) NOT NULL,
  `payment_method` VARCHAR(40) NOT NULL,
  `amount` DECIMAL(22,4) NOT NULL,
  `tendered_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `change_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `reference_no` VARCHAR(160) NULL DEFAULT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'completed',
  `metadata` JSON NULL,
  `received_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `paid_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_payment_business_no_uq` (`business_id`, `payment_no`),
  KEY `rn_payments_business_id_idx` (`business_id`),
  KEY `rn_payments_location_id_idx` (`location_id`),
  KEY `rn_payments_order_id_idx` (`order_id`),
  KEY `rn_payments_shift_id_idx` (`shift_id`),
  KEY `rn_payments_payment_method_idx` (`payment_method`),
  KEY `rn_payments_status_idx` (`status`),
  KEY `rn_payments_received_by_idx` (`received_by`),
  KEY `restnew_payment_scope_method_date_idx` (`business_id`, `location_id`, `payment_method`, `paid_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_daily_closures` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `business_date` DATE NOT NULL,
  `gross_sales` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `discount_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `tax_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `service_charge_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `net_sales` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `cash_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `card_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `other_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `refund_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `order_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `summary_json` JSON NULL,
  `closed_at` DATETIME NOT NULL,
  `closed_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_daily_closure_scope_date_uq` (`business_id`, `location_id`, `business_date`),
  KEY `rn_daily_closures_business_id_idx` (`business_id`),
  KEY `rn_daily_closures_location_id_idx` (`location_id`),
  KEY `rn_daily_closures_business_date_idx` (`business_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `entity_type` VARCHAR(100) NOT NULL,
  `entity_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `event` VARCHAR(120) NOT NULL,
  `old_values` JSON NULL,
  `new_values` JSON NULL,
  `metadata` JSON NULL,
  `ip_address` VARCHAR(64) NULL DEFAULT NULL,
  `user_agent` VARCHAR(500) NULL DEFAULT NULL,
  `created_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rn_audit_logs_business_id_idx` (`business_id`),
  KEY `rn_audit_logs_event_idx` (`event`),
  KEY `rn_audit_logs_created_by_idx` (`created_by`),
  KEY `restnew_audit_entity_date_idx` (`business_id`, `entity_type`, `entity_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== RESTAURANT-NEW STAGE 2 TABLES =====
CREATE TABLE IF NOT EXISTS `restnew_suppliers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `supplier_code` VARCHAR(60) NOT NULL,
  `name` VARCHAR(180) NOT NULL,
  `contact_person` VARCHAR(160) NULL DEFAULT NULL,
  `phone` VARCHAR(60) NULL DEFAULT NULL,
  `email` VARCHAR(160) NULL DEFAULT NULL,
  `address` TEXT NULL,
  `tax_no` VARCHAR(80) NULL DEFAULT NULL,
  `credit_limit` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `credit_days` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_supplier_business_code_uq` (`business_id`,`supplier_code`),
  KEY `rn_supplier_location_idx` (`location_id`),
  KEY `rn_supplier_scope_name_idx` (`business_id`,`location_id`,`name`),
  KEY `rn_supplier_active_idx` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_goods_receipts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `supplier_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `receipt_no` VARCHAR(80) NOT NULL,
  `supplier_invoice_no` VARCHAR(100) NULL DEFAULT NULL,
  `received_date` DATE NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'draft',
  `subtotal` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `discount_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `tax_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `total_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `notes` TEXT NULL,
  `received_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `posted_at` DATETIME NULL DEFAULT NULL,
  `posted_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_goods_receipt_business_no_uq` (`business_id`,`receipt_no`),
  KEY `rn_gr_supplier_idx` (`supplier_id`),
  KEY `rn_gr_scope_date_idx` (`business_id`,`location_id`,`received_date`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_goods_receipt_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `goods_receipt_id` BIGINT UNSIGNED NOT NULL,
  `ingredient_id` BIGINT UNSIGNED NOT NULL,
  `quantity` DECIMAL(22,4) NOT NULL,
  `unit_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `tax_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `line_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `batch_no` VARCHAR(100) NULL DEFAULT NULL,
  `expiry_date` DATE NULL DEFAULT NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rn_grl_business_idx` (`business_id`),
  KEY `rn_grl_receipt_item_idx` (`goods_receipt_id`,`ingredient_id`),
  KEY `rn_grl_expiry_idx` (`expiry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_stock_transfers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `from_location_id` BIGINT UNSIGNED NOT NULL,
  `to_location_id` BIGINT UNSIGNED NOT NULL,
  `transfer_no` VARCHAR(80) NOT NULL,
  `transfer_date` DATE NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'draft',
  `notes` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `dispatched_at` DATETIME NULL DEFAULT NULL,
  `dispatched_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `received_at` DATETIME NULL DEFAULT NULL,
  `received_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_stock_transfer_business_no_uq` (`business_id`,`transfer_no`),
  KEY `rn_transfer_date_idx` (`transfer_date`),
  KEY `rn_transfer_scope_idx` (`business_id`,`from_location_id`,`to_location_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_stock_transfer_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `stock_transfer_id` BIGINT UNSIGNED NOT NULL,
  `ingredient_id` BIGINT UNSIGNED NOT NULL,
  `requested_qty` DECIMAL(22,4) NOT NULL,
  `dispatched_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `received_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `unit_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_stock_transfer_ingredient_uq` (`stock_transfer_id`,`ingredient_id`),
  KEY `rn_transfer_line_business_idx` (`business_id`),
  KEY `rn_transfer_line_ingredient_idx` (`ingredient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_stocktakes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `stocktake_no` VARCHAR(80) NOT NULL,
  `stocktake_date` DATE NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'draft',
  `notes` TEXT NULL,
  `counted_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `posted_at` DATETIME NULL DEFAULT NULL,
  `posted_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_stocktake_business_no_uq` (`business_id`,`stocktake_no`),
  KEY `rn_stocktake_scope_date_idx` (`business_id`,`location_id`,`stocktake_date`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_stocktake_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `stocktake_id` BIGINT UNSIGNED NOT NULL,
  `ingredient_id` BIGINT UNSIGNED NOT NULL,
  `system_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `counted_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `variance_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `unit_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `variance_value` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_stocktake_ingredient_uq` (`stocktake_id`,`ingredient_id`),
  KEY `rn_stocktake_line_business_idx` (`business_id`),
  KEY `rn_stocktake_line_ingredient_idx` (`ingredient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_wastages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `wastage_no` VARCHAR(80) NOT NULL,
  `wastage_date` DATE NOT NULL,
  `reason_code` VARCHAR(50) NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'posted',
  `notes` TEXT NULL,
  `reported_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `approved_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `approved_at` DATETIME NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_wastage_business_no_uq` (`business_id`,`wastage_no`),
  KEY `rn_wastage_scope_date_idx` (`business_id`,`location_id`,`wastage_date`,`reason_code`),
  KEY `rn_wastage_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_wastage_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `wastage_id` BIGINT UNSIGNED NOT NULL,
  `ingredient_id` BIGINT UNSIGNED NOT NULL,
  `quantity` DECIMAL(22,4) NOT NULL,
  `unit_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `value` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `batch_no` VARCHAR(100) NULL DEFAULT NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rn_wastage_line_business_idx` (`business_id`),
  KEY `rn_wastage_line_item_idx` (`wastage_id`,`ingredient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_delivery_zones` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `zone_code` VARCHAR(50) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `minimum_order` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `delivery_fee` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `estimated_minutes` INT UNSIGNED NOT NULL DEFAULT 30,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_delivery_zone_scope_code_uq` (`business_id`,`location_id`,`zone_code`),
  KEY `rn_delivery_zone_active_idx` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_delivery_dispatches` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `delivery_zone_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `driver_user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `dispatch_no` VARCHAR(80) NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'waiting',
  `delivery_address` TEXT NOT NULL,
  `customer_phone` VARCHAR(60) NULL DEFAULT NULL,
  `instructions` TEXT NULL,
  `assigned_at` DATETIME NULL DEFAULT NULL,
  `dispatched_at` DATETIME NULL DEFAULT NULL,
  `delivered_at` DATETIME NULL DEFAULT NULL,
  `cash_to_collect` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `cash_collected` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `created_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_delivery_dispatch_business_no_uq` (`business_id`,`dispatch_no`),
  UNIQUE KEY `restnew_delivery_dispatch_order_uq` (`order_id`),
  KEY `rn_dispatch_zone_idx` (`delivery_zone_id`),
  KEY `rn_dispatch_driver_idx` (`driver_user_id`),
  KEY `rn_dispatch_queue_idx` (`business_id`,`location_id`,`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_discount_rules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `rule_code` VARCHAR(50) NOT NULL,
  `name` VARCHAR(140) NOT NULL,
  `discount_type` VARCHAR(20) NOT NULL DEFAULT 'percentage',
  `discount_value` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `maximum_discount` DECIMAL(22,4) NULL DEFAULT NULL,
  `minimum_order` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `starts_on` DATE NULL DEFAULT NULL,
  `ends_on` DATE NULL DEFAULT NULL,
  `starts_at` TIME NULL DEFAULT NULL,
  `ends_at` TIME NULL DEFAULT NULL,
  `days_json` JSON NULL,
  `order_type` VARCHAR(30) NULL DEFAULT NULL,
  `requires_manager` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_discount_rule_scope_code_uq` (`business_id`,`location_id`,`rule_code`),
  KEY `rn_discount_rule_active_idx` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_discount_usages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `discount_rule_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `rule_code` VARCHAR(50) NULL DEFAULT NULL,
  `rule_name` VARCHAR(140) NULL DEFAULT NULL,
  `discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `reason` VARCHAR(255) NULL DEFAULT NULL,
  `applied_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `approved_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rn_discount_usage_order_idx` (`order_id`),
  KEY `rn_discount_usage_rule_idx` (`discount_rule_id`),
  KEY `rn_discount_usage_date_idx` (`business_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_order_adjustments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `order_item_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `adjustment_type` VARCHAR(40) NOT NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `reason` TEXT NOT NULL,
  `before_json` JSON NULL,
  `after_json` JSON NULL,
  `requested_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `approved_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `approved_at` DATETIME NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rn_adjustment_order_idx` (`order_id`),
  KEY `rn_adjustment_item_idx` (`order_item_id`),
  KEY `rn_adjustment_type_date_idx` (`business_id`,`adjustment_type`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_manager_approvals` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `approval_type` VARCHAR(50) NOT NULL,
  `entity_type` VARCHAR(80) NOT NULL,
  `entity_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'approved',
  `reason` TEXT NULL,
  `payload_json` JSON NULL,
  `requested_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `approved_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `approved_at` DATETIME NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rn_approval_status_idx` (`status`),
  KEY `rn_approval_entity_idx` (`entity_type`,`entity_id`),
  KEY `rn_approval_scope_idx` (`business_id`,`location_id`,`approval_type`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;


-- Restaurant-New Stage 2 idempotent ALTER statements
-- Safe for fresh or upgraded tenant databases.
-- No shared/core table is altered.

DELIMITER $$
DROP PROCEDURE IF EXISTS `restnew_add_column_if_missing`$$
CREATE PROCEDURE `restnew_add_column_if_missing`(
    IN p_table VARCHAR(64),
    IN p_column VARCHAR(64),
    IN p_definition TEXT
)
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = DATABASE() AND table_name = p_table
    ) AND NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = p_table AND column_name = p_column
    ) THEN
        SET @restnew_sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_column, '` ', p_definition);
        PREPARE restnew_stmt FROM @restnew_sql;
        EXECUTE restnew_stmt;
        DEALLOCATE PREPARE restnew_stmt;
    END IF;
END$$

DROP PROCEDURE IF EXISTS `restnew_add_index_if_missing`$$
CREATE PROCEDURE `restnew_add_index_if_missing`(
    IN p_table VARCHAR(64),
    IN p_index VARCHAR(64),
    IN p_columns TEXT
)
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = DATABASE() AND table_name = p_table
    ) AND NOT EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = DATABASE() AND table_name = p_table AND index_name = p_index
    ) THEN
        SET @restnew_sql = CONCAT('ALTER TABLE `', p_table, '` ADD INDEX `', p_index, '` (', p_columns, ')');
        PREPARE restnew_stmt FROM @restnew_sql;
        EXECUTE restnew_stmt;
        DEALLOCATE PREPARE restnew_stmt;
    END IF;
END$$
DELIMITER ;

CALL restnew_add_column_if_missing('restnew_orders','reservation_id','BIGINT UNSIGNED NULL AFTER `table_id`');
CALL restnew_add_column_if_missing('restnew_orders','delivery_zone_id','BIGINT UNSIGNED NULL AFTER `reservation_id`');
CALL restnew_add_column_if_missing('restnew_orders','discount_rule_id','BIGINT UNSIGNED NULL AFTER `delivery_zone_id`');
CALL restnew_add_column_if_missing('restnew_orders','delivery_address','TEXT NULL AFTER `customer_email`');
CALL restnew_add_column_if_missing('restnew_orders','delivery_fee','DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `service_charge_total`');
CALL restnew_add_column_if_missing('restnew_orders','discount_reason','VARCHAR(255) NULL AFTER `discount_total`');
CALL restnew_add_column_if_missing('restnew_orders','discount_authorized_by','BIGINT UNSIGNED NULL AFTER `discount_reason`');
CALL restnew_add_column_if_missing('restnew_orders','discount_authorized_at','DATETIME NULL AFTER `discount_authorized_by`');
CALL restnew_add_index_if_missing('restnew_orders','rn_order_reservation_idx','`reservation_id`');
CALL restnew_add_index_if_missing('restnew_orders','rn_order_delivery_zone_idx','`delivery_zone_id`');
CALL restnew_add_index_if_missing('restnew_orders','rn_order_discount_rule_idx','`discount_rule_id`');

CALL restnew_add_column_if_missing('restnew_reservations','customer_email','VARCHAR(160) NULL AFTER `customer_phone`');
CALL restnew_add_column_if_missing('restnew_reservations','duration_minutes','INT UNSIGNED NOT NULL DEFAULT 90 AFTER `reserved_at`');
CALL restnew_add_column_if_missing('restnew_reservations','source','VARCHAR(30) NOT NULL DEFAULT ''phone'' AFTER `status`');
CALL restnew_add_column_if_missing('restnew_reservations','deposit_amount','DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `source`');
CALL restnew_add_column_if_missing('restnew_reservations','seated_order_id','BIGINT UNSIGNED NULL AFTER `deposit_amount`');
CALL restnew_add_column_if_missing('restnew_reservations','confirmed_at','DATETIME NULL');
CALL restnew_add_column_if_missing('restnew_reservations','seated_at','DATETIME NULL');
CALL restnew_add_column_if_missing('restnew_reservations','cancelled_at','DATETIME NULL');
CALL restnew_add_index_if_missing('restnew_reservations','rn_reservation_seated_order_idx','`seated_order_id`');

CALL restnew_add_column_if_missing('restnew_ingredients','supplier_id','BIGINT UNSIGNED NULL AFTER `business_id`');
CALL restnew_add_column_if_missing('restnew_ingredients','barcode','VARCHAR(100) NULL AFTER `ingredient_code`');
CALL restnew_add_column_if_missing('restnew_ingredients','purchase_unit','VARCHAR(40) NULL AFTER `unit`');
CALL restnew_add_column_if_missing('restnew_ingredients','purchase_conversion','DECIMAL(22,4) NOT NULL DEFAULT 1 AFTER `purchase_unit`');
CALL restnew_add_column_if_missing('restnew_ingredients','track_expiry','TINYINT(1) NOT NULL DEFAULT 0 AFTER `reorder_level`');
CALL restnew_add_index_if_missing('restnew_ingredients','rn_ingredient_supplier_idx','`supplier_id`');
CALL restnew_add_index_if_missing('restnew_ingredients','rn_ingredient_barcode_idx','`barcode`');

CALL restnew_add_column_if_missing('restnew_menu_items','is_delivery','TINYINT(1) NOT NULL DEFAULT 1 AFTER `is_takeaway`');

CALL restnew_add_column_if_missing('restnew_stock_movements','batch_no','VARCHAR(100) NULL AFTER `reference_no`');
CALL restnew_add_column_if_missing('restnew_stock_movements','expiry_date','DATE NULL AFTER `batch_no`');
CALL restnew_add_index_if_missing('restnew_stock_movements','rn_stock_movement_expiry_idx','`expiry_date`');

DROP PROCEDURE IF EXISTS `restnew_add_index_if_missing`;
DROP PROCEDURE IF EXISTS `restnew_add_column_if_missing`;

SELECT 'Restaurant-New Stage 2 ALTER checks completed' AS information;


-- Restaurant-New permissions
-- Safe to run repeatedly: every insert uses a NOT EXISTS condition.

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.access', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.access' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.dashboard.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.dashboard.view' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.manager.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.manager.view' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.waiter.use', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.waiter.use' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.cashier.use', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.cashier.use' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.kitchen.use', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.kitchen.use' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.takeaway.use', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.takeaway.use' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.collection.use', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.collection.use' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.reservations.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.reservations.view' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.reservations.manage', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.reservations.manage' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.delivery.use', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.delivery.use' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.delivery.manage', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.delivery.manage' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.orders.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.orders.view' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.orders.create', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.orders.create' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.orders.edit', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.orders.edit' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.orders.cancel', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.orders.cancel' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.orders.void_item', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.orders.void_item' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.payments.create', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.payments.create' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.payments.refund', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.payments.refund' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.shifts.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.shifts.view' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.shifts.open', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.shifts.open' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.shifts.close', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.shifts.close' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.menu.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.menu.view' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.menu.manage', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.menu.manage' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.discounts.apply', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.discounts.apply' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.discounts.manage', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.discounts.manage' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.discounts.approve', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.discounts.approve' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.setup.manage', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.setup.manage' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.recipes.manage', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.recipes.manage' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.stock.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.stock.view' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.stock.adjust', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.stock.adjust' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.stock.transfer', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.stock.transfer' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.stock.stocktake', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.stock.stocktake' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.stock.wastage', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.stock.wastage' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.procurement.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.procurement.view' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.procurement.manage', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.procurement.manage' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.procurement.post', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.procurement.post' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.reports.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.reports.view' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.reports.export', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.reports.export' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.report.sales', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.report.sales' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.report.item_sales', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.report.item_sales' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.report.kitchen', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.report.kitchen' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.report.cashier', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.report.cashier' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.report.takeaway', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.report.takeaway' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.report.hourly', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.report.hourly' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.report.waiter', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.report.waiter' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.report.discounts', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.report.discounts' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.report.voids', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.report.voids' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.report.stock_usage', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.report.stock_usage' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.report.purchases', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.report.purchases' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.report.wastage', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.report.wastage' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.report.profitability', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.report.profitability' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.documents.print', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.documents.print' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.settings.manage', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.settings.manage' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.screen_assignments.manage', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.screen_assignments.manage' AND `guard_name` = 'web');


-- Restaurant-New installation verification
-- Expected module-owned tables: 45
-- Expected permissions: 55

SELECT COUNT(*) AS restnew_table_count, 45 AS expected_table_count
FROM information_schema.tables
WHERE table_schema = DATABASE() AND table_name LIKE 'restnew\_%';

SELECT expected.table_name AS missing_table
FROM (
  SELECT 'restnew_settings' AS table_name
UNION ALL
  SELECT 'restnew_number_sequences' AS table_name
UNION ALL
  SELECT 'restnew_floors' AS table_name
UNION ALL
  SELECT 'restnew_tables' AS table_name
UNION ALL
  SELECT 'restnew_kitchen_stations' AS table_name
UNION ALL
  SELECT 'restnew_printers' AS table_name
UNION ALL
  SELECT 'restnew_user_screen_assignments' AS table_name
UNION ALL
  SELECT 'restnew_categories' AS table_name
UNION ALL
  SELECT 'restnew_menu_items' AS table_name
UNION ALL
  SELECT 'restnew_modifier_groups' AS table_name
UNION ALL
  SELECT 'restnew_modifiers' AS table_name
UNION ALL
  SELECT 'restnew_menu_item_modifier_groups' AS table_name
UNION ALL
  SELECT 'restnew_ingredients' AS table_name
UNION ALL
  SELECT 'restnew_recipes' AS table_name
UNION ALL
  SELECT 'restnew_recipe_lines' AS table_name
UNION ALL
  SELECT 'restnew_inventory_balances' AS table_name
UNION ALL
  SELECT 'restnew_stock_movements' AS table_name
UNION ALL
  SELECT 'restnew_shifts' AS table_name
UNION ALL
  SELECT 'restnew_orders' AS table_name
UNION ALL
  SELECT 'restnew_order_items' AS table_name
UNION ALL
  SELECT 'restnew_order_item_modifiers' AS table_name
UNION ALL
  SELECT 'restnew_kitchen_tickets' AS table_name
UNION ALL
  SELECT 'restnew_kitchen_ticket_items' AS table_name
UNION ALL
  SELECT 'restnew_order_status_logs' AS table_name
UNION ALL
  SELECT 'restnew_collection_tokens' AS table_name
UNION ALL
  SELECT 'restnew_reservations' AS table_name
UNION ALL
  SELECT 'restnew_print_jobs' AS table_name
UNION ALL
  SELECT 'restnew_payments' AS table_name
UNION ALL
  SELECT 'restnew_daily_closures' AS table_name
UNION ALL
  SELECT 'restnew_audit_logs' AS table_name
UNION ALL
  SELECT 'restnew_suppliers' AS table_name
UNION ALL
  SELECT 'restnew_goods_receipts' AS table_name
UNION ALL
  SELECT 'restnew_goods_receipt_lines' AS table_name
UNION ALL
  SELECT 'restnew_stock_transfers' AS table_name
UNION ALL
  SELECT 'restnew_stock_transfer_lines' AS table_name
UNION ALL
  SELECT 'restnew_stocktakes' AS table_name
UNION ALL
  SELECT 'restnew_stocktake_lines' AS table_name
UNION ALL
  SELECT 'restnew_wastages' AS table_name
UNION ALL
  SELECT 'restnew_wastage_lines' AS table_name
UNION ALL
  SELECT 'restnew_delivery_zones' AS table_name
UNION ALL
  SELECT 'restnew_delivery_dispatches' AS table_name
UNION ALL
  SELECT 'restnew_discount_rules' AS table_name
UNION ALL
  SELECT 'restnew_discount_usages' AS table_name
UNION ALL
  SELECT 'restnew_order_adjustments' AS table_name
UNION ALL
  SELECT 'restnew_manager_approvals' AS table_name
) expected
LEFT JOIN information_schema.tables actual
  ON actual.table_schema = DATABASE() AND actual.table_name = expected.table_name
WHERE actual.table_name IS NULL
ORDER BY expected.table_name;

SELECT COUNT(*) AS restaurant_permission_count, 55 AS expected_permission_count
FROM `permissions`
WHERE `guard_name`='web' AND `name` LIKE 'restaurant\_new.%';

SELECT expected.permission_name AS missing_permission
FROM (
  SELECT 'restaurant_new.access' AS permission_name
UNION ALL
  SELECT 'restaurant_new.dashboard.view' AS permission_name
UNION ALL
  SELECT 'restaurant_new.manager.view' AS permission_name
UNION ALL
  SELECT 'restaurant_new.waiter.use' AS permission_name
UNION ALL
  SELECT 'restaurant_new.cashier.use' AS permission_name
UNION ALL
  SELECT 'restaurant_new.kitchen.use' AS permission_name
UNION ALL
  SELECT 'restaurant_new.takeaway.use' AS permission_name
UNION ALL
  SELECT 'restaurant_new.collection.use' AS permission_name
UNION ALL
  SELECT 'restaurant_new.reservations.view' AS permission_name
UNION ALL
  SELECT 'restaurant_new.reservations.manage' AS permission_name
UNION ALL
  SELECT 'restaurant_new.delivery.use' AS permission_name
UNION ALL
  SELECT 'restaurant_new.delivery.manage' AS permission_name
UNION ALL
  SELECT 'restaurant_new.orders.view' AS permission_name
UNION ALL
  SELECT 'restaurant_new.orders.create' AS permission_name
UNION ALL
  SELECT 'restaurant_new.orders.edit' AS permission_name
UNION ALL
  SELECT 'restaurant_new.orders.cancel' AS permission_name
UNION ALL
  SELECT 'restaurant_new.orders.void_item' AS permission_name
UNION ALL
  SELECT 'restaurant_new.payments.create' AS permission_name
UNION ALL
  SELECT 'restaurant_new.payments.refund' AS permission_name
UNION ALL
  SELECT 'restaurant_new.shifts.view' AS permission_name
UNION ALL
  SELECT 'restaurant_new.shifts.open' AS permission_name
UNION ALL
  SELECT 'restaurant_new.shifts.close' AS permission_name
UNION ALL
  SELECT 'restaurant_new.menu.view' AS permission_name
UNION ALL
  SELECT 'restaurant_new.menu.manage' AS permission_name
UNION ALL
  SELECT 'restaurant_new.discounts.apply' AS permission_name
UNION ALL
  SELECT 'restaurant_new.discounts.manage' AS permission_name
UNION ALL
  SELECT 'restaurant_new.discounts.approve' AS permission_name
UNION ALL
  SELECT 'restaurant_new.setup.manage' AS permission_name
UNION ALL
  SELECT 'restaurant_new.recipes.manage' AS permission_name
UNION ALL
  SELECT 'restaurant_new.stock.view' AS permission_name
UNION ALL
  SELECT 'restaurant_new.stock.adjust' AS permission_name
UNION ALL
  SELECT 'restaurant_new.stock.transfer' AS permission_name
UNION ALL
  SELECT 'restaurant_new.stock.stocktake' AS permission_name
UNION ALL
  SELECT 'restaurant_new.stock.wastage' AS permission_name
UNION ALL
  SELECT 'restaurant_new.procurement.view' AS permission_name
UNION ALL
  SELECT 'restaurant_new.procurement.manage' AS permission_name
UNION ALL
  SELECT 'restaurant_new.procurement.post' AS permission_name
UNION ALL
  SELECT 'restaurant_new.reports.view' AS permission_name
UNION ALL
  SELECT 'restaurant_new.reports.export' AS permission_name
UNION ALL
  SELECT 'restaurant_new.report.sales' AS permission_name
UNION ALL
  SELECT 'restaurant_new.report.item_sales' AS permission_name
UNION ALL
  SELECT 'restaurant_new.report.kitchen' AS permission_name
UNION ALL
  SELECT 'restaurant_new.report.cashier' AS permission_name
UNION ALL
  SELECT 'restaurant_new.report.takeaway' AS permission_name
UNION ALL
  SELECT 'restaurant_new.report.hourly' AS permission_name
UNION ALL
  SELECT 'restaurant_new.report.waiter' AS permission_name
UNION ALL
  SELECT 'restaurant_new.report.discounts' AS permission_name
UNION ALL
  SELECT 'restaurant_new.report.voids' AS permission_name
UNION ALL
  SELECT 'restaurant_new.report.stock_usage' AS permission_name
UNION ALL
  SELECT 'restaurant_new.report.purchases' AS permission_name
UNION ALL
  SELECT 'restaurant_new.report.wastage' AS permission_name
UNION ALL
  SELECT 'restaurant_new.report.profitability' AS permission_name
UNION ALL
  SELECT 'restaurant_new.documents.print' AS permission_name
UNION ALL
  SELECT 'restaurant_new.settings.manage' AS permission_name
UNION ALL
  SELECT 'restaurant_new.screen_assignments.manage' AS permission_name
) expected
LEFT JOIN `permissions` actual
  ON actual.guard_name='web' AND actual.name=expected.permission_name
WHERE actual.id IS NULL
ORDER BY expected.permission_name;
