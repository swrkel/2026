-- POS Standalone S385 - Enterprise Reliability & Disaster Recovery
-- Global SQL: run inside each tenant database. Do not hardcode database names.

CREATE TABLE IF NOT EXISTS `pos_sync_reliability_tests` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `device_uuid` VARCHAR(191) NULL,
  `scenario` VARCHAR(191) NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `notes` TEXT NULL,
  `tested_by` BIGINT UNSIGNED NULL,
  `tested_at` DATETIME NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_sync_reliability_tests_device_status_idx` (`device_uuid`, `status`),
  KEY `pos_sync_reliability_tests_business_status_idx` (`business_id`, `location_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_sync_integrity_snapshots` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `device_uuid` VARCHAR(191) NULL,
  `snapshot_type` VARCHAR(80) NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `summary_json` LONGTEXT NULL,
  `checked_at` DATETIME NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_sync_integrity_snapshots_device_status_idx` (`device_uuid`, `status`),
  KEY `pos_sync_integrity_snapshots_checked_idx` (`checked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional safety columns for existing queue table.
ALTER TABLE `pos_offline_sync_queue`
  ADD COLUMN IF NOT EXISTS `transaction_hash` VARCHAR(191) NULL AFTER `client_token`,
  ADD COLUMN IF NOT EXISTS `dependency_key` VARCHAR(191) NULL AFTER `transaction_hash`,
  ADD COLUMN IF NOT EXISTS `batch_id` VARCHAR(191) NULL AFTER `dependency_key`,
  ADD COLUMN IF NOT EXISTS `locked_at` DATETIME NULL AFTER `batch_id`,
  ADD COLUMN IF NOT EXISTS `locked_by` VARCHAR(191) NULL AFTER `locked_at`;

CREATE INDEX IF NOT EXISTS `pos_offline_sync_queue_hash_idx` ON `pos_offline_sync_queue` (`transaction_hash`);
CREATE INDEX IF NOT EXISTS `pos_offline_sync_queue_dependency_idx` ON `pos_offline_sync_queue` (`dependency_key`);
CREATE INDEX IF NOT EXISTS `pos_offline_sync_queue_batch_idx` ON `pos_offline_sync_queue` (`batch_id`);
