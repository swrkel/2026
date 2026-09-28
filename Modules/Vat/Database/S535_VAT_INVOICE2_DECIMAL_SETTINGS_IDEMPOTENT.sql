-- S535 - VAT Invoice2 decimal and rounding settings (idempotent)
-- Run this in every applicable tenant database when Laravel migrations are not used.
-- Compatible with MySQL/MariaDB versions that do not support ADD COLUMN IF NOT EXISTS.
-- Existing columns and saved values are preserved.

SET @s535_table_exists := (
    SELECT COUNT(*)
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'vat_invoice2_prefixes'
);

SET @s535_column_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'vat_invoice2_prefixes'
      AND COLUMN_NAME = 'unit_vat_no_of_decimals'
);
SET @s535_sql := IF(
    @s535_table_exists > 0 AND @s535_column_exists = 0,
    'ALTER TABLE `vat_invoice2_prefixes` ADD COLUMN `unit_vat_no_of_decimals` INT UNSIGNED NULL AFTER `starting_no`',
    'SELECT 1'
);
PREPARE s535_stmt FROM @s535_sql;
EXECUTE s535_stmt;
DEALLOCATE PREPARE s535_stmt;

SET @s535_column_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'vat_invoice2_prefixes'
      AND COLUMN_NAME = 'unit_vat_rounding_off_required'
);
SET @s535_sql := IF(
    @s535_table_exists > 0 AND @s535_column_exists = 0,
    'ALTER TABLE `vat_invoice2_prefixes` ADD COLUMN `unit_vat_rounding_off_required` TINYINT(1) NOT NULL DEFAULT 0 AFTER `unit_vat_no_of_decimals`',
    'SELECT 1'
);
PREPARE s535_stmt FROM @s535_sql;
EXECUTE s535_stmt;
DEALLOCATE PREPARE s535_stmt;

SET @s535_column_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'vat_invoice2_prefixes'
      AND COLUMN_NAME = 'sub_total_no_of_decimals'
);
SET @s535_sql := IF(
    @s535_table_exists > 0 AND @s535_column_exists = 0,
    'ALTER TABLE `vat_invoice2_prefixes` ADD COLUMN `sub_total_no_of_decimals` INT UNSIGNED NULL AFTER `unit_vat_rounding_off_required`',
    'SELECT 1'
);
PREPARE s535_stmt FROM @s535_sql;
EXECUTE s535_stmt;
DEALLOCATE PREPARE s535_stmt;

SET @s535_column_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'vat_invoice2_prefixes'
      AND COLUMN_NAME = 'sub_total_rounding_off_required'
);
SET @s535_sql := IF(
    @s535_table_exists > 0 AND @s535_column_exists = 0,
    'ALTER TABLE `vat_invoice2_prefixes` ADD COLUMN `sub_total_rounding_off_required` TINYINT(1) NOT NULL DEFAULT 0 AFTER `sub_total_no_of_decimals`',
    'SELECT 1'
);
PREPARE s535_stmt FROM @s535_sql;
EXECUTE s535_stmt;
DEALLOCATE PREPARE s535_stmt;

SET @s535_table_exists := NULL;
SET @s535_column_exists := NULL;
SET @s535_sql := NULL;
