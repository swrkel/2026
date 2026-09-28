-- Verify the exact Pumper Dashboard-New source contract required by Petro PD-New.
SET NAMES utf8mb4;
SELECT DATABASE() AS `active_tenant_database`;

SELECT 'table' AS object_type, 'pone_shifts' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pone_shifts'),'OK','MISSING') AS status;
SELECT 'table' AS object_type, 'pone_pump_assignments' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pone_pump_assignments'),'OK','MISSING') AS status;
SELECT 'table' AS object_type, 'pone_meter_readings' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pone_meter_readings'),'OK','MISSING') AS status;
SELECT 'table' AS object_type, 'pone_payments' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pone_payments'),'OK','MISSING') AS status;
SELECT 'table' AS object_type, 'pone_payment_cash_denominations' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pone_payment_cash_denominations'),'OK','MISSING') AS status;
SELECT 'table' AS object_type, 'pone_payment_card_lines' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pone_payment_card_lines'),'OK','MISSING') AS status;
SELECT 'table' AS object_type, 'pone_credit_sales' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pone_credit_sales'),'OK','MISSING') AS status;
SELECT 'table' AS object_type, 'pone_credit_sale_lines' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pone_credit_sale_lines'),'OK','MISSING') AS status;
SELECT 'table' AS object_type, 'pone_other_sales' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pone_other_sales'),'OK','MISSING') AS status;
SELECT 'table' AS object_type, 'pone_other_sale_lines' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pone_other_sale_lines'),'OK','MISSING') AS status;
SELECT 'table' AS object_type, 'pone_unload_stocks' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pone_unload_stocks'),'OK','MISSING') AS status;
SELECT 'table' AS object_type, 'pone_unload_stock_lines' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pone_unload_stock_lines'),'OK','MISSING') AS status;
SELECT 'table' AS object_type, 'pone_day_entries' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pone_day_entries'),'OK','MISSING') AS status;
SELECT 'table' AS object_type, 'pone_daily_collections' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pone_daily_collections'),'OK','MISSING') AS status;
SELECT 'table' AS object_type, 'pone_shortage_recoveries' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pone_shortage_recoveries'),'OK','MISSING') AS status;
SELECT 'table' AS object_type, 'pone_excess_commissions' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pone_excess_commissions'),'OK','MISSING') AS status;
SELECT 'table' AS object_type, 'pone_operator_ledger_entries' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pone_operator_ledger_entries'),'OK','MISSING') AS status;
SELECT 'table' AS object_type, 'pone_pd_operators' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pone_pd_operators'),'OK','MISSING') AS status;
SELECT 'table' AS object_type, 'pone_shift_settlement_references' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pone_shift_settlement_references'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_shifts.id' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shifts' AND column_name='id'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_shifts.business_id' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shifts' AND column_name='business_id'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_shifts.location_id' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shifts' AND column_name='location_id'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_shifts.operator_profile_id' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shifts' AND column_name='operator_profile_id'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_shifts.pd_operator_id' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shifts' AND column_name='pd_operator_id'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_shifts.shift_number' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shifts' AND column_name='shift_number'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_shifts.status' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shifts' AND column_name='status'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_shifts.closed_at' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shifts' AND column_name='closed_at'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_shifts.meter_sales_total' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shifts' AND column_name='meter_sales_total'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_shifts.other_sales_total' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shifts' AND column_name='other_sales_total'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_shifts.payments_total' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shifts' AND column_name='payments_total'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_shifts.expected_total' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shifts' AND column_name='expected_total'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_shifts.declared_total' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shifts' AND column_name='declared_total'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_shifts.shortage_amount' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shifts' AND column_name='shortage_amount'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_shifts.excess_amount' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shifts' AND column_name='excess_amount'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_shifts.reconciliation_status' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shifts' AND column_name='reconciliation_status'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_pd_operators.id' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_pd_operators' AND column_name='id'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_pd_operators.business_id' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_pd_operators' AND column_name='business_id'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_pd_operators.pd_operator_id' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_pd_operators' AND column_name='pd_operator_id'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_pd_operators.display_name' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_pd_operators' AND column_name='display_name'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_pd_operators.status' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_pd_operators' AND column_name='status'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_pump_assignments.id' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_pump_assignments' AND column_name='id'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_pump_assignments.shift_id' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_pump_assignments' AND column_name='shift_id'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_pump_assignments.business_id' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_pump_assignments' AND column_name='business_id'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_pump_assignments.pump_id' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_pump_assignments' AND column_name='pump_id'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_pump_assignments.opening_meter' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_pump_assignments' AND column_name='opening_meter'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_pump_assignments.closing_meter' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_pump_assignments' AND column_name='closing_meter'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_pump_assignments.testing_quantity' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_pump_assignments' AND column_name='testing_quantity'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_pump_assignments.sold_quantity' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_pump_assignments' AND column_name='sold_quantity'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_pump_assignments.unit_price' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_pump_assignments' AND column_name='unit_price'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_pump_assignments.amount' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_pump_assignments' AND column_name='amount'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_pump_assignments.status' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_pump_assignments' AND column_name='status'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_meter_readings.id' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_meter_readings' AND column_name='id'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_meter_readings.shift_id' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_meter_readings' AND column_name='shift_id'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_meter_readings.assignment_id' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_meter_readings' AND column_name='assignment_id'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_meter_readings.business_id' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_meter_readings' AND column_name='business_id'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_meter_readings.meter_value' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_meter_readings' AND column_name='meter_value'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_meter_readings.recorded_at' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_meter_readings' AND column_name='recorded_at'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_payments.id' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_payments' AND column_name='id'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_payments.shift_id' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_payments' AND column_name='shift_id'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_payments.business_id' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_payments' AND column_name='business_id'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_payments.payment_number' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_payments' AND column_name='payment_number'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_payments.payment_type' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_payments' AND column_name='payment_type'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_payments.amount' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_payments' AND column_name='amount'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_payments.transaction_at' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_payments' AND column_name='transaction_at'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_payments.status' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_payments' AND column_name='status'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_other_sales.id' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_other_sales' AND column_name='id'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_other_sales.shift_id' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_other_sales' AND column_name='shift_id'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_other_sales.business_id' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_other_sales' AND column_name='business_id'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_other_sales.sale_number' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_other_sales' AND column_name='sale_number'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_other_sales.net_amount' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_other_sales' AND column_name='net_amount'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_other_sales.status' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_other_sales' AND column_name='status'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_unload_stocks.id' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_unload_stocks' AND column_name='id'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_unload_stocks.shift_id' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_unload_stocks' AND column_name='shift_id'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_unload_stocks.business_id' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_unload_stocks' AND column_name='business_id'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_unload_stocks.receipt_number' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_unload_stocks' AND column_name='receipt_number'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_unload_stocks.total_quantity' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_unload_stocks' AND column_name='total_quantity'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_unload_stocks.total_amount' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_unload_stocks' AND column_name='total_amount'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_unload_stocks.status' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_unload_stocks' AND column_name='status'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_day_entries.id' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_day_entries' AND column_name='id'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_day_entries.shift_id' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_day_entries' AND column_name='shift_id'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_day_entries.business_id' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_day_entries' AND column_name='business_id'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_day_entries.entry_type' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_day_entries' AND column_name='entry_type'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_day_entries.status' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_day_entries' AND column_name='status'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_daily_collections.id' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_daily_collections' AND column_name='id'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_daily_collections.shift_id' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_daily_collections' AND column_name='shift_id'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_daily_collections.business_id' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_daily_collections' AND column_name='business_id'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_daily_collections.collection_number' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_daily_collections' AND column_name='collection_number'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_daily_collections.declared_amount' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_daily_collections' AND column_name='declared_amount'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_daily_collections.status' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_daily_collections' AND column_name='status'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_shift_settlement_references.id' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shift_settlement_references' AND column_name='id'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_shift_settlement_references.shift_id' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shift_settlement_references' AND column_name='shift_id'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_shift_settlement_references.business_id' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shift_settlement_references' AND column_name='business_id'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_shift_settlement_references.settlement_no' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shift_settlement_references' AND column_name='settlement_no'),'OK','MISSING') AS status;
SELECT 'column' AS object_type, 'pone_shift_settlement_references.settlement_date' AS object_name, IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shift_settlement_references' AND column_name='settlement_date'),'OK','MISSING') AS status;

