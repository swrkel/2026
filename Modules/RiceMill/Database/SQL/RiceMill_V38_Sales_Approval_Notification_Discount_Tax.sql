-- Rice Mill v38 - Sales Approval Notification setting + Discount Type + Percentage Tax
-- Incremental SQL for an EXISTING tenant database.
-- Safe to run repeatedly in phpMyAdmin. No stored procedure is used.
-- Run inside the correct tenant database only (for example: nivasa_2003).
--
-- The Sales Approval auto-notify switch itself is stored in rcm_settings.settings JSON,
-- so it needs no new settings column/table. This SQL adds only the Sales Invoice fields
-- required to remember the selected discount type/value and tax percentage.
SET NAMES utf8mb4;

SET @rcm_v38_has_discount_type := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rcm_dispatches' AND COLUMN_NAME = 'discount_type'
);
SET @rcm_v38_sql := IF(
    @rcm_v38_has_discount_type = 0,
    'ALTER TABLE `rcm_dispatches` ADD COLUMN `discount_type` VARCHAR(20) NOT NULL DEFAULT ''fixed'' AFTER `subtotal`',
    'SELECT ''rcm_dispatches.discount_type already exists'''
);
PREPARE rcm_v38_stmt FROM @rcm_v38_sql;
EXECUTE rcm_v38_stmt;
DEALLOCATE PREPARE rcm_v38_stmt;

SET @rcm_v38_has_discount_value := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rcm_dispatches' AND COLUMN_NAME = 'discount_value'
);
SET @rcm_v38_sql := IF(
    @rcm_v38_has_discount_value = 0,
    'ALTER TABLE `rcm_dispatches` ADD COLUMN `discount_value` DECIMAL(20,4) NOT NULL DEFAULT 0 AFTER `discount_type`',
    'SELECT ''rcm_dispatches.discount_value already exists'''
);
PREPARE rcm_v38_stmt FROM @rcm_v38_sql;
EXECUTE rcm_v38_stmt;
DEALLOCATE PREPARE rcm_v38_stmt;

SET @rcm_v38_has_tax_percent := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rcm_dispatches' AND COLUMN_NAME = 'tax_percent'
);
SET @rcm_v38_sql := IF(
    @rcm_v38_has_tax_percent = 0,
    'ALTER TABLE `rcm_dispatches` ADD COLUMN `tax_percent` DECIMAL(10,4) NOT NULL DEFAULT 0 AFTER `discount_amount`',
    'SELECT ''rcm_dispatches.tax_percent already exists'''
);
PREPARE rcm_v38_stmt FROM @rcm_v38_sql;
EXECUTE rcm_v38_stmt;
DEALLOCATE PREPARE rcm_v38_stmt;

-- Backfill historical rows. Prior versions treated Discount as a fixed amount.
UPDATE `rcm_dispatches`
SET `discount_type` = 'fixed',
    `discount_value` = `discount_amount`
WHERE `discount_value` = 0
  AND `discount_amount` <> 0;

-- Recover historical Tax % where a positive taxable amount exists.
UPDATE `rcm_dispatches`
SET `tax_percent` = ROUND((`tax_amount` / (`subtotal` - `discount_amount`)) * 100, 4)
WHERE `tax_percent` = 0
  AND `tax_amount` <> 0
  AND (`subtotal` - `discount_amount`) > 0;
