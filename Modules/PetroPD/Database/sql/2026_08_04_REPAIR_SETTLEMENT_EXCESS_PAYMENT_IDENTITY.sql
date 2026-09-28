-- Petro PD tenant database repair
-- Fixes: SQLSTATE[42S22] Unknown column settlement_excess_payments.pump_payment_id
-- Safe to run repeatedly. Run on EACH affected tenant database, not the central database.

SET @pd_schema := DATABASE();

-- 1. Add pump_payment_id when missing.
SET @pd_sql := IF(
    EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @pd_schema AND table_name = 'settlement_excess_payments'
    )
    AND NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @pd_schema
          AND table_name = 'settlement_excess_payments'
          AND column_name = 'pump_payment_id'
    ),
    'ALTER TABLE `settlement_excess_payments` ADD COLUMN `pump_payment_id` BIGINT UNSIGNED NULL AFTER `id`',
    'SELECT ''pump_payment_id already exists or table is unavailable'' AS info'
);
PREPARE pd_stmt FROM @pd_sql;
EXECUTE pd_stmt;
DEALLOCATE PREPARE pd_stmt;

-- 2. Add shift_id when missing.
SET @pd_sql := IF(
    EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @pd_schema AND table_name = 'settlement_excess_payments'
    )
    AND NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @pd_schema
          AND table_name = 'settlement_excess_payments'
          AND column_name = 'shift_id'
    ),
    'ALTER TABLE `settlement_excess_payments` ADD COLUMN `shift_id` BIGINT UNSIGNED NULL AFTER `pump_payment_id`',
    'SELECT ''shift_id already exists or table is unavailable'' AS info'
);
PREPARE pd_stmt FROM @pd_sql;
EXECUTE pd_stmt;
DEALLOCATE PREPARE pd_stmt;

-- 3. Add pump_operator_id when missing.
SET @pd_sql := IF(
    EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @pd_schema AND table_name = 'settlement_excess_payments'
    )
    AND NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @pd_schema
          AND table_name = 'settlement_excess_payments'
          AND column_name = 'pump_operator_id'
    ),
    'ALTER TABLE `settlement_excess_payments` ADD COLUMN `pump_operator_id` BIGINT UNSIGNED NULL AFTER `shift_id`',
    'SELECT ''pump_operator_id already exists or table is unavailable'' AS info'
);
PREPARE pd_stmt FROM @pd_sql;
EXECUTE pd_stmt;
DEALLOCATE PREPARE pd_stmt;

-- 4. Add supporting indexes when missing.
SET @pd_sql := IF(
    NOT EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = @pd_schema
          AND table_name = 'settlement_excess_payments'
          AND index_name = 'settlement_excess_payments_pump_payment_idx'
    ),
    'ALTER TABLE `settlement_excess_payments` ADD INDEX `settlement_excess_payments_pump_payment_idx` (`pump_payment_id`)',
    'SELECT ''pump payment index already exists'' AS info'
);
PREPARE pd_stmt FROM @pd_sql;
EXECUTE pd_stmt;
DEALLOCATE PREPARE pd_stmt;

SET @pd_sql := IF(
    NOT EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = @pd_schema
          AND table_name = 'settlement_excess_payments'
          AND index_name = 'settlement_excess_payments_business_shift_idx'
    ),
    'ALTER TABLE `settlement_excess_payments` ADD INDEX `settlement_excess_payments_business_shift_idx` (`business_id`,`shift_id`)',
    'SELECT ''business/shift index already exists'' AS info'
);
PREPARE pd_stmt FROM @pd_sql;
EXECUTE pd_stmt;
DEALLOCATE PREPARE pd_stmt;

SET @pd_sql := IF(
    NOT EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = @pd_schema
          AND table_name = 'settlement_excess_payments'
          AND index_name = 'settlement_excess_payments_business_operator_shift_idx'
    ),
    'ALTER TABLE `settlement_excess_payments` ADD INDEX `settlement_excess_payments_business_operator_shift_idx` (`business_id`,`pump_operator_id`,`shift_id`)',
    'SELECT ''business/operator/shift index already exists'' AS info'
);
PREPARE pd_stmt FROM @pd_sql;
EXECUTE pd_stmt;
DEALLOCATE PREPARE pd_stmt;

-- 5. Backfill safe legacy links through the existing parent_id relationship.
SET @pd_can_parent_backfill := (
    SELECT IF(
        EXISTS (SELECT 1 FROM information_schema.tables
                WHERE table_schema = @pd_schema AND table_name = 'pump_operator_payments')
        AND EXISTS (SELECT 1 FROM information_schema.columns
                    WHERE table_schema = @pd_schema AND table_name = 'pump_operator_payments' AND column_name = 'parent_id')
        AND EXISTS (SELECT 1 FROM information_schema.columns
                    WHERE table_schema = @pd_schema AND table_name = 'pump_operator_payments' AND column_name = 'payment_type'),
        1,
        0
    )
);
SET @pd_sql := IF(
    @pd_can_parent_backfill = 1,
    'UPDATE `settlement_excess_payments` detail JOIN `pump_operator_payments` master ON master.business_id = detail.business_id AND master.parent_id = detail.id AND LOWER(master.payment_type) = ''excess'' SET detail.pump_payment_id = master.id, detail.shift_id = master.shift_id, detail.pump_operator_id = master.pump_operator_id WHERE detail.pump_payment_id IS NULL OR detail.pump_payment_id = 0',
    'SELECT ''legacy parent-link backfill skipped because source columns are unavailable'' AS info'
);
PREPARE pd_stmt FROM @pd_sql;
EXECUTE pd_stmt;
DEALLOCATE PREPARE pd_stmt;

-- 6. Complete Shift/operator values for linked records.
SET @pd_sql := IF(
    EXISTS (SELECT 1 FROM information_schema.tables
            WHERE table_schema = @pd_schema AND table_name = 'pump_operator_payments'),
    'UPDATE `settlement_excess_payments` detail JOIN `pump_operator_payments` master ON master.id = detail.pump_payment_id AND master.business_id = detail.business_id SET detail.shift_id = master.shift_id, detail.pump_operator_id = master.pump_operator_id WHERE detail.pump_payment_id IS NOT NULL AND detail.pump_payment_id > 0 AND ((detail.shift_id IS NULL OR detail.shift_id = 0) OR (detail.pump_operator_id IS NULL OR detail.pump_operator_id = 0))',
    'SELECT ''master-link scope backfill skipped because pump_operator_payments is unavailable'' AS info'
);
PREPARE pd_stmt FROM @pd_sql;
EXECUTE pd_stmt;
DEALLOCATE PREPARE pd_stmt;

-- 7. Verification.
SELECT column_name, column_type, is_nullable
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND table_name = 'settlement_excess_payments'
  AND column_name IN ('pump_payment_id', 'shift_id', 'pump_operator_id')
ORDER BY ordinal_position;

SELECT index_name, GROUP_CONCAT(column_name ORDER BY seq_in_index) AS indexed_columns
FROM information_schema.statistics
WHERE table_schema = DATABASE()
  AND table_name = 'settlement_excess_payments'
  AND index_name IN (
      'settlement_excess_payments_pump_payment_idx',
      'settlement_excess_payments_business_shift_idx',
      'settlement_excess_payments_business_operator_shift_idx'
  )
GROUP BY index_name
ORDER BY index_name;
