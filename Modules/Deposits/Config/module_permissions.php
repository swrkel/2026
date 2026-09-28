<?php

/*
 * MA-004 - page permissions for the Deposits module.
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
 *   12 pages found in Deposits.
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
    ['key' => 'deposits_dashboard', 'label' => 'Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'deposits_products', 'label' => 'Products', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'deposits_products_create', 'label' => 'Products Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'deposits_accounts', 'label' => 'Accounts', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'deposits_accounts_create', 'label' => 'Accounts Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'deposits_transactions', 'label' => 'Transactions', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'deposits_transactions_create', 'label' => 'Transactions Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'deposits_interest', 'label' => 'Interest', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'deposits_maturity_due', 'label' => 'Maturity Due', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'deposits_reports', 'label' => 'Reports', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'deposits_settings', 'label' => 'Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'deposits_health', 'label' => 'Health', 'type' => 'page', 'source' => 'module_pages'],
];
