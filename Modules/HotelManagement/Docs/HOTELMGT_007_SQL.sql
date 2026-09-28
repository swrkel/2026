-- HOTELMGT_007_SQL.sql
-- Hotel Management - Guest CRM enhancements only for parcel 007.
-- Run once in each tenant database that uses Hotel Management.

ALTER TABLE `hm_guests`
    ADD COLUMN IF NOT EXISTS `nationality` VARCHAR(100) NULL AFTER `id_no`,
    ADD COLUMN IF NOT EXISTS `date_of_birth` DATE NULL AFTER `nationality`,
    ADD COLUMN IF NOT EXISTS `gender` VARCHAR(30) NULL AFTER `date_of_birth`,
    ADD COLUMN IF NOT EXISTS `address` TEXT NULL AFTER `gender`,
    ADD COLUMN IF NOT EXISTS `vip_level` VARCHAR(50) NULL AFTER `address`,
    ADD COLUMN IF NOT EXISTS `marketing_consent` TINYINT(1) NOT NULL DEFAULT 0 AFTER `vip_level`;

ALTER TABLE `hm_guest_preferences`
    ADD COLUMN IF NOT EXISTS `deleted_at` TIMESTAMP NULL DEFAULT NULL AFTER `updated_at`;

ALTER TABLE `hm_guest_notes`
    ADD COLUMN IF NOT EXISTS `deleted_at` TIMESTAMP NULL DEFAULT NULL AFTER `updated_at`;

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
