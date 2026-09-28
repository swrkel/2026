-- HOTELMGT_038_SQL.sql
-- Hotel Management Parcel 038: Sustainability & Waste Management
-- Global SQL only. Execute within each tenant database. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `hm_sustainability_goals` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `goal_name` VARCHAR(160) NOT NULL,
  `category` VARCHAR(80) NULL,
  `target_value` DECIMAL(20,4) NOT NULL DEFAULT 0.0000,
  `unit` VARCHAR(40) NULL,
  `start_date` DATE NULL,
  `end_date` DATE NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'active',
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_sustainability_goals_business_idx` (`business_id`),
  KEY `hm_sustainability_goals_location_idx` (`business_location_id`),
  KEY `hm_sustainability_goals_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_waste_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `log_date` DATE NULL,
  `department` VARCHAR(100) NULL,
  `waste_type` VARCHAR(80) NOT NULL,
  `quantity` DECIMAL(20,4) NOT NULL DEFAULT 0.0000,
  `unit` VARCHAR(40) NULL,
  `disposal_method` VARCHAR(120) NULL,
  `cost` DECIMAL(20,4) NOT NULL DEFAULT 0.0000,
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_waste_logs_business_idx` (`business_id`),
  KEY `hm_waste_logs_location_idx` (`business_location_id`),
  KEY `hm_waste_logs_date_idx` (`log_date`),
  KEY `hm_waste_logs_type_idx` (`waste_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_green_initiatives` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `initiative_no` VARCHAR(60) NULL,
  `title` VARCHAR(160) NOT NULL,
  `category` VARCHAR(80) NULL,
  `owner_name` VARCHAR(160) NULL,
  `planned_start` DATE NULL,
  `planned_end` DATE NULL,
  `estimated_saving` DECIMAL(20,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(40) NOT NULL DEFAULT 'planned',
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_green_initiatives_business_idx` (`business_id`),
  KEY `hm_green_initiatives_location_idx` (`business_location_id`),
  KEY `hm_green_initiatives_status_idx` (`status`),
  KEY `hm_green_initiatives_no_idx` (`initiative_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
