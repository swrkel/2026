CREATE TABLE IF NOT EXISTS `disnew_server_testing_runs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `overall_status` VARCHAR(50) NOT NULL DEFAULT 'pending',
  `payload` LONGTEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_server_testing_runs_business_id_index` (`business_id`),
  KEY `disnew_server_testing_runs_user_id_index` (`user_id`),
  KEY `disnew_server_testing_runs_status_index` (`overall_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
('distributionnew.server_testing.view', 'web', NOW(), NOW()),
('distributionnew.server_testing.run', 'web', NOW(), NOW()),
('distributionnew.server_testing.export', 'web', NOW(), NOW());
