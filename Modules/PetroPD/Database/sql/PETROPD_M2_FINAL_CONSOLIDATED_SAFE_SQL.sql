/*
PETROPD M2 FINAL CONSOLIDATED SAFE SQL
Baseline: Modules(48).zip latest server PetroPD module
Date: 2026-07-03

This script avoids unsafe references such as pump_operator_payments.payment_ref_no.
Run per tenant database after backing up.
*/

/* Safe support indexes: create only when table/columns exist. */

SET @db := DATABASE();

/* pump_operator_payments: business/payment/shift indexes only */
SET @sql := (
    SELECT IF(
        COUNT(*) = 3 AND NOT EXISTS (
            SELECT 1 FROM information_schema.statistics
            WHERE table_schema = @db AND table_name = 'pump_operator_payments' AND index_name = 'idx_petropd_pop_business_type_shift'
        ),
        'CREATE INDEX idx_petropd_pop_business_type_shift ON pump_operator_payments (business_id, payment_type, shift_id)',
        'SELECT "skip idx_petropd_pop_business_type_shift" AS info'
    )
    FROM information_schema.columns
    WHERE table_schema = @db
      AND table_name = 'pump_operator_payments'
      AND column_name IN ('business_id', 'payment_type', 'shift_id')
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 3 AND NOT EXISTS (
            SELECT 1 FROM information_schema.statistics
            WHERE table_schema = @db AND table_name = 'pump_operator_payments' AND index_name = 'idx_petropd_pop_business_operator_shift'
        ),
        'CREATE INDEX idx_petropd_pop_business_operator_shift ON pump_operator_payments (business_id, pump_operator_id, shift_id)',
        'SELECT "skip idx_petropd_pop_business_operator_shift" AS info'
    )
    FROM information_schema.columns
    WHERE table_schema = @db
      AND table_name = 'pump_operator_payments'
      AND column_name IN ('business_id', 'pump_operator_id', 'shift_id')
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

/* settlements: settlement_no and shift finalization lookup */
SET @sql := (
    SELECT IF(
        COUNT(*) = 2 AND NOT EXISTS (
            SELECT 1 FROM information_schema.statistics
            WHERE table_schema = @db AND table_name = 'settlements' AND index_name = 'idx_petropd_settlements_business_no'
        ),
        'CREATE INDEX idx_petropd_settlements_business_no ON settlements (business_id, settlement_no)',
        'SELECT "skip idx_petropd_settlements_business_no" AS info'
    )
    FROM information_schema.columns
    WHERE table_schema = @db
      AND table_name = 'settlements'
      AND column_name IN ('business_id', 'settlement_no')
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 3 AND NOT EXISTS (
            SELECT 1 FROM information_schema.statistics
            WHERE table_schema = @db AND table_name = 'settlements' AND index_name = 'idx_petropd_settlements_business_shift_status'
        ),
        'CREATE INDEX idx_petropd_settlements_business_shift_status ON settlements (business_id, shift_id, status)',
        'SELECT "skip idx_petropd_settlements_business_shift_status" AS info'
    )
    FROM information_schema.columns
    WHERE table_schema = @db
      AND table_name = 'settlements'
      AND column_name IN ('business_id', 'shift_id', 'status')
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

/* account_transactions: settlement/account audit lookup; no optional columns assumed. */
SET @sql := (
    SELECT IF(
        COUNT(*) = 3 AND NOT EXISTS (
            SELECT 1 FROM information_schema.statistics
            WHERE table_schema = @db AND table_name = 'account_transactions' AND index_name = 'idx_petropd_account_business_ref_account'
        ),
        'CREATE INDEX idx_petropd_account_business_ref_account ON account_transactions (business_id, ref_no, account_id)',
        'SELECT "skip idx_petropd_account_business_ref_account" AS info'
    )
    FROM information_schema.columns
    WHERE table_schema = @db
      AND table_name = 'account_transactions'
      AND column_name IN ('business_id', 'ref_no', 'account_id')
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
