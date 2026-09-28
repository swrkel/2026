<?php

/*
 * MA-004 - page permissions for the DigitalWallet module.
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
 *   9 pages found in DigitalWallet.
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
    ['key' => 'digitalwallet_hierarchy', 'label' => 'Hierarchy', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'digitalwallet_transfers', 'label' => 'Transfers', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'digitalwallet_transfers_create', 'label' => 'Transfers Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'digitalwallet_approvals', 'label' => 'Approvals', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'digitalwallet_ledger', 'label' => 'Ledger', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'digitalwallet_transactions', 'label' => 'Transactions', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'digitalwallet_financial_engine', 'label' => 'Financial Engine', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'digitalwallet_reports', 'label' => 'Reports', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'digitalwallet_settings', 'label' => 'Settings', 'type' => 'page', 'source' => 'module_pages'],
];
