<?php

/*
 * MA-004 - page permissions for the Chequer module.
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
 *   15 pages found in Chequer.
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
    ['key' => 'chequer_bank_accounts', 'label' => 'Bank Accounts', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'chequer_cheque_leaves', 'label' => 'Cheque Leaves', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'chequer_write_cheque', 'label' => 'Write Cheque', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'chequer_write_cheque_create', 'label' => 'Write Cheque Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'chequer_settings', 'label' => 'Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'chequer_print_calibration', 'label' => 'Print Calibration', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'chequer_print_history', 'label' => 'Print History', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'chequer_default_settings', 'label' => 'Default Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'chequer_cheque_numbers', 'label' => 'Cheque Numbers', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'chequer_cheque_number_entries', 'label' => 'Cheque Number Entries', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'chequer_payees', 'label' => 'Payees', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'chequer_stamps', 'label' => 'Stamps', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'chequer_cancelled_cheques', 'label' => 'Cancelled Cheques', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'chequer_deleted_cheques', 'label' => 'Deleted Cheques', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'chequer_chequer', 'label' => 'Chequer', 'type' => 'page', 'source' => 'module_pages'],
];
