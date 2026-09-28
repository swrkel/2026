-- Petro PD-New 1.0.0
-- Standalone tenant schema. Safe to run repeatedly.
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `pdnew_integration_outbox` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `aggregate_type` VARCHAR(50) NOT NULL,
  `aggregate_id` BIGINT UNSIGNED NOT NULL,
  `event_type` VARCHAR(80) NOT NULL,
  `idempotency_key` CHAR(64) NOT NULL,
  `payload` LONGTEXT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `attempts` INT UNSIGNED NOT NULL DEFAULT 0,
  `available_at` TIMESTAMP NULL DEFAULT NULL,
  `processed_at` TIMESTAMP NULL DEFAULT NULL,
  `last_error` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdnew_outbox_key_uq` (`idempotency_key`),
  KEY `pdnew_outbox_status_idx` (`business_id`,`status`,`available_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_integration_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `direction` VARCHAR(20) NOT NULL,
  `operation` VARCHAR(80) NOT NULL,
  `aggregate_type` VARCHAR(50) NULL,
  `aggregate_id` BIGINT UNSIGNED NULL,
  `status` VARCHAR(30) NOT NULL,
  `payload` LONGTEXT NULL,
  `response` LONGTEXT NULL,
  `error_message` TEXT NULL,
  `started_at` TIMESTAMP NULL DEFAULT NULL,
  `completed_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pdnew_integration_log_idx` (`business_id`,`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_notification_templates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `event_key` VARCHAR(80) NOT NULL,
  `name` VARCHAR(191) NOT NULL,
  `subject` VARCHAR(255) NULL,
  `body` LONGTEXT NOT NULL,
  `channels` LONGTEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdnew_notification_event_uq` (`business_id`,`event_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_notification_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `template_id` BIGINT UNSIGNED NULL,
  `settlement_id` BIGINT UNSIGNED NULL,
  `event_key` VARCHAR(80) NOT NULL,
  `channel` VARCHAR(30) NOT NULL,
  `recipient` VARCHAR(191) NULL,
  `status` VARCHAR(30) NOT NULL,
  `payload` LONGTEXT NULL,
  `response` TEXT NULL,
  `sent_at` TIMESTAMP NULL DEFAULT NULL,
  `failed_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pdnew_notification_log_idx` (`business_id`,`event_key`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_print_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `document_type` VARCHAR(50) NOT NULL,
  `document_id` BIGINT UNSIGNED NOT NULL,
  `print_type` VARCHAR(30) NOT NULL DEFAULT 'original',
  `printed_by` INT UNSIGNED NOT NULL,
  `printed_at` TIMESTAMP NOT NULL,
  `ip_address` VARCHAR(64) NULL,
  `metadata` LONGTEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pdnew_print_document_idx` (`business_id`,`document_type`,`document_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `user_id` INT UNSIGNED NULL,
  `action` VARCHAR(100) NOT NULL,
  `entity_type` VARCHAR(100) NULL,
  `entity_id` BIGINT UNSIGNED NULL,
  `old_values` LONGTEXT NULL,
  `new_values` LONGTEXT NULL,
  `ip_address` VARCHAR(64) NULL,
  `user_agent` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pdnew_audit_entity_idx` (`business_id`,`entity_type`,`entity_id`),
  KEY `pdnew_audit_date_idx` (`business_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_documents` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `settlement_id` BIGINT UNSIGNED NULL,
  `document_type` VARCHAR(50) NOT NULL,
  `title` VARCHAR(191) NOT NULL,
  `disk` VARCHAR(50) NOT NULL DEFAULT 'public',
  `path` TEXT NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `mime_type` VARCHAR(100) NULL,
  `size_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `metadata` LONGTEXT NULL,
  `uploaded_by` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pdnew_document_settlement_idx` (`business_id`,`settlement_id`,`document_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdnew_saved_report_filters` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `report_key` VARCHAR(80) NOT NULL,
  `name` VARCHAR(191) NOT NULL,
  `filters` LONGTEXT NOT NULL,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pdnew_saved_filter_uq` (`business_id`,`user_id`,`report_key`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
