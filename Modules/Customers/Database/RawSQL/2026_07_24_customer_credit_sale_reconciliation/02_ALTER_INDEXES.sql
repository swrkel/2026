-- CUSTOMERS S526 - CREDIT SALES / LEDGER RECONCILIATION INDEXES
-- Run in EACH tenant database.
-- MySQL / MariaDB.
-- Idempotent: every table, column, named index and equivalent ordered-column
-- index is checked before ALTER TABLE is executed.

DROP PROCEDURE IF EXISTS `customers_s526_add_index_if_missing`;
DROP PROCEDURE IF EXISTS `customers_s526_apply_indexes`;

DELIMITER $$

CREATE PROCEDURE `customers_s526_add_index_if_missing`(
    IN p_table_name VARCHAR(64),
    IN p_index_name VARCHAR(64),
    IN p_columns_csv TEXT
)
procedure_body: BEGIN
    DECLARE v_table_exists INT DEFAULT 0;
    DECLARE v_named_index_exists INT DEFAULT 0;
    DECLARE v_equivalent_index_exists INT DEFAULT 0;
    DECLARE v_expected_columns INT DEFAULT 0;
    DECLARE v_existing_columns INT DEFAULT 0;
    DECLARE v_columns_sql TEXT DEFAULT NULL;

    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION BEGIN END;

    SELECT COUNT(*)
      INTO v_table_exists
      FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = p_table_name;

    IF v_table_exists = 0
       OR p_columns_csv IS NULL
       OR TRIM(p_columns_csv) = '' THEN
        LEAVE procedure_body;
    END IF;

    SET p_columns_csv = REPLACE(REPLACE(p_columns_csv, '`', ''), ' ', '');
    SET v_expected_columns =
        1 + LENGTH(p_columns_csv) - LENGTH(REPLACE(p_columns_csv, ',', ''));

    IF v_expected_columns < 2 THEN
        LEAVE procedure_body;
    END IF;

    SELECT COUNT(DISTINCT COLUMN_NAME)
      INTO v_existing_columns
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = p_table_name
       AND FIND_IN_SET(COLUMN_NAME, p_columns_csv) > 0;

    IF v_existing_columns <> v_expected_columns THEN
        LEAVE procedure_body;
    END IF;

    SELECT COUNT(*)
      INTO v_named_index_exists
      FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = p_table_name
       AND INDEX_NAME = p_index_name;

    IF v_named_index_exists > 0 THEN
        LEAVE procedure_body;
    END IF;

    SELECT COUNT(*)
      INTO v_equivalent_index_exists
      FROM (
            SELECT INDEX_NAME,
                   GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX SEPARATOR ',') AS indexed_columns
              FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = p_table_name
             GROUP BY INDEX_NAME
           ) AS existing_indexes
     WHERE existing_indexes.indexed_columns = p_columns_csv;

    IF v_equivalent_index_exists > 0 THEN
        LEAVE procedure_body;
    END IF;

    SET v_columns_sql = CONCAT('`', REPLACE(p_columns_csv, ',', '`,`'), '`');
    SET @customers_s526_sql = CONCAT(
        'ALTER TABLE `', REPLACE(p_table_name, '`', '``'),
        '` ADD INDEX `', REPLACE(p_index_name, '`', '``'),
        '` (', v_columns_sql, ')'
    );

    PREPARE customers_s526_stmt FROM @customers_s526_sql;
    EXECUTE customers_s526_stmt;
    DEALLOCATE PREPARE customers_s526_stmt;
END$$

