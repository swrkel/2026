<?php

/*
 * MA-004 - page permissions for the Suppliers module.
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
 *   32 pages found in Suppliers.
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
    ['key' => 'suppliers_lookup_suppliers', 'label' => 'Lookup Suppliers', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'suppliers_lookup_products', 'label' => 'Lookup Products', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'suppliers_dashboard', 'label' => 'Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'suppliers_settings', 'label' => 'Supplier Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'suppliers_payment_reference_settings', 'label' => 'Payment Reference Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'suppliers_list_suppliers', 'label' => 'List Suppliers', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'suppliers_all_suppliers', 'label' => 'All Suppliers', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'suppliers_add_supplier', 'label' => 'Add Supplier', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'suppliers_supplier_payments', 'label' => 'Supplier Payments', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'suppliers_supplier_product_mapping', 'label' => 'Supplier Product Mapping', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'suppliers_supplier_stock_report', 'label' => 'Supplier Stock Report', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'suppliers_import_suppliers', 'label' => 'Import Suppliers', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'suppliers_issues_payment_details', 'label' => 'Issues Payment Details', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'suppliers_reports', 'label' => 'Reports', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'suppliers_list_supplier', 'label' => 'List Supplier', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'suppliers_list', 'label' => 'List', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'suppliers_add', 'label' => 'Add', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'suppliers_payments', 'label' => 'Payments', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'suppliers_product_mapping', 'label' => 'Product Mapping', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'suppliers_add_supplier_map_products', 'label' => 'Add Supplier Map Products', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'suppliers_list_supplier_map_products', 'label' => 'List Supplier Map Products', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'suppliers_stock_report', 'label' => 'Stock Report', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'suppliers_import', 'label' => 'Import', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'suppliers_report', 'label' => 'Report', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'suppliers_issued_payment_details', 'label' => 'Issued Payment Details', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'suppliers_contact_user_activity', 'label' => 'Contact User Activity', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'suppliers_create', 'label' => 'Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'suppliers_payments_list', 'label' => 'Payments List', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'suppliers_product_mappings_list', 'label' => 'Product Mappings List', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'suppliers_imports_contacts', 'label' => 'Imports Contacts', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'suppliers_imports_opening_balance', 'label' => 'Imports Opening Balance', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'suppliers_issue_payment_details', 'label' => 'Issue Payment Details', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'suppliers_user_activity', 'label' => 'User Activity', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'suppliers_reports_index', 'label' => 'Reports Index', 'type' => 'page', 'source' => 'module_pages'],
];
