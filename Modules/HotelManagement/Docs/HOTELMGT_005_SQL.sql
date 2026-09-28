/* HOTELMGT_005_SQL.sql
   Hotel Management parcel 005 only.
   Run this in each tenant database. No database name is used.
*/

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
