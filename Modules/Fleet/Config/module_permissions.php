<?php

/*
 * MA-004 - page permissions for the Fleet module.
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
 *   10 pages found in Fleet.
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
    ['key' => 'fleet_fleet_view_fuel_details', 'label' => 'Fleet View Fuel Details', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'fleet_opening_balance', 'label' => 'Opening Balance', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'fleet_newprice', 'label' => 'Newprice', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'fleet_createincentives', 'label' => 'Createincentives', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'fleet_milage_changes', 'label' => 'Milage Changes', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'fleet_create_fleet_invoices', 'label' => 'Create Fleet Invoices', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'fleet_fleet_profit_loss', 'label' => 'Fleet Profit Loss', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'fleet_fetch_profit_loss_summary', 'label' => 'Fetch Profit Loss Summary', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'fleet_list_invoice', 'label' => 'List Invoice', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'fleet_settings_vehicle_category_create', 'label' => 'Settings Vehicle Category Create', 'type' => 'page', 'source' => 'module_pages'],
];
