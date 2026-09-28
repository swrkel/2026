-- CUS_SEP_003 Customer Audit / Attachments / Activity Separation
-- Run once in each tenant database before testing Notes / Documents / Audit actions.

CREATE TABLE IF NOT EXISTS `customer_notes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `customer_id` INT UNSIGNED NOT NULL,
  `note` TEXT NOT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_notes_business_customer_index` (`business_id`, `customer_id`),
  KEY `customer_notes_created_by_index` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `customer_activities` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `customer_id` INT UNSIGNED NOT NULL,
  `event` VARCHAR(191) NOT NULL,
  `description` TEXT NULL,
  `properties` JSON NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_activities_business_customer_index` (`business_id`, `customer_id`),
  KEY `customer_activities_event_index` (`business_id`, `event`),
  KEY `customer_activities_created_by_index` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `customer_attachments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `customer_id` INT UNSIGNED NOT NULL,
  `filename` VARCHAR(255) NOT NULL,
  `original_filename` VARCHAR(255) NULL,
  `path` VARCHAR(500) NOT NULL,
  `mime_type` VARCHAR(191) NULL,
  `size` BIGINT UNSIGNED NULL,
  `remarks` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_attachments_business_customer_index` (`business_id`, `customer_id`),
  KEY `customer_attachments_created_by_index` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
