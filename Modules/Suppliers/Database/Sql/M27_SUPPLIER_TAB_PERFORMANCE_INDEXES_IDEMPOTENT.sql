-- M27 - Supplier tab performance indexes
-- Safe to run repeatedly in every tenant database.
-- Existing tables, columns, and indexes are checked before each ALTER TABLE.

DELIMITER $$

DROP PROCEDURE IF EXISTS suppliers_add_index_if_missing$$

CREATE PROCEDURE suppliers_add_index_if_missing(
    IN p_table_name VARCHAR(64),
    IN p_index_name VARCHAR(64),
    IN p_required_columns TEXT,
    IN p_expected_column_count INT,
    IN p_index_definition TEXT
)
BEGIN
    DECLARE v_table_exists INT DEFAULT 0;
    DECLARE v_column_count INT DEFAULT 0;
    DECLARE v_index_exists INT DEFAULT 0;

    SELECT COUNT(*)
      INTO v_table_exists
      FROM information_schema.tables
     WHERE table_schema = DATABASE()
       AND table_name = p_table_name;

    IF v_table_exists > 0 THEN
        SELECT COUNT(DISTINCT column_name)
          INTO v_column_count
          FROM information_schema.columns
         WHERE table_schema = DATABASE()
           AND table_name = p_table_name
           AND FIND_IN_SET(column_name, p_required_columns) > 0;

        SELECT COUNT(*)
          INTO v_index_exists
          FROM information_schema.statistics
         WHERE table_schema = DATABASE()
           AND table_name = p_table_name
           AND index_name = p_index_name;

        IF v_column_count = p_expected_column_count AND v_index_exists = 0 THEN
            SET @supplier_index_sql = CONCAT(
                'ALTER TABLE `', REPLACE(p_table_name, '`', '``'),
                '` ADD INDEX `', REPLACE(p_index_name, '`', '``'),
                '` (', p_index_definition, ')'
            );

            PREPARE supplier_index_statement FROM @supplier_index_sql;
            EXECUTE supplier_index_statement;
            DEALLOCATE PREPARE supplier_index_statement;
        END IF;
    END IF;
END$$

CALL suppliers_add_index_if_missing(
    'contacts',
    'sup_contacts_business_type_active_idx',
    'business_id,type,active,id',
    4,
    '`business_id`, `type`, `active`, `id`'
)$$

CALL suppliers_add_index_if_missing(
    'transactions',
    'sup_transactions_contact_date_idx',
    'business_id,contact_id,transaction_date,id',
    4,
    '`business_id`, `contact_id`, `transaction_date`, `id`'
)$$

CALL suppliers_add_index_if_missing(
    'transactions',
    'sup_transactions_contact_type_status_idx',
    'business_id,contact_id,type,status,deleted_at',
    5,
    '`business_id`, `contact_id`, `type`, `status`, `deleted_at`'
)$$

CALL suppliers_add_index_if_missing(
    'transaction_payments',
    'sup_payments_supplier_date_idx',
    'business_id,payment_for,paid_on,id',
    4,
    '`business_id`, `payment_for`, `paid_on`, `id`'
)$$

CALL suppliers_add_index_if_missing(
    'transaction_payments',
    'sup_payments_transaction_parent_idx',
    'transaction_id,parent_id,deleted_at',
    3,
    '`transaction_id`, `parent_id`, `deleted_at`'
)$$

CALL suppliers_add_index_if_missing(
    'purchase_lines',
    'sup_purchase_lines_transaction_product_idx',
    'transaction_id,product_id,id',
    3,
    '`transaction_id`, `product_id`, `id`'
)$$

CALL suppliers_add_index_if_missing(
    'contact_ledgers',
    'sup_contact_ledgers_contact_transaction_idx',
    'contact_id,transaction_id,deleted_at',
    3,
    '`contact_id`, `transaction_id`, `deleted_at`'
)$$

CALL suppliers_add_index_if_missing(
    'media',
    'sup_media_supplier_lookup_idx',
    'business_id,model_type,model_id,created_at',
    4,
    '`business_id`, `model_type`(100), `model_id`, `created_at`'
)$$

CALL suppliers_add_index_if_missing(
    'notes',
    'sup_notes_supplier_lookup_idx',
    'business_id,notable_type,notable_id,created_at',
    4,
    '`business_id`, `notable_type`(100), `notable_id`, `created_at`'
)$$

CALL suppliers_add_index_if_missing(
    'activity_log',
    'sup_activity_supplier_lookup_idx',
    'subject_type,subject_id,created_at',
    3,
    '`subject_type`(100), `subject_id`, `created_at`'
)$$

CALL suppliers_add_index_if_missing(
    'products',
    'sup_products_business_name_idx',
    'business_id,name',
    2,
    '`business_id`, `name`(100)'
)$$

DROP PROCEDURE IF EXISTS suppliers_add_index_if_missing$$

DELIMITER ;
