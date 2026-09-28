-- Petro PD-New / Pumper Dashboard-New exclusive pairing patch
-- Safe to rerun after both modules are installed in each tenant database.
SET NAMES utf8mb4;


-- Enforce one finalized Petro PD-New reference for each PONE shift.  Older
-- Pumper Dashboard-New migrations did not include this business/shift key.
-- When historical duplicates exist, the installer leaves the data untouched;
-- the verification script reports the duplicate so it can be resolved safely.
DROP PROCEDURE IF EXISTS `pdnew_harden_pone_settlement_reference`;
DELIMITER $$
CREATE PROCEDURE `pdnew_harden_pone_settlement_reference`()
BEGIN
    DECLARE duplicate_groups BIGINT DEFAULT 0;

    IF EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = 'pone_shift_settlement_references'
    ) AND NOT EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = 'pone_shift_settlement_references'
          AND index_name = 'pone_settlement_business_shift_uq'
    ) THEN
        SELECT COUNT(*) INTO duplicate_groups
        FROM (
            SELECT `business_id`, `shift_id`
            FROM `pone_shift_settlement_references`
            GROUP BY `business_id`, `shift_id`
            HAVING COUNT(*) > 1
        ) AS duplicate_reference_groups;

        IF duplicate_groups = 0 THEN
            ALTER TABLE `pone_shift_settlement_references`
              ADD UNIQUE KEY `pone_settlement_business_shift_uq` (`business_id`,`shift_id`);
        END IF;
    END IF;
END$$
DELIMITER ;

CALL `pdnew_harden_pone_settlement_reference`();
DROP PROCEDURE IF EXISTS `pdnew_harden_pone_settlement_reference`;

-- Keep the legacy enum values only for data compatibility; the application fixes
-- all new settings to petro_pd_new and never invokes the legacy bridges.
ALTER TABLE `pone_module_settings`
  MODIFY COLUMN `integration_mode`
    ENUM('petro_pd_new','petropd','local_only')
    NOT NULL DEFAULT 'petro_pd_new';

UPDATE `pone_module_settings`
SET `integration_enabled` = 1,
    `integration_mode` = 'petro_pd_new',
    `sync_during_operation` = 1,
    `require_clean_sync_before_close` = 1,
    `updated_at` = CURRENT_TIMESTAMP
WHERE `integration_mode` <> 'petro_pd_new'
   OR `integration_enabled` <> 1
   OR `sync_during_operation` <> 1
   OR `require_clean_sync_before_close` <> 1;

-- Historical push-link rows are retained for audit but are inactive. Petro
-- PD-New reads immutable snapshots directly and never invokes a target table.
UPDATE `pone_integration_links`
SET `status` = 'retired',
    `last_error` = 'Retired: Pumper Dashboard-New is paired exclusively with Petro PD-New.',
    `updated_at` = CURRENT_TIMESTAMP
WHERE `status` <> 'retired';

-- Pending legacy publication states become source-ready. Petro PD-New pulls the
-- complete source graph after a shift is closed.
UPDATE `pone_shifts`
SET `integration_status` = CASE WHEN `status` = 'closed' THEN 'synced' ELSE `integration_status` END,
    `integration_error` = NULL,
    `updated_at` = CURRENT_TIMESTAMP
WHERE `status` = 'closed'
  AND `integration_status` IN ('pending','failed');

UPDATE `pone_pump_assignments`
SET `integration_status` = 'synced',
    `integration_error` = NULL,
    `updated_at` = CURRENT_TIMESTAMP
WHERE `integration_status` IN ('pending','failed');

UPDATE `pone_payments`
SET `integration_status` = 'synced',
    `integration_error` = NULL,
    `updated_at` = CURRENT_TIMESTAMP
WHERE `integration_status` IN ('pending','failed');

UPDATE `pone_other_sales`
SET `integration_status` = 'synced',
    `integration_error` = NULL,
    `updated_at` = CURRENT_TIMESTAMP
WHERE `integration_status` IN ('pending','failed');

UPDATE `pone_unload_stocks`
SET `integration_status` = 'synced',
    `integration_error` = NULL,
    `updated_at` = CURRENT_TIMESTAMP
WHERE `integration_status` IN ('pending','failed');

UPDATE `pone_day_entries`
SET `integration_status` = 'synced',
    `integration_error` = NULL,
    `updated_at` = CURRENT_TIMESTAMP
WHERE `integration_status` IN ('pending','failed');
