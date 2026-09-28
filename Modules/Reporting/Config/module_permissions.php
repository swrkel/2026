<?php

/*
 * MA-004 - page permissions for the Reporting module.
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
 *   12 pages found in Reporting.
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
    ['key' => 'reporting_dashboard', 'label' => 'Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'reporting_branch_profit_loss', 'label' => 'Branch Profit Loss', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'reporting_branch_balance_sheet', 'label' => 'Branch Balance Sheet', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'reporting_branch_trial_balance', 'label' => 'Branch Trial Balance', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'reporting_consolidated_profit_loss', 'label' => 'Consolidated Profit Loss', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'reporting_consolidated_balance_sheet', 'label' => 'Consolidated Balance Sheet', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'reporting_consolidated_trial_balance', 'label' => 'Consolidated Trial Balance', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'reporting_general_ledger', 'label' => 'General Ledger', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'reporting_financial_profit_loss', 'label' => 'Financial Profit Loss', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'reporting_recovery', 'label' => 'Recovery', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'reporting_compliance', 'label' => 'Compliance', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'reporting_governance', 'label' => 'Governance', 'type' => 'page', 'source' => 'module_pages'],
];
