-- Leads-New V9 operational core tables
-- Run on the ACTIVE TENANT DATABASE only. Do not run on the central database.
-- No database name is hardcoded so the script uses the database selected in phpMyAdmin/Adminer.

CREATE TABLE IF NOT EXISTS `leads_new_sources` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NOT NULL,
  `color` VARCHAR(20) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leads_new_sources_business_id_index` (`business_id`),
  KEY `leads_new_sources_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `leads_new_statuses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NOT NULL,
  `color` VARCHAR(20) NULL,
  `is_final` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leads_new_statuses_business_id_index` (`business_id`),
  KEY `leads_new_statuses_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `leads_new_priorities` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NOT NULL,
  `color` VARCHAR(20) NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leads_new_priorities_business_id_index` (`business_id`),
  KEY `leads_new_priorities_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `leads_new_campaigns` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NOT NULL,
  `start_date` DATE NULL,
  `end_date` DATE NULL,
  `budget` DECIMAL(18,4) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leads_new_campaigns_business_id_index` (`business_id`),
  KEY `leads_new_campaigns_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `leads_new_territories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NOT NULL,
  `description` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leads_new_territories_business_id_index` (`business_id`),
  KEY `leads_new_territories_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `leads_new_activities` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `lead_id` BIGINT UNSIGNED NULL,
  `opportunity_id` BIGINT UNSIGNED NULL,
  `type` VARCHAR(50) NOT NULL DEFAULT 'note',
  `title` VARCHAR(191) NULL,
  `description` TEXT NULL,
  `activity_date` DATETIME NULL,
  `assigned_to` BIGINT UNSIGNED NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `meta` JSON NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leads_new_activities_business_id_index` (`business_id`),
  KEY `leads_new_activities_location_id_index` (`location_id`),
  KEY `leads_new_activities_lead_id_index` (`lead_id`),
  KEY `leads_new_activities_opportunity_id_index` (`opportunity_id`),
  KEY `leads_new_activities_type_index` (`type`),
  KEY `leads_new_activities_activity_date_index` (`activity_date`),
  KEY `leads_new_activities_assigned_to_index` (`assigned_to`),
  KEY `leads_new_activities_created_by_index` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
