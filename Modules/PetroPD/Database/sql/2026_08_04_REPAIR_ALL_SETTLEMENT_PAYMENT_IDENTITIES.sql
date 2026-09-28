-- =====================================================================
-- PETRO PD - ALL TENANT PAYMENT IDENTITY REPAIR
-- Date: 04 August 2026
--
-- PURPOSE
-- -------
-- Repairs schema drift in the six PetroPD settlement payment detail tables:
--   settlement_cash_payments
--   settlement_card_payments
--   settlement_cheque_payments
--   settlement_credit_sale_payments
--   settlement_shortage_payments
--   settlement_excess_payments
--
-- SAFE USAGE
-- ----------
-- 1. Back up the selected tenant database.
-- 2. Select ONE tenant database in phpMyAdmin.
-- 3. Run this complete file.
-- 4. Repeat later for another tenant database.
--
-- This file:
-- - is idempotent;
-- - does not use DECLARE, routines, procedures or DELIMITER;
-- - safely skips missing tables;
-- - safely skips columns and indexes that already exist;
-- - never deletes financial rows;
-- - fills only missing/zero identity and scope values using exact relationships.
--
-- DO NOT run this in the central database unless that database actually
-- contains and uses the PetroPD settlement payment tables.
-- =====================================================================

SET @petropd_schema := DATABASE();
SET @petropd_old_safe_updates := @@SQL_SAFE_UPDATES;
SET SQL_SAFE_UPDATES = 0;

SELECT
    DATABASE() AS selected_tenant_database,
    'Starting PetroPD settlement payment identity repair' AS repair_status;


-- settlement_cash_payments: add pump_payment_id when missing.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cash_payments'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cash_payments'
          AND column_name = 'pump_payment_id'
    ),
    'ALTER TABLE `settlement_cash_payments` ADD COLUMN `pump_payment_id` BIGINT UNSIGNED NULL AFTER `id`',
    'SELECT 'settlement_cash_payments.pump_payment_id already exists, or the table is unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- settlement_cash_payments: add shift_id when missing.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cash_payments'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cash_payments'
          AND column_name = 'shift_id'
    ),
    'ALTER TABLE `settlement_cash_payments` ADD COLUMN `shift_id` BIGINT UNSIGNED NULL AFTER `pump_payment_id`',
    'SELECT 'settlement_cash_payments.shift_id already exists, or the table is unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- settlement_cash_payments: add pump_operator_id when missing.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cash_payments'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cash_payments'
          AND column_name = 'pump_operator_id'
    ),
    'ALTER TABLE `settlement_cash_payments` ADD COLUMN `pump_operator_id` BIGINT UNSIGNED NULL AFTER `shift_id`',
    'SELECT 'settlement_cash_payments.pump_operator_id already exists, or the table is unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- settlement_cash_payments: add index settlement_cash_payments_pump_payment_idx when missing.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cash_payments'
    )
    AND EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cash_payments'
          AND column_name = 'pump_payment_id'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cash_payments'
          AND index_name = 'settlement_cash_payments_pump_payment_idx'
    ),
    'ALTER TABLE `settlement_cash_payments` ADD INDEX `settlement_cash_payments_pump_payment_idx` (`pump_payment_id`)',
    'SELECT 'settlement_cash_payments_pump_payment_idx already exists, required columns are unavailable, or the table is unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- settlement_cash_payments: add index settlement_cash_payments_business_shift_idx when missing.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cash_payments'
    )
    AND EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cash_payments'
          AND column_name = 'business_id'
    )
    AND EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cash_payments'
          AND column_name = 'shift_id'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cash_payments'
          AND index_name = 'settlement_cash_payments_business_shift_idx'
    ),
    'ALTER TABLE `settlement_cash_payments` ADD INDEX `settlement_cash_payments_business_shift_idx` (`business_id`,`shift_id`)',
    'SELECT 'settlement_cash_payments_business_shift_idx already exists, required columns are unavailable, or the table is unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- settlement_cash_payments: add index settlement_cash_payments_business_operator_shift_idx when missing.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cash_payments'
    )
    AND EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cash_payments'
          AND column_name = 'business_id'
    )
    AND EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cash_payments'
          AND column_name = 'pump_operator_id'
    )
    AND EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cash_payments'
          AND column_name = 'shift_id'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cash_payments'
          AND index_name = 'settlement_cash_payments_business_operator_shift_idx'
    ),
    'ALTER TABLE `settlement_cash_payments` ADD INDEX `settlement_cash_payments_business_operator_shift_idx` (`business_id`,`pump_operator_id`,`shift_id`)',
    'SELECT 'settlement_cash_payments_business_operator_shift_idx already exists, required columns are unavailable, or the table is unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- settlement_cash_payments: safely backfill exact parent_id links.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cash_payments'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cash_payments'
          AND column_name = 'id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cash_payments'
          AND column_name = 'business_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cash_payments'
          AND column_name = 'pump_payment_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cash_payments'
          AND column_name = 'shift_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cash_payments'
          AND column_name = 'pump_operator_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'business_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'parent_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'payment_type'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'shift_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'pump_operator_id'
    ),
    'UPDATE `settlement_cash_payments` AS detail INNER JOIN `pump_operator_payments` AS master ON master.business_id = detail.business_id AND master.parent_id = detail.id AND LOWER(master.payment_type) IN (''cash'') SET detail.pump_payment_id = master.id, detail.shift_id = IF(detail.shift_id IS NULL OR detail.shift_id = 0, master.shift_id, detail.shift_id), detail.pump_operator_id = IF(detail.pump_operator_id IS NULL OR detail.pump_operator_id = 0, master.pump_operator_id, detail.pump_operator_id) WHERE detail.pump_payment_id IS NULL OR detail.pump_payment_id = 0',
    'SELECT 'settlement_cash_payments direct identity backfill skipped because required tables or columns are unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;

