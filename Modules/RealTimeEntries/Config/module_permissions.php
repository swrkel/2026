<?php

/*
 * MA-004 - page permissions for the RealTimeEntries module.
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
 *   5 pages found in RealTimeEntries.
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
    ['key' => 'realtimeentries_other_sales', 'label' => 'Other Sales', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'realtimeentries_list_other_sales', 'label' => 'List Other Sales', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'realtimeentries_meters_with_payments', 'label' => 'Meters With Payments', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'realtimeentries_payment_summary', 'label' => 'Payment Summary', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'realtimeentries_real_time_settings', 'label' => 'Real Time Settings', 'type' => 'page', 'source' => 'module_pages'],
];
