-- Audit Module - Master SQL
-- MySQL 5.7+/MariaDB compatible. All module-owned tables use the required audit_ prefix.
-- Import into EACH TENANT database that will use the Audit module.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS `audit_runs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `run_no` VARCHAR(50) NOT NULL,
  `tenant_key` VARCHAR(100) NULL,
  `business_id` BIGINT UNSIGNED NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `started_by` BIGINT UNSIGNED NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'running',
  `started_at` DATETIME NULL,
  `finished_at` DATETIME NULL,
  `context` LONGTEXT NULL,
  `summary` LONGTEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `audit_runs_run_no_unique` (`run_no`),
  KEY `audit_runs_tenant_key_index` (`tenant_key`), KEY `audit_runs_business_id_index` (`business_id`),
  KEY `audit_runs_location_id_index` (`location_id`), KEY `audit_runs_status_index` (`status`), KEY `audit_runs_started_at_index` (`started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `audit_findings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `audit_run_id` BIGINT UNSIGNED NULL,
  `finding_no` VARCHAR(60) NOT NULL,
  `fingerprint` VARCHAR(64) NOT NULL,
  `rule_code` VARCHAR(100) NOT NULL,
  `module` VARCHAR(100) NOT NULL,
  `tenant_key` VARCHAR(100) NULL,
  `business_id` BIGINT UNSIGNED NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `source_table` VARCHAR(120) NULL,
  `source_id` VARCHAR(120) NULL,
  `severity` VARCHAR(30) NOT NULL DEFAULT 'warning',
  `status` VARCHAR(30) NOT NULL DEFAULT 'open',
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NULL,
  `expected_value` TEXT NULL,
  `actual_value` TEXT NULL,
  `payload` LONGTEXT NULL,
  `first_seen_at` DATETIME NULL,
  `last_seen_at` DATETIME NULL,
  `resolved_at` DATETIME NULL,
  `resolved_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `audit_findings_finding_no_unique` (`finding_no`), UNIQUE KEY `audit_findings_fingerprint_unique` (`fingerprint`),
  KEY `audit_findings_run_index` (`audit_run_id`), KEY `audit_findings_rule_index` (`rule_code`), KEY `audit_findings_module_index` (`module`),
  KEY `audit_findings_business_index` (`business_id`), KEY `audit_findings_location_index` (`location_id`), KEY `audit_findings_severity_index` (`severity`),
  KEY `audit_findings_status_index` (`status`), KEY `audit_findings_first_seen_index` (`first_seen_at`), KEY `audit_findings_last_seen_index` (`last_seen_at`),
  KEY `audit_findings_context_idx` (`business_id`,`location_id`,`module`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `audit_finding_history` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `audit_finding_id` BIGINT UNSIGNED NOT NULL,
  `from_status` VARCHAR(30) NULL,
  `to_status` VARCHAR(30) NOT NULL,
  `note` TEXT NULL,
  `changed_by` BIGINT UNSIGNED NULL,
  `meta` LONGTEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`), KEY `audit_finding_history_finding_index` (`audit_finding_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `audit_resolutions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `audit_finding_id` BIGINT UNSIGNED NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `note` TEXT NULL,
  `before_data` LONGTEXT NULL,
  `after_data` LONGTEXT NULL,
  `performed_by` BIGINT UNSIGNED NULL,
  `performed_at` DATETIME NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`), KEY `audit_resolutions_finding_index` (`audit_finding_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `audit_rule_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `rule_code` VARCHAR(100) NOT NULL,
  `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `severity_override` VARCHAR(30) NULL,
  `settings` LONGTEXT NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `audit_rule_business_unique` (`business_id`,`rule_code`), KEY `audit_rule_code_index` (`rule_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `audit_schedules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(150) NOT NULL,
  `frequency` VARCHAR(30) NOT NULL,
  `run_time` VARCHAR(10) NULL,
  `modules` LONGTEXT NULL,
  `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `last_run_at` DATETIME NULL,
  `next_run_at` DATETIME NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`), KEY `audit_schedules_business_index` (`business_id`), KEY `audit_schedules_enabled_index` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `audit_exclusions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `rule_code` VARCHAR(100) NOT NULL,
  `source_table` VARCHAR(120) NULL,
  `source_id` VARCHAR(120) NULL,
  `reason` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `expires_at` DATETIME NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`), KEY `audit_exclusions_business_index` (`business_id`), KEY `audit_exclusions_location_index` (`location_id`), KEY `audit_exclusions_rule_index` (`rule_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;