-- settlement_cash_payments: fill only missing Shift/operator scope from an existing identity.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cash_payments'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cash_payments'
          AND column_name = 'id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cash_payments'
          AND column_name = 'business_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cash_payments'
          AND column_name = 'pump_payment_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cash_payments'
          AND column_name = 'shift_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cash_payments'
          AND column_name = 'pump_operator_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'business_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'parent_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'payment_type'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'shift_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'pump_operator_id'
    ),
    'UPDATE `settlement_cash_payments` AS detail INNER JOIN `pump_operator_payments` AS master ON master.id = detail.pump_payment_id AND master.business_id = detail.business_id SET detail.shift_id = IF(detail.shift_id IS NULL OR detail.shift_id = 0, master.shift_id, detail.shift_id), detail.pump_operator_id = IF(detail.pump_operator_id IS NULL OR detail.pump_operator_id = 0, master.pump_operator_id, detail.pump_operator_id) WHERE detail.pump_payment_id IS NOT NULL AND detail.pump_payment_id > 0 AND (detail.shift_id IS NULL OR detail.shift_id = 0 OR detail.pump_operator_id IS NULL OR detail.pump_operator_id = 0)',
    'SELECT 'settlement_cash_payments linked scope backfill skipped because required tables or columns are unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- settlement_card_payments: add pump_payment_id when missing.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_card_payments'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_card_payments'
          AND column_name = 'pump_payment_id'
    ),
    'ALTER TABLE `settlement_card_payments` ADD COLUMN `pump_payment_id` BIGINT UNSIGNED NULL AFTER `id`',
    'SELECT 'settlement_card_payments.pump_payment_id already exists, or the table is unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- settlement_card_payments: add shift_id when missing.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_card_payments'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_card_payments'
          AND column_name = 'shift_id'
    ),
    'ALTER TABLE `settlement_card_payments` ADD COLUMN `shift_id` BIGINT UNSIGNED NULL AFTER `pump_payment_id`',
    'SELECT 'settlement_card_payments.shift_id already exists, or the table is unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- settlement_card_payments: add pump_operator_id when missing.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_card_payments'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_card_payments'
          AND column_name = 'pump_operator_id'
    ),
    'ALTER TABLE `settlement_card_payments` ADD COLUMN `pump_operator_id` BIGINT UNSIGNED NULL AFTER `shift_id`',
    'SELECT 'settlement_card_payments.pump_operator_id already exists, or the table is unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- settlement_card_payments: add index settlement_card_payments_pump_payment_idx when missing.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_card_payments'
    )
    AND EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_card_payments'
          AND column_name = 'pump_payment_id'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_card_payments'
          AND index_name = 'settlement_card_payments_pump_payment_idx'
    ),
    'ALTER TABLE `settlement_card_payments` ADD INDEX `settlement_card_payments_pump_payment_idx` (`pump_payment_id`)',
    'SELECT 'settlement_card_payments_pump_payment_idx already exists, required columns are unavailable, or the table is unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- settlement_card_payments: add index settlement_card_payments_business_shift_idx when missing.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_card_payments'
    )
    AND EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_card_payments'
          AND column_name = 'business_id'
    )
    AND EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_card_payments'
          AND column_name = 'shift_id'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_card_payments'
          AND index_name = 'settlement_card_payments_business_shift_idx'
    ),
    'ALTER TABLE `settlement_card_payments` ADD INDEX `settlement_card_payments_business_shift_idx` (`business_id`,`shift_id`)',
    'SELECT 'settlement_card_payments_business_shift_idx already exists, required columns are unavailable, or the table is unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- settlement_card_payments: add index settlement_card_payments_business_operator_shift_idx when missing.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_card_payments'
    )
    AND EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_card_payments'
          AND column_name = 'business_id'
    )
    AND EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_card_payments'
          AND column_name = 'pump_operator_id'
    )
    AND EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_card_payments'
          AND column_name = 'shift_id'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_card_payments'
          AND index_name = 'settlement_card_payments_business_operator_shift_idx'
    ),
    'ALTER TABLE `settlement_card_payments` ADD INDEX `settlement_card_payments_business_operator_shift_idx` (`business_id`,`pump_operator_id`,`shift_id`)',
    'SELECT 'settlement_card_payments_business_operator_shift_idx already exists, required columns are unavailable, or the table is unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- settlement_card_payments: safely backfill exact parent_id links.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_card_payments'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_card_payments'
          AND column_name = 'id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_card_payments'
          AND column_name = 'business_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_card_payments'
          AND column_name = 'pump_payment_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_card_payments'
          AND column_name = 'shift_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_card_payments'
          AND column_name = 'pump_operator_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'business_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'parent_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'payment_type'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'shift_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'pump_operator_id'
    ),
    'UPDATE `settlement_card_payments` AS detail INNER JOIN `pump_operator_payments` AS master ON master.business_id = detail.business_id AND master.parent_id = detail.id AND LOWER(master.payment_type) IN (''card'',''cards'') SET detail.pump_payment_id = master.id, detail.shift_id = IF(detail.shift_id IS NULL OR detail.shift_id = 0, master.shift_id, detail.shift_id), detail.pump_operator_id = IF(detail.pump_operator_id IS NULL OR detail.pump_operator_id = 0, master.pump_operator_id, detail.pump_operator_id) WHERE detail.pump_payment_id IS NULL OR detail.pump_payment_id = 0',
    'SELECT 'settlement_card_payments direct identity backfill skipped because required tables or columns are unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;

