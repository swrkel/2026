<?php

/*
 * MA-004 - page permissions for the FinanceReports module.
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
 *   91 pages found in FinanceReports.
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
    ['key' => 'financereports_executive_bi_dashboard_new', 'label' => 'Executive Bi Dashboard New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_report_engine_status', 'label' => 'Report Engine Status', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_trial_balance_new', 'label' => 'Trial Balance New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_balance_sheet_new', 'label' => 'Balance Sheet New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_profit_loss_new', 'label' => 'Profit Loss New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_income_statement_new', 'label' => 'Income Statement New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_account_ledger_new', 'label' => 'Account Ledger New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_trial_balance', 'label' => 'Trial Balance', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_balance_sheet', 'label' => 'Balance Sheet', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_profit_loss', 'label' => 'Profit Loss', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_income_statement', 'label' => 'Income Statement', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_account_ledger', 'label' => 'Account Ledger', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_general_ledger', 'label' => 'General Ledger', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_cash_book', 'label' => 'Cash Book', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_bank_book', 'label' => 'Bank Book', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_financial_intelligence', 'label' => 'Financial Intelligence', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_executive_dashboard', 'label' => 'Executive Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_cfo_dashboard', 'label' => 'Cfo Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_general_ledger_new', 'label' => 'General Ledger New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_cash_book_new', 'label' => 'Cash Book New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_bank_book_new', 'label' => 'Bank Book New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_journal_register_new', 'label' => 'Journal Register New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_day_book_new', 'label' => 'Day Book New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_financial_dashboard_new', 'label' => 'Financial Dashboard New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_budget_vs_actual_new', 'label' => 'Budget Vs Actual New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_revenue_analysis_new', 'label' => 'Revenue Analysis New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_expense_analysis_new', 'label' => 'Expense Analysis New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_branch_performance_new', 'label' => 'Branch Performance New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_financial_ratios_new', 'label' => 'Financial Ratios New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_comparative_report_new', 'label' => 'Comparative Report New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_customer_outstanding_new', 'label' => 'Customer Outstanding New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_supplier_outstanding_new', 'label' => 'Supplier Outstanding New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_customer_aging_new', 'label' => 'Customer Aging New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_supplier_aging_new', 'label' => 'Supplier Aging New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_collection_analysis_new', 'label' => 'Collection Analysis New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_payment_analysis_new', 'label' => 'Payment Analysis New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_receivable_summary_new', 'label' => 'Receivable Summary New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_payable_summary_new', 'label' => 'Payable Summary New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_customer_statement_new', 'label' => 'Customer Statement New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_supplier_statement_new', 'label' => 'Supplier Statement New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_cash_flow_statement_new', 'label' => 'Cash Flow Statement New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_cash_position_report_new', 'label' => 'Cash Position Report New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_bank_position_report_new', 'label' => 'Bank Position Report New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_bank_reconciliation_new', 'label' => 'Bank Reconciliation New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_cheque_register_new', 'label' => 'Cheque Register New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_post_dated_cheque_register_new', 'label' => 'Post Dated Cheque Register New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_cash_movement_analysis_new', 'label' => 'Cash Movement Analysis New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_audit_trail_new', 'label' => 'Audit Trail New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_transaction_history_new', 'label' => 'Transaction History New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_user_financial_activity_new', 'label' => 'User Financial Activity New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_deleted_transactions_new', 'label' => 'Deleted Transactions New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_edited_transactions_new', 'label' => 'Edited Transactions New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_voucher_approval_history_new', 'label' => 'Voucher Approval History New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_exception_report_new', 'label' => 'Exception Report New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_financial_log_viewer_new', 'label' => 'Financial Log Viewer New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_fixed_asset_dashboard_new', 'label' => 'Fixed Asset Dashboard New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_fixed_asset_register_new', 'label' => 'Fixed Asset Register New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_depreciation_register_new', 'label' => 'Depreciation Register New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_asset_movement_register_new', 'label' => 'Asset Movement Register New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_asset_transfer_report_new', 'label' => 'Asset Transfer Report New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_asset_disposal_register_new', 'label' => 'Asset Disposal Register New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_asset_category_summary_new', 'label' => 'Asset Category Summary New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_asset_valuation_report_new', 'label' => 'Asset Valuation Report New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_enterprise_center', 'label' => 'Enterprise Center', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_cash_flow_forecast_new', 'label' => 'Cash Flow Forecast New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_revenue_forecast_new', 'label' => 'Revenue Forecast New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_expense_forecast_new', 'label' => 'Expense Forecast New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_profit_forecast_new', 'label' => 'Profit Forecast New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_consolidation_center_new', 'label' => 'Consolidation Center New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_performance_center_new', 'label' => 'Performance Center New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_standalone_audit_new', 'label' => 'Standalone Audit New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_export_center_new', 'label' => 'Export Center New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_print_layout_center_new', 'label' => 'Print Layout Center New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_drilldown_center_new', 'label' => 'Drilldown Center New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_financial_pack_new', 'label' => 'Financial Pack New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_report_scheduler_new', 'label' => 'Report Scheduler New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_executive_kpi_center_new', 'label' => 'Executive Kpi Center New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_calculation_verification_new', 'label' => 'Calculation Verification New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_production_readiness_new', 'label' => 'Production Readiness New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_financial_intelligence_new', 'label' => 'Financial Intelligence New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_cfo_dashboard_new', 'label' => 'Cfo Dashboard New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_financial_health_score_new', 'label' => 'Financial Health Score New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_scenario_analysis_new', 'label' => 'Scenario Analysis New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_board_pack_new', 'label' => 'Board Pack New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_enterprise_data_hub_new', 'label' => 'Enterprise Data Hub New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_cross_module_financial_intelligence_new', 'label' => 'Cross Module Financial Intelligence New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_enterprise_dashboard_builder_new', 'label' => 'Enterprise Dashboard Builder New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_enterprise_report_builder_new', 'label' => 'Enterprise Report Builder New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_financial_workspace_new', 'label' => 'Financial Workspace New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_enterprise_report_scheduler_new', 'label' => 'Enterprise Report Scheduler New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'financereports_enterprise_platform_audit_new', 'label' => 'Enterprise Platform Audit New', 'type' => 'page', 'source' => 'module_pages'],
];
