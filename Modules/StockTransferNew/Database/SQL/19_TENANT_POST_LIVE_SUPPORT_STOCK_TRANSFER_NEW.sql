-- StockTransferNew STN_019 - Tenant DB post-live support SQL
-- Safe to run more than once where supported by MySQL/MariaDB.

CREATE TABLE IF NOT EXISTS `stn_post_live_notes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `location_id` INT UNSIGNED NULL,
  `store_id` INT UNSIGNED NULL,
  `note_type` VARCHAR(50) NOT NULL DEFAULT 'post_live',
  `title` VARCHAR(191) NOT NULL,
  `description` TEXT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'open',
  `priority` VARCHAR(50) NOT NULL DEFAULT 'normal',
  `assigned_to` BIGINT UNSIGNED NULL,
  `resolved_at` TIMESTAMP NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `stn_post_live_notes_scope_idx` (`business_id`, `location_id`, `store_id`),
  KEY `stn_post_live_notes_status_idx` (`status`, `priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'stocktransfernew.post_live.monitor', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'stocktransfernew.post_live.monitor');
