-- Petro PD Stable Recovery M2 V4 - Safe support indexes
-- Run this inside each tenant database only. Do NOT run in the central database.
-- This script never references payment_ref_no because that column is not present in some live tenant schemas.

DROP PROCEDURE IF EXISTS petro_pd_add_index_if_columns_exist_v4;
DELIMITER $$
CREATE PROCEDURE petro_pd_add_index_if_columns_exist_v4(
    IN p_table_name VARCHAR(128),
    IN p_index_name VARCHAR(128),
    IN p_columns_csv VARCHAR(512)
)
BEGIN
    DECLARE v_missing_count INT DEFAULT 0;
    DECLARE v_index_count INT DEFAULT 0;
    DECLARE v_sql TEXT;

    SELECT COUNT(*) INTO v_index_count
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = p_table_name
      AND INDEX_NAME = p_index_name;

    IF v_index_count = 0 THEN
        SELECT COUNT(*) INTO v_missing_count
        FROM (
            SELECT TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(p_columns_csv, ',', numbers.n), ',', -1)) AS column_name
            FROM (
                SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6
            ) numbers
            WHERE numbers.n <= 1 + LENGTH(p_columns_csv) - LENGTH(REPLACE(p_columns_csv, ',', ''))
        ) requested_columns
        LEFT JOIN INFORMATION_SCHEMA.COLUMNS c
          ON c.TABLE_SCHEMA = DATABASE()
         AND c.TABLE_NAME = p_table_name
         AND c.COLUMN_NAME = requested_columns.column_name
        WHERE c.COLUMN_NAME IS NULL;

        IF v_missing_count = 0 THEN
            SET v_sql = CONCAT('CREATE INDEX ', p_index_name, ' ON ', p_table_name, ' (', p_columns_csv, ')');
            SET @petro_pd_sql = v_sql;
            PREPARE stmt FROM @petro_pd_sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        END IF;
    END IF;
END$$
DELIMITER ;

CALL petro_pd_add_index_if_columns_exist_v4('pump_operator_payments', 'idx_petro_pd_pop_business_shift_type', 'business_id,shift_id,payment_type');
CALL petro_pd_add_index_if_columns_exist_v4('pump_operator_payments', 'idx_petro_pd_pop_business_created', 'business_id,created_at');
CALL petro_pd_add_index_if_columns_exist_v4('pump_operator_other_sales', 'idx_petro_pd_other_sales_business_shift', 'business_id,shift_id');
CALL petro_pd_add_index_if_columns_exist_v4('pumper_day_entries', 'idx_petro_pd_day_entries_business_shift', 'business_id,shift_id');
CALL petro_pd_add_index_if_columns_exist_v4('settlement_credit_sale_payments', 'idx_petro_pd_credit_sales_business_shift', 'business_id,shift_id');
CALL petro_pd_add_index_if_columns_exist_v4('account_transactions', 'idx_petro_pd_account_txn_business_source', 'business_id,transaction_id,sub_type');
CALL petro_pd_add_index_if_columns_exist_v4('account_transactions', 'idx_petro_pd_account_txn_business_date', 'business_id,operation_date');

DROP PROCEDURE IF EXISTS petro_pd_add_index_if_columns_exist_v4;
