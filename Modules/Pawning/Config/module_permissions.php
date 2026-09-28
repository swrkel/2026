<?php

/*
 * MA-004 - page permissions for the Pawning module.
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
 *   17 pages found in Pawning.
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
    ['key' => 'pawning_dashboard', 'label' => 'Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pawning_collateral_types', 'label' => 'Collateral Types', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pawning_collateral_types_create', 'label' => 'Collateral Types Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pawning_products', 'label' => 'Products', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pawning_products_create', 'label' => 'Products Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pawning_articles', 'label' => 'Articles', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pawning_articles_create', 'label' => 'Articles Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pawning_pledges', 'label' => 'Pledges', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pawning_pledges_create', 'label' => 'Pledges Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pawning_valuations', 'label' => 'Valuations', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pawning_redemptions', 'label' => 'Redemptions', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pawning_renewals', 'label' => 'Renewals', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pawning_auction', 'label' => 'Auction', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pawning_vault', 'label' => 'Vault', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pawning_reports', 'label' => 'Reports', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pawning_settings', 'label' => 'Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pawning_health', 'label' => 'Health', 'type' => 'page', 'source' => 'module_pages'],
];
