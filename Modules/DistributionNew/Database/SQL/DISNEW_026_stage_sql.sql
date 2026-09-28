-- DISNEW_026 Server Feedback Stabilization SQL
CREATE TABLE IF NOT EXISTS `disnew_server_checks` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `check_code` VARCHAR(150) NOT NULL,
  `check_name` VARCHAR(191) NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `message` TEXT NULL,
  `repair_hint` TEXT NULL,
  `checked_by` BIGINT UNSIGNED NULL,
  `checked_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `disnew_server_checks_business_id_index` (`business_id`),
  KEY `disnew_server_checks_location_id_index` (`location_id`),
  KEY `disnew_server_checks_check_code_index` (`check_code`),
  KEY `disnew_server_checks_status_index` (`status`),
  KEY `disnew_server_checks_checked_by_index` (`checked_by`),
  KEY `disnew_server_checks_checked_at_index` (`checked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'disnew.stabilization.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'disnew.stabilization.view');

INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'disnew.stabilization.repair', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'disnew.stabilization.repair');
