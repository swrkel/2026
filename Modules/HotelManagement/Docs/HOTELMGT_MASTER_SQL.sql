/* HOTELMGT_001 - Hotel Management tenant/business scope support
   Run on every tenant database that uses the Hotel Management module.
   These queries are written as MySQL 8+ idempotent ADD COLUMN IF NOT EXISTS statements.
*/
ALTER TABLE hm_buildings ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id, ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE hm_wings ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id, ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE hm_floors ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id, ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE hm_amenities ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id, ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE hm_room_types ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id, ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE hm_rooms ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id, ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE hm_room_features ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id, ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE hm_rate_plans ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id, ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE hm_seasons ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id, ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE hm_guests ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id, ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE hm_reservations ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id, ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE hm_reservation_rooms ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id, ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE hm_checkins ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id, ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE hm_checkouts ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id, ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE hm_deposits ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id, ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE hm_folio_lines ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id, ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE hm_store_movements ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id, ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE hm_guest_preferences ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id, ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE hm_guest_notes ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id, ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;

CREATE INDEX IF NOT EXISTS hm_buildings_business_id_index ON hm_buildings (business_id);
CREATE INDEX IF NOT EXISTS hm_rooms_business_id_index ON hm_rooms (business_id);
CREATE INDEX IF NOT EXISTS hm_guests_business_id_index ON hm_guests (business_id);
CREATE INDEX IF NOT EXISTS hm_reservations_business_id_index ON hm_reservations (business_id);
CREATE INDEX IF NOT EXISTS hm_folios_business_id_index ON hm_folios (business_id);

/* HOTELMGT_002 support - business scoped CRUD continuation */
/* See Docs/HOTELMGT_002_SQL.sql */

-- =========================================================
-- HOTELMGT_003 cumulative section
-- =========================================================
SOURCE HOTELMGT_003_SQL.sql;
-- HOTELMGT_003_SQL.sql
-- Run this inside EACH tenant database that uses Hotel Management.
-- No database name is hard-coded. This script uses DATABASE() so it is safe for multi-tenant execution.

DROP PROCEDURE IF EXISTS hm_add_index_if_not_exists;
DELIMITER $$
CREATE PROCEDURE hm_add_index_if_not_exists(
    IN p_table_name VARCHAR(128),
    IN p_index_name VARCHAR(128),
    IN p_index_columns TEXT
)
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = p_table_name) THEN
        IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = p_table_name AND index_name = p_index_name) THEN
            SET @sql = CONCAT('ALTER TABLE `', p_table_name, '` ADD INDEX `', p_index_name, '` (', p_index_columns, ')');
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        END IF;
    END IF;
END$$
DELIMITER ;

CALL hm_add_index_if_not_exists('hm_reservation_rooms', 'hm_rr_business_reservation_idx', '`business_id`, `reservation_id`');
CALL hm_add_index_if_not_exists('hm_reservation_rooms', 'hm_rr_business_room_idx', '`business_id`, `room_id`');
CALL hm_add_index_if_not_exists('hm_checkins', 'hm_checkins_business_status_idx', '`business_id`, `status`');
CALL hm_add_index_if_not_exists('hm_checkins', 'hm_checkins_business_room_idx', '`business_id`, `room_id`');
CALL hm_add_index_if_not_exists('hm_checkouts', 'hm_checkouts_business_room_idx', '`business_id`, `room_id`');
CALL hm_add_index_if_not_exists('hm_folios', 'hm_folios_business_status_idx', '`business_id`, `status`');
CALL hm_add_index_if_not_exists('hm_folios', 'hm_folios_business_reservation_idx', '`business_id`, `reservation_id`');
CALL hm_add_index_if_not_exists('hm_folio_lines', 'hm_folio_lines_folio_date_idx', '`folio_id`, `charge_date`');
CALL hm_add_index_if_not_exists('hm_guest_payments', 'hm_guest_payments_folio_date_idx', '`folio_id`, `payment_date`');

DROP PROCEDURE IF EXISTS hm_add_index_if_not_exists;


-- =========================================================
-- HOTELMGT_004 cumulative section
-- =========================================================
-- Maintenance work orders and room out-of-service tracking.

CREATE TABLE IF NOT EXISTS hm_maintenance_work_orders (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id BIGINT UNSIGNED NULL,
    business_location_id BIGINT UNSIGNED NULL,
    room_id BIGINT UNSIGNED NULL,
    work_order_no VARCHAR(50) NULL,
    category VARCHAR(50) NULL DEFAULT 'general',
    priority VARCHAR(30) NULL DEFAULT 'normal',
    status VARCHAR(30) NULL DEFAULT 'open',
    reported_at DATETIME NULL,
    assigned_to VARCHAR(100) NULL,
    description TEXT NULL,
    estimated_cost DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    completed_at DATETIME NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    KEY hm_mwo_business_id_index (business_id),
    KEY hm_mwo_location_id_index (business_location_id),
    KEY hm_mwo_room_id_index (room_id),
    KEY hm_mwo_status_index (status),
    KEY hm_mwo_work_order_no_index (work_order_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP PROCEDURE IF EXISTS hm_hotelmgt_004_add_column;
DELIMITER $$
CREATE PROCEDURE hm_hotelmgt_004_add_column(
    IN p_table_name VARCHAR(128),
    IN p_column_name VARCHAR(128),
    IN p_column_definition TEXT
)
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = p_table_name)
       AND NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = p_table_name AND column_name = p_column_name) THEN
        SET @ddl = CONCAT('ALTER TABLE `', p_table_name, '` ADD COLUMN `', p_column_name, '` ', p_column_definition);
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

CALL hm_hotelmgt_004_add_column('hm_rooms', 'maintenance_status', "VARCHAR(30) NULL DEFAULT 'clear' AFTER housekeeping_status");
CALL hm_hotelmgt_004_add_column('hm_rooms', 'last_maintenance_at', 'DATETIME NULL AFTER maintenance_status');

DROP PROCEDURE IF EXISTS hm_hotelmgt_004_add_column;

/* ================= HOTELMGT_005 ================= */

CREATE TABLE IF NOT EXISTS `hm_report_exports` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `report_key` VARCHAR(100) NOT NULL,
  `export_type` VARCHAR(20) NOT NULL DEFAULT 'csv',
  `date_from` DATE NULL,
  `date_to` DATE NULL,
  `filters_json` JSON NULL,
  `generated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_report_exports_business_idx` (`business_id`),
  KEY `hm_report_exports_location_idx` (`business_location_id`),
  KEY `hm_report_exports_report_idx` (`report_key`),
  KEY `hm_report_exports_created_idx` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @idx := (SELECT COUNT(1) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'hm_reservations' AND index_name = 'hm_reservations_report_date_idx');
