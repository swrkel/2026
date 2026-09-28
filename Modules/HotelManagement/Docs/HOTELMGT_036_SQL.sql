-- HOTELMGT_036_SQL.sql
-- Hotel Management Parcel 036: Energy & Utilities
-- Global SQL only. Execute inside each tenant database as needed. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `hm_utility_meters` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `meter_no` VARCHAR(60) NOT NULL,
  `meter_name` VARCHAR(160) NOT NULL,
  `utility_type` VARCHAR(60) NOT NULL DEFAULT 'electricity',
  `department` VARCHAR(100) NULL,
  `linked_room_id` BIGINT UNSIGNED NULL,
  `unit_name` VARCHAR(40) NOT NULL DEFAULT 'Unit',
  `rate_per_unit` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_utility_meters_no_unique` (`business_id`,`meter_no`),
  KEY `hm_utility_meters_scope_idx` (`business_id`,`business_location_id`,`utility_type`,`is_active`),
  KEY `hm_utility_meters_room_idx` (`linked_room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_utility_readings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `reading_no` VARCHAR(60) NOT NULL,
  `meter_id` BIGINT UNSIGNED NOT NULL,
  `reading_date` DATE NOT NULL,
  `previous_reading` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `current_reading` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `consumption_units` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `rate_per_unit` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `total_cost` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(40) NOT NULL DEFAULT 'draft',
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_utility_readings_no_unique` (`business_id`,`reading_no`),
  KEY `hm_utility_readings_scope_idx` (`business_id`,`business_location_id`,`reading_date`,`status`),
  KEY `hm_utility_readings_meter_idx` (`meter_id`,`reading_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_utility_cost_allocations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `allocation_no` VARCHAR(60) NOT NULL,
  `reading_id` BIGINT UNSIGNED NULL,
  `allocation_type` VARCHAR(60) NOT NULL DEFAULT 'department',
  `target_reference` VARCHAR(160) NULL,
  `allocated_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(40) NOT NULL DEFAULT 'draft',
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_utility_allocations_no_unique` (`business_id`,`allocation_no`),
  KEY `hm_utility_allocations_scope_idx` (`business_id`,`business_location_id`,`allocation_type`,`status`),
  KEY `hm_utility_allocations_reading_idx` (`reading_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
