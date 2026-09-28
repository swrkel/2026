-- PetroPD Stable Recovery M2 V6
-- Safe support indexes for tenant databases.
-- This script deliberately DOES NOT reference pump_operator_payments.payment_ref_no
-- because some tenant databases do not have that column.

DELIMITER $$

DROP PROCEDURE IF EXISTS petropd_add_index_if_columns_exist $$
CREATE PROCEDURE petropd_add_index_if_columns_exist(
    IN p_table_name VARCHAR(128),
    IN p_index_name VARCHAR(128),
    IN p_columns_csv VARCHAR(500),
    IN p_index_sql TEXT
)
BEGIN
    DECLARE v_missing_count INT DEFAULT 0;
    DECLARE v_index_count INT DEFAULT 0;

    SELECT COUNT(*) INTO v_index_count
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = p_table_name
      AND index_name = p_index_name;

    SELECT COUNT(*) INTO v_missing_count
    FROM (
        SELECT TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(p_columns_csv, ',', n.n), ',', -1)) AS column_name
        FROM (
            SELECT 1 n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5
        ) n
        WHERE n.n <= 1 + LENGTH(p_columns_csv) - LENGTH(REPLACE(p_columns_csv, ',', ''))
    ) required_columns
    LEFT JOIN information_schema.columns c
      ON c.table_schema = DATABASE()
     AND c.table_name = p_table_name
     AND c.column_name = required_columns.column_name
    WHERE c.column_name IS NULL;

    IF v_index_count = 0 AND v_missing_count = 0 THEN
        SET @sql = p_index_sql;
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END $$

DELIMITER ;

CALL petropd_add_index_if_columns_exist(
    'pump_operator_payments',
    'idx_petro_pd_pop_business_shift_type',
    'business_id,shift_id,payment_type',
    'CREATE INDEX idx_petro_pd_pop_business_shift_type ON pump_operator_payments (business_id, shift_id, payment_type)'
);

CALL petropd_add_index_if_columns_exist(
    'pump_operator_payments',
    'idx_petro_pd_pop_business_settlement',
    'business_id,settlement_no',
    'CREATE INDEX idx_petro_pd_pop_business_settlement ON pump_operator_payments (business_id, settlement_no)'
);

CALL petropd_add_index_if_columns_exist(
    'settlements',
    'idx_petro_pd_settlements_business_shift',
    'business_id,shift_id',
    'CREATE INDEX idx_petro_pd_settlements_business_shift ON settlements (business_id, shift_id)'
);

CALL petropd_add_index_if_columns_exist(
    'settlements',
    'idx_petro_pd_settlements_business_no',
    'business_id,settlement_no',
    'CREATE INDEX idx_petro_pd_settlements_business_no ON settlements (business_id, settlement_no)'
);

CALL petropd_add_index_if_columns_exist(
    'account_transactions',
    'idx_petro_pd_account_business_ref',
    'business_id,ref_no',
    'CREATE INDEX idx_petro_pd_account_business_ref ON account_transactions (business_id, ref_no)'
);

CALL petropd_add_index_if_columns_exist(
    'account_transactions',
    'idx_petro_pd_account_business_subtype',
    'business_id,sub_type',
    'CREATE INDEX idx_petro_pd_account_business_subtype ON account_transactions (business_id, sub_type)'
);

DROP PROCEDURE IF EXISTS petropd_add_index_if_columns_exist;