-- settlement_card_payments: fill only missing Shift/operator scope from an existing identity.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_card_payments'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_card_payments'
          AND column_name = 'id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_card_payments'
          AND column_name = 'business_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_card_payments'
          AND column_name = 'pump_payment_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_card_payments'
          AND column_name = 'shift_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_card_payments'
          AND column_name = 'pump_operator_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'business_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'parent_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'payment_type'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'shift_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'pump_operator_id'
    ),
    'UPDATE `settlement_card_payments` AS detail INNER JOIN `pump_operator_payments` AS master ON master.id = detail.pump_payment_id AND master.business_id = detail.business_id SET detail.shift_id = IF(detail.shift_id IS NULL OR detail.shift_id = 0, master.shift_id, detail.shift_id), detail.pump_operator_id = IF(detail.pump_operator_id IS NULL OR detail.pump_operator_id = 0, master.pump_operator_id, detail.pump_operator_id) WHERE detail.pump_payment_id IS NOT NULL AND detail.pump_payment_id > 0 AND (detail.shift_id IS NULL OR detail.shift_id = 0 OR detail.pump_operator_id IS NULL OR detail.pump_operator_id = 0)',
    'SELECT 'settlement_card_payments linked scope backfill skipped because required tables or columns are unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- settlement_cheque_payments: add pump_payment_id when missing.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cheque_payments'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cheque_payments'
          AND column_name = 'pump_payment_id'
    ),
    'ALTER TABLE `settlement_cheque_payments` ADD COLUMN `pump_payment_id` BIGINT UNSIGNED NULL AFTER `id`',
    'SELECT 'settlement_cheque_payments.pump_payment_id already exists, or the table is unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- settlement_cheque_payments: add shift_id when missing.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cheque_payments'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cheque_payments'
          AND column_name = 'shift_id'
    ),
    'ALTER TABLE `settlement_cheque_payments` ADD COLUMN `shift_id` BIGINT UNSIGNED NULL AFTER `pump_payment_id`',
    'SELECT 'settlement_cheque_payments.shift_id already exists, or the table is unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- settlement_cheque_payments: add pump_operator_id when missing.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cheque_payments'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cheque_payments'
          AND column_name = 'pump_operator_id'
    ),
    'ALTER TABLE `settlement_cheque_payments` ADD COLUMN `pump_operator_id` BIGINT UNSIGNED NULL AFTER `shift_id`',
    'SELECT 'settlement_cheque_payments.pump_operator_id already exists, or the table is unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- settlement_cheque_payments: add index settlement_cheque_payments_pump_payment_idx when missing.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cheque_payments'
    )
    AND EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cheque_payments'
          AND column_name = 'pump_payment_id'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cheque_payments'
          AND index_name = 'settlement_cheque_payments_pump_payment_idx'
    ),
    'ALTER TABLE `settlement_cheque_payments` ADD INDEX `settlement_cheque_payments_pump_payment_idx` (`pump_payment_id`)',
    'SELECT 'settlement_cheque_payments_pump_payment_idx already exists, required columns are unavailable, or the table is unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- settlement_cheque_payments: add index settlement_cheque_payments_business_shift_idx when missing.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cheque_payments'
    )
    AND EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cheque_payments'
          AND column_name = 'business_id'
    )
    AND EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cheque_payments'
          AND column_name = 'shift_id'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cheque_payments'
          AND index_name = 'settlement_cheque_payments_business_shift_idx'
    ),
    'ALTER TABLE `settlement_cheque_payments` ADD INDEX `settlement_cheque_payments_business_shift_idx` (`business_id`,`shift_id`)',
    'SELECT 'settlement_cheque_payments_business_shift_idx already exists, required columns are unavailable, or the table is unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- settlement_cheque_payments: add index settlement_cheque_payments_business_operator_shift_idx when missing.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cheque_payments'
    )
    AND EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cheque_payments'
          AND column_name = 'business_id'
    )
    AND EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cheque_payments'
          AND column_name = 'pump_operator_id'
    )
    AND EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cheque_payments'
          AND column_name = 'shift_id'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cheque_payments'
          AND index_name = 'settlement_cheque_payments_business_operator_shift_idx'
    ),
    'ALTER TABLE `settlement_cheque_payments` ADD INDEX `settlement_cheque_payments_business_operator_shift_idx` (`business_id`,`pump_operator_id`,`shift_id`)',
    'SELECT 'settlement_cheque_payments_business_operator_shift_idx already exists, required columns are unavailable, or the table is unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- settlement_cheque_payments: safely backfill exact parent_id links.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cheque_payments'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cheque_payments'
          AND column_name = 'id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cheque_payments'
          AND column_name = 'business_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cheque_payments'
          AND column_name = 'pump_payment_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cheque_payments'
          AND column_name = 'shift_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cheque_payments'
          AND column_name = 'pump_operator_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'business_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'parent_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'payment_type'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'shift_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'pump_operator_id'
    ),
    'UPDATE `settlement_cheque_payments` AS detail INNER JOIN `pump_operator_payments` AS master ON master.business_id = detail.business_id AND master.parent_id = detail.id AND LOWER(master.payment_type) IN (''cheque'',''cheques'') SET detail.pump_payment_id = master.id, detail.shift_id = IF(detail.shift_id IS NULL OR detail.shift_id = 0, master.shift_id, detail.shift_id), detail.pump_operator_id = IF(detail.pump_operator_id IS NULL OR detail.pump_operator_id = 0, master.pump_operator_id, detail.pump_operator_id) WHERE detail.pump_payment_id IS NULL OR detail.pump_payment_id = 0',
    'SELECT 'settlement_cheque_payments direct identity backfill skipped because required tables or columns are unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;

