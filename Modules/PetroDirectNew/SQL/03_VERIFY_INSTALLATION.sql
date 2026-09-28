-- Petro Direct-New installation verification
SELECT 'pdirectnew_settings' AS table_name, IF(COUNT(*)=1,'OK','MISSING') AS status FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pdirectnew_settings';
SELECT 'pdirectnew_number_sequences' AS table_name, IF(COUNT(*)=1,'OK','MISSING') AS status FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pdirectnew_number_sequences';
SELECT 'pdirectnew_operators' AS table_name, IF(COUNT(*)=1,'OK','MISSING') AS status FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pdirectnew_operators';
SELECT 'pdirectnew_tanks' AS table_name, IF(COUNT(*)=1,'OK','MISSING') AS status FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pdirectnew_tanks';
SELECT 'pdirectnew_pumps' AS table_name, IF(COUNT(*)=1,'OK','MISSING') AS status FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pdirectnew_pumps';
SELECT 'pdirectnew_shifts' AS table_name, IF(COUNT(*)=1,'OK','MISSING') AS status FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pdirectnew_shifts';
SELECT 'pdirectnew_assignments' AS table_name, IF(COUNT(*)=1,'OK','MISSING') AS status FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pdirectnew_assignments';
SELECT 'pdirectnew_meter_readings' AS table_name, IF(COUNT(*)=1,'OK','MISSING') AS status FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pdirectnew_meter_readings';
SELECT 'pdirectnew_meter_resets' AS table_name, IF(COUNT(*)=1,'OK','MISSING') AS status FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pdirectnew_meter_resets';
SELECT 'pdirectnew_settlements' AS table_name, IF(COUNT(*)=1,'OK','MISSING') AS status FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pdirectnew_settlements';
SELECT 'pdirectnew_settlement_meter_sales' AS table_name, IF(COUNT(*)=1,'OK','MISSING') AS status FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pdirectnew_settlement_meter_sales';
SELECT 'pdirectnew_settlement_payments' AS table_name, IF(COUNT(*)=1,'OK','MISSING') AS status FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pdirectnew_settlement_payments';
SELECT 'pdirectnew_settlement_other_sales' AS table_name, IF(COUNT(*)=1,'OK','MISSING') AS status FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pdirectnew_settlement_other_sales';
SELECT 'pdirectnew_settlement_other_sale_lines' AS table_name, IF(COUNT(*)=1,'OK','MISSING') AS status FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pdirectnew_settlement_other_sale_lines';
SELECT 'pdirectnew_settlement_other_income' AS table_name, IF(COUNT(*)=1,'OK','MISSING') AS status FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pdirectnew_settlement_other_income';
SELECT 'pdirectnew_settlement_customer_payments' AS table_name, IF(COUNT(*)=1,'OK','MISSING') AS status FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pdirectnew_settlement_customer_payments';
SELECT 'pdirectnew_daily_collections' AS table_name, IF(COUNT(*)=1,'OK','MISSING') AS status FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pdirectnew_daily_collections';
SELECT 'pdirectnew_collection_lines' AS table_name, IF(COUNT(*)=1,'OK','MISSING') AS status FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pdirectnew_collection_lines';
SELECT 'pdirectnew_pumper_day_entries' AS table_name, IF(COUNT(*)=1,'OK','MISSING') AS status FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pdirectnew_pumper_day_entries';
SELECT 'pdirectnew_unload_stocks' AS table_name, IF(COUNT(*)=1,'OK','MISSING') AS status FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pdirectnew_unload_stocks';
SELECT 'pdirectnew_unload_stock_lines' AS table_name, IF(COUNT(*)=1,'OK','MISSING') AS status FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pdirectnew_unload_stock_lines';
SELECT 'pdirectnew_tank_transfers' AS table_name, IF(COUNT(*)=1,'OK','MISSING') AS status FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pdirectnew_tank_transfers';
SELECT 'pdirectnew_dip_charts' AS table_name, IF(COUNT(*)=1,'OK','MISSING') AS status FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pdirectnew_dip_charts';
SELECT 'pdirectnew_dip_chart_lines' AS table_name, IF(COUNT(*)=1,'OK','MISSING') AS status FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pdirectnew_dip_chart_lines';
SELECT 'pdirectnew_dip_readings' AS table_name, IF(COUNT(*)=1,'OK','MISSING') AS status FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pdirectnew_dip_readings';
SELECT 'pdirectnew_adjustments' AS table_name, IF(COUNT(*)=1,'OK','MISSING') AS status FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pdirectnew_adjustments';
SELECT 'pdirectnew_print_logs' AS table_name, IF(COUNT(*)=1,'OK','MISSING') AS status FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pdirectnew_print_logs';
SELECT 'pdirectnew_audit_logs' AS table_name, IF(COUNT(*)=1,'OK','MISSING') AS status FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pdirectnew_audit_logs';
SELECT 'pdirectnew_saved_report_filters' AS table_name, IF(COUNT(*)=1,'OK','MISSING') AS status FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='pdirectnew_saved_report_filters';
SELECT 'petro_direct_new.access' AS permission_name, IF(COUNT(*)>=1,'OK','MISSING') AS status FROM permissions WHERE name='petro_direct_new.access' AND guard_name='web';
SELECT 'petro_direct_new.dashboard.view' AS permission_name, IF(COUNT(*)>=1,'OK','MISSING') AS status FROM permissions WHERE name='petro_direct_new.dashboard.view' AND guard_name='web';
SELECT 'petro_direct_new.settlements.view' AS permission_name, IF(COUNT(*)>=1,'OK','MISSING') AS status FROM permissions WHERE name='petro_direct_new.settlements.view' AND guard_name='web';
SELECT 'petro_direct_new.settlements.create' AS permission_name, IF(COUNT(*)>=1,'OK','MISSING') AS status FROM permissions WHERE name='petro_direct_new.settlements.create' AND guard_name='web';
SELECT 'petro_direct_new.settlements.edit' AS permission_name, IF(COUNT(*)>=1,'OK','MISSING') AS status FROM permissions WHERE name='petro_direct_new.settlements.edit' AND guard_name='web';
SELECT 'petro_direct_new.settlements.delete' AS permission_name, IF(COUNT(*)>=1,'OK','MISSING') AS status FROM permissions WHERE name='petro_direct_new.settlements.delete' AND guard_name='web';
SELECT 'petro_direct_new.settlements.finalize' AS permission_name, IF(COUNT(*)>=1,'OK','MISSING') AS status FROM permissions WHERE name='petro_direct_new.settlements.finalize' AND guard_name='web';
SELECT 'petro_direct_new.settlements.print' AS permission_name, IF(COUNT(*)>=1,'OK','MISSING') AS status FROM permissions WHERE name='petro_direct_new.settlements.print' AND guard_name='web';
SELECT 'petro_direct_new.pumpers.view' AS permission_name, IF(COUNT(*)>=1,'OK','MISSING') AS status FROM permissions WHERE name='petro_direct_new.pumpers.view' AND guard_name='web';
SELECT 'petro_direct_new.operators.manage' AS permission_name, IF(COUNT(*)>=1,'OK','MISSING') AS status FROM permissions WHERE name='petro_direct_new.operators.manage' AND guard_name='web';
SELECT 'petro_direct_new.pumps.manage' AS permission_name, IF(COUNT(*)>=1,'OK','MISSING') AS status FROM permissions WHERE name='petro_direct_new.pumps.manage' AND guard_name='web';
SELECT 'petro_direct_new.tanks.manage' AS permission_name, IF(COUNT(*)>=1,'OK','MISSING') AS status FROM permissions WHERE name='petro_direct_new.tanks.manage' AND guard_name='web';
SELECT 'petro_direct_new.assignments.manage' AS permission_name, IF(COUNT(*)>=1,'OK','MISSING') AS status FROM permissions WHERE name='petro_direct_new.assignments.manage' AND guard_name='web';
SELECT 'petro_direct_new.meters.manage' AS permission_name, IF(COUNT(*)>=1,'OK','MISSING') AS status FROM permissions WHERE name='petro_direct_new.meters.manage' AND guard_name='web';
SELECT 'petro_direct_new.dips.manage' AS permission_name, IF(COUNT(*)>=1,'OK','MISSING') AS status FROM permissions WHERE name='petro_direct_new.dips.manage' AND guard_name='web';
SELECT 'petro_direct_new.transfers.manage' AS permission_name, IF(COUNT(*)>=1,'OK','MISSING') AS status FROM permissions WHERE name='petro_direct_new.transfers.manage' AND guard_name='web';
SELECT 'petro_direct_new.collections.manage' AS permission_name, IF(COUNT(*)>=1,'OK','MISSING') AS status FROM permissions WHERE name='petro_direct_new.collections.manage' AND guard_name='web';
SELECT 'petro_direct_new.payments.manage' AS permission_name, IF(COUNT(*)>=1,'OK','MISSING') AS status FROM permissions WHERE name='petro_direct_new.payments.manage' AND guard_name='web';
SELECT 'petro_direct_new.day_entries.manage' AS permission_name, IF(COUNT(*)>=1,'OK','MISSING') AS status FROM permissions WHERE name='petro_direct_new.day_entries.manage' AND guard_name='web';
SELECT 'petro_direct_new.shifts.close' AS permission_name, IF(COUNT(*)>=1,'OK','MISSING') AS status FROM permissions WHERE name='petro_direct_new.shifts.close' AND guard_name='web';
SELECT 'petro_direct_new.unload_stock.manage' AS permission_name, IF(COUNT(*)>=1,'OK','MISSING') AS status FROM permissions WHERE name='petro_direct_new.unload_stock.manage' AND guard_name='web';
SELECT 'petro_direct_new.reports.view' AS permission_name, IF(COUNT(*)>=1,'OK','MISSING') AS status FROM permissions WHERE name='petro_direct_new.reports.view' AND guard_name='web';
SELECT 'petro_direct_new.reports.export' AS permission_name, IF(COUNT(*)>=1,'OK','MISSING') AS status FROM permissions WHERE name='petro_direct_new.reports.export' AND guard_name='web';
SELECT 'petro_direct_new.settings.manage' AS permission_name, IF(COUNT(*)>=1,'OK','MISSING') AS status FROM permissions WHERE name='petro_direct_new.settings.manage' AND guard_name='web';

-- Pump Operator workspace verification (03 Aug 2026)
SELECT
  'pdirectnew_operators workspace columns' AS verification_item,
  CASE WHEN COUNT(*) = 17 THEN 'OK' ELSE CONCAT('MISSING: ', 17 - COUNT(*)) END AS result
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND table_name = 'pdirectnew_operators'
  AND column_name IN (
    'source_operator_id','address','landline','dob','email','username','passcode_hash',
    'opening_balance','commission_type','commission_value','short_amount','excess_amount',
    'transaction_date','is_default','can_fullscreen',
    'hide_in_direct_settlement_if_pending_shifts','source_updated_at'
  );

SELECT
  'pdirectnew_operators performance indexes' AS verification_item,
  CASE WHEN COUNT(DISTINCT index_name) = 2 THEN 'OK' ELSE 'MISSING INDEX' END AS result
FROM information_schema.statistics
WHERE table_schema = DATABASE()
  AND table_name = 'pdirectnew_operators'
  AND index_name IN ('pdn_operator_source_uq','pdn_operator_list_idx');
