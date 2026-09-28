/*
 PETROPD PAYMENT RECONCILIATION CONTROL CENTRE - PARCEL 2
 23 July 2026

 Run on EACH tenant database AFTER Parcel 1 payment-integrity SQL/migrations.
 Idempotent. No financial amount or Shift ID is changed.
*/

DELIMITER $$
DROP PROCEDURE IF EXISTS pd_add_recon_column_if_missing$$
CREATE PROCEDURE pd_add_recon_column_if_missing(
    IN p_column VARCHAR(128), IN p_definition TEXT
)
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = 'petro_pd_payment_reconciliation_events'
    ) AND NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'petro_pd_payment_reconciliation_events'
          AND column_name = p_column
    ) THEN
        SET @sql = CONCAT(
            'ALTER TABLE `petro_pd_payment_reconciliation_events` ADD COLUMN `',
            REPLACE(p_column, '`', '``'), '` ', p_definition
        );
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DROP PROCEDURE IF EXISTS pd_add_recon_index_if_missing$$
CREATE PROCEDURE pd_add_recon_index_if_missing(
    IN p_index VARCHAR(128), IN p_columns TEXT
)
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = 'petro_pd_payment_reconciliation_events'
    ) AND NOT EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = 'petro_pd_payment_reconciliation_events'
          AND index_name = p_index
    ) THEN
        SET @sql = CONCAT(
            'ALTER TABLE `petro_pd_payment_reconciliation_events` ADD INDEX `',
            REPLACE(p_index, '`', '``'), '` (', p_columns, ')'
        );
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

CALL pd_add_recon_column_if_missing('first_seen_at', 'TIMESTAMP NULL AFTER `event_hash`');
CALL pd_add_recon_column_if_missing('last_seen_at', 'TIMESTAMP NULL AFTER `first_seen_at`');
CALL pd_add_recon_column_if_missing('last_checked_at', 'TIMESTAMP NULL AFTER `last_seen_at`');
CALL pd_add_recon_column_if_missing('occurrence_count', 'INT UNSIGNED NOT NULL DEFAULT 1 AFTER `last_checked_at`');
CALL pd_add_recon_column_if_missing('resolved_by', 'BIGINT UNSIGNED NULL AFTER `resolved_at`');
CALL pd_add_recon_column_if_missing('resolution_note', 'TEXT NULL AFTER `resolved_by`');

CALL pd_add_recon_index_if_missing('ppdre_first_seen_idx', '`first_seen_at`');
CALL pd_add_recon_index_if_missing('ppdre_last_seen_idx', '`last_seen_at`');
CALL pd_add_recon_index_if_missing('ppdre_last_checked_idx', '`last_checked_at`');
CALL pd_add_recon_index_if_missing('ppdre_resolved_by_idx', '`resolved_by`');
CALL pd_add_recon_index_if_missing('ppdre_business_open_severity_idx', '`business_id`,`resolved_at`,`severity`');
CALL pd_add_recon_index_if_missing('ppdre_business_operator_shift_idx', '`business_id`,`pump_operator_id`,`settlement_id`');

UPDATE petro_pd_payment_reconciliation_events
SET first_seen_at = COALESCE(first_seen_at, created_at, NOW()),
    last_seen_at = COALESCE(last_seen_at, updated_at, created_at, NOW()),
    occurrence_count = CASE WHEN occurrence_count IS NULL OR occurrence_count = 0 THEN 1 ELSE occurrence_count END;

DROP PROCEDURE IF EXISTS pd_add_recon_column_if_missing;
DROP PROCEDURE IF EXISTS pd_add_recon_index_if_missing;

/* Verification */
SELECT
    COUNT(*) AS total_events,
    SUM(CASE WHEN resolved_at IS NULL AND LOWER(COALESCE(severity,'critical'))='critical' THEN 1 ELSE 0 END) AS open_critical,
    SUM(CASE WHEN resolved_at IS NOT NULL THEN 1 ELSE 0 END) AS resolved_events,
    MAX(last_seen_at) AS latest_seen_at
FROM petro_pd_payment_reconciliation_events;
