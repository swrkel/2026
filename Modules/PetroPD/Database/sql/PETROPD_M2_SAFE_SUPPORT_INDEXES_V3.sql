-- PetroPD Stable Recovery M2 V3 safe support indexes
-- Run per TENANT database only. This script checks columns before creating indexes.

SET @db_name := DATABASE();

SET @sql := (
    SELECT IF(
        COUNT(*) = 3 AND NOT EXISTS (
            SELECT 1 FROM information_schema.statistics
            WHERE table_schema = @db_name AND table_name = 'pump_operator_payments'
              AND index_name = 'idx_petro_pd_pop_business_type_shift'
        ),
        'CREATE INDEX idx_petro_pd_pop_business_type_shift ON pump_operator_payments (business_id, payment_type, shift_id)',
        'SELECT "skip idx_petro_pd_pop_business_type_shift"'
    )
    FROM information_schema.columns
    WHERE table_schema = @db_name
      AND table_name = 'pump_operator_payments'
      AND column_name IN ('business_id','payment_type','shift_id')
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 2 AND NOT EXISTS (
            SELECT 1 FROM information_schema.statistics
            WHERE table_schema = @db_name AND table_name = 'pump_operator_payments'
              AND index_name = 'idx_petro_pd_pop_business_created'
        ),
        'CREATE INDEX idx_petro_pd_pop_business_created ON pump_operator_payments (business_id, created_at)',
        'SELECT "skip idx_petro_pd_pop_business_created"'
    )
    FROM information_schema.columns
    WHERE table_schema = @db_name
      AND table_name = 'pump_operator_payments'
      AND column_name IN ('business_id','created_at')
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 2 AND NOT EXISTS (
            SELECT 1 FROM information_schema.statistics
            WHERE table_schema = @db_name AND table_name = 'settlements'
              AND index_name = 'idx_petro_pd_settlements_business_shift'
        ),
        'CREATE INDEX idx_petro_pd_settlements_business_shift ON settlements (business_id, shift_id)',
        'SELECT "skip idx_petro_pd_settlements_business_shift"'
    )
    FROM information_schema.columns
    WHERE table_schema = @db_name
      AND table_name = 'settlements'
      AND column_name IN ('business_id','shift_id')
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
