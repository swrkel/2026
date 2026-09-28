-- HOTELMGT_019_SQL.sql
-- Hotel Management Parcel 019 specific SQL only.
-- Purpose: final readiness checks, production deployment logs, and missing permission/menu safeguards.
-- Run this on each tenant database that already has HOTELMGT_001 to HOTELMGT_018 applied.

CREATE TABLE IF NOT EXISTS `hm_final_readiness_checks` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `business_location_id` INT UNSIGNED NULL,
  `check_area` VARCHAR(80) NOT NULL,
  `check_group` VARCHAR(100) NULL,
  `check_key` VARCHAR(160) NOT NULL,
  `status` ENUM('ok','review','failed') NOT NULL DEFAULT 'review',
  `note` TEXT NULL,
  `checked_by` INT UNSIGNED NULL,
  `checked_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `hm_final_ready_scope_idx` (`business_id`,`business_location_id`,`check_area`,`status`),
  KEY `hm_final_ready_key_idx` (`check_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_deployment_notes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `business_location_id` INT UNSIGNED NULL,
  `parcel_no` VARCHAR(30) NOT NULL,
  `note_type` VARCHAR(60) NOT NULL DEFAULT 'deployment',
  `note` TEXT NOT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `hm_deployment_notes_scope_idx` (`business_id`,`business_location_id`,`parcel_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `hm_deployment_notes` (`parcel_no`, `note_type`, `note`, `created_at`, `updated_at`)
SELECT 'HOTELMGT_019', 'deployment', 'Final readiness parcel added: route/table/permission/scope checklist page and production deployment notes.', NOW(), NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM `hm_deployment_notes` WHERE `parcel_no` = 'HOTELMGT_019' AND `note_type` = 'deployment'
);

INSERT INTO `hm_module_permissions` (`permission_key`, `permission_name`, `module_section`, `created_at`, `updated_at`)
SELECT 'hotel.final_readiness', 'Hotel Final Readiness', 'Control', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'hm_module_permissions')
  AND NOT EXISTS (SELECT 1 FROM `hm_module_permissions` WHERE `permission_key` = 'hotel.final_readiness');

INSERT INTO `hm_menu_registry` (`menu_key`, `menu_label`, `route_name`, `permission_key`, `sort_order`, `is_active`, `created_at`, `updated_at`)
SELECT 'hotel_final_readiness', 'Final Readiness', 'hotel-management.final-readiness.index', 'hotel.final_readiness', 990, 1, NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'hm_menu_registry')
  AND NOT EXISTS (SELECT 1 FROM `hm_menu_registry` WHERE `menu_key` = 'hotel_final_readiness');
