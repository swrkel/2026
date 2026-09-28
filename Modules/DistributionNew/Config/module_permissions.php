<?php

/*
 * MA-004 - page permissions for the DistributionNew module.
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
 *   65 pages found in DistributionNew.
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
    ['key' => 'distributionnew_workflow_validation', 'label' => 'Workflow Validation', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_stock_reconciliation', 'label' => 'Stock Reconciliation', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_visit_plans', 'label' => 'Visit Plans', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_management_dashboard', 'label' => 'Management Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_production_stabilization', 'label' => 'Production Stabilization', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_production_stabilization_exceptions', 'label' => 'Production Stabilization Exceptions', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_production_stabilization_audits', 'label' => 'Production Stabilization Audits', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_permissions', 'label' => 'Permissions', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_menu', 'label' => 'Menu', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_routes', 'label' => 'Routes', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_sql', 'label' => 'Sql', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_ui', 'label' => 'Ui', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_sync_batches', 'label' => 'Sync Batches', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_devices', 'label' => 'Devices', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_dashboard', 'label' => 'Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_orders', 'label' => 'Orders', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_invoices', 'label' => 'Invoices', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_deliveries', 'label' => 'Deliveries', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_dashboard_operations', 'label' => 'Dashboard Operations', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_dashboard_role', 'label' => 'Dashboard Role', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_vehicle_stock', 'label' => 'Vehicle Stock', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_vehicle_store_stock', 'label' => 'Vehicle Store Stock', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_store_vehicle_stock', 'label' => 'Store Vehicle Stock', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_sales_rep_portal', 'label' => 'Sales Rep Portal', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_sales_rep_portal_route_today', 'label' => 'Sales Rep Portal Route Today', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_sales_rep_portal_orders', 'label' => 'Sales Rep Portal Orders', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_sales_rep_portal_collections', 'label' => 'Sales Rep Portal Collections', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_settings', 'label' => 'Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_settings_sms_officer_groups', 'label' => 'Settings Sms Officer Groups', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_settings_notification_preferences', 'label' => 'Settings Notification Preferences', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_sms_templates', 'label' => 'Sms Templates', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_sms_logs', 'label' => 'Sms Logs', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_approvals', 'label' => 'Approvals', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_audit_health', 'label' => 'Audit Health', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_install_checklist', 'label' => 'Install Checklist', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_deployment_logs', 'label' => 'Deployment Logs', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_reports', 'label' => 'Reports', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_reports_operational_daily', 'label' => 'Reports Operational Daily', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_reports_operational_vehicle_stock', 'label' => 'Reports Operational Vehicle Stock', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_reports_returns_summary', 'label' => 'Reports Returns Summary', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_analytics_executive_dashboard', 'label' => 'Analytics Executive Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_analytics_kpi_dashboard', 'label' => 'Analytics Kpi Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_analytics_scheduled_reports', 'label' => 'Analytics Scheduled Reports', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_installation_checklist', 'label' => 'Installation Checklist', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_command_centre', 'label' => 'Command Centre', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_dispatch_monitor', 'label' => 'Dispatch Monitor', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_route_progress', 'label' => 'Route Progress', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_role_dashboard', 'label' => 'Role Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_reports_deployment_logs', 'label' => 'Reports Deployment Logs', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_download_checklist', 'label' => 'Download Checklist', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_stabilization', 'label' => 'Stabilization', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_stabilization_checklist', 'label' => 'Stabilization Checklist', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_dashboard_stage8', 'label' => 'Dashboard Stage8', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_limits', 'label' => 'Limits', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_profitability', 'label' => 'Profitability', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_collection_controls', 'label' => 'Collection Controls', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_reconciliation_exceptions', 'label' => 'Reconciliation Exceptions', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_deployment_verification', 'label' => 'Deployment Verification', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_statements', 'label' => 'Statements', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_loading', 'label' => 'Loading', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_unloading', 'label' => 'Unloading', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_bins', 'label' => 'Bins', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_verify', 'label' => 'Verify', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_labels', 'label' => 'Labels', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distributionnew_server_testing_fix_pack_2', 'label' => 'Server Testing Fix Pack 2', 'type' => 'page', 'source' => 'module_pages'],
];