-- settlement_cheque_payments: fill only missing Shift/operator scope from an existing identity.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cheque_payments'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cheque_payments'
          AND column_name = 'id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cheque_payments'
          AND column_name = 'business_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cheque_payments'
          AND column_name = 'pump_payment_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cheque_payments'
          AND column_name = 'shift_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_cheque_payments'
          AND column_name = 'pump_operator_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'business_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'parent_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'payment_type'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'shift_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'pump_operator_id'
    ),
    'UPDATE `settlement_cheque_payments` AS detail INNER JOIN `pump_operator_payments` AS master ON master.id = detail.pump_payment_id AND master.business_id = detail.business_id SET detail.shift_id = IF(detail.shift_id IS NULL OR detail.shift_id = 0, master.shift_id, detail.shift_id), detail.pump_operator_id = IF(detail.pump_operator_id IS NULL OR detail.pump_operator_id = 0, master.pump_operator_id, detail.pump_operator_id) WHERE detail.pump_payment_id IS NOT NULL AND detail.pump_payment_id > 0 AND (detail.shift_id IS NULL OR detail.shift_id = 0 OR detail.pump_operator_id IS NULL OR detail.pump_operator_id = 0)',
    'SELECT 'settlement_cheque_payments linked scope backfill skipped because required tables or columns are unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- settlement_credit_sale_payments: add pump_payment_id when missing.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_credit_sale_payments'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_credit_sale_payments'
          AND column_name = 'pump_payment_id'
    ),
    'ALTER TABLE `settlement_credit_sale_payments` ADD COLUMN `pump_payment_id` BIGINT UNSIGNED NULL AFTER `id`',
    'SELECT 'settlement_credit_sale_payments.pump_payment_id already exists, or the table is unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- settlement_credit_sale_payments: add shift_id when missing.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_credit_sale_payments'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_credit_sale_payments'
          AND column_name = 'shift_id'
    ),
    'ALTER TABLE `settlement_credit_sale_payments` ADD COLUMN `shift_id` BIGINT UNSIGNED NULL AFTER `pump_payment_id`',
    'SELECT 'settlement_credit_sale_payments.shift_id already exists, or the table is unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- settlement_credit_sale_payments: add pump_operator_id when missing.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_credit_sale_payments'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_credit_sale_payments'
          AND column_name = 'pump_operator_id'
    ),
    'ALTER TABLE `settlement_credit_sale_payments` ADD COLUMN `pump_operator_id` BIGINT UNSIGNED NULL AFTER `shift_id`',
    'SELECT 'settlement_credit_sale_payments.pump_operator_id already exists, or the table is unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- settlement_credit_sale_payments: add index settlement_credit_sale_payments_pump_payment_idx when missing.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_credit_sale_payments'
    )
    AND EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_credit_sale_payments'
          AND column_name = 'pump_payment_id'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_credit_sale_payments'
          AND index_name = 'settlement_credit_sale_payments_pump_payment_idx'
    ),
    'ALTER TABLE `settlement_credit_sale_payments` ADD INDEX `settlement_credit_sale_payments_pump_payment_idx` (`pump_payment_id`)',
    'SELECT 'settlement_credit_sale_payments_pump_payment_idx already exists, required columns are unavailable, or the table is unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- settlement_credit_sale_payments: add index settlement_credit_sale_payments_business_shift_idx when missing.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_credit_sale_payments'
    )
    AND EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_credit_sale_payments'
          AND column_name = 'business_id'
    )
    AND EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_credit_sale_payments'
          AND column_name = 'shift_id'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_credit_sale_payments'
          AND index_name = 'settlement_credit_sale_payments_business_shift_idx'
    ),
    'ALTER TABLE `settlement_credit_sale_payments` ADD INDEX `settlement_credit_sale_payments_business_shift_idx` (`business_id`,`shift_id`)',
    'SELECT 'settlement_credit_sale_payments_business_shift_idx already exists, required columns are unavailable, or the table is unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- settlement_credit_sale_payments: add index settlement_credit_sale_payments_business_operator_shift_idx when missing.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_credit_sale_payments'
    )
    AND EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_credit_sale_payments'
          AND column_name = 'business_id'
    )
    AND EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_credit_sale_payments'
          AND column_name = 'pump_operator_id'
    )
    AND EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_credit_sale_payments'
          AND column_name = 'shift_id'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_credit_sale_payments'
          AND index_name = 'settlement_credit_sale_payments_business_operator_shift_idx'
    ),
    'ALTER TABLE `settlement_credit_sale_payments` ADD INDEX `settlement_credit_sale_payments_business_operator_shift_idx` (`business_id`,`pump_operator_id`,`shift_id`)',
    'SELECT 'settlement_credit_sale_payments_business_operator_shift_idx already exists, required columns are unavailable, or the table is unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- settlement_credit_sale_payments: safely backfill exact parent_id links.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_credit_sale_payments'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_credit_sale_payments'
          AND column_name = 'id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_credit_sale_payments'
          AND column_name = 'business_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_credit_sale_payments'
          AND column_name = 'pump_payment_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_credit_sale_payments'
          AND column_name = 'shift_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_credit_sale_payments'
          AND column_name = 'pump_operator_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'business_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'parent_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'payment_type'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'shift_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'pump_operator_id'
    ),
    'UPDATE `settlement_credit_sale_payments` AS detail INNER JOIN `pump_operator_payments` AS master ON master.business_id = detail.business_id AND master.parent_id = detail.id AND LOWER(master.payment_type) IN (''credit'',''multiple_credit'') SET detail.pump_payment_id = master.id, detail.shift_id = IF(detail.shift_id IS NULL OR detail.shift_id = 0, master.shift_id, detail.shift_id), detail.pump_operator_id = IF(detail.pump_operator_id IS NULL OR detail.pump_operator_id = 0, master.pump_operator_id, detail.pump_operator_id) WHERE detail.pump_payment_id IS NULL OR detail.pump_payment_id = 0',
    'SELECT 'settlement_credit_sale_payments direct identity backfill skipped because required tables or columns are unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;

