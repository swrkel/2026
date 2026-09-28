-- HOTELMGT_020_SQL.sql
-- Hotel Management Parcel 020 specific SQL only.
-- Purpose: tenant data integrity snapshots, data-check menu/permission registration, and test-stabilization notes.
-- Run this on each tenant database that already has HOTELMGT_001 to HOTELMGT_019 applied.

CREATE TABLE IF NOT EXISTS `hm_data_integrity_snapshots` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `business_location_id` INT UNSIGNED NULL,
  `snapshot_date` DATE NOT NULL,
  `checks_total` INT UNSIGNED NOT NULL DEFAULT 0,
  `checks_ok` INT UNSIGNED NOT NULL DEFAULT 0,
  `checks_review` INT UNSIGNED NOT NULL DEFAULT 0,
  `checks_missing` INT UNSIGNED NOT NULL DEFAULT 0,
  `payload` LONGTEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_data_integrity_scope_idx` (`business_id`,`business_location_id`,`snapshot_date`),
  KEY `hm_data_integrity_result_idx` (`checks_review`,`checks_missing`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `hm_deployment_notes` (`parcel_no`, `note_type`, `note`, `created_at`, `updated_at`)
SELECT 'HOTELMGT_020', 'deployment', 'Data Integrity parcel added: tenant/business/location consistency checks, orphan record review and snapshot saving before server testing.', NOW(), NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM `hm_deployment_notes` WHERE `parcel_no` = 'HOTELMGT_020' AND `note_type` = 'deployment'
);

INSERT INTO `hm_module_permissions` (`permission_key`, `permission_name`, `module_section`, `created_at`, `updated_at`)
SELECT 'hotel.data_integrity', 'Hotel Data Integrity', 'Control', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `hm_module_permissions` WHERE `permission_key` = 'hotel.data_integrity');

INSERT INTO `hm_menu_registry` (`menu_key`, `menu_label`, `route_name`, `permission_key`, `sort_order`, `is_active`, `created_at`, `updated_at`)
SELECT 'hotel_data_integrity', 'Data Integrity', 'hotel-management.data-integrity.index', 'hotel.data_integrity', 985, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `hm_menu_registry` WHERE `menu_key` = 'hotel_data_integrity');
