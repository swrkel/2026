-- POS Standalone S379 - Offline / Online Sync Foundation
-- Run in every tenant database that will use POS offline mode.
-- No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `pos_offline_sync_queue` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `device_uuid` VARCHAR(80) NOT NULL,
  `terminal_code` VARCHAR(80) NULL,
  `client_token` VARCHAR(120) NOT NULL,
  `offline_invoice_no` VARCHAR(120) NULL,
  `server_invoice_no` VARCHAR(120) NULL,
  `transaction_type` VARCHAR(40) NOT NULL DEFAULT 'sale',
  `payload` LONGTEXT NOT NULL,
  `server_response` LONGTEXT NULL,
  `status` ENUM('pending','synced','failed','conflict') NOT NULL DEFAULT 'pending',
  `attempt_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `last_error` TEXT NULL,
  `created_offline_at` DATETIME NULL,
  `synced_at` DATETIME NULL,
  `failed_at` DATETIME NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pos_offline_sync_queue_client_token_unique` (`client_token`),
  KEY `pos_offline_sync_queue_device_status_idx` (`device_uuid`, `status`),
  KEY `pos_offline_sync_queue_invoice_idx` (`offline_invoice_no`),
  KEY `pos_offline_sync_queue_business_status_idx` (`business_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_offline_sync_conflicts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue_id` BIGINT UNSIGNED NULL,
  `business_id` BIGINT UNSIGNED NULL,
  `device_uuid` VARCHAR(80) NULL,
  `offline_invoice_no` VARCHAR(120) NULL,
  `conflict_type` VARCHAR(80) NOT NULL,
  `conflict_message` TEXT NOT NULL,
  `payload` LONGTEXT NULL,
  `server_snapshot` LONGTEXT NULL,
  `resolution_status` ENUM('open','resolved','ignored') NOT NULL DEFAULT 'open',
  `resolution_note` TEXT NULL,
  `resolved_by` BIGINT UNSIGNED NULL,
  `resolved_at` DATETIME NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_offline_sync_conflicts_queue_idx` (`queue_id`),
  KEY `pos_offline_sync_conflicts_status_idx` (`resolution_status`),
  KEY `pos_offline_sync_conflicts_invoice_idx` (`offline_invoice_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `pos_devices`
  ADD COLUMN IF NOT EXISTS `offline_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `offline_device_uuid` VARCHAR(80) NULL,
  ADD COLUMN IF NOT EXISTS `offline_invoice_prefix` VARCHAR(20) NULL,
  ADD COLUMN IF NOT EXISTS `last_sync_at` DATETIME NULL;

CREATE UNIQUE INDEX IF NOT EXISTS `pos_devices_offline_uuid_unique` ON `pos_devices` (`offline_device_uuid`);
