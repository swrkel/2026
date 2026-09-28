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
