<?php

/*
 * MA-004 - page permissions for the Distribution module.
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
 *   31 pages found in Distribution.
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
    ['key' => 'distribution_route_user_maps', 'label' => 'Route User Maps', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distribution_route_user_maps_routes_by_sales_rep', 'label' => 'Route User Maps Routes By Sales Rep', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distribution_discounts_subcategories', 'label' => 'Discounts Subcategories', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distribution_discounts_products', 'label' => 'Discounts Products', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distribution_discounts_product_units', 'label' => 'Discounts Product Units', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distribution_invoices', 'label' => 'Invoices', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distribution_invoices_create', 'label' => 'Invoices Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distribution_invoices_products', 'label' => 'Invoices Products', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distribution_invoices_product_info', 'label' => 'Invoices Product Info', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distribution_invoices_free_issues_check', 'label' => 'Invoices Free Issues Check', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distribution_list_invoices', 'label' => 'List Invoices', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distribution_sales_orders', 'label' => 'Sales Orders', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distribution_sales_orders_create', 'label' => 'Sales Orders Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distribution_sales_orders_routes_by_user', 'label' => 'Sales Orders Routes By User', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distribution_daily_summary_sheet', 'label' => 'Daily Summary Sheet', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distribution_daily_summary_sheet_create', 'label' => 'Daily Summary Sheet Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distribution_daily_summary_sheet_bills', 'label' => 'Daily Summary Sheet Bills', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distribution_daily_summary_sheet_free_issues', 'label' => 'Daily Summary Sheet Free Issues', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distribution_daily_summary_sheet_free_issue_columns', 'label' => 'Daily Summary Sheet Free Issue Columns', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distribution_daily_summary_sheet_vehicles', 'label' => 'Daily Summary Sheet Vehicles', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distribution_daily_summary_sheet_loading_info', 'label' => 'Daily Summary Sheet Loading Info', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distribution_loadings', 'label' => 'Loadings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distribution_loadings_create', 'label' => 'Loadings Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distribution_loadings_subcategories', 'label' => 'Loadings Subcategories', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distribution_loadings_products', 'label' => 'Loadings Products', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distribution_loadings_vehicle_stock', 'label' => 'Loadings Vehicle Stock', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distribution_free_issues', 'label' => 'Free Issues', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distribution_free_issues_create', 'label' => 'Free Issues Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distribution_products', 'label' => 'Products', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distribution_vat_invoices', 'label' => 'Vat Invoices', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'distribution_vat_invoices_create', 'label' => 'Vat Invoices Create', 'type' => 'page', 'source' => 'module_pages'],
];