-- settlement_credit_sale_payments: fill only missing Shift/operator scope from an existing identity.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_credit_sale_payments'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_credit_sale_payments'
          AND column_name = 'id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_credit_sale_payments'
          AND column_name = 'business_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_credit_sale_payments'
          AND column_name = 'pump_payment_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_credit_sale_payments'
          AND column_name = 'shift_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_credit_sale_payments'
          AND column_name = 'pump_operator_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'business_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'parent_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'payment_type'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'shift_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'pump_operator_id'
    ),
    'UPDATE `settlement_credit_sale_payments` AS detail INNER JOIN `pump_operator_payments` AS master ON master.id = detail.pump_payment_id AND master.business_id = detail.business_id SET detail.shift_id = IF(detail.shift_id IS NULL OR detail.shift_id = 0, master.shift_id, detail.shift_id), detail.pump_operator_id = IF(detail.pump_operator_id IS NULL OR detail.pump_operator_id = 0, master.pump_operator_id, detail.pump_operator_id) WHERE detail.pump_payment_id IS NOT NULL AND detail.pump_payment_id > 0 AND (detail.shift_id IS NULL OR detail.shift_id = 0 OR detail.pump_operator_id IS NULL OR detail.pump_operator_id = 0)',
    'SELECT 'settlement_credit_sale_payments linked scope backfill skipped because required tables or columns are unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- settlement_shortage_payments: add pump_payment_id when missing.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_shortage_payments'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_shortage_payments'
          AND column_name = 'pump_payment_id'
    ),
    'ALTER TABLE `settlement_shortage_payments` ADD COLUMN `pump_payment_id` BIGINT UNSIGNED NULL AFTER `id`',
    'SELECT 'settlement_shortage_payments.pump_payment_id already exists, or the table is unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- settlement_shortage_payments: add shift_id when missing.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_shortage_payments'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_shortage_payments'
          AND column_name = 'shift_id'
    ),
    'ALTER TABLE `settlement_shortage_payments` ADD COLUMN `shift_id` BIGINT UNSIGNED NULL AFTER `pump_payment_id`',
    'SELECT 'settlement_shortage_payments.shift_id already exists, or the table is unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- settlement_shortage_payments: add pump_operator_id when missing.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_shortage_payments'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_shortage_payments'
          AND column_name = 'pump_operator_id'
    ),
    'ALTER TABLE `settlement_shortage_payments` ADD COLUMN `pump_operator_id` BIGINT UNSIGNED NULL AFTER `shift_id`',
    'SELECT 'settlement_shortage_payments.pump_operator_id already exists, or the table is unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- settlement_shortage_payments: add index settlement_shortage_payments_pump_payment_idx when missing.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_shortage_payments'
    )
    AND EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_shortage_payments'
          AND column_name = 'pump_payment_id'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_shortage_payments'
          AND index_name = 'settlement_shortage_payments_pump_payment_idx'
    ),
    'ALTER TABLE `settlement_shortage_payments` ADD INDEX `settlement_shortage_payments_pump_payment_idx` (`pump_payment_id`)',
    'SELECT 'settlement_shortage_payments_pump_payment_idx already exists, required columns are unavailable, or the table is unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- settlement_shortage_payments: add index settlement_shortage_payments_business_shift_idx when missing.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_shortage_payments'
    )
    AND EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_shortage_payments'
          AND column_name = 'business_id'
    )
    AND EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_shortage_payments'
          AND column_name = 'shift_id'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_shortage_payments'
          AND index_name = 'settlement_shortage_payments_business_shift_idx'
    ),
    'ALTER TABLE `settlement_shortage_payments` ADD INDEX `settlement_shortage_payments_business_shift_idx` (`business_id`,`shift_id`)',
    'SELECT 'settlement_shortage_payments_business_shift_idx already exists, required columns are unavailable, or the table is unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- settlement_shortage_payments: add index settlement_shortage_payments_business_operator_shift_idx when missing.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_shortage_payments'
    )
    AND EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_shortage_payments'
          AND column_name = 'business_id'
    )
    AND EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_shortage_payments'
          AND column_name = 'pump_operator_id'
    )
    AND EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_shortage_payments'
          AND column_name = 'shift_id'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_shortage_payments'
          AND index_name = 'settlement_shortage_payments_business_operator_shift_idx'
    ),
    'ALTER TABLE `settlement_shortage_payments` ADD INDEX `settlement_shortage_payments_business_operator_shift_idx` (`business_id`,`pump_operator_id`,`shift_id`)',
    'SELECT 'settlement_shortage_payments_business_operator_shift_idx already exists, required columns are unavailable, or the table is unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- settlement_shortage_payments: safely backfill exact parent_id links.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_shortage_payments'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_shortage_payments'
          AND column_name = 'id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_shortage_payments'
          AND column_name = 'business_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_shortage_payments'
          AND column_name = 'pump_payment_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_shortage_payments'
          AND column_name = 'shift_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_shortage_payments'
          AND column_name = 'pump_operator_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'business_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'parent_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'payment_type'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'shift_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'pump_operator_id'
    ),
    'UPDATE `settlement_shortage_payments` AS detail INNER JOIN `pump_operator_payments` AS master ON master.business_id = detail.business_id AND master.parent_id = detail.id AND LOWER(master.payment_type) IN (''shortage'') SET detail.pump_payment_id = master.id, detail.shift_id = IF(detail.shift_id IS NULL OR detail.shift_id = 0, master.shift_id, detail.shift_id), detail.pump_operator_id = IF(detail.pump_operator_id IS NULL OR detail.pump_operator_id = 0, master.pump_operator_id, detail.pump_operator_id) WHERE detail.pump_payment_id IS NULL OR detail.pump_payment_id = 0',
    'SELECT 'settlement_shortage_payments direct identity backfill skipped because required tables or columns are unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;

