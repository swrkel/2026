<?php

/*
 * MA-004 - page permissions for the Loan module.
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
 *   90 pages found in Loan.
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
    ['key' => 'loan_dashboard', 'label' => 'Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_loan_settings', 'label' => 'Loan Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_create', 'label' => 'Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_collections_dashboard', 'label' => 'Collections Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_ajax_data', 'label' => 'Ajax Data', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_assignments', 'label' => 'Assignments', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_write_offs', 'label' => 'Write Offs', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_stabilization', 'label' => 'Stabilization', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_stabilization_arrears_aging', 'label' => 'Stabilization Arrears Aging', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_operations', 'label' => 'Operations', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_portfolio_summary', 'label' => 'Portfolio Summary', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_disbursements', 'label' => 'Disbursements', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_repayments', 'label' => 'Repayments', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_overdue', 'label' => 'Overdue', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_collection_report', 'label' => 'Collection Report', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_loan_status_report', 'label' => 'Loan Status Report', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_mis', 'label' => 'Mis', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_portfolio_intelligence', 'label' => 'Portfolio Intelligence', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_par_analytics', 'label' => 'Par Analytics', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_risk_analytics', 'label' => 'Risk Analytics', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_loans', 'label' => 'Loans', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_notifications', 'label' => 'Notifications', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_settlements', 'label' => 'Settlements', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_restructuring', 'label' => 'Restructuring', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_profile', 'label' => 'Profile', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_statement', 'label' => 'Statement', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_install', 'label' => 'Install', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_install_uninstall', 'label' => 'Install Uninstall', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_install_update', 'label' => 'Install Update', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_get_totals', 'label' => 'Get Totals', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_get_loans_awaiting_disbursement_chart', 'label' => 'Get Loans Awaiting Disbursement Chart', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_get_loans_rejected_chart', 'label' => 'Get Loans Rejected Chart', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_get_principal_projected_chart', 'label' => 'Get Principal Projected Chart', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_get_principal_collected_chart', 'label' => 'Get Principal Collected Chart', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_get_interest_projected_chart', 'label' => 'Get Interest Projected Chart', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_get_interest_collected_chart', 'label' => 'Get Interest Collected Chart', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_get_penalties_projected_chart', 'label' => 'Get Penalties Projected Chart', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_get_penalties_collected_chart', 'label' => 'Get Penalties Collected Chart', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_get_fees_projected_chart', 'label' => 'Get Fees Projected Chart', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_get_fees_collected_chart', 'label' => 'Get Fees Collected Chart', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_get_total_paid_chart', 'label' => 'Get Total Paid Chart', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_get_loans', 'label' => 'Get Loans', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_repaymentbulk', 'label' => 'Repaymentbulk', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_import', 'label' => 'Import', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_create_client_loan', 'label' => 'Create Client Loan', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_calculator', 'label' => 'Calculator', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_bulk_import_repayments', 'label' => 'Bulk Import Repayments', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_get_purposes', 'label' => 'Get Purposes', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_get_collateral_types', 'label' => 'Get Collateral Types', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_get_charges', 'label' => 'Get Charges', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_get_charge_types', 'label' => 'Get Charge Types', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_get_charge_options', 'label' => 'Get Charge Options', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_collection_sheet', 'label' => 'Collection Sheet', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_repayment', 'label' => 'Repayment', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_expected_repayment', 'label' => 'Expected Repayment', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_arrears', 'label' => 'Arrears', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_disbursement', 'label' => 'Disbursement', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_account_statement', 'label' => 'Account Statement', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_awaiting_disbursement', 'label' => 'Awaiting Disbursement', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_pending_approval', 'label' => 'Pending Approval', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_rescheduled_loans', 'label' => 'Rescheduled Loans', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_written_off_loans', 'label' => 'Written Off Loans', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_fully_paid_loans', 'label' => 'Fully Paid Loans', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_active_past_maturity_loans', 'label' => 'Active Past Maturity Loans', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_active_loans_in_last_installment', 'label' => 'Active Loans In Last Installment', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_active_loan_summary_per_branch', 'label' => 'Active Loan Summary Per Branch', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_active_loans_by_disbursal_period', 'label' => 'Active Loans By Disbursal Period', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_closed_loans', 'label' => 'Closed Loans', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_aging_detail', 'label' => 'Aging Detail', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_loans_awaiting_disbursal_summary', 'label' => 'Loans Awaiting Disbursal Summary', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_loans_awaiting_disbursal_by_month', 'label' => 'Loans Awaiting Disbursal By Month', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_active_loans_details', 'label' => 'Active Loans Details', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_active_loans_summary', 'label' => 'Active Loans Summary', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_overdue_mature_loans', 'label' => 'Overdue Mature Loans', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_loan_transactions_detailed', 'label' => 'Loan Transactions Detailed', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_loan_transactions_summary', 'label' => 'Loan Transactions Summary', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_loan_funds_movement', 'label' => 'Loan Funds Movement', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_loan_classification_by_product', 'label' => 'Loan Classification By Product', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_active_past_maturity_loans_summary', 'label' => 'Active Past Maturity Loans Summary', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_aging_summary_in_months', 'label' => 'Aging Summary In Months', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_aging_summary_in_weeks', 'label' => 'Aging Summary In Weeks', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_balance_outstanding', 'label' => 'Balance Outstanding', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_branch_expected_cash_flow', 'label' => 'Branch Expected Cash Flow', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_basic_expected_payment_by_date', 'label' => 'Basic Expected Payment By Date', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_formatted_expected_payment_by_date', 'label' => 'Formatted Expected Payment By Date', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_loan_trends_by_month_by_created', 'label' => 'Loan Trends By Month By Created', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_loan_trends_by_month_by_disbursed', 'label' => 'Loan Trends By Month By Disbursed', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_obligation_met_loans_details', 'label' => 'Obligation Met Loans Details', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_portfolio_at_risk', 'label' => 'Portfolio At Risk', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'loan_portfolio_at_risk_by_branch', 'label' => 'Portfolio At Risk By Branch', 'type' => 'page', 'source' => 'module_pages'],
];
