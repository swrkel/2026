-- Pumper Dashboard-New 2.0.0 tenant installation verification.
SET NAMES utf8mb4;

SELECT expected.table_name,
       CASE WHEN actual.table_name IS NULL THEN 'MISSING' ELSE 'OK' END AS installation_status
FROM (
SELECT 'pone_assignment_events' AS table_name
UNION ALL
SELECT 'pone_audit_logs' AS table_name
UNION ALL
SELECT 'pone_credit_sale_lines' AS table_name
UNION ALL
SELECT 'pone_credit_sales' AS table_name
UNION ALL
SELECT 'pone_daily_collections' AS table_name
UNION ALL
SELECT 'pone_day_entries' AS table_name
UNION ALL
SELECT 'pone_excess_commissions' AS table_name
UNION ALL
SELECT 'pone_integration_links' AS table_name
UNION ALL
SELECT 'pone_integration_outbox' AS table_name
UNION ALL
SELECT 'pone_login_attempts' AS table_name
UNION ALL
SELECT 'pone_meter_readings' AS table_name
UNION ALL
SELECT 'pone_module_settings' AS table_name
UNION ALL
SELECT 'pone_number_sequences' AS table_name
UNION ALL
SELECT 'pone_operator_documents' AS table_name
UNION ALL
SELECT 'pone_operator_ledger_entries' AS table_name
UNION ALL
SELECT 'pone_operator_notes' AS table_name
UNION ALL
SELECT 'pone_operator_sessions' AS table_name
UNION ALL
SELECT 'pone_other_sale_lines' AS table_name
UNION ALL
SELECT 'pone_other_sales' AS table_name
UNION ALL
SELECT 'pone_payment_card_lines' AS table_name
UNION ALL
SELECT 'pone_payment_cash_denominations' AS table_name
UNION ALL
SELECT 'pone_payment_edit_histories' AS table_name
UNION ALL
SELECT 'pone_payments' AS table_name
UNION ALL
SELECT 'pone_pd_operators' AS table_name
UNION ALL
SELECT 'pone_print_logs' AS table_name
UNION ALL
SELECT 'pone_pump_assignments' AS table_name
UNION ALL
SELECT 'pone_shift_settlement_references' AS table_name
UNION ALL
SELECT 'pone_shifts' AS table_name
UNION ALL
SELECT 'pone_shortage_recoveries' AS table_name
UNION ALL
SELECT 'pone_unload_stock_lines' AS table_name
UNION ALL
SELECT 'pone_unload_stocks' AS table_name
) expected
LEFT JOIN information_schema.tables actual
  ON actual.table_schema = DATABASE()
 AND actual.table_name = expected.table_name
ORDER BY expected.table_name;

SELECT c.table_name, c.column_name,
       CASE WHEN actual.column_name IS NULL THEN 'MISSING' ELSE 'OK' END AS installation_status
FROM (
  SELECT 'pone_shifts' AS table_name, 'petropd_shift_number' AS column_name
  UNION ALL SELECT 'pone_module_settings', 'cash_denomination_enabled'
  UNION ALL SELECT 'pone_shifts', 'collection_form_no'
  UNION ALL SELECT 'pone_shifts', 'reconciliation_status'
  UNION ALL SELECT 'pone_payments', 'edit_version'
  UNION ALL SELECT 'pone_other_sales', 'customer_id'
  UNION ALL SELECT 'pone_unload_stocks', 'supplier_id'
  UNION ALL SELECT 'pone_day_entries', 'settlement_no'
) c
LEFT JOIN information_schema.columns actual
  ON actual.table_schema = DATABASE()
 AND actual.table_name = c.table_name
 AND actual.column_name = c.column_name
ORDER BY c.table_name, c.column_name;