CREATE PROCEDURE `customers_s526_apply_indexes`()
BEGIN
    DECLARE v_columns TEXT DEFAULT NULL;

    -- transactions: normal customer sale/type lookup.
    IF EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transactions'
           AND COLUMN_NAME = 'business_id'
    ) AND EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transactions'
           AND COLUMN_NAME = 'contact_id'
    ) THEN
        SET v_columns = 'business_id,contact_id';

        IF EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transactions'
               AND COLUMN_NAME = 'type'
        ) THEN
            SET v_columns = CONCAT(v_columns, ',type');
        END IF;
        IF EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transactions'
               AND COLUMN_NAME = 'deleted_at'
        ) THEN
            SET v_columns = CONCAT(v_columns, ',deleted_at');
        END IF;
        IF EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transactions'
               AND COLUMN_NAME = 'id'
        ) THEN
            SET v_columns = CONCAT(v_columns, ',id');
        END IF;

        CALL `customers_s526_add_index_if_missing`(
            'transactions',
            'cus_tx_biz_contact_type_idx',
            v_columns
        );

        IF EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transactions'
               AND COLUMN_NAME = 'is_credit_sale'
        ) THEN
            SET v_columns = 'business_id,contact_id,is_credit_sale';

            IF EXISTS (
                SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transactions'
                   AND COLUMN_NAME = 'deleted_at'
            ) THEN
                SET v_columns = CONCAT(v_columns, ',deleted_at');
            END IF;
            IF EXISTS (
                SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transactions'
                   AND COLUMN_NAME = 'id'
            ) THEN
                SET v_columns = CONCAT(v_columns, ',id');
            END IF;

            CALL `customers_s526_add_index_if_missing`(
                'transactions',
                'cus_tx_biz_contact_credit_idx',
                v_columns
            );
        END IF;
    END IF;

    -- transaction_payments: linked payment and parent allocation lookup.
    IF EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transaction_payments'
           AND COLUMN_NAME = 'business_id'
    ) AND EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transaction_payments'
           AND COLUMN_NAME = 'transaction_id'
    ) THEN
        SET v_columns = 'business_id,transaction_id';

        IF EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transaction_payments'
               AND COLUMN_NAME = 'deleted_at'
        ) THEN
            SET v_columns = CONCAT(v_columns, ',deleted_at');
        END IF;
        IF EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transaction_payments'
               AND COLUMN_NAME = 'parent_id'
        ) THEN
            SET v_columns = CONCAT(v_columns, ',parent_id');
        END IF;
        IF EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transaction_payments'
               AND COLUMN_NAME = 'id'
        ) THEN
            SET v_columns = CONCAT(v_columns, ',id');
        END IF;

        CALL `customers_s526_add_index_if_missing`(
            'transaction_payments',
            'cus_tp_biz_tx_parent_idx',
            v_columns
        );
    END IF;

    -- contact_ledgers: transaction duplicate check.
    IF EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contact_ledgers'
           AND COLUMN_NAME = 'business_id'
    ) AND EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contact_ledgers'
           AND COLUMN_NAME = 'contact_id'
    ) AND EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contact_ledgers'
           AND COLUMN_NAME = 'transaction_id'
    ) THEN
        SET v_columns = 'business_id,contact_id,transaction_id';

        IF EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contact_ledgers'
               AND COLUMN_NAME = 'deleted_at'
        ) THEN
            SET v_columns = CONCAT(v_columns, ',deleted_at');
        END IF;

        CALL `customers_s526_add_index_if_missing`(
            'contact_ledgers',
            'cus_cl_biz_contact_tx_idx',
            v_columns
        );
    END IF;

    -- contact_ledgers: payment/parent duplicate check.
    IF EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contact_ledgers'
           AND COLUMN_NAME = 'business_id'
    ) AND EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contact_ledgers'
           AND COLUMN_NAME = 'contact_id'
    ) AND EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contact_ledgers'
           AND COLUMN_NAME = 'transaction_payment_id'
    ) THEN
        SET v_columns = 'business_id,contact_id,transaction_payment_id';

        IF EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contact_ledgers'
               AND COLUMN_NAME = 'deleted_at'
        ) THEN
            SET v_columns = CONCAT(v_columns, ',deleted_at');
        END IF;

        CALL `customers_s526_add_index_if_missing`(
            'contact_ledgers',
            'cus_cl_biz_contact_payment_idx',
            v_columns
        );
    END IF;
END$$

DELIMITER ;

CALL `customers_s526_apply_indexes`();

DROP PROCEDURE IF EXISTS `customers_s526_apply_indexes`;
DROP PROCEDURE IF EXISTS `customers_s526_add_index_if_missing`;

SELECT TABLE_NAME,
       INDEX_NAME,
       GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX SEPARATOR ',') AS indexed_columns
  FROM information_schema.STATISTICS
 WHERE TABLE_SCHEMA = DATABASE()
   AND INDEX_NAME IN (
       'cus_tx_biz_contact_type_idx',
       'cus_tx_biz_contact_credit_idx',
       'cus_tp_biz_tx_parent_idx',
       'cus_cl_biz_contact_tx_idx',
       'cus_cl_biz_contact_payment_idx'
   )
 GROUP BY TABLE_NAME, INDEX_NAME
 ORDER BY TABLE_NAME, INDEX_NAME;
