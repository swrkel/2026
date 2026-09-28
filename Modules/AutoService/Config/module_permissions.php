<?php

/*
 * MA-004 - page permissions for the AutoService module.
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
 *   52 pages found in AutoService.
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
    ['key' => 'autoservice_register', 'label' => 'Register', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_login', 'label' => 'Login', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_dashboard', 'label' => 'Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_customer_login', 'label' => 'Customer Login', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_customer_portal_payment_history', 'label' => 'Customer Portal Payment History', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_customer_experience_live_progress', 'label' => 'Customer Experience Live Progress', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_command_centre', 'label' => 'Command Centre', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_command_centre_live', 'label' => 'Command Centre Live', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_command_center', 'label' => 'Command Center', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_workspace', 'label' => 'Workspace', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_vehicle_search', 'label' => 'Vehicle Search', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_central_vehicle_workshop_history', 'label' => 'Central Vehicle Workshop History', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_workshop', 'label' => 'Workshop', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_parts_labour', 'label' => 'Parts Labour', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_service_flow', 'label' => 'Service Flow', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_billing_delivery', 'label' => 'Billing Delivery', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_whiteboard', 'label' => 'Whiteboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_quality_control', 'label' => 'Quality Control', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_deliveries', 'label' => 'Deliveries', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_mechanic_dashboard', 'label' => 'Mechanic Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_bays', 'label' => 'Bays', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_communications', 'label' => 'Communications', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_calendar', 'label' => 'Calendar', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_notifications', 'label' => 'Notifications', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_customer_portal_lookup', 'label' => 'Customer Portal Lookup', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_reminders', 'label' => 'Reminders', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_reports', 'label' => 'Reports', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_reports_service_due', 'label' => 'Reports Service Due', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_reports_profitability', 'label' => 'Reports Profitability', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_reports_daily_summary', 'label' => 'Reports Daily Summary', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_reports_mechanic_performance', 'label' => 'Reports Mechanic Performance', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_reports_invoice_aging', 'label' => 'Reports Invoice Aging', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_release_audit', 'label' => 'Release Audit', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_customer_care', 'label' => 'Customer Care', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_maintenance_planner', 'label' => 'Maintenance Planner', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_maintenance_planner_vehicle_health', 'label' => 'Maintenance Planner Vehicle Health', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_completion_check', 'label' => 'Completion Check', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_production_audit', 'label' => 'Production Audit', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_stabilization_centre', 'label' => 'Stabilization Centre', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_deployment_diagnostics', 'label' => 'Deployment Diagnostics', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_enterprise_integration', 'label' => 'Enterprise Integration', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_ui_standardization_performance', 'label' => 'Ui Standardization Performance', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_management_kpi', 'label' => 'Management Kpi', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_inventory_control', 'label' => 'Inventory Control', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_workshop_planning', 'label' => 'Workshop Planning', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_advanced_vehicle_history', 'label' => 'Advanced Vehicle History', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_settings', 'label' => 'Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_business_intelligence', 'label' => 'Business Intelligence', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_dealer_enterprise', 'label' => 'Dealer Enterprise', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_reports_service_package_usage', 'label' => 'Reports Service Package Usage', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_reports_service_package_profitability', 'label' => 'Reports Service Package Profitability', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autoservice_reports_service_package_stock_consumption', 'label' => 'Reports Service Package Stock Consumption', 'type' => 'page', 'source' => 'module_pages'],
];
