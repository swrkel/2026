-- POS Standalone S370 - Configuration & Administration
-- Global tenant SQL. Run inside each tenant database. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `pos_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `location_id` BIGINT UNSIGNED NULL,
  `setting_group` VARCHAR(80) NOT NULL,
  `setting_key` VARCHAR(120) NOT NULL,
  `setting_value` TEXT NULL,
  `value_type` VARCHAR(30) NOT NULL DEFAULT 'string',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pos_settings_unique_key` (`business_id`,`location_id`,`setting_group`,`setting_key`),
  KEY `pos_settings_group_idx` (`business_id`,`setting_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `location_id` BIGINT UNSIGNED NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `module_area` VARCHAR(80) NOT NULL DEFAULT 'pos',
  `action` VARCHAR(120) NOT NULL,
  `reference_type` VARCHAR(120) NULL,
  `reference_id` BIGINT UNSIGNED NULL,
  `description` TEXT NULL,
  `before_payload` LONGTEXT NULL,
  `after_payload` LONGTEXT NULL,
  `ip_address` VARCHAR(80) NULL,
  `user_agent` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_audit_business_action_idx` (`business_id`,`action`),
  KEY `pos_audit_reference_idx` (`reference_type`,`reference_id`),
  KEY `pos_audit_created_idx` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
