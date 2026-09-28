-- Rice Mill v9 - Receive Paddy / Paddy Variety settings schema update
-- Safe for phpMyAdmin/MySQL versions that do not support ADD COLUMN IF NOT EXISTS.
-- Run on each TENANT database once when upgrading from Rice Mill v8 or earlier.

SET @db_name := DATABASE();

SET @sql := (SELECT IF(
    EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='rcm_paddy_varieties' AND COLUMN_NAME='default_moisture_percent'),
    'SELECT 1',
    'ALTER TABLE `rcm_paddy_varieties` ADD COLUMN `default_moisture_percent` DECIMAL(8,3) NULL AFTER `name`'
)); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(
    EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='rcm_paddy_varieties' AND COLUMN_NAME='foreign_matter_limit_percent'),
    'SELECT 1',
    'ALTER TABLE `rcm_paddy_varieties` ADD COLUMN `foreign_matter_limit_percent` DECIMAL(8,3) NULL AFTER `default_moisture_percent`'
)); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(
    EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='rcm_paddy_varieties' AND COLUMN_NAME='expected_rice_yield_percent'),
    'SELECT 1',
    'ALTER TABLE `rcm_paddy_varieties` ADD COLUMN `expected_rice_yield_percent` DECIMAL(8,3) NULL AFTER `foreign_matter_limit_percent`'
)); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(
    EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='rcm_paddy_varieties' AND COLUMN_NAME='expected_broken_rice_percent'),
    'SELECT 1',
    'ALTER TABLE `rcm_paddy_varieties` ADD COLUMN `expected_broken_rice_percent` DECIMAL(8,3) NULL AFTER `expected_rice_yield_percent`'
)); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(
    EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='rcm_paddy_varieties' AND COLUMN_NAME='expected_bran_percent'),
    'SELECT 1',
    'ALTER TABLE `rcm_paddy_varieties` ADD COLUMN `expected_bran_percent` DECIMAL(8,3) NULL AFTER `expected_broken_rice_percent`'
)); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(
    EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='rcm_paddy_varieties' AND COLUMN_NAME='expected_husk_percent'),
    'SELECT 1',
    'ALTER TABLE `rcm_paddy_varieties` ADD COLUMN `expected_husk_percent` DECIMAL(8,3) NULL AFTER `expected_bran_percent`'
)); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(
    EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='rcm_paddy_varieties' AND COLUMN_NAME='expected_process_loss_percent'),
    'SELECT 1',
    'ALTER TABLE `rcm_paddy_varieties` ADD COLUMN `expected_process_loss_percent` DECIMAL(8,3) NULL AFTER `expected_husk_percent`'
)); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(
    EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='rcm_paddy_varieties' AND COLUMN_NAME='quality_grade'),
    'SELECT 1',
    'ALTER TABLE `rcm_paddy_varieties` ADD COLUMN `quality_grade` VARCHAR(50) NULL AFTER `expected_process_loss_percent`'
)); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(
    EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='rcm_paddy_varieties' AND COLUMN_NAME='lot_opening_number'),
    'SELECT 1',
    'ALTER TABLE `rcm_paddy_varieties` ADD COLUMN `lot_opening_number` BIGINT UNSIGNED NOT NULL DEFAULT 1 AFTER `quality_grade`'
)); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Preserve existing rows with a usable opening number.
UPDATE `rcm_paddy_varieties` SET `lot_opening_number` = 1 WHERE `lot_opening_number` IS NULL OR `lot_opening_number` < 1;
