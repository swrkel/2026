<?php

/*
 * MA-004 - page permissions for the StockTransferNew module.
 *
 * WHY THIS FILE EXISTS
 *   The Role and Permissions screen builds its checkbox list from each
 *   module's Config/module_permissions.php. Only SEVEN of the 119 modules
 *   had one, so a Business Admin could set page permissions for seven
 *   modules and saw nothing for the other 112 - which is what MA-004
 *   reports.
 *
 *   These entries were derived from this module's own GET routes: one page
 *   per navigable url. Endpoints that are not pages - anything ending in
 *   /data, /options, /export, /get-something, or carrying a {parameter} -
 *   were left out, because they are ajax calls rather than screens a
 *   permission would sensibly guard.
 *
 *   61 pages found in StockTransferNew.
 *
 * EDITING THIS FILE
 *   It is ordinary configuration, safe to edit by hand. Change a label to
 *   whatever reads better on the permissions screen, or delete a line to
 *   remove a page from it. Nothing regenerates this automatically.
 *
 *   Modules that already had a hand-written module_permissions.php were
 *   NOT touched - PetroPDNew, PumperDashboardNew, StockTakingNew,
 *   PetroDirectNew, RestaurantNew, UserManagementNew and ManagementReport
 *   keep their curated lists.
 */

return [
    ['key' => 'stocktransfernew_transfer_register', 'label' => 'Transfer Register', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_register', 'label' => 'Register', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_stock_movement', 'label' => 'Stock Movement', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_balances', 'label' => 'Balances', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_stock_in_transit', 'label' => 'Stock In Transit', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_variance', 'label' => 'Variance', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_aging', 'label' => 'Aging', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_claims', 'label' => 'Claims', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_claims_create', 'label' => 'Claims Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_calendar', 'label' => 'Calendar', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_signoff', 'label' => 'Signoff', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_snapshot', 'label' => 'Snapshot', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_production_console', 'label' => 'Production Console', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_export_csv', 'label' => 'Export Csv', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_dock_schedules', 'label' => 'Dock Schedules', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_capacity_planning', 'label' => 'Capacity Planning', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_sql_tracker', 'label' => 'Sql Tracker', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_rollback', 'label' => 'Rollback', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_command_center', 'label' => 'Command Center', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_approvals', 'label' => 'Approvals', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_dispatch', 'label' => 'Dispatch', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_receive', 'label' => 'Receive', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_settings', 'label' => 'Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_readiness', 'label' => 'Readiness', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_readiness_sql_checklist', 'label' => 'Readiness Sql Checklist', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_readiness_export_checklist', 'label' => 'Readiness Export Checklist', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_predictive_planning', 'label' => 'Predictive Planning', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_workload_balance', 'label' => 'Workload Balance', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_inter_business_settlements', 'label' => 'Inter Business Settlements', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_inter_business_settlements_create', 'label' => 'Inter Business Settlements Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_training', 'label' => 'Training', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_ai_replenishment_review', 'label' => 'Ai Replenishment Review', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_data_quality', 'label' => 'Data Quality', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_production_validation', 'label' => 'Production Validation', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_locations', 'label' => 'Locations', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_stores', 'label' => 'Stores', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_products', 'label' => 'Products', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_available_stock', 'label' => 'Available Stock', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_candidates', 'label' => 'Candidates', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_freight_settlement', 'label' => 'Freight Settlement', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_freight_settlement_export', 'label' => 'Freight Settlement Export', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_create', 'label' => 'Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_transfer_policies', 'label' => 'Transfer Policies', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_transfer_policies_cost_center_summary', 'label' => 'Transfer Policies Cost Center Summary', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_monitor', 'label' => 'Monitor', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_stock_transfer_new_tenant_diagnostic', 'label' => 'Stock Transfer New Tenant Diagnostic', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_diagnostics', 'label' => 'Diagnostics', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_tenant_scope', 'label' => 'Tenant Scope', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_permissions', 'label' => 'Permissions', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_routes_assets', 'label' => 'Routes Assets', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_repair', 'label' => 'Repair', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_production_hardening', 'label' => 'Production Hardening', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_cost_reconciliation', 'label' => 'Cost Reconciliation', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_period_close', 'label' => 'Period Close', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_test_cases', 'label' => 'Test Cases', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_troubleshooting', 'label' => 'Troubleshooting', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_cleanup_preview', 'label' => 'Cleanup Preview', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_forecasting', 'label' => 'Forecasting', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_live_board', 'label' => 'Live Board', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_exceptions', 'label' => 'Exceptions', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'stocktransfernew_workload', 'label' => 'Workload', 'type' => 'page', 'source' => 'module_pages'],
];
