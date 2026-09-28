-- Petro PD-New installation verification

SELECT 'pdnew_module_settings' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pdnew_module_settings'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_number_sequences' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pdnew_number_sequences'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_operator_mappings' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pdnew_operator_mappings'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_source_imports' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pdnew_source_imports'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_source_snapshots' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pdnew_source_snapshots'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_settlements' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pdnew_settlements'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_settlements.expected_adjustments_total' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'pdnew_settlements'
      AND column_name = 'expected_adjustments_total'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_settlements.received_adjustments_total' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'pdnew_settlements'
      AND column_name = 'received_adjustments_total'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_settlement_sources' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pdnew_settlement_sources'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_settlement_pumps' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pdnew_settlement_pumps'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_settlement_meter_sales' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pdnew_settlement_meter_sales'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_settlement_payments' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pdnew_settlement_payments'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_settlement_payment_details' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pdnew_settlement_payment_details'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_settlement_credit_sales' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pdnew_settlement_credit_sales'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_settlement_credit_sale_lines' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pdnew_settlement_credit_sale_lines'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_settlement_other_sales' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pdnew_settlement_other_sales'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_settlement_other_sale_lines' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pdnew_settlement_other_sale_lines'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_settlement_unload_stocks' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pdnew_settlement_unload_stocks'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_settlement_unload_stock_lines' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pdnew_settlement_unload_stock_lines'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_settlement_day_entries' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pdnew_settlement_day_entries'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_settlement_collections' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pdnew_settlement_collections'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_settlement_ledger_entries' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pdnew_settlement_ledger_entries'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_settlement_recoveries' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pdnew_settlement_recoveries'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_settlement_commissions' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pdnew_settlement_commissions'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_settlement_adjustments' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pdnew_settlement_adjustments'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_settlement_approvals' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pdnew_settlement_approvals'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_settlement_status_histories' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pdnew_settlement_status_histories'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_reconciliation_issues' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pdnew_reconciliation_issues'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_day_ends' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pdnew_day_ends'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_day_end_settlements' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pdnew_day_end_settlements'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_posting_batches' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pdnew_posting_batches'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_posting_lines' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pdnew_posting_lines'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_integration_outbox' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pdnew_integration_outbox'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_integration_logs' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pdnew_integration_logs'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_notification_templates' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pdnew_notification_templates'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_notification_logs' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pdnew_notification_logs'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_print_logs' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pdnew_print_logs'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_audit_logs' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pdnew_audit_logs'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_documents' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pdnew_documents'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_saved_report_filters' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pdnew_saved_report_filters'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pone_shifts' AS source_object,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pone_shifts'
) THEN 'OK' ELSE 'MISSING' END AS source_status;

SELECT 'pone_pump_assignments' AS source_object,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pone_pump_assignments'
) THEN 'OK' ELSE 'MISSING' END AS source_status;

SELECT 'pone_payments' AS source_object,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pone_payments'
) THEN 'OK' ELSE 'MISSING' END AS source_status;

SELECT 'pone_shift_settlement_references' AS source_object,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pone_shift_settlement_references'
) THEN 'OK' ELSE 'MISSING' END AS source_status;

-- Petro PD-New settlement accounting-boundary columns.
SELECT 'pdnew_settlements.source_declared_total' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'pdnew_settlements'
      AND column_name = 'source_declared_total'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_settlements.source_shortage_total' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'pdnew_settlements'
      AND column_name = 'source_shortage_total'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_settlements.source_excess_total' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'pdnew_settlements'
      AND column_name = 'source_excess_total'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_settlements.manual_shortage_total' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'pdnew_settlements'
      AND column_name = 'manual_shortage_total'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_settlements.manual_excess_total' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'pdnew_settlements'
      AND column_name = 'manual_excess_total'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_settlements.shortage_recovery_total' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'pdnew_settlements'
      AND column_name = 'shortage_recovery_total'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_settlements.excess_commission_total' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'pdnew_settlements'
      AND column_name = 'excess_commission_total'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_settlements.expected_adjustments_total' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'pdnew_settlements'
      AND column_name = 'expected_adjustments_total'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_settlements.received_adjustments_total' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'pdnew_settlements'
      AND column_name = 'received_adjustments_total'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pdnew_settlements.operational_variance_amount' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'pdnew_settlements'
      AND column_name = 'operational_variance_amount'
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

-- Exclusive-pairing integrity: exactly one final settlement reference per
-- business/shift and a database key that enforces the rule under concurrency.
SELECT 'pone_settlement_business_shift_uq' AS object_name,
CASE WHEN EXISTS (
    SELECT 1 FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'pone_shift_settlement_references'
      AND index_name = 'pone_settlement_business_shift_uq'
      AND non_unique = 0
) THEN 'OK' ELSE 'MISSING' END AS installation_status;

SELECT 'pone_shift_settlement_references.duplicate_business_shift' AS object_name,
CASE WHEN NOT EXISTS (
    SELECT 1
    FROM `pone_shift_settlement_references`
    GROUP BY `business_id`, `shift_id`
    HAVING COUNT(*) > 1
) THEN 'OK' ELSE 'DUPLICATES_FOUND' END AS installation_status;

SELECT 'petropdnew_permissions' AS object_name,
CASE WHEN (
    SELECT COUNT(*) FROM `permissions`
    WHERE `name` LIKE 'petro_pd_new.%'
) >= 53 THEN 'OK' ELSE 'MISSING' END AS installation_status;

