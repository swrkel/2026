-- Membership New - Member Profile Fields
-- 24 Sep 2026
-- Safe to run on the currently selected central/tenant business database.
-- No database name is hard-coded. Existing values are preserved.

SET NAMES utf8mb4;

SET @mn_has_title := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mn_members' AND COLUMN_NAME = 'title');
SET @mn_sql := IF(@mn_has_title = 0, 'ALTER TABLE `mn_members` ADD COLUMN `title` varchar(30) NULL AFTER `member_code`', 'SELECT 1'); PREPARE mn_stmt FROM @mn_sql; EXECUTE mn_stmt; DEALLOCATE PREPARE mn_stmt;

SET @mn_has_region_id := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mn_members' AND COLUMN_NAME = 'region_id');
SET @mn_sql := IF(@mn_has_region_id = 0, 'ALTER TABLE `mn_members` ADD COLUMN `region_id` bigint unsigned NULL AFTER `full_name_second_language`, ADD INDEX `mn_members_region_id_index` (`region_id`)', 'SELECT 1'); PREPARE mn_stmt FROM @mn_sql; EXECUTE mn_stmt; DEALLOCATE PREPARE mn_stmt;

SET @mn_has_other_mobiles := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mn_members' AND COLUMN_NAME = 'other_mobile_nos');
SET @mn_sql := IF(@mn_has_other_mobiles = 0, 'ALTER TABLE `mn_members` ADD COLUMN `other_mobile_nos` text NULL AFTER `mobile`', 'SELECT 1'); PREPARE mn_stmt FROM @mn_sql; EXECUTE mn_stmt; DEALLOCATE PREPARE mn_stmt;

SET @mn_has_business_name := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mn_members' AND COLUMN_NAME = 'business_name');
SET @mn_sql := IF(@mn_has_business_name = 0, 'ALTER TABLE `mn_members` ADD COLUMN `business_name` varchar(191) NULL AFTER `other_mobile_nos`', 'SELECT 1'); PREPARE mn_stmt FROM @mn_sql; EXECUTE mn_stmt; DEALLOCATE PREPARE mn_stmt;

SET @mn_has_membership_type := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mn_members' AND COLUMN_NAME = 'membership_type_id');
SET @mn_sql := IF(@mn_has_membership_type = 0, 'ALTER TABLE `mn_members` ADD COLUMN `membership_type_id` bigint unsigned NULL AFTER `business_name`, ADD INDEX `mn_members_membership_type_id_index` (`membership_type_id`)', 'SELECT 1'); PREPARE mn_stmt FROM @mn_sql; EXECUTE mn_stmt; DEALLOCATE PREPARE mn_stmt;

SET @mn_has_shares := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mn_members' AND COLUMN_NAME = 'no_of_shares');
SET @mn_sql := IF(@mn_has_shares = 0, 'ALTER TABLE `mn_members` ADD COLUMN `no_of_shares` decimal(22,4) NULL AFTER `membership_type_id`', 'SELECT 1'); PREPARE mn_stmt FROM @mn_sql; EXECUTE mn_stmt; DEALLOCATE PREPARE mn_stmt;

SET @mn_has_share_value := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mn_members' AND COLUMN_NAME = 'total_share_value');
SET @mn_sql := IF(@mn_has_share_value = 0, 'ALTER TABLE `mn_members` ADD COLUMN `total_share_value` decimal(22,4) NULL AFTER `no_of_shares`', 'SELECT 1'); PREPARE mn_stmt FROM @mn_sql; EXECUTE mn_stmt; DEALLOCATE PREPARE mn_stmt;

SET @mn_has_gender := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mn_members' AND COLUMN_NAME = 'gender');
SET @mn_sql := IF(@mn_has_gender = 0, 'ALTER TABLE `mn_members` ADD COLUMN `gender` varchar(30) NULL AFTER `total_share_value`', 'SELECT 1'); PREPARE mn_stmt FROM @mn_sql; EXECUTE mn_stmt; DEALLOCATE PREPARE mn_stmt;
