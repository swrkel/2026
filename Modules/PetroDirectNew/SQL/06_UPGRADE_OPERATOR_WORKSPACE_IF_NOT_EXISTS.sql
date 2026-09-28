-- Petro Direct-New: force-repair the Pump Operator workspace schema.
-- Safe to rerun. Run in the CURRENT TENANT DATABASE, not the central database.
-- This version avoids PREPARE/EXECUTE and avoids fragile AFTER clauses.

SET NAMES utf8mb4;

SELECT DATABASE() AS `petro_direct_new_target_database`;

DROP PROCEDURE IF EXISTS `pdirectnew_repair_operator_workspace`;
DELIMITER $$
CREATE PROCEDURE `pdirectnew_repair_operator_workspace`()
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = 'pdirectnew_operators'
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'pdirectnew_operators does not exist in the selected database. Run 00_MASTER_INSTALL_PETRO_DIRECT_NEW.sql first.';
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators' AND column_name = 'source_operator_id') THEN
        ALTER TABLE `pdirectnew_operators` ADD COLUMN `source_operator_id` BIGINT UNSIGNED NULL;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators' AND column_name = 'address') THEN
        ALTER TABLE `pdirectnew_operators` ADD COLUMN `address` TEXT NULL;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators' AND column_name = 'landline') THEN
        ALTER TABLE `pdirectnew_operators` ADD COLUMN `landline` VARCHAR(50) NULL;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators' AND column_name = 'dob') THEN
        ALTER TABLE `pdirectnew_operators` ADD COLUMN `dob` DATE NULL;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators' AND column_name = 'email') THEN
        ALTER TABLE `pdirectnew_operators` ADD COLUMN `email` VARCHAR(190) NULL;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators' AND column_name = 'username') THEN
        ALTER TABLE `pdirectnew_operators` ADD COLUMN `username` VARCHAR(100) NULL;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators' AND column_name = 'passcode_hash') THEN
        ALTER TABLE `pdirectnew_operators` ADD COLUMN `passcode_hash` VARCHAR(255) NULL;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators' AND column_name = 'opening_balance') THEN
        ALTER TABLE `pdirectnew_operators` ADD COLUMN `opening_balance` DECIMAL(22,4) NOT NULL DEFAULT 0;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators' AND column_name = 'commission_type') THEN
        ALTER TABLE `pdirectnew_operators` ADD COLUMN `commission_type` VARCHAR(30) NOT NULL DEFAULT 'none';
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators' AND column_name = 'commission_value') THEN
        ALTER TABLE `pdirectnew_operators` ADD COLUMN `commission_value` DECIMAL(22,4) NOT NULL DEFAULT 0;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators' AND column_name = 'short_amount') THEN
        ALTER TABLE `pdirectnew_operators` ADD COLUMN `short_amount` DECIMAL(22,4) NOT NULL DEFAULT 0;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators' AND column_name = 'excess_amount') THEN
        ALTER TABLE `pdirectnew_operators` ADD COLUMN `excess_amount` DECIMAL(22,4) NOT NULL DEFAULT 0;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators' AND column_name = 'transaction_date') THEN
        ALTER TABLE `pdirectnew_operators` ADD COLUMN `transaction_date` DATE NULL;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators' AND column_name = 'is_default') THEN
        ALTER TABLE `pdirectnew_operators` ADD COLUMN `is_default` TINYINT(1) NOT NULL DEFAULT 0;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators' AND column_name = 'can_fullscreen') THEN
        ALTER TABLE `pdirectnew_operators` ADD COLUMN `can_fullscreen` TINYINT(1) NOT NULL DEFAULT 0;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators' AND column_name = 'hide_in_direct_settlement_if_pending_shifts') THEN
        ALTER TABLE `pdirectnew_operators` ADD COLUMN `hide_in_direct_settlement_if_pending_shifts` TINYINT(1) NOT NULL DEFAULT 0;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pdirectnew_operators' AND column_name = 'source_updated_at') THEN
        ALTER TABLE `pdirectnew_operators` ADD COLUMN `source_updated_at` DATETIME NULL;
    END IF;

    UPDATE `pdirectnew_operators`
       SET `commission_type` = 'none'
     WHERE `commission_type` IS NULL OR TRIM(`commission_type`) = '';

    UPDATE `pdirectnew_operators`
       SET `status` = IF(COALESCE(`is_active`, 1) = 1, 'active', 'inactive')
     WHERE `status` IS NULL OR TRIM(`status`) = '';

    -- Make the source identity unique without deleting any operator record.
    UPDATE `pdirectnew_operators` AS duplicate_row
    INNER JOIN (
        SELECT `business_id`, `source_operator_id`, MIN(`id`) AS `keep_id`
        FROM `pdirectnew_operators`
        WHERE `source_operator_id` IS NOT NULL
        GROUP BY `business_id`, `source_operator_id`
        HAVING COUNT(*) > 1
    ) AS duplicate_group
       ON duplicate_group.`business_id` = duplicate_row.`business_id`
      AND duplicate_group.`source_operator_id` = duplicate_row.`source_operator_id`
      AND duplicate_row.`id` <> duplicate_group.`keep_id`
       SET duplicate_row.`source_operator_id` = NULL;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = 'pdirectnew_operators'
          AND index_name = 'pdn_operator_source_uq'
    ) THEN
        ALTER TABLE `pdirectnew_operators`
            ADD UNIQUE INDEX `pdn_operator_source_uq` (`business_id`, `source_operator_id`);
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = 'pdirectnew_operators'
          AND index_name = 'pdn_operator_list_idx'
    ) THEN
        ALTER TABLE `pdirectnew_operators`
            ADD INDEX `pdn_operator_list_idx` (`business_id`, `location_id`, `is_active`, `name`);
    END IF;
END$$
DELIMITER ;

CALL `pdirectnew_repair_operator_workspace`();
DROP PROCEDURE IF EXISTS `pdirectnew_repair_operator_workspace`;

-- Final verification: every row below must show OK.
SELECT
    required_columns.column_name,
    CASE WHEN actual_columns.column_name IS NULL THEN 'MISSING' ELSE 'OK' END AS status
FROM (
    SELECT 'source_operator_id' AS column_name UNION ALL
    SELECT 'address' UNION ALL
    SELECT 'landline' UNION ALL
    SELECT 'dob' UNION ALL
    SELECT 'email' UNION ALL
    SELECT 'username' UNION ALL
    SELECT 'passcode_hash' UNION ALL
    SELECT 'opening_balance' UNION ALL
    SELECT 'commission_type' UNION ALL
    SELECT 'commission_value' UNION ALL
    SELECT 'short_amount' UNION ALL
    SELECT 'excess_amount' UNION ALL
    SELECT 'transaction_date' UNION ALL
    SELECT 'is_default' UNION ALL
    SELECT 'can_fullscreen' UNION ALL
    SELECT 'hide_in_direct_settlement_if_pending_shifts' UNION ALL
    SELECT 'source_updated_at'
) AS required_columns
LEFT JOIN information_schema.columns AS actual_columns
  ON actual_columns.table_schema = DATABASE()
 AND actual_columns.table_name = 'pdirectnew_operators'
 AND actual_columns.column_name = required_columns.column_name
ORDER BY required_columns.column_name;
