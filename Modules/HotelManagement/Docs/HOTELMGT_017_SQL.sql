-- HOTELMGT_017_SQL.sql
-- Hotel Management Parcel 017 only
-- Testing readiness logging and post-upload verification support.

CREATE TABLE IF NOT EXISTS `hm_testing_readiness_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `business_location_id` INT UNSIGNED NULL,
  `checked_by` BIGINT UNSIGNED NULL,
  `check_title` VARCHAR(191) NOT NULL,
  `check_status` VARCHAR(50) NOT NULL DEFAULT 'passed',
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_testing_logs_business_idx` (`business_id`),
  KEY `hm_testing_logs_location_idx` (`business_location_id`),
  KEY `hm_testing_logs_status_idx` (`check_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `hm_permission_registry` (`permission_key`, `permission_name`, `module`, `group_name`, `is_active`, `created_at`, `updated_at`)
SELECT 'hotel_management.testing_readiness.view', 'Hotel Management Testing Readiness View', 'HotelManagement', 'System Check', 1, NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'hm_permission_registry')
  AND NOT EXISTS (SELECT 1 FROM `hm_permission_registry` WHERE `permission_key` = 'hotel_management.testing_readiness.view');

INSERT INTO `hm_menu_registry` (`menu_key`, `menu_name`, `route_name`, `parent_key`, `sort_order`, `is_active`, `created_at`, `updated_at`)
SELECT 'hotel_management.testing_readiness', 'Testing Readiness', 'hotel-management.testing-readiness.index', 'hotel_management.system', 990, 1, NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'hm_menu_registry')
  AND NOT EXISTS (SELECT 1 FROM `hm_menu_registry` WHERE `menu_key` = 'hotel_management.testing_readiness');
