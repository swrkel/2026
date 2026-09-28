<?php

/*
 * MA-004 - page permissions for the CustomerStatements module.
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
 *   11 pages found in CustomerStatements.
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
    ['key' => 'customerstatements_dashboard', 'label' => 'Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customerstatements_create', 'label' => 'Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customerstatements_list', 'label' => 'List', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customerstatements_statement_settings', 'label' => 'Statement Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customerstatements_settings', 'label' => 'Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customerstatements_payments', 'label' => 'Payments', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customerstatements_font_settings', 'label' => 'Font Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customerstatements_print_formats', 'label' => 'Print Formats', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customerstatements_alert_settings', 'label' => 'Alert Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customerstatements_numbering_settings', 'label' => 'Numbering Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customerstatements_numbering_settings_next', 'label' => 'Numbering Settings Next', 'type' => 'page', 'source' => 'module_pages'],
];
