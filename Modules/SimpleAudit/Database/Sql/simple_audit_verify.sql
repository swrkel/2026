-- =====================================================================
-- Simple Audit (SAU) - Tenant Verification v1.0.2
-- SAFE / READ-ONLY / phpMyAdmin-compatible
--
-- IMPORTANT:
--   1) Select ONE TENANT database first in phpMyAdmin.
--   2) This verification file never queries sau_* tables directly.
--   3) It only reads INFORMATION_SCHEMA, so a missing Simple Audit table
--      is reported as MISSING instead of causing MySQL error #1109.
-- =====================================================================

-- 1. Confirm which database phpMyAdmin is currently using.
SELECT
    COALESCE(DATABASE(), '[NO DATABASE SELECTED]') AS selected_database,
    NOW() AS checked_at,
    CASE
        WHEN DATABASE() IS NULL THEN 'WRONG DATABASE - select the tenant database first'
        WHEN DATABASE() IN ('information_schema','mysql','performance_schema','sys')
            THEN 'WRONG DATABASE - select the tenant database first'
        ELSE 'DATABASE SELECTED - continue with the checks below'
    END AS database_status;

-- 2. Confirm that the selected database looks like an ERP tenant database.
SELECT
    required_table AS tenant_required_table,
    CASE WHEN actual.table_name IS NULL THEN 'MISSING' ELSE 'OK' END AS status
FROM (
    SELECT 'business' AS required_table
    UNION ALL SELECT 'transactions'
    UNION ALL SELECT 'products'
    UNION ALL SELECT 'variation_location_details'
) expected
LEFT JOIN information_schema.tables actual
       ON actual.table_schema = DATABASE()
      AND actual.table_name = expected.required_table
ORDER BY required_table;

-- 3. Verify the five Simple Audit module tables.
SELECT
    expected.table_name AS simple_audit_table,
    CASE WHEN actual.table_name IS NULL THEN 'MISSING' ELSE 'OK' END AS status
FROM (
    SELECT 'sau_settings' AS table_name
    UNION ALL SELECT 'sau_change_events'
    UNION ALL SELECT 'sau_stock_snapshots'
    UNION ALL SELECT 'sau_report_shares'
    UNION ALL SELECT 'sau_activity_logs'
) expected
LEFT JOIN information_schema.tables actual
       ON actual.table_schema = DATABASE()
      AND actual.table_name = expected.table_name
ORDER BY expected.table_name;

-- 4. Verify every expected Simple Audit trigger individually.
SELECT
    expected.trigger_name AS simple_audit_trigger,
    CASE WHEN actual.trigger_name IS NULL THEN 'MISSING' ELSE 'OK' END AS status,
    actual.event_manipulation,
    actual.event_object_table
FROM (
    SELECT 'sau_vld_ai' AS trigger_name
    UNION ALL SELECT 'sau_vld_au'
    UNION ALL SELECT 'sau_vld_ad'
    UNION ALL SELECT 'sau_transactions_ai'
    UNION ALL SELECT 'sau_transactions_au'
    UNION ALL SELECT 'sau_transactions_ad'
    UNION ALL SELECT 'sau_purchase_lines_ai'
    UNION ALL SELECT 'sau_purchase_lines_au'
    UNION ALL SELECT 'sau_purchase_lines_ad'
    UNION ALL SELECT 'sau_stock_adjustment_lines_ai'
    UNION ALL SELECT 'sau_stock_adjustment_lines_au'
    UNION ALL SELECT 'sau_stock_adjustment_lines_ad'
    UNION ALL SELECT 'sau_transaction_payments_ai'
    UNION ALL SELECT 'sau_transaction_payments_au'
    UNION ALL SELECT 'sau_transaction_payments_ad'
    UNION ALL SELECT 'sau_account_transactions_ai'
    UNION ALL SELECT 'sau_account_transactions_au'
    UNION ALL SELECT 'sau_account_transactions_ad'
    UNION ALL SELECT 'sau_contact_ledgers_ai'
    UNION ALL SELECT 'sau_contact_ledgers_au'
    UNION ALL SELECT 'sau_contact_ledgers_ad'
) expected
LEFT JOIN information_schema.triggers actual
       ON actual.trigger_schema = DATABASE()
      AND actual.trigger_name = expected.trigger_name
ORDER BY expected.trigger_name;

-- 5. One-line summary.
SELECT
    DATABASE() AS selected_database,
    (
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name IN (
              'sau_settings',
              'sau_change_events',
              'sau_stock_snapshots',
              'sau_report_shares',
              'sau_activity_logs'
          )
    ) AS simple_audit_tables_found,
    5 AS simple_audit_tables_expected,
    (
        SELECT COUNT(*)
        FROM information_schema.triggers
        WHERE trigger_schema = DATABASE()
          AND trigger_name IN (
              'sau_vld_ai','sau_vld_au','sau_vld_ad',
              'sau_transactions_ai','sau_transactions_au','sau_transactions_ad',
              'sau_purchase_lines_ai','sau_purchase_lines_au','sau_purchase_lines_ad',
              'sau_stock_adjustment_lines_ai','sau_stock_adjustment_lines_au','sau_stock_adjustment_lines_ad',
              'sau_transaction_payments_ai','sau_transaction_payments_au','sau_transaction_payments_ad',
              'sau_account_transactions_ai','sau_account_transactions_au','sau_account_transactions_ad',
              'sau_contact_ledgers_ai','sau_contact_ledgers_au','sau_contact_ledgers_ad'
          )
    ) AS simple_audit_triggers_found,
    21 AS simple_audit_triggers_expected,
    CASE
        WHEN DATABASE() IS NULL
          OR DATABASE() IN ('information_schema','mysql','performance_schema','sys')
            THEN 'WRONG DATABASE'
        WHEN (
            SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_name IN ('business','transactions','products','variation_location_details')
        ) < 4
            THEN 'NOT A VALID TENANT DATABASE'
        WHEN (
            SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_name IN ('sau_settings','sau_change_events','sau_stock_snapshots','sau_report_shares','sau_activity_logs')
        ) = 5
         AND (
            SELECT COUNT(*)
            FROM information_schema.triggers
            WHERE trigger_schema = DATABASE()
              AND trigger_name IN (
                  'sau_vld_ai','sau_vld_au','sau_vld_ad',
                  'sau_transactions_ai','sau_transactions_au','sau_transactions_ad',
                  'sau_purchase_lines_ai','sau_purchase_lines_au','sau_purchase_lines_ad',
                  'sau_stock_adjustment_lines_ai','sau_stock_adjustment_lines_au','sau_stock_adjustment_lines_ad',
                  'sau_transaction_payments_ai','sau_transaction_payments_au','sau_transaction_payments_ad',
                  'sau_account_transactions_ai','sau_account_transactions_au','sau_account_transactions_ad',
                  'sau_contact_ledgers_ai','sau_contact_ledgers_au','sau_contact_ledgers_ad'
              )
        ) = 21
            THEN 'PASS - Simple Audit database objects are installed'
        ELSE 'INCOMPLETE - see MISSING rows above'
    END AS verification_result;
