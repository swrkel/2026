-- PETRO PD - VERIFY ALL SETTLEMENT PAYMENT IDENTITY COLUMNS
-- Select one tenant database before running.

SELECT DATABASE() AS selected_tenant_database;

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