-- settlement_shortage_payments: fill only missing Shift/operator scope from an existing identity.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_shortage_payments'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_shortage_payments'
          AND column_name = 'id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_shortage_payments'
          AND column_name = 'business_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_shortage_payments'
          AND column_name = 'pump_payment_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_shortage_payments'
          AND column_name = 'shift_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_shortage_payments'
          AND column_name = 'pump_operator_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'business_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'parent_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'payment_type'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'shift_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'pump_operator_id'
    ),
    'UPDATE `settlement_shortage_payments` AS detail INNER JOIN `pump_operator_payments` AS master ON master.id = detail.pump_payment_id AND master.business_id = detail.business_id SET detail.shift_id = IF(detail.shift_id IS NULL OR detail.shift_id = 0, master.shift_id, detail.shift_id), detail.pump_operator_id = IF(detail.pump_operator_id IS NULL OR detail.pump_operator_id = 0, master.pump_operator_id, detail.pump_operator_id) WHERE detail.pump_payment_id IS NOT NULL AND detail.pump_payment_id > 0 AND (detail.shift_id IS NULL OR detail.shift_id = 0 OR detail.pump_operator_id IS NULL OR detail.pump_operator_id = 0)',
    'SELECT 'settlement_shortage_payments linked scope backfill skipped because required tables or columns are unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- settlement_excess_payments: add pump_payment_id when missing.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_excess_payments'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_excess_payments'
          AND column_name = 'pump_payment_id'
    ),
    'ALTER TABLE `settlement_excess_payments` ADD COLUMN `pump_payment_id` BIGINT UNSIGNED NULL AFTER `id`',
    'SELECT 'settlement_excess_payments.pump_payment_id already exists, or the table is unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- settlement_excess_payments: add shift_id when missing.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_excess_payments'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_excess_payments'
          AND column_name = 'shift_id'
    ),
    'ALTER TABLE `settlement_excess_payments` ADD COLUMN `shift_id` BIGINT UNSIGNED NULL AFTER `pump_payment_id`',
    'SELECT 'settlement_excess_payments.shift_id already exists, or the table is unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- settlement_excess_payments: add pump_operator_id when missing.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_excess_payments'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_excess_payments'
          AND column_name = 'pump_operator_id'
    ),
    'ALTER TABLE `settlement_excess_payments` ADD COLUMN `pump_operator_id` BIGINT UNSIGNED NULL AFTER `shift_id`',
    'SELECT 'settlement_excess_payments.pump_operator_id already exists, or the table is unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- settlement_excess_payments: add index settlement_excess_payments_pump_payment_idx when missing.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_excess_payments'
    )
    AND EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_excess_payments'
          AND column_name = 'pump_payment_id'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_excess_payments'
          AND index_name = 'settlement_excess_payments_pump_payment_idx'
    ),
    'ALTER TABLE `settlement_excess_payments` ADD INDEX `settlement_excess_payments_pump_payment_idx` (`pump_payment_id`)',
    'SELECT 'settlement_excess_payments_pump_payment_idx already exists, required columns are unavailable, or the table is unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- settlement_excess_payments: add index settlement_excess_payments_business_shift_idx when missing.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_excess_payments'
    )
    AND EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_excess_payments'
          AND column_name = 'business_id'
    )
    AND EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_excess_payments'
          AND column_name = 'shift_id'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_excess_payments'
          AND index_name = 'settlement_excess_payments_business_shift_idx'
    ),
    'ALTER TABLE `settlement_excess_payments` ADD INDEX `settlement_excess_payments_business_shift_idx` (`business_id`,`shift_id`)',
    'SELECT 'settlement_excess_payments_business_shift_idx already exists, required columns are unavailable, or the table is unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- settlement_excess_payments: add index settlement_excess_payments_business_operator_shift_idx when missing.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_excess_payments'
    )
    AND EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_excess_payments'
          AND column_name = 'business_id'
    )
    AND EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_excess_payments'
          AND column_name = 'pump_operator_id'
    )
    AND EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_excess_payments'
          AND column_name = 'shift_id'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_excess_payments'
          AND index_name = 'settlement_excess_payments_business_operator_shift_idx'
    ),
    'ALTER TABLE `settlement_excess_payments` ADD INDEX `settlement_excess_payments_business_operator_shift_idx` (`business_id`,`pump_operator_id`,`shift_id`)',
    'SELECT 'settlement_excess_payments_business_operator_shift_idx already exists, required columns are unavailable, or the table is unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- settlement_excess_payments: safely backfill exact parent_id links.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_excess_payments'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_excess_payments'
          AND column_name = 'id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_excess_payments'
          AND column_name = 'business_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_excess_payments'
          AND column_name = 'pump_payment_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_excess_payments'
          AND column_name = 'shift_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_excess_payments'
          AND column_name = 'pump_operator_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'business_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'parent_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'payment_type'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'shift_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'pump_operator_id'
    ),
    'UPDATE `settlement_excess_payments` AS detail INNER JOIN `pump_operator_payments` AS master ON master.business_id = detail.business_id AND master.parent_id = detail.id AND LOWER(master.payment_type) IN (''excess'') SET detail.pump_payment_id = master.id, detail.shift_id = IF(detail.shift_id IS NULL OR detail.shift_id = 0, master.shift_id, detail.shift_id), detail.pump_operator_id = IF(detail.pump_operator_id IS NULL OR detail.pump_operator_id = 0, master.pump_operator_id, detail.pump_operator_id) WHERE detail.pump_payment_id IS NULL OR detail.pump_payment_id = 0',
    'SELECT 'settlement_excess_payments direct identity backfill skipped because required tables or columns are unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;

