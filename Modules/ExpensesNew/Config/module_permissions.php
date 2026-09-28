<?php

/*
 * MA-004 - page permissions for the ExpensesNew module.
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
 *   65 pages found in ExpensesNew.
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
    ['key' => 'expensesnew_command_center', 'label' => 'Command Center', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_analytics', 'label' => 'Analytics', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_intelligence', 'label' => 'Intelligence', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_reports', 'label' => 'Reports', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_dashboard', 'label' => 'Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_expenses', 'label' => 'Expenses', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_expenses_create', 'label' => 'Expenses Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_quick_category_create', 'label' => 'Quick Category Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_categories', 'label' => 'Categories', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_categories_create', 'label' => 'Categories Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_payees', 'label' => 'Payees', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_payees_create', 'label' => 'Payees Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_accounts', 'label' => 'Accounts', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_accounts_create', 'label' => 'Accounts Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_settings', 'label' => 'Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_reports_expense_summary', 'label' => 'Reports Expense Summary', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_approvals', 'label' => 'Approvals', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_departments', 'label' => 'Departments', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_cost_centers', 'label' => 'Cost Centers', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_projects', 'label' => 'Projects', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_integration', 'label' => 'Integration', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_notifications', 'label' => 'Notifications', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_budgets', 'label' => 'Budgets', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_budgets_create', 'label' => 'Budgets Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_policies', 'label' => 'Policies', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_policies_create', 'label' => 'Policies Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_taxes', 'label' => 'Taxes', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_taxes_create', 'label' => 'Taxes Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_recurring', 'label' => 'Recurring', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_recurring_create', 'label' => 'Recurring Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_budget_control', 'label' => 'Budget Control', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_budget_control_create', 'label' => 'Budget Control Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_analytics_create', 'label' => 'Analytics Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_analytics_overview', 'label' => 'Analytics Overview', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_vendor_analytics', 'label' => 'Vendor Analytics', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_vendor_analytics_create', 'label' => 'Vendor Analytics Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_command_center_widgets', 'label' => 'Command Center Widgets', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_approval_workbench', 'label' => 'Approval Workbench', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_operations_board', 'label' => 'Operations Board', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_operations_board_feed', 'label' => 'Operations Board Feed', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_financial_intelligence', 'label' => 'Financial Intelligence', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_audit_centre', 'label' => 'Audit Centre', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_closing_centre', 'label' => 'Closing Centre', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_kpi_dashboard', 'label' => 'Kpi Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_reporting_centre', 'label' => 'Reporting Centre', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_budget_centre', 'label' => 'Budget Centre', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_budget_centre_approvals', 'label' => 'Budget Centre Approvals', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_budget_centre_forecast', 'label' => 'Budget Centre Forecast', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_budget_centre_variance', 'label' => 'Budget Centre Variance', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_costing_engine', 'label' => 'Costing Engine', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_costing_engine_create', 'label' => 'Costing Engine Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_costing_allocation_rules', 'label' => 'Costing Allocation Rules', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_costing_allocation_rules_create', 'label' => 'Costing Allocation Rules Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_costing_allocation_runs', 'label' => 'Costing Allocation Runs', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_costing_allocation_runs_create', 'label' => 'Costing Allocation Runs Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_costing_activity_based', 'label' => 'Costing Activity Based', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_costing_activity_based_create', 'label' => 'Costing Activity Based Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_costing_shared_expenses', 'label' => 'Costing Shared Expenses', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_costing_shared_expenses_create', 'label' => 'Costing Shared Expenses Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_costing_overhead_recovery', 'label' => 'Costing Overhead Recovery', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_costing_overhead_recovery_create', 'label' => 'Costing Overhead Recovery Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_costing_profitability', 'label' => 'Costing Profitability', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_costing_profitability_create', 'label' => 'Costing Profitability Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_costing_kpis', 'label' => 'Costing Kpis', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'expensesnew_costing_kpis_create', 'label' => 'Costing Kpis Create', 'type' => 'page', 'source' => 'module_pages'],
];
