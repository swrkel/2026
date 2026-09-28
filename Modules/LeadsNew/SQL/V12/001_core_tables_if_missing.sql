-- Leads-New V12 core tenant tables
-- Run on the ACTIVE TENANT DATABASE only. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `leads_new_leads` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `lead_no` VARCHAR(191) NOT NULL,
  `name` VARCHAR(191) NOT NULL,
  `mobile` VARCHAR(50) NULL,
  `email` VARCHAR(191) NULL,
  `nic` VARCHAR(100) NULL,
  `source` VARCHAR(100) NULL,
  `status` VARCHAR(100) NULL DEFAULT 'New',
  `priority` VARCHAR(50) NULL DEFAULT 'Medium',
  `transaction_date` DATE NULL,
  `next_followup_at` DATETIME NULL,
  `note` TEXT NULL,
  `is_archived` TINYINT(1) NOT NULL DEFAULT 0,
  `archived_at` DATETIME NULL,
  `archived_by` BIGINT UNSIGNED NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `leads_new_leads_lead_no_unique` (`lead_no`),
  KEY `leads_new_leads_business_id_index` (`business_id`),
  KEY `leads_new_leads_location_id_index` (`location_id`),
  KEY `leads_new_leads_mobile_index` (`mobile`),
  KEY `leads_new_leads_nic_index` (`nic`),
  KEY `leads_new_leads_source_index` (`source`),
  KEY `leads_new_leads_status_index` (`status`),
  KEY `leads_new_leads_priority_index` (`priority`),
  KEY `leads_new_leads_next_followup_at_index` (`next_followup_at`),
  KEY `leads_new_leads_is_archived_index` (`is_archived`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `leads_new_followups` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `lead_id` BIGINT UNSIGNED NULL,
  `title` VARCHAR(191) NULL,
  `note` TEXT NULL,
  `followup_at` DATETIME NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'Pending',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leads_new_followups_business_id_index` (`business_id`),
  KEY `leads_new_followups_lead_id_index` (`lead_id`),
  KEY `leads_new_followups_followup_at_index` (`followup_at`),
  KEY `leads_new_followups_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `leads_new_opportunities` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `lead_id` BIGINT UNSIGNED NULL,
  `title` VARCHAR(191) NULL,
  `stage` VARCHAR(100) NULL DEFAULT 'New',
  `expected_value` DECIMAL(18,4) NULL DEFAULT 0,
  `probability` DECIMAL(5,2) NULL DEFAULT 0,
  `expected_close_date` DATE NULL,
  `status` VARCHAR(50) NULL DEFAULT 'Open',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leads_new_opportunities_business_id_index` (`business_id`),
  KEY `leads_new_opportunities_lead_id_index` (`lead_id`),
  KEY `leads_new_opportunities_stage_index` (`stage`),
  KEY `leads_new_opportunities_status_index` (`status`)
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
  KEY `leads_new_activities_lead_id_index` (`lead_id`),
  KEY `leads_new_activities_activity_date_index` (`activity_date`),
  KEY `leads_new_activities_type_index` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
