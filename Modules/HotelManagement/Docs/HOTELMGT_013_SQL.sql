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