-- settlement_excess_payments: fill only missing Shift/operator scope from an existing identity.
SET @petropd_sql := IF(
    EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_excess_payments'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_excess_payments'
          AND column_name = 'id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_excess_payments'
          AND column_name = 'business_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_excess_payments'
          AND column_name = 'pump_payment_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_excess_payments'
          AND column_name = 'shift_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'settlement_excess_payments'
          AND column_name = 'pump_operator_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'business_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'parent_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'payment_type'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'shift_id'
    )
    AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @petropd_schema
          AND table_name = 'pump_operator_payments'
          AND column_name = 'pump_operator_id'
    ),
    'UPDATE `settlement_excess_payments` AS detail INNER JOIN `pump_operator_payments` AS master ON master.id = detail.pump_payment_id AND master.business_id = detail.business_id SET detail.shift_id = IF(detail.shift_id IS NULL OR detail.shift_id = 0, master.shift_id, detail.shift_id), detail.pump_operator_id = IF(detail.pump_operator_id IS NULL OR detail.pump_operator_id = 0, master.pump_operator_id, detail.pump_operator_id) WHERE detail.pump_payment_id IS NOT NULL AND detail.pump_payment_id > 0 AND (detail.shift_id IS NULL OR detail.shift_id = 0 OR detail.pump_operator_id IS NULL OR detail.pump_operator_id = 0)',
    'SELECT 'settlement_excess_payments linked scope backfill skipped because required tables or columns are unavailable' AS repair_status'
);
PREPARE petropd_stmt FROM @petropd_sql;
EXECUTE petropd_stmt;
DEALLOCATE PREPARE petropd_stmt;


