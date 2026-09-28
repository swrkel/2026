<?php

/*
 * MA-004 - page permissions for the BankingMicrofinance module.
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
 *   24 pages found in BankingMicrofinance.
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
    ['key' => 'bankingmicrofinance_reports_compliance', 'label' => 'Reports Compliance', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bankingmicrofinance_reports_risk', 'label' => 'Reports Risk', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bankingmicrofinance_reports_npl', 'label' => 'Reports Npl', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bankingmicrofinance_reports_dashboard', 'label' => 'Reports Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bankingmicrofinance_offline_batches', 'label' => 'Offline Batches', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bankingmicrofinance_offline_batches_create', 'label' => 'Offline Batches Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bankingmicrofinance_receipt_verification', 'label' => 'Receipt Verification', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bankingmicrofinance_reports_daily_collection', 'label' => 'Reports Daily Collection', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bankingmicrofinance_reports_officer_productivity', 'label' => 'Reports Officer Productivity', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bankingmicrofinance_reports_receipt_exceptions', 'label' => 'Reports Receipt Exceptions', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bankingmicrofinance_reports_cash_handover_variance', 'label' => 'Reports Cash Handover Variance', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bankingmicrofinance_reports_credit_pipeline', 'label' => 'Reports Credit Pipeline', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bankingmicrofinance_reports_cashflow', 'label' => 'Reports Cashflow', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bankingmicrofinance_reports_guarantors', 'label' => 'Reports Guarantors', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bankingmicrofinance_reports_pricing', 'label' => 'Reports Pricing', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bankingmicrofinance_reports_credit_committee', 'label' => 'Reports Credit Committee', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bankingmicrofinance_penalty_rules', 'label' => 'Penalty Rules', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bankingmicrofinance_penalty_charges', 'label' => 'Penalty Charges', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bankingmicrofinance_reports_aging', 'label' => 'Reports Aging', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bankingmicrofinance_reports_portfolio', 'label' => 'Reports Portfolio', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bankingmicrofinance_reports_collection_efficiency', 'label' => 'Reports Collection Efficiency', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bankingmicrofinance_repayment_allocation', 'label' => 'Repayment Allocation', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bankingmicrofinance_portfolio_quality', 'label' => 'Portfolio Quality', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bankingmicrofinance_audit_exceptions', 'label' => 'Audit Exceptions', 'type' => 'page', 'source' => 'module_pages'],
];
