<?php

/*
 * MA-004 - page permissions for the Finance module.
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
 *   48 pages found in Finance.
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
    ['key' => 'finance_bank_reconciliation', 'label' => 'Bank Reconciliation', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_settings', 'label' => 'Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_settings_default_date_range', 'label' => 'Settings Default Date Range', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_default_accounts_create', 'label' => 'Default Accounts Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_finance_reports_trial_balance_rewrite_data', 'label' => 'Finance Reports Trial Balance Rewrite Data', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_trial_balance', 'label' => 'Trial Balance', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_balance_sheet', 'label' => 'Balance Sheet', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_balance_sheet_comparison', 'label' => 'Balance Sheet Comparison', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_profit_loss', 'label' => 'Profit Loss', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_profit_loss_consolidated', 'label' => 'Profit Loss Consolidated', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_income_statement', 'label' => 'Income Statement', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_income_statement_consolidated', 'label' => 'Income Statement Consolidated', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_general_ledger', 'label' => 'General Ledger', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_fixed_asset_filter_options', 'label' => 'Fixed Asset Filter Options', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_list_deposit_transfer', 'label' => 'List Deposit Transfer', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_expenses_create', 'label' => 'Expenses Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_expense_categories_create', 'label' => 'Expense Categories Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_check_insufficient_balance_for_accounts', 'label' => 'Check Insufficient Balance For Accounts', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_account_image_modal', 'label' => 'Account Image Modal', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_account_fix_sales_accounts', 'label' => 'Account Fix Sales Accounts', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_account_correct_sale_income_accounts_tax', 'label' => 'Account Correct Sale Income Accounts Tax', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_correct_sell_lines_tax', 'label' => 'Correct Sell Lines Tax', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_update_sell_lines_tax', 'label' => 'Update Sell Lines Tax', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_correct_sell_lines_decimal_difference', 'label' => 'Correct Sell Lines Decimal Difference', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_update_sell_lines_decimal_difference', 'label' => 'Update Sell Lines Decimal Difference', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_account_correct_cogs_accounts_tax', 'label' => 'Account Correct Cogs Accounts Tax', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_account_update_accounts_receivable_settlement_customer_payment_to_credit', 'label' => 'Account Update Accounts Receivable Settlement Customer Payment To Credit', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_account_update_finished_goods_account_pos_sale_tax', 'label' => 'Account Update Finished Goods Account Pos Sale Tax', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_account_update_cash_account_pos_sale_tax', 'label' => 'Account Update Cash Account Pos Sale Tax', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_account_correct_accounts_discount', 'label' => 'Account Correct Accounts Discount', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_account_types_create', 'label' => 'Account Types Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_account_groups_create', 'label' => 'Account Groups Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_account_list_deposit_transfer', 'label' => 'Account List Deposit Transfer', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_account_book_contact_options', 'label' => 'Account Book Contact Options', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_account_cheque_opening_filter_options', 'label' => 'Account Cheque Opening Filter Options', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_finance_cheque_list', 'label' => 'Finance Cheque List', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_finance_cheque_deposit', 'label' => 'Finance Cheque Deposit', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_cheque_list', 'label' => 'Cheque List', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_cheque_deposit', 'label' => 'Cheque Deposit', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_realize_cheque_deposit', 'label' => 'Realize Cheque Deposit', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_realize_cheque_list', 'label' => 'Realize Cheque List', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_disabled_account', 'label' => 'Disabled Account', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_check_account_number', 'label' => 'Check Account Number', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_cash_flow', 'label' => 'Cash Flow', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_import', 'label' => 'Import', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_cheques_ob_details', 'label' => 'Cheques Ob Details', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_journals_get_row', 'label' => 'Journals Get Row', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_journal', 'label' => 'Journal', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'finance_journal_create', 'label' => 'Journal Create', 'type' => 'page', 'source' => 'module_pages'],
];