SET @pone_verify_permissions_sql = IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions'),
  'SELECT expected.permission_name,
          CASE WHEN actual.name IS NULL THEN ''MISSING'' ELSE ''OK'' END AS installation_status
   FROM (
SELECT ''pumper_dashboard_new.access'' AS permission_name
     UNION ALL
     SELECT ''pumper_dashboard_new.dashboard.view'' AS permission_name
     UNION ALL
     SELECT ''pumper_dashboard_new.operators.view'' AS permission_name
     UNION ALL
     SELECT ''pumper_dashboard_new.operators.manage'' AS permission_name
     UNION ALL
     SELECT ''pumper_dashboard_new.shifts.view'' AS permission_name
     UNION ALL
     SELECT ''pumper_dashboard_new.shifts.manage'' AS permission_name
     UNION ALL
     SELECT ''pumper_dashboard_new.assignments.manage'' AS permission_name
     UNION ALL
     SELECT ''pumper_dashboard_new.collections.view'' AS permission_name
     UNION ALL
     SELECT ''pumper_dashboard_new.collections.manage'' AS permission_name
     UNION ALL
     SELECT ''pumper_dashboard_new.reconciliation.view'' AS permission_name
     UNION ALL
     SELECT ''pumper_dashboard_new.reconciliation.manage'' AS permission_name
     UNION ALL
     SELECT ''pumper_dashboard_new.ledger.view'' AS permission_name
     UNION ALL
     SELECT ''pumper_dashboard_new.documents.view'' AS permission_name
     UNION ALL
     SELECT ''pumper_dashboard_new.documents.manage'' AS permission_name
     UNION ALL
     SELECT ''pumper_dashboard_new.login_attempts.view'' AS permission_name
     UNION ALL
     SELECT ''pumper_dashboard_new.login_attempts.manage'' AS permission_name
     UNION ALL
     SELECT ''pumper_dashboard_new.print_logs.view'' AS permission_name
     UNION ALL
     SELECT ''pumper_dashboard_new.reports.view'' AS permission_name
     UNION ALL
     SELECT ''pumper_dashboard_new.reports.export'' AS permission_name
     UNION ALL
     SELECT ''pumper_dashboard_new.reports.print'' AS permission_name
     UNION ALL
     SELECT ''pumper_dashboard_new.reports.shifts'' AS permission_name
     UNION ALL
     SELECT ''pumper_dashboard_new.reports.payments'' AS permission_name
     UNION ALL
     SELECT ''pumper_dashboard_new.reports.meters'' AS permission_name
     UNION ALL
     SELECT ''pumper_dashboard_new.reports.other_sales'' AS permission_name
     UNION ALL
     SELECT ''pumper_dashboard_new.reports.unloads'' AS permission_name
     UNION ALL
     SELECT ''pumper_dashboard_new.reports.day_entries'' AS permission_name
     UNION ALL
     SELECT ''pumper_dashboard_new.reports.collections'' AS permission_name
     UNION ALL
     SELECT ''pumper_dashboard_new.reports.ledger'' AS permission_name
     UNION ALL
     SELECT ''pumper_dashboard_new.reports.shortages'' AS permission_name
     UNION ALL
     SELECT ''pumper_dashboard_new.reports.commissions'' AS permission_name
     UNION ALL
     SELECT ''pumper_dashboard_new.reports.print_logs'' AS permission_name
     UNION ALL
     SELECT ''pumper_dashboard_new.reports.audit'' AS permission_name
     UNION ALL
     SELECT ''pumper_dashboard_new.integration.view'' AS permission_name
     UNION ALL
     SELECT ''pumper_dashboard_new.integration.manage'' AS permission_name
     UNION ALL
     SELECT ''pumper_dashboard_new.settings.manage'' AS permission_name
     UNION ALL
     SELECT ''pumper_dashboard_new.operator.use'' AS permission_name
   ) expected
   LEFT JOIN `permissions` actual
     ON actual.name = expected.permission_name AND actual.guard_name = ''web''
   ORDER BY expected.permission_name',
  'SELECT ''permissions table is not present in this database'' AS message'
);
PREPARE pone_verify_permissions_stmt FROM @pone_verify_permissions_sql;
EXECUTE pone_verify_permissions_stmt;
DEALLOCATE PREPARE pone_verify_permissions_stmt;
