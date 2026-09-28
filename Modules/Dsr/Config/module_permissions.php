<?php

/*
 * MA-004 - page permissions for the Dsr module.
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
 *   12 pages found in Dsr.
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
    ['key' => 'dsr_settings', 'label' => 'Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'dsr_report', 'label' => 'Report', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'dsr_locations', 'label' => 'Locations', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'dsr_add_country', 'label' => 'Add Country', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'dsr_countries', 'label' => 'Countries', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'dsr_provinces', 'label' => 'Provinces', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'dsr_add_province', 'label' => 'Add Province', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'dsr_districts', 'label' => 'Districts', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'dsr_add_district', 'label' => 'Add District', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'dsr_areas', 'label' => 'Areas', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'dsr_add_areas', 'label' => 'Add Areas', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'dsr_list_accumulative_sale_purchase', 'label' => 'List Accumulative Sale Purchase', 'type' => 'page', 'source' => 'module_pages'],
];