SELECT
  SUM(status = 'MISSING') AS missing_object_count
FROM (

SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pone_shifts'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pone_pump_assignments'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pone_meter_readings'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pone_payments'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pone_payment_cash_denominations'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pone_payment_card_lines'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pone_credit_sales'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pone_credit_sale_lines'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pone_other_sales'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pone_other_sale_lines'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pone_unload_stocks'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pone_unload_stock_lines'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pone_day_entries'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pone_daily_collections'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pone_shortage_recoveries'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pone_excess_commissions'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pone_operator_ledger_entries'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pone_pd_operators'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pone_shift_settlement_references'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shifts' AND column_name='id'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shifts' AND column_name='business_id'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shifts' AND column_name='location_id'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shifts' AND column_name='operator_profile_id'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shifts' AND column_name='pd_operator_id'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shifts' AND column_name='shift_number'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shifts' AND column_name='status'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shifts' AND column_name='closed_at'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shifts' AND column_name='meter_sales_total'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shifts' AND column_name='other_sales_total'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shifts' AND column_name='payments_total'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shifts' AND column_name='expected_total'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shifts' AND column_name='declared_total'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shifts' AND column_name='shortage_amount'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shifts' AND column_name='excess_amount'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shifts' AND column_name='reconciliation_status'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_pd_operators' AND column_name='id'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_pd_operators' AND column_name='business_id'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_pd_operators' AND column_name='pd_operator_id'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_pd_operators' AND column_name='display_name'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_pd_operators' AND column_name='status'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_pump_assignments' AND column_name='id'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_pump_assignments' AND column_name='shift_id'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_pump_assignments' AND column_name='business_id'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_pump_assignments' AND column_name='pump_id'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_pump_assignments' AND column_name='opening_meter'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_pump_assignments' AND column_name='closing_meter'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_pump_assignments' AND column_name='testing_quantity'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_pump_assignments' AND column_name='sold_quantity'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_pump_assignments' AND column_name='unit_price'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_pump_assignments' AND column_name='amount'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_pump_assignments' AND column_name='status'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_meter_readings' AND column_name='id'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_meter_readings' AND column_name='shift_id'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_meter_readings' AND column_name='assignment_id'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_meter_readings' AND column_name='business_id'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_meter_readings' AND column_name='meter_value'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_meter_readings' AND column_name='recorded_at'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_payments' AND column_name='id'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_payments' AND column_name='shift_id'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_payments' AND column_name='business_id'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_payments' AND column_name='payment_number'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_payments' AND column_name='payment_type'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_payments' AND column_name='amount'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_payments' AND column_name='transaction_at'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_payments' AND column_name='status'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_other_sales' AND column_name='id'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_other_sales' AND column_name='shift_id'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_other_sales' AND column_name='business_id'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_other_sales' AND column_name='sale_number'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_other_sales' AND column_name='net_amount'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_other_sales' AND column_name='status'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_unload_stocks' AND column_name='id'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_unload_stocks' AND column_name='shift_id'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_unload_stocks' AND column_name='business_id'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_unload_stocks' AND column_name='receipt_number'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_unload_stocks' AND column_name='total_quantity'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_unload_stocks' AND column_name='total_amount'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_unload_stocks' AND column_name='status'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_day_entries' AND column_name='id'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_day_entries' AND column_name='shift_id'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_day_entries' AND column_name='business_id'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_day_entries' AND column_name='entry_type'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_day_entries' AND column_name='status'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_daily_collections' AND column_name='id'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_daily_collections' AND column_name='shift_id'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_daily_collections' AND column_name='business_id'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_daily_collections' AND column_name='collection_number'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_daily_collections' AND column_name='declared_amount'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_daily_collections' AND column_name='status'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shift_settlement_references' AND column_name='id'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shift_settlement_references' AND column_name='shift_id'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shift_settlement_references' AND column_name='business_id'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shift_settlement_references' AND column_name='settlement_no'),'OK','MISSING') AS status
UNION ALL
SELECT IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='pone_shift_settlement_references' AND column_name='settlement_date'),'OK','MISSING') AS status
) verified_contract;
