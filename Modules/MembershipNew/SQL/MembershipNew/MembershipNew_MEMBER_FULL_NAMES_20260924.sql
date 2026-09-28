-- Membership New: add Full Name fields to mn_members safely.
-- Run against the active database that hosts Membership New for the business.
-- No database name is hard-coded. Existing data is preserved.

SET NAMES utf8mb4;

SET @mn_has_members := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mn_members');
SET @mn_has_full_name := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mn_members' AND COLUMN_NAME = 'full_name');
SET @mn_sql := IF(@mn_has_members = 1 AND @mn_has_full_name = 0,
    'ALTER TABLE `mn_members` ADD COLUMN `full_name` varchar(255) NULL AFTER `last_name`',
    'SELECT 1');
PREPARE mn_stmt FROM @mn_sql;
EXECUTE mn_stmt;
DEALLOCATE PREPARE mn_stmt;

SET @mn_has_full_name_second := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mn_members' AND COLUMN_NAME = 'full_name_second_language');
SET @mn_sql := IF(@mn_has_members = 1 AND @mn_has_full_name_second = 0,
    'ALTER TABLE `mn_members` ADD COLUMN `full_name_second_language` varchar(255) NULL AFTER `full_name`',
    'SELECT 1');
PREPARE mn_stmt FROM @mn_sql;
EXECUTE mn_stmt;
DEALLOCATE PREPARE mn_stmt;
