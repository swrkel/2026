<?php

/*
 * MA-004 - page permissions for the Bakery module.
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
 *   9 pages found in Bakery.
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
    ['key' => 'bakery_settings', 'label' => 'Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bakery_activity_log', 'label' => 'Activity Log', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bakery_fleet_test', 'label' => 'Fleet Test', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bakery_bakery_users_update_passcode', 'label' => 'Bakery Users Update Passcode', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bakery_bakery_users_ledger', 'label' => 'Bakery Users Ledger', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bakery_bakery_users_dashboard', 'label' => 'Bakery Users Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bakery_bakery_users_check_passcode', 'label' => 'Bakery Users Check Passcode', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bakery_bakery_users_check_username', 'label' => 'Bakery Users Check Username', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bakery_bakery_users_set_main_system_session', 'label' => 'Bakery Users Set Main System Session', 'type' => 'page', 'source' => 'module_pages'],
];