-- Final verification. For every existing table, the three *_exists values
-- must each be 1.
SELECT
    'settlement_cash_payments' AS table_name,
    (
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = 'settlement_cash_payments'
    ) AS table_exists,
    (
        SELECT COUNT(*)
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'settlement_cash_payments'
          AND column_name = 'pump_payment_id'
    ) AS pump_payment_id_exists,
    (
        SELECT COUNT(*)
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'settlement_cash_payments'
          AND column_name = 'shift_id'
    ) AS shift_id_exists,
    (
        SELECT COUNT(*)
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'settlement_cash_payments'
          AND column_name = 'pump_operator_id'
    ) AS pump_operator_id_exists
UNION ALL
SELECT
    'settlement_card_payments' AS table_name,
    (
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = 'settlement_card_payments'
    ) AS table_exists,
    (
        SELECT COUNT(*)
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'settlement_card_payments'
          AND column_name = 'pump_payment_id'
    ) AS pump_payment_id_exists,
    (
        SELECT COUNT(*)
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'settlement_card_payments'
          AND column_name = 'shift_id'
    ) AS shift_id_exists,
    (
        SELECT COUNT(*)
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'settlement_card_payments'
          AND column_name = 'pump_operator_id'
    ) AS pump_operator_id_exists
UNION ALL
SELECT
    'settlement_cheque_payments' AS table_name,
    (
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = 'settlement_cheque_payments'
    ) AS table_exists,
    (
        SELECT COUNT(*)
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'settlement_cheque_payments'
          AND column_name = 'pump_payment_id'
    ) AS pump_payment_id_exists,
    (
        SELECT COUNT(*)
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'settlement_cheque_payments'
          AND column_name = 'shift_id'
    ) AS shift_id_exists,
    (
        SELECT COUNT(*)
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'settlement_cheque_payments'
          AND column_name = 'pump_operator_id'
    ) AS pump_operator_id_exists
UNION ALL
SELECT
    'settlement_credit_sale_payments' AS table_name,
    (
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = 'settlement_credit_sale_payments'
    ) AS table_exists,
    (
        SELECT COUNT(*)
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'settlement_credit_sale_payments'
          AND column_name = 'pump_payment_id'
    ) AS pump_payment_id_exists,
    (
        SELECT COUNT(*)
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'settlement_credit_sale_payments'
          AND column_name = 'shift_id'
    ) AS shift_id_exists,
    (
        SELECT COUNT(*)
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'settlement_credit_sale_payments'
          AND column_name = 'pump_operator_id'
    ) AS pump_operator_id_exists
UNION ALL
SELECT
    'settlement_shortage_payments' AS table_name,
    (
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = 'settlement_shortage_payments'
    ) AS table_exists,
    (
        SELECT COUNT(*)
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'settlement_shortage_payments'
          AND column_name = 'pump_payment_id'
    ) AS pump_payment_id_exists,
    (
        SELECT COUNT(*)
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'settlement_shortage_payments'
          AND column_name = 'shift_id'
    ) AS shift_id_exists,
    (
        SELECT COUNT(*)
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'settlement_shortage_payments'
          AND column_name = 'pump_operator_id'
    ) AS pump_operator_id_exists
UNION ALL
SELECT
    'settlement_excess_payments' AS table_name,
    (
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = 'settlement_excess_payments'
    ) AS table_exists,
    (
        SELECT COUNT(*)
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'settlement_excess_payments'
          AND column_name = 'pump_payment_id'
    ) AS pump_payment_id_exists,
    (
        SELECT COUNT(*)
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'settlement_excess_payments'
          AND column_name = 'shift_id'
    ) AS shift_id_exists,
    (
        SELECT COUNT(*)
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'settlement_excess_payments'
          AND column_name = 'pump_operator_id'
    ) AS pump_operator_id_exists;


SET SQL_SAFE_UPDATES = @petropd_old_safe_updates;

SELECT
    DATABASE() AS selected_tenant_database,
    'PetroPD settlement payment identity repair completed' AS repair_status;
