-- =========================================================
-- HOTELMGT_042 - Assets & Equipment Management
-- Specific SQL only for parcel HOTELMGT_042
-- Global tenant SQL: run inside each tenant database. Do not hardcode database names.
-- =========================================================

CREATE TABLE IF NOT EXISTS `hm_assets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `asset_code` VARCHAR(60) NOT NULL,
  `asset_name` VARCHAR(191) NOT NULL,
  `asset_category` VARCHAR(100) NOT NULL,
  `department` VARCHAR(100) NULL,
  `room_id` BIGINT UNSIGNED NULL,
  `serial_no` VARCHAR(100) NULL,
  `purchase_date` DATE NULL,
  `purchase_cost` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `current_value` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(40) NOT NULL DEFAULT 'active',
  `disposal_date` DATE NULL,
  `disposal_value` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `disposal_reason` VARCHAR(191) NULL,
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_assets_business_asset_code_unique` (`business_id`, `asset_code`),
  KEY `hm_assets_scope_idx` (`business_id`, `business_location_id`, `asset_category`, `department`, `status`),
  KEY `hm_assets_room_idx` (`business_id`, `room_id`),
  KEY `hm_assets_serial_idx` (`business_id`, `serial_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_asset_assignments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `asset_id` BIGINT UNSIGNED NOT NULL,
  `assignment_no` VARCHAR(60) NOT NULL,
  `assigned_to_type` VARCHAR(40) NOT NULL DEFAULT 'room',
  `assigned_to_id` BIGINT UNSIGNED NULL,
  `room_id` BIGINT UNSIGNED NULL,
  `assigned_date` DATE NOT NULL,
  `return_due_date` DATE NULL,
  `returned_date` DATE NULL,
  `condition_out` VARCHAR(40) NOT NULL DEFAULT 'good',
  `condition_in` VARCHAR(40) NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'active',
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_asset_assignments_business_no_unique` (`business_id`, `assignment_no`),
  KEY `hm_asset_assignments_scope_idx` (`business_id`, `business_location_id`, `asset_id`, `status`),
  KEY `hm_asset_assignments_room_idx` (`business_id`, `room_id`),
  KEY `hm_asset_assignments_assigned_to_idx` (`business_id`, `assigned_to_type`, `assigned_to_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_asset_inspections` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `asset_id` BIGINT UNSIGNED NOT NULL,
  `inspection_no` VARCHAR(60) NOT NULL,
  `inspection_date` DATE NOT NULL,
  `condition_status` VARCHAR(40) NOT NULL DEFAULT 'good',
  `next_inspection_date` DATE NULL,
  `maintenance_required` TINYINT(1) NOT NULL DEFAULT 0,
  `estimated_cost` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_asset_inspections_business_no_unique` (`business_id`, `inspection_no`),
  KEY `hm_asset_inspections_scope_idx` (`business_id`, `business_location_id`, `asset_id`, `inspection_date`),
  KEY `hm_asset_inspections_due_idx` (`business_id`, `next_inspection_date`, `maintenance_required`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
