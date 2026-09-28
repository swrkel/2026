<?php

/*
 * MA-004 - page permissions for the Leasing module.
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
 *   17 pages found in Leasing.
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
    ['key' => 'leasing_dashboard', 'label' => 'Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leasing_collateral_types', 'label' => 'Collateral Types', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leasing_collateral_types_create', 'label' => 'Collateral Types Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leasing_products', 'label' => 'Products', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leasing_products_create', 'label' => 'Products Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leasing_lease_assets', 'label' => 'Lease Assets', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leasing_lease_assets_create', 'label' => 'Lease Assets Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leasing_lease_contracts', 'label' => 'Lease Contracts', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leasing_lease_contracts_create', 'label' => 'Lease Contracts Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leasing_applications', 'label' => 'Applications', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leasing_payments', 'label' => 'Payments', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leasing_restructures', 'label' => 'Restructures', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leasing_insurance', 'label' => 'Insurance', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leasing_asset', 'label' => 'Asset', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leasing_reports', 'label' => 'Reports', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leasing_settings', 'label' => 'Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leasing_health', 'label' => 'Health', 'type' => 'page', 'source' => 'module_pages'],
];
