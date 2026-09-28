-- HOTELMGT_018_SQL.sql
-- Hotel Management Parcel 018 only: production hardening, performance indexes, audit snapshot table.
-- Safe for multi-tenant tenant databases. Do not specify database name; run inside each tenant DB.

CREATE TABLE IF NOT EXISTS `hm_production_audit_snapshots` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `business_location_id` INT UNSIGNED NULL,
  `audit_date` DATE NOT NULL,
  `audit_type` VARCHAR(80) NOT NULL DEFAULT 'production_hardening',
  `tables_total` INT UNSIGNED NOT NULL DEFAULT 0,
  `tables_ok` INT UNSIGNED NOT NULL DEFAULT 0,
  `routes_total` INT UNSIGNED NOT NULL DEFAULT 0,
  `routes_ok` INT UNSIGNED NOT NULL DEFAULT 0,
  `integrations_total` INT UNSIGNED NOT NULL DEFAULT 0,
  `integrations_ok` INT UNSIGNED NOT NULL DEFAULT 0,
  `recommended_indexes` INT UNSIGNED NOT NULL DEFAULT 0,
  `payload` LONGTEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_prod_audit_business_location_idx` (`business_id`, `business_location_id`),
  KEY `hm_prod_audit_date_idx` (`audit_date`, `audit_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conditional performance indexes. Each block avoids duplicate-index failure.
SET @idx := (SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hm_rooms' AND INDEX_NAME = 'hm_rooms_scope_status_idx');
SET @sql := IF(@idx = 0, 'ALTER TABLE `hm_rooms` ADD INDEX `hm_rooms_scope_status_idx` (`business_id`, `business_location_id`, `status`)', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx := (SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hm_reservations' AND INDEX_NAME = 'hm_res_scope_status_arrival_idx');
SET @sql := IF(@idx = 0, 'ALTER TABLE `hm_reservations` ADD INDEX `hm_res_scope_status_arrival_idx` (`business_id`, `business_location_id`, `status`, `arrival_date`)', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx := (SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hm_folios' AND INDEX_NAME = 'hm_folios_scope_status_idx');
SET @sql := IF(@idx = 0, 'ALTER TABLE `hm_folios` ADD INDEX `hm_folios_scope_status_idx` (`business_id`, `business_location_id`, `status`)', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx := (SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hm_folio_lines' AND INDEX_NAME = 'hm_folio_lines_posting_idx');
SET @sql := IF(@idx = 0, 'ALTER TABLE `hm_folio_lines` ADD INDEX `hm_folio_lines_posting_idx` (`business_id`, `folio_id`, `posting_date`)', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx := (SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hm_guest_payments' AND INDEX_NAME = 'hm_guest_payments_date_idx');
SET @sql := IF(@idx = 0, 'ALTER TABLE `hm_guest_payments` ADD INDEX `hm_guest_payments_date_idx` (`business_id`, `folio_id`, `payment_date`)', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx := (SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hm_store_movements' AND INDEX_NAME = 'hm_store_movements_scope_date_idx');
SET @sql := IF(@idx = 0, 'ALTER TABLE `hm_store_movements` ADD INDEX `hm_store_movements_scope_date_idx` (`business_id`, `business_location_id`, `movement_date`)', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx := (SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hm_pos_orders' AND INDEX_NAME = 'hm_pos_orders_scope_date_idx');
SET @sql := IF(@idx = 0, 'ALTER TABLE `hm_pos_orders` ADD INDEX `hm_pos_orders_scope_date_idx` (`business_id`, `business_location_id`, `order_date`)', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx := (SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hm_housekeeping_schedules' AND INDEX_NAME = 'hm_hk_schedule_scope_date_status_idx');
SET @sql := IF(@idx = 0, 'ALTER TABLE `hm_housekeeping_schedules` ADD INDEX `hm_hk_schedule_scope_date_status_idx` (`business_id`, `business_location_id`, `work_date`, `status`)', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx := (SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hm_night_audits' AND INDEX_NAME = 'hm_night_audits_scope_date_idx');
SET @sql := IF(@idx = 0, 'ALTER TABLE `hm_night_audits` ADD INDEX `hm_night_audits_scope_date_idx` (`business_id`, `business_location_id`, `audit_date`)', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `hm_module_permissions` (`module`, `permission_key`, `permission_name`, `group_name`, `is_active`, `created_at`, `updated_at`)
SELECT 'HotelManagement', 'hotel.production_hardening.view', 'View Production Hardening', 'Settings', 1, NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hm_module_permissions')
AND NOT EXISTS (SELECT 1 FROM `hm_module_permissions` WHERE `module` = 'HotelManagement' AND `permission_key` = 'hotel.production_hardening.view');

INSERT INTO `hm_menu_registry` (`module`, `menu_key`, `menu_label`, `route_name`, `permission_key`, `sort_order`, `is_active`, `created_at`, `updated_at`)
SELECT 'HotelManagement', 'production_hardening', 'Production Hardening', 'hotel-management.production-hardening.index', 'hotel.settings', 970, 1, NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hm_menu_registry')
AND NOT EXISTS (SELECT 1 FROM `hm_menu_registry` WHERE `module` = 'HotelManagement' AND `menu_key` = 'production_hardening');
