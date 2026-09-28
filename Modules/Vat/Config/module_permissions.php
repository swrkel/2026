<?php

/*
 * MA-004 - page permissions for the Vat module.
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
 *   31 pages found in Vat.
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
    ['key' => 'vat_update_statement_nos', 'label' => 'Update Statement Nos', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'vat_customer_date', 'label' => 'Customer Date', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'vat_vat_invoice_products_sold', 'label' => 'Vat Invoice Products Sold', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'vat_invoices_127', 'label' => 'Invoices 127', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'vat_invoices_127_create', 'label' => 'Invoices 127 Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'vat_statement_126', 'label' => 'Statement 126', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'vat_statement_126_create', 'label' => 'Statement 126 Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'vat_statement_126_products_sold', 'label' => 'Statement 126 Products Sold', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'vat_statement_126_invoices_setting', 'label' => 'Statement 126 Invoices Setting', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'vat_statement_126_assign_prefix', 'label' => 'Statement 126 Assign Prefix', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'vat_quick_add_customer', 'label' => 'Quick Add Customer', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'vat_quick_add_reference', 'label' => 'Quick Add Reference', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'vat_vat_invoice2_products_sold', 'label' => 'Vat Invoice2 Products Sold', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'vat_vat_invoice2_logo_upload', 'label' => 'Vat Invoice2 Logo Upload', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'vat_vat_invoice2_invoices_setting', 'label' => 'Vat Invoice2 Invoices Setting', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'vat_vat_statement_setting', 'label' => 'Vat Statement Setting', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'vat_vat_invoice2_vat_invoice_to_transactions', 'label' => 'Vat Invoice2 Vat Invoice To Transactions', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'vat_vat_invoice2_vat_invoice_to_transactions_history', 'label' => 'Vat Invoice2 Vat Invoice To Transactions History', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'vat_import_products', 'label' => 'Import Products', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'vat_import_contacts', 'label' => 'Import Contacts', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'vat_vat_product_search', 'label' => 'Vat Product Search', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'vat_vat_expense_prefix_settings', 'label' => 'Vat Expense Prefix Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'vat_vat_expense_prefix_settings_edit', 'label' => 'Vat Expense Prefix Settings Edit', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'vat_reports_get_ledger', 'label' => 'Reports Get Ledger', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'vat_customer_vat_schedule', 'label' => 'Customer Vat Schedule', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'vat_supplier_vat_schedule', 'label' => 'Supplier Vat Schedule', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'vat_update_single_vats', 'label' => 'Update Single Vats', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'vat_reports_summary', 'label' => 'Reports Summary', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'vat_reports', 'label' => 'Reports', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'vat_settlement_update_credit_sales', 'label' => 'Settlement Update Credit Sales', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'vat_settlement_payment', 'label' => 'Settlement Payment', 'type' => 'page', 'source' => 'module_pages'],
];