SET @sql := IF(@idx = 0, 'ALTER TABLE `hm_reservations` ADD INDEX `hm_reservations_report_date_idx` (`business_id`, `arrival_date`, `departure_date`, `status`)', 'SELECT "hm_reservations_report_date_idx already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx := (SELECT COUNT(1) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'hm_folio_lines' AND index_name = 'hm_folio_lines_report_date_idx');
SET @sql := IF(@idx = 0, 'ALTER TABLE `hm_folio_lines` ADD INDEX `hm_folio_lines_report_date_idx` (`business_id`, `created_at`)', 'SELECT "hm_folio_lines_report_date_idx already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx := (SELECT COUNT(1) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'hm_guest_payments' AND index_name = 'hm_guest_payments_report_date_idx');
SET @sql := IF(@idx = 0, 'ALTER TABLE `hm_guest_payments` ADD INDEX `hm_guest_payments_report_date_idx` (`business_id`, `created_at`)', 'SELECT "hm_guest_payments_report_date_idx already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx := (SELECT COUNT(1) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'hm_housekeeping_tasks' AND index_name = 'hm_housekeeping_report_date_idx');
SET @sql := IF(@idx = 0, 'ALTER TABLE `hm_housekeeping_tasks` ADD INDEX `hm_housekeeping_report_date_idx` (`business_id`, `created_at`, `status`)', 'SELECT "hm_housekeeping_report_date_idx already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


/* ================= HOTELMGT_006 ================= */
/* Inventory stock movement and reorder support. */

CREATE TABLE IF NOT EXISTS `hm_store_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `item_code` VARCHAR(50) NULL,
  `name` VARCHAR(191) NOT NULL,
  `category` VARCHAR(100) NULL,
  `unit` VARCHAR(30) NULL DEFAULT 'unit',
  `purchase_price` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `selling_price` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `current_stock` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `reorder_level` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(30) NOT NULL DEFAULT 'active',
  `last_movement_at` DATETIME NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_store_items_business_idx` (`business_id`),
  KEY `hm_store_items_location_idx` (`business_location_id`),
  KEY `hm_store_items_code_idx` (`item_code`),
  KEY `hm_store_items_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_store_movements` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `store_item_id` BIGINT UNSIGNED NOT NULL,
  `movement_no` VARCHAR(50) NULL,
  `movement_type` VARCHAR(30) NOT NULL,
  `movement_date` DATE NULL,
  `direction` TINYINT NOT NULL DEFAULT 1,
  `quantity` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `unit_cost` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `total_cost` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `stock_before` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `stock_after` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `reference_no` VARCHAR(100) NULL,
  `note` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_store_movements_business_idx` (`business_id`),
  KEY `hm_store_movements_location_idx` (`business_location_id`),
  KEY `hm_store_movements_item_idx` (`store_item_id`),
  KEY `hm_store_movements_date_idx` (`movement_date`),
  KEY `hm_store_movements_no_idx` (`movement_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- HOTELMGT_007 - Guest CRM profile, preference and note enhancements
-- =====================================================================
ALTER TABLE `hm_guests`
    ADD COLUMN IF NOT EXISTS `nationality` VARCHAR(100) NULL AFTER `id_no`,
    ADD COLUMN IF NOT EXISTS `date_of_birth` DATE NULL AFTER `nationality`,
    ADD COLUMN IF NOT EXISTS `gender` VARCHAR(30) NULL AFTER `date_of_birth`,
    ADD COLUMN IF NOT EXISTS `address` TEXT NULL AFTER `gender`,
    ADD COLUMN IF NOT EXISTS `vip_level` VARCHAR(50) NULL AFTER `address`,
    ADD COLUMN IF NOT EXISTS `marketing_consent` TINYINT(1) NOT NULL DEFAULT 0 AFTER `vip_level`;
ALTER TABLE `hm_guest_preferences` ADD COLUMN IF NOT EXISTS `deleted_at` TIMESTAMP NULL DEFAULT NULL AFTER `updated_at`;
ALTER TABLE `hm_guest_notes` ADD COLUMN IF NOT EXISTS `deleted_at` TIMESTAMP NULL DEFAULT NULL AFTER `updated_at`;
SET @idx := (SELECT COUNT(1) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'hm_guests' AND index_name = 'hm_guests_business_search_idx');
SET @sql := IF(@idx = 0, 'ALTER TABLE `hm_guests` ADD INDEX `hm_guests_business_search_idx` (`business_id`, `guest_name`, `mobile`, `id_no`)', 'SELECT "hm_guests_business_search_idx already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @idx := (SELECT COUNT(1) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'hm_guests' AND index_name = 'hm_guests_vip_status_idx');
SET @sql := IF(@idx = 0, 'ALTER TABLE `hm_guests` ADD INDEX `hm_guests_vip_status_idx` (`business_id`, `vip_level`, `status`)', 'SELECT "hm_guests_vip_status_idx already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @idx := (SELECT COUNT(1) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'hm_guest_preferences' AND index_name = 'hm_guest_preferences_guest_type_idx');
SET @sql := IF(@idx = 0, 'ALTER TABLE `hm_guest_preferences` ADD INDEX `hm_guest_preferences_guest_type_idx` (`business_id`, `guest_id`, `preference_type`)', 'SELECT "hm_guest_preferences_guest_type_idx already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @idx := (SELECT COUNT(1) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'hm_guest_notes' AND index_name = 'hm_guest_notes_guest_type_idx');
SET @sql := IF(@idx = 0, 'ALTER TABLE `hm_guest_notes` ADD INDEX `hm_guest_notes_guest_type_idx` (`business_id`, `guest_id`, `note_type`)', 'SELECT "hm_guest_notes_guest_type_idx already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
-- =====================================================================
-- HOTELMGT_008 - Hotel POS menu and order posting
-- Specific SQL for HOTELMGT_008 only.
-- Execute this on every tenant database that uses the Hotel Management module.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `hm_pos_categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NOT NULL,
  `code` VARCHAR(50) NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_pos_categories_business_idx` (`business_id`),
  KEY `hm_pos_categories_location_idx` (`business_location_id`),
  KEY `hm_pos_categories_code_idx` (`code`),
  KEY `hm_pos_categories_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_pos_menu_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `category_id` BIGINT UNSIGNED NULL,
  `item_code` VARCHAR(50) NULL,
  `name` VARCHAR(191) NOT NULL,
  `price` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `unit` VARCHAR(30) NOT NULL DEFAULT 'unit',
  `status` VARCHAR(30) NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_pos_menu_items_business_idx` (`business_id`),
  KEY `hm_pos_menu_items_location_idx` (`business_location_id`),
  KEY `hm_pos_menu_items_category_idx` (`category_id`),
  KEY `hm_pos_menu_items_code_idx` (`item_code`),
  KEY `hm_pos_menu_items_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_pos_orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `folio_id` BIGINT UNSIGNED NULL,
  `room_id` BIGINT UNSIGNED NULL,
  `order_no` VARCHAR(50) NULL,
  `order_date` DATE NULL,
  `guest_name` VARCHAR(191) NULL,
  `subtotal` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `service_charge` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `tax_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `grand_total` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `payment_mode` VARCHAR(40) NOT NULL DEFAULT 'cash',
  `status` VARCHAR(30) NOT NULL DEFAULT 'open',
  `note` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_pos_orders_business_idx` (`business_id`),
  KEY `hm_pos_orders_location_idx` (`business_location_id`),
  KEY `hm_pos_orders_folio_idx` (`folio_id`),
  KEY `hm_pos_orders_room_idx` (`room_id`),
  KEY `hm_pos_orders_no_idx` (`order_no`),
  KEY `hm_pos_orders_date_idx` (`order_date`),
  KEY `hm_pos_orders_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_pos_order_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `pos_order_id` BIGINT UNSIGNED NOT NULL,
  `menu_item_id` BIGINT UNSIGNED NULL,
  `description` VARCHAR(191) NOT NULL,
  `quantity` DECIMAL(22,4) NOT NULL DEFAULT 1.0000,
  `unit_price` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `line_total` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_pos_order_lines_business_idx` (`business_id`),
  KEY `hm_pos_order_lines_location_idx` (`business_location_id`),
  KEY `hm_pos_order_lines_order_idx` (`pos_order_id`),
  KEY `hm_pos_order_lines_item_idx` (`menu_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- HOTELMGT_009_SQL.sql
-- Hotel Management Parcel 009 only: Housekeeping schedules, Lost & Found, Linen movements
-- Run in each tenant database. Do not prefix database name; this is safe for multi-tenant execution.

CREATE TABLE IF NOT EXISTS `hm_housekeeping_schedules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `room_id` BIGINT UNSIGNED NOT NULL,
  `attendant_id` BIGINT UNSIGNED NULL,
  `schedule_no` VARCHAR(50) NULL,
  `cleaning_type` VARCHAR(50) NOT NULL DEFAULT 'departure',
  `priority` VARCHAR(30) NOT NULL DEFAULT 'normal',
  `status` VARCHAR(30) NOT NULL DEFAULT 'scheduled',
  `cleaning_date` DATE NULL,
  `scheduled_at` DATETIME NULL,
  `started_at` DATETIME NULL,
  `completed_at` DATETIME NULL,
  `notes` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `deleted_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `hm_hks_business_id_index` (`business_id`),
  KEY `hm_hks_location_id_index` (`business_location_id`),
  KEY `hm_hks_room_id_index` (`room_id`),
  KEY `hm_hks_attendant_id_index` (`attendant_id`),
  KEY `hm_hks_schedule_no_index` (`schedule_no`),
  KEY `hm_hks_status_index` (`status`),
  KEY `hm_hks_cleaning_date_index` (`cleaning_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_lost_found_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `room_id` BIGINT UNSIGNED NULL,
  `guest_id` BIGINT UNSIGNED NULL,
  `item_no` VARCHAR(50) NULL,
  `item_name` VARCHAR(191) NOT NULL,
  `category` VARCHAR(80) NULL,
  `found_date` DATE NULL,
  `found_by` VARCHAR(191) NULL,
  `storage_location` VARCHAR(191) NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'stored',
  `claimed_date` DATE NULL,
  `claimed_by` VARCHAR(191) NULL,
  `description` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `deleted_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `hm_lf_business_id_index` (`business_id`),
  KEY `hm_lf_location_id_index` (`business_location_id`),
  KEY `hm_lf_room_id_index` (`room_id`),
  KEY `hm_lf_guest_id_index` (`guest_id`),
  KEY `hm_lf_item_no_index` (`item_no`),
  KEY `hm_lf_found_date_index` (`found_date`),
  KEY `hm_lf_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_linen_movements` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `room_id` BIGINT UNSIGNED NULL,
  `movement_no` VARCHAR(50) NULL,
  `linen_item` VARCHAR(191) NOT NULL,
  `quantity` DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
  `movement_type` VARCHAR(50) NOT NULL DEFAULT 'issue',
  `from_location` VARCHAR(191) NULL,
  `to_location` VARCHAR(191) NULL,
  `movement_date` DATE NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'posted',
  `notes` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `deleted_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `hm_linen_business_id_index` (`business_id`),
  KEY `hm_linen_location_id_index` (`business_location_id`),
  KEY `hm_linen_room_id_index` (`room_id`),
  KEY `hm_linen_movement_no_index` (`movement_no`),
  KEY `hm_linen_type_index` (`movement_type`),
  KEY `hm_linen_date_index` (`movement_date`),
  KEY `hm_linen_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- HOTELMGT_010_SQL.sql
-- Hotel Management Parcel 010 only: Room Service / In-room Dining workflow.
-- Run against each tenant database that uses Hotel Management.

CREATE TABLE IF NOT EXISTS `hm_room_service_orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `room_service_no` VARCHAR(50) NOT NULL,
  `order_date` DATE NOT NULL,
  `delivery_time` TIME NULL,
  `room_id` BIGINT UNSIGNED NOT NULL,
  `folio_id` BIGINT UNSIGNED NULL,
  `guest_name` VARCHAR(191) NULL,
  `menu_item_id` BIGINT UNSIGNED NOT NULL,
  `item_name` VARCHAR(191) NOT NULL,
  `quantity` DECIMAL(22,4) NOT NULL DEFAULT 1.0000,
  `unit_price` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `subtotal` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `service_charge` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `tax_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `grand_total` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `payment_mode` VARCHAR(40) NOT NULL DEFAULT 'room',
  `priority` VARCHAR(30) NOT NULL DEFAULT 'normal',
  `status` VARCHAR(30) NOT NULL DEFAULT 'ordered',
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `delivered_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `hm_rs_business_id_index` (`business_id`),
  KEY `hm_rs_location_id_index` (`business_location_id`),
  KEY `hm_rs_order_no_index` (`room_service_no`),
  KEY `hm_rs_order_date_index` (`order_date`),
  KEY `hm_rs_room_id_index` (`room_id`),
  KEY `hm_rs_folio_id_index` (`folio_id`),
  KEY `hm_rs_menu_item_id_index` (`menu_item_id`),
  KEY `hm_rs_payment_mode_index` (`payment_mode`),
  KEY `hm_rs_priority_index` (`priority`),
  KEY `hm_rs_status_index` (`status`),
  KEY `hm_rs_scope_date_idx` (`business_id`, `business_location_id`, `order_date`),
  KEY `hm_rs_scope_no_idx` (`business_id`, `room_service_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =========================
-- HOTELMGT_011
-- =========================
-- HOTELMGT_011_SQL.sql
-- Banquet & Event Management tables only for parcel 011.

CREATE TABLE IF NOT EXISTS `hm_banquet_halls` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `hall_code` VARCHAR(50) NOT NULL,
  `hall_name` VARCHAR(191) NOT NULL,
  `capacity` INT NOT NULL DEFAULT 0,
  `base_rate` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(30) NOT NULL DEFAULT 'active',
  `description` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `hm_bh_business_id_idx` (`business_id`),
  KEY `hm_bh_location_id_idx` (`business_location_id`),
  KEY `hm_bh_hall_code_idx` (`hall_code`),
  KEY `hm_bh_status_idx` (`status`),
  KEY `hm_bh_scope_status_idx` (`business_id`,`business_location_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_banquet_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `event_no` VARCHAR(50) NOT NULL,
  `event_date` DATE NOT NULL,
  `start_time` TIME NULL,
  `end_time` TIME NULL,
  `hall_id` BIGINT UNSIGNED NOT NULL,
  `hall_name` VARCHAR(191) NOT NULL,
  `event_type` VARCHAR(80) NOT NULL,
  `customer_name` VARCHAR(191) NOT NULL,
  `customer_mobile` VARCHAR(50) NULL,
  `guest_count` INT NOT NULL DEFAULT 0,
  `package_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `tax_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `advance_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `total_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `balance_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(30) NOT NULL DEFAULT 'reserved',
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `hm_be_business_id_idx` (`business_id`),
  KEY `hm_be_location_id_idx` (`business_location_id`),
  KEY `hm_be_event_no_idx` (`event_no`),
  KEY `hm_be_event_date_idx` (`event_date`),
  KEY `hm_be_hall_id_idx` (`hall_id`),
  KEY `hm_be_event_type_idx` (`event_type`),
  KEY `hm_be_status_idx` (`status`),
  KEY `hm_be_scope_date_idx` (`business_id`,`business_location_id`,`event_date`),
  KEY `hm_be_hall_date_status_idx` (`hall_id`,`event_date`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- HOTELMGT_012_SQL.sql
-- Hotel Management Parcel 012 specific SQL only.
-- Adds Conference & Meeting Hall booking tables.

CREATE TABLE IF NOT EXISTS `hm_conference_rooms` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `room_code` VARCHAR(50) NULL,
  `room_name` VARCHAR(191) NOT NULL,
  `capacity` INT NOT NULL DEFAULT 0,
  `hourly_rate` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `half_day_rate` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `full_day_rate` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `setup_style` VARCHAR(80) NULL,
  `equipment` TEXT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_conference_rooms_business_id_index` (`business_id`),
  KEY `hm_conference_rooms_business_location_id_index` (`business_location_id`),
  KEY `hm_conference_rooms_room_code_index` (`room_code`),
  KEY `hm_conference_rooms_status_index` (`status`),
  KEY `hm_conf_rooms_scope_status_idx` (`business_id`,`business_location_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_conference_bookings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `booking_no` VARCHAR(50) NULL,
  `booking_date` DATE NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `conference_room_id` BIGINT UNSIGNED NOT NULL,
  `room_name` VARCHAR(191) NULL,
  `meeting_title` VARCHAR(191) NOT NULL,
  `customer_name` VARCHAR(191) NOT NULL,
  `customer_mobile` VARCHAR(50) NULL,
  `customer_email` VARCHAR(191) NULL,
  `attendees` INT NOT NULL DEFAULT 0,
  `setup_style` VARCHAR(80) NULL,
  `equipment_required` TEXT NULL,
  `catering_required` TINYINT(1) NOT NULL DEFAULT 0,
  `catering_note` TEXT NULL,
  `rental_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `catering_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `tax_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `advance_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `total_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `balance_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(30) NOT NULL DEFAULT 'reserved',
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_conference_bookings_business_id_index` (`business_id`),
  KEY `hm_conference_bookings_business_location_id_index` (`business_location_id`),
  KEY `hm_conference_bookings_booking_no_index` (`booking_no`),
  KEY `hm_conference_bookings_booking_date_index` (`booking_date`),
  KEY `hm_conference_bookings_conference_room_id_index` (`conference_room_id`),
  KEY `hm_conference_bookings_status_index` (`status`),
  KEY `hm_conf_booking_scope_date_idx` (`business_id`,`business_location_id`,`booking_date`),
  KEY `hm_conf_booking_clash_idx` (`conference_room_id`,`booking_date`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ================= HOTELMGT_013 ================= */
-- HOTELMGT_013_SQL.sql
-- Night Audit / End-of-Day processing tables.
-- Run inside EACH tenant database. No database name is hard-coded.

CREATE TABLE IF NOT EXISTS `hm_night_audits` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `audit_no` VARCHAR(50) NULL,
  `audit_date` DATE NOT NULL,
  `room_revenue` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `pos_revenue` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `room_service_revenue` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `banquet_revenue` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `conference_revenue` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `total_revenue` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `cash_collected` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `card_collected` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `other_collected` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `total_collected` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `open_folio_balance` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `arrivals_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `departures_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `occupied_rooms` INT UNSIGNED NOT NULL DEFAULT 0,
  `vacant_rooms` INT UNSIGNED NOT NULL DEFAULT 0,
  `out_of_service_rooms` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` VARCHAR(30) NOT NULL DEFAULT 'posted',
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `closed_by` BIGINT UNSIGNED NULL,
  `closed_at` DATETIME NULL,
  `reopened_by` BIGINT UNSIGNED NULL,
  `reopened_at` DATETIME NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_night_audits_business_date_unique` (`business_id`, `business_location_id`, `audit_date`, `deleted_at`),
  KEY `hm_night_audits_business_idx` (`business_id`),
  KEY `hm_night_audits_location_idx` (`business_location_id`),
  KEY `hm_night_audits_date_idx` (`audit_date`),
  KEY `hm_night_audits_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_night_audit_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `night_audit_id` BIGINT UNSIGNED NOT NULL,
  `audit_date` DATE NOT NULL,
  `section` VARCHAR(80) NOT NULL,
  `line_label` VARCHAR(191) NOT NULL,
  `line_value` VARCHAR(191) NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_night_audit_lines_business_idx` (`business_id`),
  KEY `hm_night_audit_lines_location_idx` (`business_location_id`),
  KEY `hm_night_audit_lines_audit_idx` (`night_audit_id`),
  KEY `hm_night_audit_lines_date_idx` (`audit_date`),
  KEY `hm_night_audit_lines_section_idx` (`section`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP PROCEDURE IF EXISTS hm_hotelmgt_013_add_index;
DELIMITER $$
CREATE PROCEDURE hm_hotelmgt_013_add_index(IN p_table VARCHAR(128), IN p_index VARCHAR(128), IN p_cols TEXT)
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = p_table)
     AND NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = p_table AND index_name = p_index) THEN
    SET @ddl = CONCAT('ALTER TABLE `', p_table, '` ADD INDEX `', p_index, '` (', p_cols, ')');
    PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;
END$$
DELIMITER ;

CALL hm_hotelmgt_013_add_index('hm_folio_lines', 'hm_folio_lines_audit_charge_date_idx', '`business_id`, `business_location_id`, `charge_date`, `charge_type`');
CALL hm_hotelmgt_013_add_index('hm_guest_payments', 'hm_guest_payments_audit_payment_date_idx', '`business_id`, `business_location_id`, `payment_date`, `payment_method`');
CALL hm_hotelmgt_013_add_index('hm_folios', 'hm_folios_audit_status_idx', '`business_id`, `business_location_id`, `status`');
CALL hm_hotelmgt_013_add_index('hm_rooms', 'hm_rooms_audit_status_idx', '`business_id`, `business_location_id`, `status`');

DROP PROCEDURE IF EXISTS hm_hotelmgt_013_add_index;


-- =========================================================
-- HOTELMGT_014_SQL.sql
-- =========================================================
-- HOTELMGT_014_SQL.sql
-- Hotel Management Parcel 014 specific SQL only.
-- Purpose: management analytics snapshots, report export/audit support and business/location scope.

ALTER TABLE hm_report_snapshots
    ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id,
    ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id,
    ADD COLUMN IF NOT EXISTS created_by BIGINT UNSIGNED NULL AFTER payload,
    ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at;

CREATE INDEX IF NOT EXISTS hm_report_snapshots_business_location_idx ON hm_report_snapshots (business_id, business_location_id);
CREATE INDEX IF NOT EXISTS hm_report_snapshots_report_date_idx ON hm_report_snapshots (report_key, snapshot_date);
CREATE INDEX IF NOT EXISTS hm_report_snapshots_deleted_idx ON hm_report_snapshots (deleted_at);

CREATE TABLE IF NOT EXISTS hm_report_export_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    business_location_id BIGINT UNSIGNED NULL,
    report_key VARCHAR(100) NOT NULL,
    export_type VARCHAR(30) NULL,
    filters JSON NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    INDEX hm_report_export_logs_scope_idx (business_id, business_location_id),
    INDEX hm_report_export_logs_report_idx (report_key, export_type),
    INDEX hm_report_export_logs_deleted_idx (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
-- HOTELMGT_015 - Final permission/menu audit
-- =====================================================================
-- HOTELMGT_015_SQL.sql
-- Final UI/permission/menu audit SQL only for parcel 015.
-- Run this on each tenant database. It does not require hard-coded database names.

CREATE TABLE IF NOT EXISTS `hm_module_permissions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `module` VARCHAR(100) NOT NULL DEFAULT 'HotelManagement',
  `permission_key` VARCHAR(150) NOT NULL,
  `label` VARCHAR(150) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_module_permissions_permission_key_unique` (`permission_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_menu_registry` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `module` VARCHAR(100) NOT NULL DEFAULT 'HotelManagement',
  `menu_key` VARCHAR(120) NOT NULL,
  `label` VARCHAR(150) NOT NULL,
  `route_name` VARCHAR(150) NULL,
  `permission_key` VARCHAR(150) NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_menu_registry_business_id_index` (`business_id`),
  KEY `hm_menu_registry_business_location_id_index` (`business_location_id`),
  UNIQUE KEY `hm_menu_registry_scope_unique` (`business_id`,`business_location_id`,`menu_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `hm_module_permissions` (`module`,`permission_key`,`label`,`is_active`,`created_at`,`updated_at`) VALUES
('HotelManagement','hotel.view','Hotel Module Access',1,NOW(),NOW()),
('HotelManagement','hotel.dashboard.view','Dashboard View',1,NOW(),NOW()),
('HotelManagement','hotel.setup.view','Setup View',1,NOW(),NOW()),
('HotelManagement','hotel.setup.create','Setup Create',1,NOW(),NOW()),
('HotelManagement','hotel.setup.update','Setup Update',1,NOW(),NOW()),
('HotelManagement','hotel.setup.delete','Setup Delete',1,NOW(),NOW()),
('HotelManagement','hotel.rooms.view','Rooms View',1,NOW(),NOW()),
('HotelManagement','hotel.rooms.create','Rooms Create',1,NOW(),NOW()),
('HotelManagement','hotel.rooms.update','Rooms Update',1,NOW(),NOW()),
('HotelManagement','hotel.rooms.delete','Rooms Delete',1,NOW(),NOW()),
('HotelManagement','hotel.rates.view','Rates View',1,NOW(),NOW()),
('HotelManagement','hotel.reservations.view','Reservations View',1,NOW(),NOW()),
('HotelManagement','hotel.reservations.create','Reservations Create',1,NOW(),NOW()),
('HotelManagement','hotel.front_office.view','Front Office View',1,NOW(),NOW()),
('HotelManagement','hotel.checkin','Check In',1,NOW(),NOW()),
('HotelManagement','hotel.checkout','Check Out',1,NOW(),NOW()),
('HotelManagement','hotel.housekeeping.view','Housekeeping View',1,NOW(),NOW()),
('HotelManagement','hotel.maintenance.view','Maintenance View',1,NOW(),NOW()),
('HotelManagement','hotel.billing.view','Billing View',1,NOW(),NOW()),
('HotelManagement','hotel.pos.view','POS Charges View',1,NOW(),NOW()),
('HotelManagement','hotel.inventory.view','Inventory View',1,NOW(),NOW()),
('HotelManagement','hotel.crm.view','Guest CRM View',1,NOW(),NOW()),
('HotelManagement','hotel.banquets.view','Banquets View',1,NOW(),NOW()),
('HotelManagement','hotel.conference.view','Conference View',1,NOW(),NOW()),
('HotelManagement','hotel.room_service.view','Room Service View',1,NOW(),NOW()),
('HotelManagement','hotel.night_audit.view','Night Audit View',1,NOW(),NOW()),
('HotelManagement','hotel.night_audit.post','Night Audit Post',1,NOW(),NOW()),
('HotelManagement','hotel.night_audit.close','Night Audit Close',1,NOW(),NOW()),
('HotelManagement','hotel.reports.view','Reports View',1,NOW(),NOW()),
('HotelManagement','hotel.reports.export','Reports Export',1,NOW(),NOW()),
('HotelManagement','hotel.reports.snapshot','Reports Snapshot',1,NOW(),NOW()),
('HotelManagement','hotel.settings','Hotel Settings',1,NOW(),NOW())
ON DUPLICATE KEY UPDATE `label`=VALUES(`label`), `is_active`=VALUES(`is_active`), `updated_at`=NOW();

INSERT INTO `hm_menu_registry` (`business_id`,`business_location_id`,`module`,`menu_key`,`label`,`route_name`,`permission_key`,`sort_order`,`is_active`,`created_at`,`updated_at`) VALUES
(NULL,NULL,'HotelManagement','dashboard','Dashboard','hotel-management.dashboard','hotel.dashboard.view',10,1,NOW(),NOW()),
(NULL,NULL,'HotelManagement','setup','Setup','hotel-management.hotels.index','hotel.setup.view',20,1,NOW(),NOW()),
(NULL,NULL,'HotelManagement','rooms','Rooms','hotel-management.rooms.index','hotel.rooms.view',30,1,NOW(),NOW()),
(NULL,NULL,'HotelManagement','rates','Rates','hotel-management.rate-plans.index','hotel.rates.view',40,1,NOW(),NOW()),
(NULL,NULL,'HotelManagement','reservations','Reservations','hotel-management.reservations.index','hotel.reservations.view',50,1,NOW(),NOW()),
(NULL,NULL,'HotelManagement','front_office','Front Office','hotel-management.front-office.index','hotel.front_office.view',60,1,NOW(),NOW()),
(NULL,NULL,'HotelManagement','housekeeping','Housekeeping','hotel-management.housekeeping.index','hotel.housekeeping.view',70,1,NOW(),NOW()),
(NULL,NULL,'HotelManagement','maintenance','Maintenance','hotel-management.maintenance.index','hotel.maintenance.view',80,1,NOW(),NOW()),
(NULL,NULL,'HotelManagement','billing','Billing','hotel-management.billing.index','hotel.billing.view',90,1,NOW(),NOW()),
(NULL,NULL,'HotelManagement','pos','POS Charges','hotel-management.pos.index','hotel.pos.view',100,1,NOW(),NOW()),
(NULL,NULL,'HotelManagement','banquets','Banquets','hotel-management.banquets.index','hotel.banquets.view',110,1,NOW(),NOW()),
(NULL,NULL,'HotelManagement','conference','Conference','hotel-management.conference.index','hotel.conference.view',120,1,NOW(),NOW()),
(NULL,NULL,'HotelManagement','room_service','Room Service','hotel-management.room-service.index','hotel.room_service.view',130,1,NOW(),NOW()),
(NULL,NULL,'HotelManagement','night_audit','Night Audit','hotel-management.night-audit.index','hotel.night_audit.view',140,1,NOW(),NOW()),
(NULL,NULL,'HotelManagement','stores','Stores','hotel-management.inventory.index','hotel.inventory.view',150,1,NOW(),NOW()),
(NULL,NULL,'HotelManagement','guests','Guests','hotel-management.crm.index','hotel.crm.view',160,1,NOW(),NOW()),
(NULL,NULL,'HotelManagement','reports','Reports','hotel-management.reports.index','hotel.reports.view',170,1,NOW(),NOW())
ON DUPLICATE KEY UPDATE `label`=VALUES(`label`), `route_name`=VALUES(`route_name`), `permission_key`=VALUES(`permission_key`), `sort_order`=VALUES(`sort_order`), `is_active`=VALUES(`is_active`), `updated_at`=NOW();

/* ===========================
   HOTELMGT_016 - System Check / Testing Readiness
   =========================== */
INSERT INTO `hm_module_permissions` (`module`,`permission_key`,`label`,`is_active`,`created_at`,`updated_at`) VALUES
('HotelManagement','hotel.system_check.view','Hotel System Check View',1,NOW(),NOW())
ON DUPLICATE KEY UPDATE `label`=VALUES(`label`), `is_active`=VALUES(`is_active`), `updated_at`=NOW();

INSERT INTO `hm_menu_registry` (`business_id`,`business_location_id`,`module`,`menu_key`,`label`,`route_name`,`permission_key`,`sort_order`,`is_active`,`created_at`,`updated_at`) VALUES
(NULL,NULL,'HotelManagement','system_check','System Check','hotel-management.system-check.index','hotel.settings',180,1,NOW(),NOW())
ON DUPLICATE KEY UPDATE `label`=VALUES(`label`), `route_name`=VALUES(`route_name`), `permission_key`=VALUES(`permission_key`), `sort_order`=VALUES(`sort_order`), `is_active`=VALUES(`is_active`), `updated_at`=NOW();


-- ==============================
-- HOTELMGT_017
-- ==============================
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

-- =========================================================
-- HOTELMGT_018 - Production hardening and performance indexes
-- =========================================================

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

-- Run HOTELMGT_018_SQL.sql for conditional performance indexes and registry inserts.
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
-- HOTELMGT_021_SQL.sql
-- Hotel Management Parcel 021 only
-- Guest Communication bridge tables for hotel-specific SMS/email/notification templates and logs.
-- Apply to each tenant database that uses Hotel Management.

CREATE TABLE IF NOT EXISTS `hm_guest_message_templates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `code` VARCHAR(60) NULL,
  `name` VARCHAR(120) NOT NULL,
  `channel` VARCHAR(30) NOT NULL DEFAULT 'sms',
  `event_key` VARCHAR(60) NULL,
  `message_body` TEXT NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_gmt_business_location_idx` (`business_id`, `business_location_id`),
  KEY `hm_gmt_event_idx` (`event_key`, `channel`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_guest_message_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `guest_id` BIGINT UNSIGNED NULL,
  `reservation_id` BIGINT UNSIGNED NULL,
  `folio_id` BIGINT UNSIGNED NULL,
  `template_id` BIGINT UNSIGNED NULL,
  `channel` VARCHAR(30) NOT NULL DEFAULT 'sms',
  `recipient` VARCHAR(191) NOT NULL,
  `subject` VARCHAR(191) NULL,
  `message_body` TEXT NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `source` VARCHAR(60) NULL DEFAULT 'hotel_management',
  `external_message_id` VARCHAR(191) NULL,
  `error_message` TEXT NULL,
  `queued_by` BIGINT UNSIGNED NULL,
  `sent_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_gml_business_location_idx` (`business_id`, `business_location_id`),
  KEY `hm_gml_guest_idx` (`guest_id`),
  KEY `hm_gml_reservation_idx` (`reservation_id`),
  KEY `hm_gml_folio_idx` (`folio_id`),
  KEY `hm_gml_status_idx` (`status`, `channel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `hm_guest_message_templates`
(`business_id`, `business_location_id`, `code`, `name`, `channel`, `event_key`, `message_body`, `is_active`, `created_at`, `updated_at`)
SELECT NULL, NULL, 'booking_confirm', 'Booking Confirmation', 'sms', 'reservation_created', 'Dear {guest_name}, your reservation {reservation_no} is confirmed. Thank you.', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `hm_guest_message_templates` WHERE `code` = 'booking_confirm');

INSERT INTO `hm_guest_message_templates`
(`business_id`, `business_location_id`, `code`, `name`, `channel`, `event_key`, `message_body`, `is_active`, `created_at`, `updated_at`)
SELECT NULL, NULL, 'checkin_welcome', 'Check-in Welcome', 'sms', 'check_in', 'Dear {guest_name}, welcome to {hotel_name}. We wish you a pleasant stay.', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `hm_guest_message_templates` WHERE `code` = 'checkin_welcome');

INSERT INTO `hm_guest_message_templates`
(`business_id`, `business_location_id`, `code`, `name`, `channel`, `event_key`, `message_body`, `is_active`, `created_at`, `updated_at`)
SELECT NULL, NULL, 'checkout_thanks', 'Check-out Thank You', 'sms', 'check_out', 'Dear {guest_name}, thank you for staying with us. We hope to welcome you again.', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `hm_guest_message_templates` WHERE `code` = 'checkout_thanks');
-- HOTELMGT_022_SQL.sql
-- Hotel Management Parcel 022 specific SQL only.
-- Scope: Guest Feedback / ratings / service recovery.

CREATE TABLE IF NOT EXISTS `hm_guest_feedback_questions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `business_location_id` INT UNSIGNED NULL,
  `question` VARCHAR(255) NOT NULL,
  `category` VARCHAR(60) NOT NULL DEFAULT 'general',
  `rating_scale` TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `display_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_feedback_q_business_idx` (`business_id`),
  KEY `hm_feedback_q_location_idx` (`business_location_id`),
  KEY `hm_feedback_q_category_idx` (`category`),
  KEY `hm_feedback_q_active_idx` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_guest_feedback` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `business_location_id` INT UNSIGNED NULL,
  `guest_id` BIGINT UNSIGNED NULL,
  `reservation_id` BIGINT UNSIGNED NULL,
  `folio_id` BIGINT UNSIGNED NULL,
  `feedback_date` DATE NULL,
  `source` VARCHAR(60) NOT NULL DEFAULT 'front_desk',
  `overall_rating` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  `room_rating` DECIMAL(6,2) NULL,
  `service_rating` DECIMAL(6,2) NULL,
  `food_rating` DECIMAL(6,2) NULL,
  `cleanliness_rating` DECIMAL(6,2) NULL,
  `comments` TEXT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'open',
  `resolution_note` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `resolved_by` INT UNSIGNED NULL,
  `resolved_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_feedback_business_idx` (`business_id`),
  KEY `hm_feedback_location_idx` (`business_location_id`),
  KEY `hm_feedback_guest_idx` (`guest_id`),
  KEY `hm_feedback_reservation_idx` (`reservation_id`),
  KEY `hm_feedback_folio_idx` (`folio_id`),
  KEY `hm_feedback_date_idx` (`feedback_date`),
  KEY `hm_feedback_source_idx` (`source`),
  KEY `hm_feedback_status_idx` (`status`),
  KEY `hm_feedback_scope_date_idx` (`business_id`, `business_location_id`, `feedback_date`),
  KEY `hm_feedback_scope_status_idx` (`business_id`, `business_location_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- HOTELMGT_023_SQL.sql
-- Hotel Management Parcel 023 specific SQL only.
-- Scope: Loyalty / Membership / Points ledger.

CREATE TABLE IF NOT EXISTS `hm_loyalty_tiers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `business_location_id` INT UNSIGNED NULL,
  `code` VARCHAR(50) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `min_points` DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
  `discount_percent` DECIMAL(8,4) NOT NULL DEFAULT 0.0000,
  `benefits` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_loyalty_tiers_business_code_unique` (`business_id`, `code`),
  KEY `hm_loyalty_tiers_scope_idx` (`business_id`, `business_location_id`),
  KEY `hm_loyalty_tiers_points_idx` (`min_points`),
  KEY `hm_loyalty_tiers_active_idx` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_loyalty_members` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `business_location_id` INT UNSIGNED NULL,
  `guest_id` BIGINT UNSIGNED NULL,
  `tier_id` BIGINT UNSIGNED NULL,
  `member_no` VARCHAR(60) NOT NULL,
  `guest_name` VARCHAR(150) NOT NULL,
  `mobile` VARCHAR(30) NULL,
  `email` VARCHAR(120) NULL,
  `join_date` DATE NULL,
  `points_balance` DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
  `lifetime_points` DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(30) NOT NULL DEFAULT 'active',
  `last_activity_at` TIMESTAMP NULL DEFAULT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_loyalty_members_business_no_unique` (`business_id`, `member_no`),
  KEY `hm_loyalty_members_scope_idx` (`business_id`, `business_location_id`),
  KEY `hm_loyalty_members_guest_idx` (`guest_id`),
  KEY `hm_loyalty_members_tier_idx` (`tier_id`),
  KEY `hm_loyalty_members_mobile_idx` (`mobile`),
  KEY `hm_loyalty_members_email_idx` (`email`),
  KEY `hm_loyalty_members_status_idx` (`status`),
  KEY `hm_loyalty_members_points_idx` (`points_balance`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_loyalty_point_ledger` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `business_location_id` INT UNSIGNED NULL,
  `member_id` BIGINT UNSIGNED NOT NULL,
  `transaction_date` DATE NULL,
  `type` VARCHAR(20) NOT NULL DEFAULT 'earn',
  `points` DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
  `signed_points` DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
  `amount` DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
  `reference_type` VARCHAR(60) NULL,
  `reference_no` VARCHAR(80) NULL,
  `remarks` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_loyalty_ledger_scope_idx` (`business_id`, `business_location_id`),
  KEY `hm_loyalty_ledger_member_idx` (`member_id`),
  KEY `hm_loyalty_ledger_date_idx` (`transaction_date`),
  KEY `hm_loyalty_ledger_type_idx` (`type`),
  KEY `hm_loyalty_ledger_reference_idx` (`reference_type`, `reference_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `hm_loyalty_tiers`
(`business_id`, `business_location_id`, `code`, `name`, `min_points`, `discount_percent`, `benefits`, `is_active`, `created_at`, `updated_at`)
SELECT NULL, NULL, 'SILVER', 'Silver', 0.0000, 0.0000, 'Standard member benefits', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `hm_loyalty_tiers` WHERE `code` = 'SILVER');

INSERT INTO `hm_loyalty_tiers`
(`business_id`, `business_location_id`, `code`, `name`, `min_points`, `discount_percent`, `benefits`, `is_active`, `created_at`, `updated_at`)
SELECT NULL, NULL, 'GOLD', 'Gold', 5000.0000, 5.0000, 'Priority service and selected discounts', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `hm_loyalty_tiers` WHERE `code` = 'GOLD');

INSERT INTO `hm_loyalty_tiers`
(`business_id`, `business_location_id`, `code`, `name`, `min_points`, `discount_percent`, `benefits`, `is_active`, `created_at`, `updated_at`)
SELECT NULL, NULL, 'PLATINUM', 'Platinum', 15000.0000, 10.0000, 'Premium guest benefits, priority service and selected discounts', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `hm_loyalty_tiers` WHERE `code` = 'PLATINUM');
-- HOTELMGT_024_SQL.sql
-- Parcel 024 only: Spa & Wellness tables and indexes.
-- Run this against each tenant database that uses Hotel Management.

CREATE TABLE IF NOT EXISTS hm_spa_services (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id BIGINT UNSIGNED NULL,
    business_location_id BIGINT UNSIGNED NULL,
    code VARCHAR(60) NOT NULL,
    name VARCHAR(150) NOT NULL,
    category VARCHAR(80) NULL,
    duration_minutes INT UNSIGNED NOT NULL DEFAULT 0,
    price DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    description TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY hm_spa_services_business_code_unique (business_id, code),
    KEY hm_spa_services_business_location_index (business_id, business_location_id),
    KEY hm_spa_services_category_index (category),
    KEY hm_spa_services_active_index (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hm_spa_appointments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id BIGINT UNSIGNED NULL,
    business_location_id BIGINT UNSIGNED NULL,
    appointment_no VARCHAR(80) NOT NULL,
    service_id BIGINT UNSIGNED NULL,
    guest_id BIGINT UNSIGNED NULL,
    folio_id BIGINT UNSIGNED NULL,
    guest_name VARCHAR(150) NOT NULL,
    mobile VARCHAR(30) NULL,
    room_no VARCHAR(30) NULL,
    therapist_name VARCHAR(120) NULL,
    appointment_date DATE NULL,
    start_time TIME NULL,
    end_time TIME NULL,
    amount DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    discount_amount DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    tax_amount DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    net_amount DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    paid_amount DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    status VARCHAR(30) NOT NULL DEFAULT 'booked',
    remarks TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY hm_spa_appointments_business_no_unique (business_id, appointment_no),
    KEY hm_spa_appointments_business_location_index (business_id, business_location_id),
    KEY hm_spa_appointments_date_status_index (appointment_date, status),
    KEY hm_spa_appointments_service_index (service_id),
    KEY hm_spa_appointments_guest_index (guest_id),
    KEY hm_spa_appointments_folio_index (folio_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hm_spa_payments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id BIGINT UNSIGNED NULL,
    business_location_id BIGINT UNSIGNED NULL,
    appointment_id BIGINT UNSIGNED NOT NULL,
    payment_date DATE NULL,
    method VARCHAR(40) NOT NULL DEFAULT 'cash',
    amount DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    reference_no VARCHAR(100) NULL,
    remarks TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY hm_spa_payments_business_location_index (business_id, business_location_id),
    KEY hm_spa_payments_appointment_index (appointment_id),
    KEY hm_spa_payments_date_method_index (payment_date, method)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'hotel.spa.view', 'web', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'hotel.spa.view');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'hotel.spa.manage', 'web', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'hotel.spa.manage');

-- HOTELMGT_025_SQL.sql
-- Parcel 025 only: Hotel Transport / Airport Transfer tables, indexes and permissions.
-- Run this against each tenant database that uses Hotel Management.

CREATE TABLE IF NOT EXISTS hm_transport_vehicles (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id BIGINT UNSIGNED NULL,
    business_location_id BIGINT UNSIGNED NULL,
    vehicle_no VARCHAR(60) NOT NULL,
    vehicle_type VARCHAR(80) NULL,
    driver_name VARCHAR(120) NULL,
    driver_mobile VARCHAR(30) NULL,
    seating_capacity INT UNSIGNED NOT NULL DEFAULT 0,
    base_rate DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    remarks TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY hm_transport_vehicles_business_vehicle_unique (business_id, vehicle_no),
    KEY hm_transport_vehicles_business_location_index (business_id, business_location_id),
    KEY hm_transport_vehicles_type_index (vehicle_type),
    KEY hm_transport_vehicles_active_index (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hm_transport_bookings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id BIGINT UNSIGNED NULL,
    business_location_id BIGINT UNSIGNED NULL,
    booking_no VARCHAR(80) NOT NULL,
    vehicle_id BIGINT UNSIGNED NULL,
    reservation_id BIGINT UNSIGNED NULL,
    folio_id BIGINT UNSIGNED NULL,
    guest_name VARCHAR(150) NOT NULL,
    mobile VARCHAR(30) NULL,
    room_no VARCHAR(30) NULL,
    trip_type VARCHAR(40) NOT NULL DEFAULT 'airport_pickup',
    pickup_date DATE NULL,
    pickup_time TIME NULL,
    pickup_location VARCHAR(255) NULL,
    drop_location VARCHAR(255) NULL,
    flight_no VARCHAR(80) NULL,
    driver_name VARCHAR(120) NULL,
    rate DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    discount_amount DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    tax_amount DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    net_amount DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    paid_amount DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    status VARCHAR(30) NOT NULL DEFAULT 'requested',
    remarks TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY hm_transport_bookings_business_no_unique (business_id, booking_no),
    KEY hm_transport_bookings_business_location_index (business_id, business_location_id),
    KEY hm_transport_bookings_pickup_status_index (pickup_date, status),
    KEY hm_transport_bookings_vehicle_index (vehicle_id),
    KEY hm_transport_bookings_reservation_index (reservation_id),
    KEY hm_transport_bookings_folio_index (folio_id),
    KEY hm_transport_bookings_trip_type_index (trip_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hm_transport_payments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id BIGINT UNSIGNED NULL,
    business_location_id BIGINT UNSIGNED NULL,
    booking_id BIGINT UNSIGNED NOT NULL,
    payment_date DATE NULL,
    method VARCHAR(40) NOT NULL DEFAULT 'cash',
    amount DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    reference_no VARCHAR(100) NULL,
    remarks TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY hm_transport_payments_business_location_index (business_id, business_location_id),
    KEY hm_transport_payments_booking_index (booking_id),
    KEY hm_transport_payments_date_method_index (payment_date, method)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'hotel.transport.view', 'web', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'hotel.transport.view');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'hotel.transport.manage', 'web', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'hotel.transport.manage');


-- =========================================================
-- HOTELMGT_026 - Mini Bar & Room Consumption
-- =========================================================
-- HOTELMGT_026 - Mini Bar & Room Consumption
-- Apply this SQL in each tenant database that uses the Hotel Management module.

CREATE TABLE IF NOT EXISTS hm_minibar_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id BIGINT UNSIGNED NULL,
    business_location_id BIGINT UNSIGNED NULL,
    item_code VARCHAR(80) NOT NULL,
    item_name VARCHAR(150) NOT NULL,
    category VARCHAR(80) NULL,
    unit VARCHAR(30) NULL,
    selling_price DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    cost_price DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    current_stock DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    reorder_level DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    remarks TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY hm_minibar_items_business_code_unique (business_id, item_code),
    KEY hm_minibar_items_business_location_index (business_id, business_location_id),
    KEY hm_minibar_items_category_index (category),
    KEY hm_minibar_items_active_index (is_active),
    KEY hm_minibar_items_stock_index (current_stock, reorder_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hm_minibar_consumptions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id BIGINT UNSIGNED NULL,
    business_location_id BIGINT UNSIGNED NULL,
    consumption_no VARCHAR(80) NOT NULL,
    room_id BIGINT UNSIGNED NULL,
    room_no VARCHAR(30) NULL,
    reservation_id BIGINT UNSIGNED NULL,
    folio_id BIGINT UNSIGNED NULL,
    guest_name VARCHAR(150) NULL,
    item_id BIGINT UNSIGNED NULL,
    item_code VARCHAR(80) NULL,
    item_name VARCHAR(150) NULL,
    consumption_date DATE NULL,
    qty DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    unit_price DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    discount_amount DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    tax_amount DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    total_amount DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    status VARCHAR(30) NOT NULL DEFAULT 'draft',
    posted_charge_id BIGINT UNSIGNED NULL,
    remarks TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    posted_by BIGINT UNSIGNED NULL,
    posted_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY hm_minibar_consumptions_business_no_unique (business_id, consumption_no),
    KEY hm_minibar_consumptions_business_location_index (business_id, business_location_id),
    KEY hm_minibar_consumptions_room_index (room_id, room_no),
    KEY hm_minibar_consumptions_folio_index (folio_id),
    KEY hm_minibar_consumptions_item_index (item_id),
    KEY hm_minibar_consumptions_date_status_index (consumption_date, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'hotel.minibar.view', 'web', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'hotel.minibar.view');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'hotel.minibar.manage', 'web', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'hotel.minibar.manage');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'hotel.minibar.post_to_folio', 'web', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'hotel.minibar.post_to_folio');
-- HOTELMGT_027_SQL.sql
-- Hotel Management Parcel 027 - Laundry / Guest Garment Services
-- Run this SQL in every tenant database where Hotel Management is enabled.

CREATE TABLE IF NOT EXISTS `hm_laundry_services` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned NULL,
  `business_location_id` int unsigned NULL,
  `service_code` varchar(80) NOT NULL,
  `service_name` varchar(150) NOT NULL,
  `category` varchar(80) NULL,
  `unit` varchar(30) NULL,
  `standard_rate` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `express_rate` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `remarks` text NULL,
  `created_by` int unsigned NULL,
  `updated_by` int unsigned NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_laundry_services_business_code_unique` (`business_id`,`service_code`),
  KEY `hm_laundry_services_business_location_index` (`business_id`,`business_location_id`),
  KEY `hm_laundry_services_active_index` (`business_id`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_laundry_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned NULL,
  `business_location_id` int unsigned NULL,
  `order_no` varchar(80) NOT NULL,
  `order_date` date NULL,
  `room_id` bigint unsigned NULL,
  `room_no` varchar(30) NULL,
  `reservation_id` bigint unsigned NULL,
  `folio_id` bigint unsigned NULL,
  `guest_name` varchar(150) NULL,
  `service_id` bigint unsigned NULL,
  `service_code` varchar(80) NULL,
  `service_name` varchar(150) NULL,
  `qty` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `rate_type` varchar(30) NULL,
  `unit_price` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `discount_amount` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `tax_amount` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `total_amount` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `paid_amount` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `balance_amount` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `payment_method` varchar(40) NULL,
  `payment_reference` varchar(150) NULL,
  `pickup_time` varchar(30) NULL,
  `delivery_time` varchar(30) NULL,
  `status` varchar(30) NOT NULL DEFAULT 'received',
  `folio_posted_charge_id` bigint unsigned NULL,
  `posted_charge_id` bigint unsigned NULL,
  `posted_by` int unsigned NULL,
  `posted_at` timestamp NULL DEFAULT NULL,
  `paid_by` int unsigned NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `remarks` text NULL,
  `created_by` int unsigned NULL,
  `updated_by` int unsigned NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_laundry_orders_business_order_unique` (`business_id`,`order_no`),
  KEY `hm_laundry_orders_business_location_index` (`business_id`,`business_location_id`),
  KEY `hm_laundry_orders_date_index` (`business_id`,`order_date`),
  KEY `hm_laundry_orders_room_index` (`business_id`,`room_id`,`room_no`),
  KEY `hm_laundry_orders_folio_index` (`business_id`,`folio_id`),
  KEY `hm_laundry_orders_status_index` (`business_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- HOTELMGT_028_SQL.sql
-- Hotel Management Parcel 028: Valet Parking
-- Run on each tenant database that uses the Hotel Management module.

CREATE TABLE IF NOT EXISTS `hm_valet_parking_zones` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `zone_code` VARCHAR(50) NOT NULL,
  `zone_name` VARCHAR(120) NOT NULL,
  `capacity` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_valet_zones_business_code_unique` (`business_id`, `zone_code`),
  KEY `hm_valet_zones_business_id_index` (`business_id`),
  KEY `hm_valet_zones_location_id_index` (`business_location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_valet_parking_tickets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `ticket_no` VARCHAR(80) NOT NULL,
  `ticket_date` DATE NULL,
  `zone_id` BIGINT UNSIGNED NULL,
  `room_no` VARCHAR(30) NULL,
  `guest_name` VARCHAR(150) NULL,
  `mobile` VARCHAR(50) NULL,
  `vehicle_no` VARCHAR(80) NOT NULL,
  `vehicle_type` VARCHAR(80) NULL,
  `vehicle_colour` VARCHAR(80) NULL,
  `key_tag_no` VARCHAR(80) NULL,
  `parked_slot` VARCHAR(80) NULL,
  `check_in_time` VARCHAR(30) NULL,
  `expected_out_time` VARCHAR(30) NULL,
  `retrieved_time` VARCHAR(30) NULL,
  `driver_name` VARCHAR(150) NULL,
  `rate` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `net_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `paid_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(40) NOT NULL DEFAULT 'parked',
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_valet_tickets_business_no_unique` (`business_id`, `ticket_no`),
  KEY `hm_valet_tickets_business_id_index` (`business_id`),
  KEY `hm_valet_tickets_location_id_index` (`business_location_id`),
  KEY `hm_valet_tickets_ticket_date_index` (`ticket_date`),
  KEY `hm_valet_tickets_zone_id_index` (`zone_id`),
  KEY `hm_valet_tickets_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_valet_parking_payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `ticket_id` BIGINT UNSIGNED NOT NULL,
  `payment_date` DATE NULL,
  `payment_method` VARCHAR(40) NOT NULL,
  `paid_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `payment_reference` VARCHAR(150) NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_valet_payments_business_id_index` (`business_id`),
  KEY `hm_valet_payments_location_id_index` (`business_location_id`),
  KEY `hm_valet_payments_ticket_id_index` (`ticket_id`),
  KEY `hm_valet_payments_payment_date_index` (`payment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =========================================================
-- HOTELMGT_029 - Procurement / Purchasing / GRN
-- =========================================================
-- HOTELMGT_029_SQL.sql
-- Hotel Management Parcel 029: Procurement / Purchasing / GRN
-- Run this inside each tenant database. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `hm_procurement_suppliers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `supplier_code` VARCHAR(80) NULL,
  `supplier_name` VARCHAR(160) NOT NULL,
  `contact_person` VARCHAR(120) NULL,
  `mobile` VARCHAR(40) NULL,
  `email` VARCHAR(160) NULL,
  `address` TEXT NULL,
  `category` VARCHAR(80) NULL,
  `credit_days` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_proc_sup_biz_name_unique` (`business_id`, `supplier_name`),
  KEY `hm_proc_sup_biz_loc_idx` (`business_id`, `business_location_id`),
  KEY `hm_proc_sup_code_idx` (`supplier_code`),
  KEY `hm_proc_sup_active_idx` (`business_id`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_purchase_requests` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `request_no` VARCHAR(80) NOT NULL,
  `request_date` DATE NULL,
  `department` VARCHAR(100) NOT NULL,
  `requested_by` VARCHAR(120) NULL,
  `required_date` DATE NULL,
  `priority` VARCHAR(30) NOT NULL DEFAULT 'normal',
  `item_name` VARCHAR(160) NOT NULL,
  `description` TEXT NULL,
  `qty` DECIMAL(20,3) NOT NULL DEFAULT 0.000,
  `estimated_unit_cost` DECIMAL(20,4) NOT NULL DEFAULT 0.0000,
  `estimated_total` DECIMAL(20,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(30) NOT NULL DEFAULT 'submitted',
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_pr_biz_no_unique` (`business_id`, `request_no`),
  KEY `hm_pr_scope_idx` (`business_id`, `business_location_id`),
  KEY `hm_pr_date_idx` (`business_id`, `request_date`),
  KEY `hm_pr_department_idx` (`business_id`, `department`),
  KEY `hm_pr_status_idx` (`business_id`, `status`),
  KEY `hm_pr_priority_idx` (`business_id`, `priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_purchase_orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `po_no` VARCHAR(80) NOT NULL,
  `po_date` DATE NULL,
  `supplier_id` BIGINT UNSIGNED NOT NULL,
  `request_id` BIGINT UNSIGNED NULL,
  `item_name` VARCHAR(160) NOT NULL,
  `description` TEXT NULL,
  `qty` DECIMAL(20,3) NOT NULL DEFAULT 0.000,
  `unit_cost` DECIMAL(20,4) NOT NULL DEFAULT 0.0000,
  `gross_amount` DECIMAL(20,4) NOT NULL DEFAULT 0.0000,
  `discount_amount` DECIMAL(20,4) NOT NULL DEFAULT 0.0000,
  `tax_amount` DECIMAL(20,4) NOT NULL DEFAULT 0.0000,
  `net_amount` DECIMAL(20,4) NOT NULL DEFAULT 0.0000,
  `received_qty` DECIMAL(20,3) NOT NULL DEFAULT 0.000,
  `expected_delivery_date` DATE NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'ordered',
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_po_biz_no_unique` (`business_id`, `po_no`),
  KEY `hm_po_scope_idx` (`business_id`, `business_location_id`),
  KEY `hm_po_supplier_idx` (`business_id`, `supplier_id`),
  KEY `hm_po_request_idx` (`business_id`, `request_id`),
  KEY `hm_po_date_idx` (`business_id`, `po_date`),
  KEY `hm_po_status_idx` (`business_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_goods_received_notes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `grn_no` VARCHAR(80) NOT NULL,
  `po_id` BIGINT UNSIGNED NOT NULL,
  `received_date` DATE NULL,
  `received_qty` DECIMAL(20,3) NOT NULL DEFAULT 0.000,
  `accepted_qty` DECIMAL(20,3) NOT NULL DEFAULT 0.000,
  `rejected_qty` DECIMAL(20,3) NOT NULL DEFAULT 0.000,
  `accepted_value` DECIMAL(20,4) NOT NULL DEFAULT 0.0000,
  `received_by` VARCHAR(120) NULL,
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_grn_biz_no_unique` (`business_id`, `grn_no`),
  KEY `hm_grn_scope_idx` (`business_id`, `business_location_id`),
  KEY `hm_grn_po_idx` (`business_id`, `po_id`),
  KEY `hm_grn_date_idx` (`business_id`, `received_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ============================================================
-- HOTELMGT_030: Channel Manager / OTA controls
-- ============================================================
-- HOTELMGT_030_SQL.sql
-- Hotel Management Parcel 030: Channel Manager / OTA controls
-- Raw SQL only for this parcel. Run inside each tenant database; no database name is hardcoded.

CREATE TABLE IF NOT EXISTS `hm_sales_channels` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `channel_name` VARCHAR(160) NOT NULL,
  `channel_code` VARCHAR(60) NULL,
  `channel_type` VARCHAR(60) NOT NULL DEFAULT 'ota',
  `contact_email` VARCHAR(160) NULL,
  `commission_percent` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_sales_channels_business_name_unique` (`business_id`,`channel_name`),
  KEY `hm_sales_channels_business_idx` (`business_id`),
  KEY `hm_sales_channels_location_idx` (`business_location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_channel_rate_maps` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `channel_id` BIGINT UNSIGNED NOT NULL,
  `room_type_id` BIGINT UNSIGNED NULL,
  `rate_plan_id` BIGINT UNSIGNED NULL,
  `external_room_code` VARCHAR(100) NULL,
  `external_rate_code` VARCHAR(100) NULL,
  `sell_rate` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'LKR',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_channel_rate_maps_unique` (`business_id`,`channel_id`,`room_type_id`,`rate_plan_id`),
  KEY `hm_channel_rate_maps_business_idx` (`business_id`),
  KEY `hm_channel_rate_maps_location_idx` (`business_location_id`),
  KEY `hm_channel_rate_maps_channel_idx` (`channel_id`),
  KEY `hm_channel_rate_maps_room_type_idx` (`room_type_id`),
  KEY `hm_channel_rate_maps_rate_plan_idx` (`rate_plan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_channel_availability` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `channel_id` BIGINT UNSIGNED NOT NULL,
  `room_type_id` BIGINT UNSIGNED NULL,
  `available_date` DATE NOT NULL,
  `available_rooms` INT NOT NULL DEFAULT 0,
  `stop_sell` TINYINT(1) NOT NULL DEFAULT 0,
  `min_stay` INT NOT NULL DEFAULT 0,
  `max_stay` INT NOT NULL DEFAULT 0,
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_channel_availability_unique` (`business_id`,`channel_id`,`room_type_id`,`available_date`),
  KEY `hm_channel_availability_business_idx` (`business_id`),
  KEY `hm_channel_availability_location_idx` (`business_location_id`),
  KEY `hm_channel_availability_channel_idx` (`channel_id`),
  KEY `hm_channel_availability_room_type_idx` (`room_type_id`),
  KEY `hm_channel_availability_date_idx` (`available_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_channel_bookings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `channel_id` BIGINT UNSIGNED NOT NULL,
  `external_booking_ref` VARCHAR(120) NOT NULL,
  `guest_name` VARCHAR(160) NOT NULL,
  `guest_mobile` VARCHAR(50) NULL,
  `guest_email` VARCHAR(160) NULL,
  `arrival_date` DATE NOT NULL,
  `departure_date` DATE NOT NULL,
  `rooms` INT NOT NULL DEFAULT 1,
  `adults` INT NOT NULL DEFAULT 0,
  `children` INT NOT NULL DEFAULT 0,
  `gross_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `commission_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `net_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(40) NOT NULL DEFAULT 'new',
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_channel_bookings_ref_unique` (`business_id`,`channel_id`,`external_booking_ref`),
  KEY `hm_channel_bookings_business_idx` (`business_id`),
  KEY `hm_channel_bookings_location_idx` (`business_location_id`),
  KEY `hm_channel_bookings_channel_idx` (`channel_id`),
  KEY `hm_channel_bookings_arrival_idx` (`arrival_date`),
  KEY `hm_channel_bookings_departure_idx` (`departure_date`),
  KEY `hm_channel_bookings_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
('hotel.channel_manager.view', 'web', NOW(), NOW()),
('hotel.channel_manager.manage', 'web', NOW(), NOW());

-- HOTELMGT_031_SQL.sql
-- Online Booking Engine / direct web booking layer.
-- Execute in each tenant database. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `hm_online_promotions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `promo_code` VARCHAR(80) NOT NULL,
  `promo_name` VARCHAR(160) NOT NULL,
  `discount_type` VARCHAR(30) NOT NULL DEFAULT 'percent',
  `discount_value` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `valid_from` DATE NULL,
  `valid_to` DATE NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_online_promotions_business_code_unique` (`business_id`, `promo_code`),
  KEY `hm_online_promotions_scope_idx` (`business_id`, `business_location_id`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_online_bookings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `booking_no` VARCHAR(80) NOT NULL,
  `booking_source` VARCHAR(80) NOT NULL DEFAULT 'online_engine',
  `guest_name` VARCHAR(160) NOT NULL,
  `guest_mobile` VARCHAR(50) NULL,
  `guest_email` VARCHAR(160) NULL,
  `arrival_date` DATE NOT NULL,
  `departure_date` DATE NOT NULL,
  `room_type_id` BIGINT UNSIGNED NULL,
  `rate_plan_id` BIGINT UNSIGNED NULL,
  `rooms` INT NOT NULL DEFAULT 1,
  `adults` INT NOT NULL DEFAULT 0,
  `children` INT NOT NULL DEFAULT 0,
  `coupon_code` VARCHAR(80) NULL,
  `gross_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `net_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `advance_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `balance_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `refund_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `refund_note` TEXT NULL,
  `cancel_reason` TEXT NULL,
  `cancelled_at` TIMESTAMP NULL DEFAULT NULL,
  `refunded_at` TIMESTAMP NULL DEFAULT NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'new',
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_online_bookings_business_no_unique` (`business_id`, `booking_no`),
  KEY `hm_online_bookings_scope_status_idx` (`business_id`, `business_location_id`, `status`),
  KEY `hm_online_bookings_dates_idx` (`business_id`, `arrival_date`, `departure_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- HOTELMGT_032 - Advanced Revenue Management
-- =========================================================

CREATE TABLE IF NOT EXISTS `hm_revenue_seasons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned DEFAULT NULL,
  `business_location_id` bigint unsigned DEFAULT NULL,
  `season_code` varchar(40) NOT NULL,
  `season_name` varchar(160) NOT NULL,
  `date_from` date NOT NULL,
  `date_to` date NOT NULL,
  `rate_adjustment_type` varchar(30) NOT NULL DEFAULT 'percent',
  `rate_adjustment_value` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `remarks` text NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_revenue_seasons_business_code_unique` (`business_id`,`season_code`),
  KEY `hm_revenue_seasons_business_location_idx` (`business_id`,`business_location_id`),
  KEY `hm_revenue_seasons_date_idx` (`date_from`,`date_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_yield_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned DEFAULT NULL,
  `business_location_id` bigint unsigned DEFAULT NULL,
  `rule_name` varchar(160) NOT NULL,
  `occupancy_from` decimal(10,2) NOT NULL DEFAULT 0.00,
  `occupancy_to` decimal(10,2) NOT NULL DEFAULT 100.00,
  `adjustment_type` varchar(30) NOT NULL DEFAULT 'percent',
  `adjustment_value` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `priority` int NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `remarks` text NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_yield_rules_business_location_idx` (`business_id`,`business_location_id`),
  KEY `hm_yield_rules_occupancy_idx` (`occupancy_from`,`occupancy_to`,`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_corporate_contracts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned DEFAULT NULL,
  `business_location_id` bigint unsigned DEFAULT NULL,
  `contract_no` varchar(80) NOT NULL,
  `company_name` varchar(180) NOT NULL,
  `contact_person` varchar(160) DEFAULT NULL,
  `mobile` varchar(60) DEFAULT NULL,
  `email` varchar(160) DEFAULT NULL,
  `contract_from` date DEFAULT NULL,
  `contract_to` date DEFAULT NULL,
  `rate_type` varchar(60) DEFAULT 'contracted',
  `discount_percent` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `credit_limit` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `current_balance` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `direct_billing_allowed` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(40) NOT NULL DEFAULT 'active',
  `remarks` text NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_corporate_contracts_business_no_unique` (`business_id`,`contract_no`),
  KEY `hm_corporate_contracts_business_location_idx` (`business_id`,`business_location_id`),
  KEY `hm_corporate_contracts_status_idx` (`status`,`contract_from`,`contract_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_travel_agent_contracts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned DEFAULT NULL,
  `business_location_id` bigint unsigned DEFAULT NULL,
  `agent_name` varchar(180) NOT NULL,
  `agent_code` varchar(60) NOT NULL,
  `commission_type` varchar(30) NOT NULL DEFAULT 'percent',
  `commission_value` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `contract_from` date DEFAULT NULL,
  `contract_to` date DEFAULT NULL,
  `credit_limit` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `status` varchar(40) NOT NULL DEFAULT 'active',
  `remarks` text NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_travel_agent_contracts_business_code_unique` (`business_id`,`agent_code`),
  KEY `hm_travel_agent_contracts_business_location_idx` (`business_id`,`business_location_id`),
  KEY `hm_travel_agent_contracts_status_idx` (`status`,`contract_from`,`contract_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_group_room_blocks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned DEFAULT NULL,
  `business_location_id` bigint unsigned DEFAULT NULL,
  `block_no` varchar(80) NOT NULL,
  `group_name` varchar(180) NOT NULL,
  `arrival_date` date NOT NULL,
  `departure_date` date NOT NULL,
  `blocked_rooms` int NOT NULL DEFAULT 0,
  `released_rooms` int NOT NULL DEFAULT 0,
  `rate_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `cutoff_date` date DEFAULT NULL,
  `status` varchar(40) NOT NULL DEFAULT 'blocked',
  `remarks` text NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_group_room_blocks_business_no_unique` (`business_id`,`block_no`),
  KEY `hm_group_room_blocks_business_location_idx` (`business_id`,`business_location_id`),
  KEY `hm_group_room_blocks_dates_idx` (`arrival_date`,`departure_date`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_split_folio_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned DEFAULT NULL,
  `business_location_id` bigint unsigned DEFAULT NULL,
  `rule_name` varchar(160) NOT NULL,
  `payer_type` varchar(60) NOT NULL,
  `charge_category` varchar(80) NOT NULL,
  `split_type` varchar(40) NOT NULL DEFAULT 'percent',
  `split_value` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `remarks` text NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_split_folio_rules_business_location_idx` (`business_id`,`business_location_id`),
  KEY `hm_split_folio_rules_active_idx` (`is_active`,`payer_type`,`charge_category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
('hotel.revenue_management.view', 'web', NOW(), NOW()),
('hotel.revenue_management.create', 'web', NOW(), NOW()),
('hotel.revenue_management.update', 'web', NOW(), NOW()),
('hotel.revenue_management.delete', 'web', NOW(), NOW()),
('hotel.corporate_contracts.view', 'web', NOW(), NOW()),
('hotel.group_blocks.manage', 'web', NOW(), NOW());
-- HOTELMGT_033_SQL.sql
-- Hotel Management Parcel 033 only
-- City Ledger / Accounts Receivable for corporate, agent and house account direct billing
-- Global tenant SQL: run inside each tenant database. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `hm_city_ledger_accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned DEFAULT NULL,
  `business_location_id` bigint unsigned DEFAULT NULL,
  `account_code` varchar(60) NOT NULL,
  `account_name` varchar(180) NOT NULL,
  `account_type` varchar(40) NOT NULL DEFAULT 'corporate',
  `contact_person` varchar(160) DEFAULT NULL,
  `mobile` varchar(60) DEFAULT NULL,
  `email` varchar(160) DEFAULT NULL,
  `credit_limit` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `current_balance` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `credit_days` int NOT NULL DEFAULT 30,
  `status` varchar(40) NOT NULL DEFAULT 'active',
  `remarks` text NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_city_ledger_accounts_business_code_unique` (`business_id`,`account_code`),
  KEY `hm_city_ledger_accounts_business_location_idx` (`business_id`,`business_location_id`),
  KEY `hm_city_ledger_accounts_status_idx` (`status`,`account_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_city_ledger_invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned DEFAULT NULL,
  `business_location_id` bigint unsigned DEFAULT NULL,
  `ledger_account_id` bigint unsigned NOT NULL,
  `invoice_no` varchar(80) NOT NULL,
  `folio_no` varchar(80) DEFAULT NULL,
  `guest_name` varchar(180) DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `invoice_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `paid_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `balance_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `status` varchar(40) NOT NULL DEFAULT 'open',
  `remarks` text NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_city_ledger_invoices_business_no_unique` (`business_id`,`invoice_no`),
  KEY `hm_city_ledger_invoices_account_idx` (`ledger_account_id`,`status`),
  KEY `hm_city_ledger_invoices_due_idx` (`business_id`,`due_date`,`status`),
  KEY `hm_city_ledger_invoices_location_idx` (`business_id`,`business_location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_city_ledger_receipts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned DEFAULT NULL,
  `business_location_id` bigint unsigned DEFAULT NULL,
  `ledger_account_id` bigint unsigned NOT NULL,
  `invoice_id` bigint unsigned DEFAULT NULL,
  `receipt_no` varchar(80) NOT NULL,
  `receipt_date` date NOT NULL,
  `payment_method` varchar(40) NOT NULL DEFAULT 'cash',
  `reference_no` varchar(120) DEFAULT NULL,
  `receipt_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `remarks` text NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_city_ledger_receipts_business_no_unique` (`business_id`,`receipt_no`),
  KEY `hm_city_ledger_receipts_account_idx` (`ledger_account_id`,`receipt_date`),
  KEY `hm_city_ledger_receipts_invoice_idx` (`invoice_id`),
  KEY `hm_city_ledger_receipts_location_idx` (`business_id`,`business_location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_city_ledger_adjustments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned DEFAULT NULL,
  `business_location_id` bigint unsigned DEFAULT NULL,
  `ledger_account_id` bigint unsigned NOT NULL,
  `adjustment_no` varchar(80) NOT NULL,
  `adjustment_date` date NOT NULL,
  `adjustment_type` varchar(20) NOT NULL DEFAULT 'debit',
  `adjustment_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `reason` text NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_city_ledger_adjustments_business_no_unique` (`business_id`,`adjustment_no`),
  KEY `hm_city_ledger_adjustments_account_idx` (`ledger_account_id`,`adjustment_date`),
  KEY `hm_city_ledger_adjustments_location_idx` (`business_id`,`business_location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
('hotel.city_ledger.view', 'web', NOW(), NOW()),
('hotel.city_ledger.create', 'web', NOW(), NOW()),
('hotel.city_ledger.update', 'web', NOW(), NOW()),
('hotel.city_ledger.receipt', 'web', NOW(), NOW()),
('hotel.city_ledger.adjustment', 'web', NOW(), NOW());
-- HOTELMGT_034_SQL.sql
-- Hotel Management Parcel 034: Cashier Control / Shift Reconciliation
-- Execute inside each tenant database. No database names are hardcoded.

CREATE TABLE IF NOT EXISTS `hm_cashier_shifts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `shift_no` VARCHAR(60) NOT NULL,
  `cashier_user_id` BIGINT UNSIGNED NULL,
  `counter_name` VARCHAR(120) NULL,
  `opening_float` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `expected_cash` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `declared_cash` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `variance_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `opened_at` DATETIME NULL,
  `closed_at` DATETIME NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'open',
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_cashier_shifts_shift_no_unique` (`business_id`,`shift_no`),
  KEY `hm_cashier_shifts_scope_idx` (`business_id`,`business_location_id`,`status`),
  KEY `hm_cashier_shifts_cashier_idx` (`cashier_user_id`,`opened_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_cashier_safe_drops` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `shift_id` BIGINT UNSIGNED NOT NULL,
  `drop_no` VARCHAR(60) NOT NULL,
  `drop_datetime` DATETIME NULL,
  `drop_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `received_by` VARCHAR(120) NULL,
  `safe_bag_no` VARCHAR(120) NULL,
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_cashier_safe_drops_drop_no_unique` (`business_id`,`drop_no`),
  KEY `hm_cashier_safe_drops_shift_idx` (`shift_id`),
  KEY `hm_cashier_safe_drops_scope_idx` (`business_id`,`business_location_id`,`drop_datetime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_cashier_cash_counts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `shift_id` BIGINT UNSIGNED NOT NULL,
  `denomination` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `quantity` INT UNSIGNED NOT NULL DEFAULT 0,
  `line_total` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_cashier_cash_counts_shift_idx` (`shift_id`),
  KEY `hm_cashier_cash_counts_scope_idx` (`business_id`,`business_location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_cashier_variances` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `shift_id` BIGINT UNSIGNED NOT NULL,
  `variance_no` VARCHAR(60) NOT NULL,
  `variance_type` VARCHAR(40) NOT NULL,
  `variance_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `reason` TEXT NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'pending_review',
  `review_note` TEXT NULL,
  `reviewed_by` BIGINT UNSIGNED NULL,
  `reviewed_at` DATETIME NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_cashier_variances_no_unique` (`business_id`,`variance_no`),
  KEY `hm_cashier_variances_shift_idx` (`shift_id`),
  KEY `hm_cashier_variances_scope_idx` (`business_id`,`business_location_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- HOTELMGT_035_SQL.sql
-- Hotel Management Parcel 035: Tax & Compliance
-- Global SQL only. Execute inside each tenant database as needed. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `hm_tax_rules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `tax_code` VARCHAR(60) NOT NULL,
  `tax_name` VARCHAR(160) NOT NULL,
  `tax_type` VARCHAR(40) NOT NULL DEFAULT 'percentage',
  `applies_to` VARCHAR(60) NOT NULL DEFAULT 'room',
  `rate` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `inclusive_type` VARCHAR(40) NOT NULL DEFAULT 'exclusive',
  `effective_from` DATE NULL,
  `effective_to` DATE NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_tax_rules_code_unique` (`business_id`,`tax_code`),
  KEY `hm_tax_rules_scope_idx` (`business_id`,`business_location_id`,`applies_to`,`is_active`),
  KEY `hm_tax_rules_effective_idx` (`effective_from`,`effective_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_service_charge_rules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `rule_code` VARCHAR(60) NOT NULL,
  `rule_name` VARCHAR(160) NOT NULL,
  `applies_to` VARCHAR(60) NOT NULL DEFAULT 'all',
  `rate` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `distribution_method` VARCHAR(80) NULL,
  `effective_from` DATE NULL,
  `effective_to` DATE NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_service_charge_rules_code_unique` (`business_id`,`rule_code`),
  KEY `hm_service_charge_rules_scope_idx` (`business_id`,`business_location_id`,`applies_to`,`is_active`),
  KEY `hm_service_charge_rules_effective_idx` (`effective_from`,`effective_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_tax_invoices` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `invoice_no` VARCHAR(60) NOT NULL,
  `source_type` VARCHAR(60) NULL,
  `source_id` BIGINT UNSIGNED NULL,
  `guest_name` VARCHAR(160) NULL,
  `invoice_date` DATE NULL,
  `net_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `tax_rate` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `tax_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `service_charge_rate` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `service_charge_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `gross_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(40) NOT NULL DEFAULT 'posted',
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_tax_invoices_no_unique` (`business_id`,`invoice_no`),
  KEY `hm_tax_invoices_source_idx` (`source_type`,`source_id`),
  KEY `hm_tax_invoices_scope_idx` (`business_id`,`business_location_id`,`invoice_date`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_tax_period_summaries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `summary_no` VARCHAR(60) NOT NULL,
  `period_from` DATE NOT NULL,
  `period_to` DATE NOT NULL,
  `room_revenue` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `fb_revenue` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `other_revenue` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `tax_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `service_charge_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(40) NOT NULL DEFAULT 'draft',
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_tax_period_summaries_no_unique` (`business_id`,`summary_no`),
  KEY `hm_tax_period_summaries_scope_idx` (`business_id`,`business_location_id`,`period_from`,`period_to`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- HOTELMGT_036_SQL.sql
-- Hotel Management Parcel 036: Energy & Utilities
-- Global SQL only. Execute inside each tenant database as needed. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `hm_utility_meters` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `meter_no` VARCHAR(60) NOT NULL,
  `meter_name` VARCHAR(160) NOT NULL,
  `utility_type` VARCHAR(60) NOT NULL DEFAULT 'electricity',
  `department` VARCHAR(100) NULL,
  `linked_room_id` BIGINT UNSIGNED NULL,
  `unit_name` VARCHAR(40) NOT NULL DEFAULT 'Unit',
  `rate_per_unit` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_utility_meters_no_unique` (`business_id`,`meter_no`),
  KEY `hm_utility_meters_scope_idx` (`business_id`,`business_location_id`,`utility_type`,`is_active`),
  KEY `hm_utility_meters_room_idx` (`linked_room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_utility_readings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `reading_no` VARCHAR(60) NOT NULL,
  `meter_id` BIGINT UNSIGNED NOT NULL,
  `reading_date` DATE NOT NULL,
  `previous_reading` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `current_reading` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `consumption_units` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `rate_per_unit` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `total_cost` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(40) NOT NULL DEFAULT 'draft',
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_utility_readings_no_unique` (`business_id`,`reading_no`),
  KEY `hm_utility_readings_scope_idx` (`business_id`,`business_location_id`,`reading_date`,`status`),
  KEY `hm_utility_readings_meter_idx` (`meter_id`,`reading_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_utility_cost_allocations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `allocation_no` VARCHAR(60) NOT NULL,
  `reading_id` BIGINT UNSIGNED NULL,
  `allocation_type` VARCHAR(60) NOT NULL DEFAULT 'department',
  `target_reference` VARCHAR(160) NULL,
  `allocated_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(40) NOT NULL DEFAULT 'draft',
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_utility_allocations_no_unique` (`business_id`,`allocation_no`),
  KEY `hm_utility_allocations_scope_idx` (`business_id`,`business_location_id`,`allocation_type`,`status`),
  KEY `hm_utility_allocations_reading_idx` (`reading_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- HOTELMGT_037 - Security & Key Control
-- =========================================================

CREATE TABLE IF NOT EXISTS `hm_room_key_cards` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `key_no` VARCHAR(60) NULL,
  `room_id` BIGINT UNSIGNED NULL,
  `reservation_id` BIGINT UNSIGNED NULL,
  `guest_name` VARCHAR(160) NULL,
  `issued_at` DATETIME NULL,
  `expires_at` DATETIME NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'issued',
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_room_key_cards_business_status_idx` (`business_id`, `status`),
  KEY `hm_room_key_cards_location_idx` (`business_location_id`),
  KEY `hm_room_key_cards_room_idx` (`room_id`),
  KEY `hm_room_key_cards_reservation_idx` (`reservation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_visitor_passes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `pass_no` VARCHAR(60) NULL,
  `visitor_name` VARCHAR(160) NOT NULL,
  `mobile` VARCHAR(40) NULL,
  `nic_no` VARCHAR(80) NULL,
  `guest_name` VARCHAR(160) NULL,
  `room_id` BIGINT UNSIGNED NULL,
  `purpose` VARCHAR(160) NULL,
  `check_in_at` DATETIME NULL,
  `check_out_at` DATETIME NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'checked_in',
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_visitor_passes_business_status_idx` (`business_id`, `status`),
  KEY `hm_visitor_passes_location_idx` (`business_location_id`),
  KEY `hm_visitor_passes_room_idx` (`room_id`),
  KEY `hm_visitor_passes_mobile_idx` (`mobile`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_security_incidents` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `incident_no` VARCHAR(60) NULL,
  `incident_date` DATE NULL,
  `incident_time` VARCHAR(20) NULL,
  `incident_type` VARCHAR(80) NOT NULL,
  `severity` VARCHAR(40) NOT NULL DEFAULT 'medium',
  `location_reference` VARCHAR(160) NULL,
  `reported_by` VARCHAR(160) NULL,
  `guest_name` VARCHAR(160) NULL,
  `description` TEXT NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'open',
  `action_taken` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_security_incidents_business_status_idx` (`business_id`, `status`),
  KEY `hm_security_incidents_location_idx` (`business_location_id`),
  KEY `hm_security_incidents_date_idx` (`incident_date`),
  KEY `hm_security_incidents_severity_idx` (`severity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- HOTELMGT_038: Sustainability & Waste Management
-- =========================================================

CREATE TABLE IF NOT EXISTS `hm_sustainability_goals` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `goal_name` VARCHAR(160) NOT NULL,
  `category` VARCHAR(80) NULL,
  `target_value` DECIMAL(20,4) NOT NULL DEFAULT 0.0000,
  `unit` VARCHAR(40) NULL,
  `start_date` DATE NULL,
  `end_date` DATE NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'active',
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_sustainability_goals_business_idx` (`business_id`),
  KEY `hm_sustainability_goals_location_idx` (`business_location_id`),
  KEY `hm_sustainability_goals_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_waste_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `log_date` DATE NULL,
  `department` VARCHAR(100) NULL,
  `waste_type` VARCHAR(80) NOT NULL,
  `quantity` DECIMAL(20,4) NOT NULL DEFAULT 0.0000,
  `unit` VARCHAR(40) NULL,
  `disposal_method` VARCHAR(120) NULL,
  `cost` DECIMAL(20,4) NOT NULL DEFAULT 0.0000,
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_waste_logs_business_idx` (`business_id`),
  KEY `hm_waste_logs_location_idx` (`business_location_id`),
  KEY `hm_waste_logs_date_idx` (`log_date`),
  KEY `hm_waste_logs_type_idx` (`waste_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_green_initiatives` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `initiative_no` VARCHAR(60) NULL,
  `title` VARCHAR(160) NOT NULL,
  `category` VARCHAR(80) NULL,
  `owner_name` VARCHAR(160) NULL,
  `planned_start` DATE NULL,
  `planned_end` DATE NULL,
  `estimated_saving` DECIMAL(20,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(40) NOT NULL DEFAULT 'planned',
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_green_initiatives_business_idx` (`business_id`),
  KEY `hm_green_initiatives_location_idx` (`business_location_id`),
  KEY `hm_green_initiatives_status_idx` (`status`),
  KEY `hm_green_initiatives_no_idx` (`initiative_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- HOTELMGT_039: Staff Rostering & Attendance Control
-- Raw SQL for parcel 039 only. Execute in the selected tenant database.
-- No database name is hardcoded for multi-tenant execution.
-- =========================================================

CREATE TABLE IF NOT EXISTS `hm_staff_roles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `role_name` VARCHAR(120) NOT NULL,
  `department` VARCHAR(100) NULL,
  `standard_hours` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_staff_roles_business_department_idx` (`business_id`, `department`),
  KEY `hm_staff_roles_location_idx` (`business_location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_staff_members` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `employee_no` VARCHAR(60) NULL,
  `name` VARCHAR(160) NOT NULL,
  `mobile` VARCHAR(40) NULL,
  `email` VARCHAR(160) NULL,
  `department` VARCHAR(100) NULL,
  `role_id` BIGINT UNSIGNED NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_staff_members_business_status_idx` (`business_id`, `status`),
  KEY `hm_staff_members_location_idx` (`business_location_id`),
  KEY `hm_staff_members_employee_no_idx` (`employee_no`),
  KEY `hm_staff_members_role_idx` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_staff_roster_shifts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `shift_no` VARCHAR(60) NULL,
  `staff_id` BIGINT UNSIGNED NULL,
  `shift_date` DATE NOT NULL,
  `start_time` TIME NULL,
  `end_time` TIME NULL,
  `department` VARCHAR(100) NULL,
  `station` VARCHAR(120) NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'planned',
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_staff_roster_shifts_business_date_idx` (`business_id`, `shift_date`),
  KEY `hm_staff_roster_shifts_location_idx` (`business_location_id`),
  KEY `hm_staff_roster_shifts_staff_date_idx` (`staff_id`, `shift_date`),
  KEY `hm_staff_roster_shifts_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_staff_attendance_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `staff_id` BIGINT UNSIGNED NOT NULL,
  `attendance_date` DATE NOT NULL,
  `clock_in` TIME NULL,
  `clock_out` TIME NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'present',
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_staff_attendance_logs_business_date_idx` (`business_id`, `attendance_date`),
  KEY `hm_staff_attendance_logs_location_idx` (`business_location_id`),
  KEY `hm_staff_attendance_logs_staff_date_idx` (`staff_id`, `attendance_date`),
  KEY `hm_staff_attendance_logs_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ==========================================================
-- HOTELMGT_040 - Staff Payroll / Payroll Cost Control
-- ==========================================================
-- HOTELMGT_040_SQL.sql
-- Parcel 040 only: Hotel Staff Payroll / Payroll Cost Control.
-- Execute inside each tenant database. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `hm_staff_payroll_rules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `staff_id` BIGINT UNSIGNED NOT NULL,
  `salary_type` VARCHAR(40) NOT NULL DEFAULT 'monthly',
  `basic_salary` DECIMAL(20,4) NOT NULL DEFAULT 0.0000,
  `ot_rate` DECIMAL(20,4) NOT NULL DEFAULT 0.0000,
  `allowance_amount` DECIMAL(20,4) NOT NULL DEFAULT 0.0000,
  `deduction_amount` DECIMAL(20,4) NOT NULL DEFAULT 0.0000,
  `effective_from` DATE NOT NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'active',
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_staff_payroll_rules_business_staff_idx` (`business_id`, `staff_id`),
  KEY `hm_staff_payroll_rules_location_idx` (`business_location_id`),
  KEY `hm_staff_payroll_rules_status_idx` (`status`),
  KEY `hm_staff_payroll_rules_effective_idx` (`effective_from`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_staff_payroll_runs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `payroll_no` VARCHAR(60) NULL,
  `period_from` DATE NOT NULL,
  `period_to` DATE NOT NULL,
  `pay_date` DATE NULL,
  `department` VARCHAR(100) NULL,
  `gross_amount` DECIMAL(20,4) NOT NULL DEFAULT 0.0000,
  `deduction_amount` DECIMAL(20,4) NOT NULL DEFAULT 0.0000,
  `net_amount` DECIMAL(20,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(40) NOT NULL DEFAULT 'draft',
  `remarks` TEXT NULL,
  `approved_by` BIGINT UNSIGNED NULL,
  `approved_at` TIMESTAMP NULL DEFAULT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_staff_payroll_runs_business_period_idx` (`business_id`, `period_from`, `period_to`),
  KEY `hm_staff_payroll_runs_location_idx` (`business_location_id`),
  KEY `hm_staff_payroll_runs_status_idx` (`status`),
  KEY `hm_staff_payroll_runs_payroll_no_idx` (`payroll_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_staff_payroll_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `payroll_run_id` BIGINT UNSIGNED NOT NULL,
  `staff_id` BIGINT UNSIGNED NOT NULL,
  `payroll_rule_id` BIGINT UNSIGNED NULL,
  `attendance_days` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `ot_hours` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `basic_amount` DECIMAL(20,4) NOT NULL DEFAULT 0.0000,
  `ot_amount` DECIMAL(20,4) NOT NULL DEFAULT 0.0000,
  `allowance_amount` DECIMAL(20,4) NOT NULL DEFAULT 0.0000,
  `deduction_amount` DECIMAL(20,4) NOT NULL DEFAULT 0.0000,
  `gross_amount` DECIMAL(20,4) NOT NULL DEFAULT 0.0000,
  `net_amount` DECIMAL(20,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(40) NOT NULL DEFAULT 'draft',
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_staff_payroll_lines_business_run_idx` (`business_id`, `payroll_run_id`),
  KEY `hm_staff_payroll_lines_location_idx` (`business_location_id`),
  KEY `hm_staff_payroll_lines_staff_idx` (`staff_id`),
  KEY `hm_staff_payroll_lines_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- HOTELMGT_041 - Staff Training & Compliance
-- =========================================================

CREATE TABLE IF NOT EXISTS `hm_staff_training_courses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `course_code` VARCHAR(60) NOT NULL,
  `course_name` VARCHAR(191) NOT NULL,
  `department` VARCHAR(100) NULL,
  `training_type` VARCHAR(60) NOT NULL DEFAULT 'service',
  `validity_days` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_mandatory` TINYINT(1) NOT NULL DEFAULT 0,
  `status` VARCHAR(40) NOT NULL DEFAULT 'active',
  `description` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_stc_business_course_code_unique` (`business_id`, `course_code`),
  KEY `hm_stc_scope_idx` (`business_id`, `business_location_id`, `department`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_staff_training_sessions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `course_id` BIGINT UNSIGNED NOT NULL,
  `session_no` VARCHAR(60) NOT NULL,
  `trainer_name` VARCHAR(191) NULL,
  `training_date` DATE NOT NULL,
  `start_time` TIME NULL,
  `end_time` TIME NULL,
  `venue` VARCHAR(191) NULL,
  `capacity` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` VARCHAR(40) NOT NULL DEFAULT 'planned',
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_sts_business_session_no_unique` (`business_id`, `session_no`),
  KEY `hm_sts_scope_idx` (`business_id`, `business_location_id`, `course_id`, `training_date`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_staff_training_records` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `course_id` BIGINT UNSIGNED NULL,
  `session_id` BIGINT UNSIGNED NOT NULL,
  `staff_id` BIGINT UNSIGNED NOT NULL,
  `attendance_status` VARCHAR(40) NOT NULL DEFAULT 'assigned',
  `score` DECIMAL(8,2) NULL,
  `result_status` VARCHAR(40) NOT NULL DEFAULT 'assigned',
  `completed_at` DATE NULL,
  `valid_until` DATE NULL,
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_str_session_staff_unique` (`business_id`, `session_id`, `staff_id`),
  KEY `hm_str_scope_idx` (`business_id`, `business_location_id`, `course_id`, `staff_id`, `result_status`),
  KEY `hm_str_valid_until_idx` (`business_id`, `valid_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- =========================================================
-- HOTELMGT_042 - Assets & Equipment Management
-- Specific SQL only for parcel HOTELMGT_042
-- Global tenant SQL: run inside each tenant database. Do not hardcode database names.
-- =========================================================

CREATE TABLE IF NOT EXISTS `hm_assets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `asset_code` VARCHAR(60) NOT NULL,
  `asset_name` VARCHAR(191) NOT NULL,
  `asset_category` VARCHAR(100) NOT NULL,
  `department` VARCHAR(100) NULL,
  `room_id` BIGINT UNSIGNED NULL,
  `serial_no` VARCHAR(100) NULL,
  `purchase_date` DATE NULL,
  `purchase_cost` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `current_value` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(40) NOT NULL DEFAULT 'active',
  `disposal_date` DATE NULL,
  `disposal_value` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `disposal_reason` VARCHAR(191) NULL,
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_assets_business_asset_code_unique` (`business_id`, `asset_code`),
  KEY `hm_assets_scope_idx` (`business_id`, `business_location_id`, `asset_category`, `department`, `status`),
  KEY `hm_assets_room_idx` (`business_id`, `room_id`),
  KEY `hm_assets_serial_idx` (`business_id`, `serial_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_asset_assignments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `asset_id` BIGINT UNSIGNED NOT NULL,
  `assignment_no` VARCHAR(60) NOT NULL,
  `assigned_to_type` VARCHAR(40) NOT NULL DEFAULT 'room',
  `assigned_to_id` BIGINT UNSIGNED NULL,
  `room_id` BIGINT UNSIGNED NULL,
  `assigned_date` DATE NOT NULL,
  `return_due_date` DATE NULL,
  `returned_date` DATE NULL,
  `condition_out` VARCHAR(40) NOT NULL DEFAULT 'good',
  `condition_in` VARCHAR(40) NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'active',
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_asset_assignments_business_no_unique` (`business_id`, `assignment_no`),
  KEY `hm_asset_assignments_scope_idx` (`business_id`, `business_location_id`, `asset_id`, `status`),
  KEY `hm_asset_assignments_room_idx` (`business_id`, `room_id`),
  KEY `hm_asset_assignments_assigned_to_idx` (`business_id`, `assigned_to_type`, `assigned_to_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_asset_inspections` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `asset_id` BIGINT UNSIGNED NOT NULL,
  `inspection_no` VARCHAR(60) NOT NULL,
  `inspection_date` DATE NOT NULL,
  `condition_status` VARCHAR(40) NOT NULL DEFAULT 'good',
  `next_inspection_date` DATE NULL,
  `maintenance_required` TINYINT(1) NOT NULL DEFAULT 0,
  `estimated_cost` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_asset_inspections_business_no_unique` (`business_id`, `inspection_no`),
  KEY `hm_asset_inspections_scope_idx` (`business_id`, `business_location_id`, `asset_id`, `inspection_date`),
  KEY `hm_asset_inspections_due_idx` (`business_id`, `next_inspection_date`, `maintenance_required`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- HOTELMGT_043_SQL.sql
-- ============================================================
-- HOTELMGT_043_SQL.sql
-- Parcel 043 only: Preventive Maintenance Scheduler
-- Global SQL: execute inside each tenant database. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `hm_preventive_maintenance_plans` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `plan_no` VARCHAR(60) NOT NULL,
  `plan_name` VARCHAR(191) NOT NULL,
  `asset_id` BIGINT UNSIGNED NULL,
  `room_id` BIGINT UNSIGNED NULL,
  `department` VARCHAR(100) NULL,
  `frequency_type` VARCHAR(30) NOT NULL DEFAULT 'monthly',
  `frequency_value` INT UNSIGNED NOT NULL DEFAULT 1,
  `start_date` DATE NOT NULL,
  `last_completed_date` DATE NULL,
  `next_due_date` DATE NULL,
  `priority` VARCHAR(30) NOT NULL DEFAULT 'medium',
  `estimated_cost` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(30) NOT NULL DEFAULT 'active',
  `instructions` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `hm_pm_plans_business_idx` (`business_id`, `business_location_id`),
  KEY `hm_pm_plans_due_idx` (`business_id`, `next_due_date`, `status`),
  KEY `hm_pm_plans_asset_idx` (`asset_id`),
  KEY `hm_pm_plans_room_idx` (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_preventive_maintenance_checklists` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `plan_id` BIGINT UNSIGNED NOT NULL,
  `check_item` VARCHAR(191) NOT NULL,
  `required_result` VARCHAR(191) NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_required` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `hm_pm_checklists_business_idx` (`business_id`, `business_location_id`),
  KEY `hm_pm_checklists_plan_idx` (`plan_id`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_preventive_maintenance_tasks` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `business_location_id` INT UNSIGNED NULL,
  `plan_id` BIGINT UNSIGNED NULL,
  `task_no` VARCHAR(60) NOT NULL,
  `asset_id` BIGINT UNSIGNED NULL,
  `room_id` BIGINT UNSIGNED NULL,
  `task_title` VARCHAR(191) NOT NULL,
  `due_date` DATE NOT NULL,
  `assigned_to` VARCHAR(100) NULL,
  `priority` VARCHAR(30) NOT NULL DEFAULT 'medium',
  `estimated_cost` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `actual_cost` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(30) NOT NULL DEFAULT 'scheduled',
  `checklist_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `completed_date` DATE NULL,
  `completion_notes` TEXT NULL,
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `hm_pm_tasks_business_idx` (`business_id`, `business_location_id`),
  KEY `hm_pm_tasks_due_idx` (`business_id`, `due_date`, `status`),
  KEY `hm_pm_tasks_plan_idx` (`plan_id`),
  KEY `hm_pm_tasks_asset_idx` (`asset_id`),
  KEY `hm_pm_tasks_room_idx` (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'hotel.preventive_maintenance.view', 'web', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'hotel.preventive_maintenance.view');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'hotel.preventive_maintenance.create', 'web', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'hotel.preventive_maintenance.create');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'hotel.preventive_maintenance.update', 'web', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'hotel.preventive_maintenance.update');
