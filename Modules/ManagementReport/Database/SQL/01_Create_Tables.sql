-- Management Report Module - DYNAMIC TENANT DATABASE CREATE TABLES
-- IMPORTANT: this SQL does not contain a fixed USE/database name.
-- Select the required tenant database before importing this file.
-- Safe to run repeatedly: every table uses CREATE TABLE IF NOT EXISTS.
-- Do not import this file into the central/base database.

SELECT DATABASE() AS `selected_tenant_database`;

CREATE TABLE IF NOT EXISTS `mgmt_report_templates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(150) NOT NULL,
  `report_type` VARCHAR(80) NOT NULL DEFAULT 'daily_management',
  `section_keys` JSON NULL,
  `filter_defaults` JSON NULL,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mgmt_tpl_business_name_unique` (`business_id`,`name`),
  KEY `mgmt_tpl_scope_idx` (`business_id`,`location_id`,`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mgmt_report_runs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` CHAR(36) NOT NULL,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `shift_id` BIGINT UNSIGNED NULL,
  `report_type` VARCHAR(80) NOT NULL DEFAULT 'daily_management',
  `report_title` VARCHAR(190) NOT NULL,
  `period_start` DATE NOT NULL,
  `period_end` DATE NOT NULL,
  `filter_payload` JSON NULL,
  `snapshot_payload` LONGTEXT NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'generated',
  `review_status` VARCHAR(40) NOT NULL DEFAULT 'pending',
  `generated_by` BIGINT UNSIGNED NULL,
  `generated_at` TIMESTAMP NULL,
  `reviewed_by` BIGINT UNSIGNED NULL,
  `reviewed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mgmt_runs_uuid_unique` (`uuid`),
  KEY `mgmt_runs_scope_period_idx` (`business_id`,`period_start`,`period_end`),
  KEY `mgmt_runs_location_idx` (`location_id`),
  KEY `mgmt_runs_store_idx` (`store_id`),
  KEY `mgmt_runs_shift_idx` (`shift_id`),
  KEY `mgmt_runs_status_idx` (`status`),
  KEY `mgmt_runs_review_idx` (`review_status`),
  KEY `mgmt_runs_generated_at_idx` (`generated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mgmt_report_run_sections` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `report_run_id` BIGINT UNSIGNED NOT NULL,
  `section_key` VARCHAR(80) NOT NULL,
  `section_label` VARCHAR(190) NOT NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `section_payload` LONGTEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mgmt_run_section_unique` (`report_run_id`,`section_key`),
  KEY `mgmt_run_section_sort_idx` (`report_run_id`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mgmt_report_shares` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `report_run_id` BIGINT UNSIGNED NOT NULL,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `channel` VARCHAR(30) NOT NULL,
  `token` CHAR(64) NOT NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'pending',
  `provider` VARCHAR(120) NULL,
  `message_body` TEXT NULL,
  `provider_response` LONGTEXT NULL,
  `failure_reason` TEXT NULL,
  `view_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `last_viewed_at` TIMESTAMP NULL,
  `expires_at` TIMESTAMP NULL,
  `delivered_at` TIMESTAMP NULL,
  `failed_at` TIMESTAMP NULL,
  `revoked_at` TIMESTAMP NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `revoked_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mgmt_shares_token_unique` (`token`),
  KEY `mgmt_shares_scope_idx` (`business_id`,`channel`,`status`),
  KEY `mgmt_shares_run_idx` (`report_run_id`),
  KEY `mgmt_shares_expires_idx` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mgmt_report_share_recipients` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `report_share_id` BIGINT UNSIGNED NOT NULL,
  `recipient` VARCHAR(190) NOT NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'pending',
  `provider_message_id` VARCHAR(190) NULL,
  `provider_response` LONGTEXT NULL,
  `failure_reason` TEXT NULL,
  `delivered_at` TIMESTAMP NULL,
  `failed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `mgmt_share_recipient_share_idx` (`report_share_id`),
  KEY `mgmt_share_recipient_status_idx` (`status`),
  KEY `mgmt_share_recipient_message_idx` (`provider_message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mgmt_report_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `setting_key` VARCHAR(120) NOT NULL,
  `setting_value` LONGTEXT NULL,
  `is_encrypted` TINYINT(1) NOT NULL DEFAULT 0,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mgmt_setting_scope_unique` (`business_id`,`location_id`,`store_id`,`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mgmt_report_review_statuses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `report_run_id` BIGINT UNSIGNED NOT NULL,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `review_status` VARCHAR(40) NOT NULL,
  `review_notes` TEXT NULL,
  `reviewed_by` BIGINT UNSIGNED NULL,
  `reviewed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `mgmt_review_run_idx` (`report_run_id`),
  KEY `mgmt_review_scope_idx` (`business_id`,`review_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
