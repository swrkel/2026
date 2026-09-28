-- StockTransferNew STN_020 Tenant SQL
-- Run in every tenant database where StockTransferNew is enabled.

CREATE TABLE IF NOT EXISTS `stn_data_quality_checks` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `store_id` BIGINT UNSIGNED NULL,
  `check_key` VARCHAR(120) NOT NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'info',
  `message` TEXT NULL,
  `checked_by` BIGINT UNSIGNED NULL,
  `checked_at` DATETIME NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stn_dq_business_idx` (`business_id`),
  KEY `stn_dq_location_idx` (`location_id`),
  KEY `stn_dq_store_idx` (`store_id`),
  KEY `stn_dq_check_idx` (`check_key`),
  KEY `stn_dq_checked_at_idx` (`checked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'stocktransfernew.admin.data_quality', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'stocktransfernew.admin.data_quality');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'stocktransfernew.admin.data_quality.export', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'stocktransfernew.admin.data_quality.export');
