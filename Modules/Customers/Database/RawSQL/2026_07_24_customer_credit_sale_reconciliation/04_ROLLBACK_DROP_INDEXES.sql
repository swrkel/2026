-- OPTIONAL S526 ROLLBACK
-- Idempotently drops only indexes created by this package.

DROP PROCEDURE IF EXISTS `customers_s526_drop_index_if_exists`;

DELIMITER $$
CREATE PROCEDURE `customers_s526_drop_index_if_exists`(
    IN p_table_name VARCHAR(64),
    IN p_index_name VARCHAR(64)
)
procedure_body: BEGIN
    DECLARE v_exists INT DEFAULT 0;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION BEGIN END;

    SELECT COUNT(*)
      INTO v_exists
      FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = p_table_name
       AND INDEX_NAME = p_index_name;

    IF v_exists = 0 THEN
        LEAVE procedure_body;
    END IF;

    SET @customers_s526_drop_sql = CONCAT(
        'ALTER TABLE `', REPLACE(p_table_name, '`', '``'),
        '` DROP INDEX `', REPLACE(p_index_name, '`', '``'), '`'
    );
    PREPARE customers_s526_drop_stmt FROM @customers_s526_drop_sql;
    EXECUTE customers_s526_drop_stmt;
    DEALLOCATE PREPARE customers_s526_drop_stmt;
END$$
DELIMITER ;

CALL `customers_s526_drop_index_if_exists`('transactions', 'cus_tx_biz_contact_type_idx');
CALL `customers_s526_drop_index_if_exists`('transactions', 'cus_tx_biz_contact_credit_idx');
CALL `customers_s526_drop_index_if_exists`('transaction_payments', 'cus_tp_biz_tx_parent_idx');
CALL `customers_s526_drop_index_if_exists`('contact_ledgers', 'cus_cl_biz_contact_tx_idx');
CALL `customers_s526_drop_index_if_exists`('contact_ledgers', 'cus_cl_biz_contact_payment_idx');

DROP PROCEDURE IF EXISTS `customers_s526_drop_index_if_exists`;
