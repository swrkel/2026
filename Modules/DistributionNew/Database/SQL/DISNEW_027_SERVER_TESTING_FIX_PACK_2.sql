CREATE TABLE IF NOT EXISTS `disnew_server_fix_pack_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned DEFAULT NULL,
  `location_id` int unsigned DEFAULT NULL,
  `check_key` varchar(150) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `message` text NULL,
  `payload` json NULL,
  `created_by` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_fix_business_idx` (`business_id`),
  KEY `disnew_fix_location_idx` (`location_id`),
  KEY `disnew_fix_check_key_idx` (`check_key`),
  KEY `disnew_fix_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'distribution_new.server_testing.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'distribution_new.server_testing.view');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'distribution_new.server_testing.run', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'distribution_new.server_testing.run');
