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


-- Management Report Module - TENANT DATABASE ALTER TABLES
-- No existing ERP or central database table is altered.
-- All module-owned tables use the mgmt_ prefix and live in each tenant database.
SELECT DATABASE() AS `tenant_database`,
       'No legacy table alterations required' AS `message`;


-- Management Report Module - TENANT DATABASE DEFAULT DATA
-- Safe to run repeatedly. INSERT IGNORE uses the business/name unique key.
-- Inserts a standard template for every business in the currently selected tenant DB.

INSERT IGNORE INTO `mgmt_report_templates`
(`business_id`,`location_id`,`store_id`,`name`,`report_type`,`section_keys`,`filter_defaults`,`is_default`,`is_active`,`created_at`,`updated_at`)
SELECT `id`, NULL, NULL, 'Standard Daily Management Report', 'daily_management',
       JSON_ARRAY('sales','operator_sales','received_in','out','total_add','returns','financial_status','financial_status_two','financial_breakup','outstanding','stock_value','pump_variance','dip_details','final_review'),
       JSON_OBJECT('date_range','today'), 1, 1, NOW(), NOW()
FROM `business`;

SELECT DATABASE() AS `tenant_database`,
       COUNT(*) AS `management_report_templates`
FROM `mgmt_report_templates`;

-- Final verification: all seven Management Report tables must exist in this tenant DB.
SELECT DATABASE() AS `tenant_database`,
       SUM(TABLE_NAME = 'mgmt_report_templates') AS `mgmt_report_templates`,
       SUM(TABLE_NAME = 'mgmt_report_runs') AS `mgmt_report_runs`,
       SUM(TABLE_NAME = 'mgmt_report_run_sections') AS `mgmt_report_run_sections`,
       SUM(TABLE_NAME = 'mgmt_report_shares') AS `mgmt_report_shares`,
       SUM(TABLE_NAME = 'mgmt_report_share_recipients') AS `mgmt_report_share_recipients`,
       SUM(TABLE_NAME = 'mgmt_report_settings') AS `mgmt_report_settings`,
       SUM(TABLE_NAME = 'mgmt_report_review_statuses') AS `mgmt_report_review_statuses`
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN (
      'mgmt_report_templates',
      'mgmt_report_runs',
      'mgmt_report_run_sections',
      'mgmt_report_shares',
      'mgmt_report_share_recipients',
      'mgmt_report_settings',
      'mgmt_report_review_statuses'
  );
