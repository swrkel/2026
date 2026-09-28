/*
 Petro PD Stable Recovery M2 V5 - Safe Support Indexes
 Run this on EACH tenant database only.
 This script does NOT reference payment_ref_no because that column is not present
 in your pump_operator_payments table.
*/

DELIMITER $$

DROP PROCEDURE IF EXISTS petro_pd_add_index_if_columns_exist $$
CREATE PROCEDURE petro_pd_add_index_if_columns_exist(
    IN p_table_name VARCHAR(128),
    IN p_index_name VARCHAR(128),
    IN p_columns_csv VARCHAR(512),
    IN p_columns_required_csv VARCHAR(512)
)
BEGIN
    DECLARE v_table_exists INT DEFAULT 0;
    DECLARE v_index_exists INT DEFAULT 0;
    DECLARE v_missing_required INT DEFAULT 0;
    DECLARE v_sql TEXT;

    SELECT COUNT(*) INTO v_table_exists
    FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = p_table_name;

    IF v_table_exists > 0 THEN
        SELECT COUNT(*) INTO v_index_exists
        FROM information_schema.statistics
        WHERE table_schema = DATABASE() AND table_name = p_table_name AND index_name = p_index_name;

        SELECT COUNT(*) INTO v_missing_required
        FROM (
            SELECT TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(p_columns_required_csv, ',', numbers.n), ',', -1)) AS column_name
            FROM (
                SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6
            ) numbers
            WHERE numbers.n <= 1 + LENGTH(p_columns_required_csv) - LENGTH(REPLACE(p_columns_required_csv, ',', ''))
        ) required_columns
        WHERE required_columns.column_name <> ''
          AND required_columns.column_name NOT IN (
              SELECT column_name FROM information_schema.columns
              WHERE table_schema = DATABASE() AND table_name = p_table_name
          );

        IF v_index_exists = 0 AND v_missing_required = 0 THEN
            SET v_sql = CONCAT('CREATE INDEX ', p_index_name, ' ON ', p_table_name, ' (', p_columns_csv, ')');
            SET @petro_pd_sql = v_sql;
            PREPARE stmt FROM @petro_pd_sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        END IF;
    END IF;
END $$

DELIMITER ;

CALL petro_pd_add_index_if_columns_exist('pump_operator_payments', 'idx_petro_pd_pop_business_shift_type', 'business_id, shift_id, payment_type', 'business_id,shift_id,payment_type');
CALL petro_pd_add_index_if_columns_exist('pump_operator_payments', 'idx_petro_pd_pop_shift_operator', 'shift_id, pump_operator_id', 'shift_id,pump_operator_id');
CALL petro_pd_add_index_if_columns_exist('pump_operator_payments', 'idx_petro_pd_pop_settlement', 'settlement_id', 'settlement_id');
CALL petro_pd_add_index_if_columns_exist('settlements', 'idx_petro_pd_settlements_business_shift', 'business_id, shift_id', 'business_id,shift_id');
CALL petro_pd_add_index_if_columns_exist('settlements', 'idx_petro_pd_settlements_finalized', 'business_id, is_finalized', 'business_id,is_finalized');
CALL petro_pd_add_index_if_columns_exist('account_transactions', 'idx_petro_pd_account_txn_lookup', 'business_id, transaction_id, account_id', 'business_id,transaction_id,account_id');
CALL petro_pd_add_index_if_columns_exist('settlement_card_payments', 'idx_petro_pd_card_settlement', 'settlement_id', 'settlement_id');
CALL petro_pd_add_index_if_columns_exist('settlement_cash_payments', 'idx_petro_pd_cash_settlement', 'settlement_id', 'settlement_id');
CALL petro_pd_add_index_if_columns_exist('settlement_credit_sale_payments', 'idx_petro_pd_credit_settlement', 'settlement_id', 'settlement_id');

DROP PROCEDURE IF EXISTS petro_pd_add_index_if_columns_exist;
