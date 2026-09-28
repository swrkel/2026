<?php

/*
 * MA-004 - page permissions for the PumperDashboard module.
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
 *   19 pages found in PumperDashboard.
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
    ['key' => 'pumperdashboard_pump_operators_update_passcode', 'label' => 'Pump Operators Update Passcode', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pumperdashboard_pump_operators_import', 'label' => 'Pump Operators Import', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pumperdashboard_pump_operators_ledger', 'label' => 'Pump Operators Ledger', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pumperdashboard_pump_operators_setting_dash', 'label' => 'Pump Operators Setting Dash', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pumperdashboard_pump_operators_dashboard', 'label' => 'Pump Operators Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pumperdashboard_pump_operators_my_auto_dashboard', 'label' => 'Pump Operators My Auto Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pumperdashboard_pump_operators_check_passcode', 'label' => 'Pump Operators Check Passcode', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pumperdashboard_pump_operators_check_username', 'label' => 'Pump Operators Check Username', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pumperdashboard_pump_operators_set_main_system_session', 'label' => 'Pump Operators Set Main System Session', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pumperdashboard_pump_operators_unblock_pumper_login_attempts', 'label' => 'Pump Operators Unblock Pumper Login Attempts', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pumperdashboard_pump_operators_login_attempt_history', 'label' => 'Pump Operators Login Block / Unblock History', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pumperdashboard_pump_operators_pumper_day_entries_summary', 'label' => 'Pump Operators Pumper Day Entries Summary', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pumperdashboard_pump_operator_pmts_print_other_sale_preview', 'label' => 'Pump Operator Pmts Print Other Sale Preview', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pumperdashboard_pump_operator_payments_open_page', 'label' => 'Pump Operator Payments Open Page', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pumperdashboard_pump_operator_payments_meters_with_payments', 'label' => 'Pump Operator Payments Meters With Payments', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pumperdashboard_pump_operator_payments_othersale', 'label' => 'Pump Operator Payments Othersale', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pumperdashboard_pump_operator_payments_othersale_getproducts', 'label' => 'Pump Operator Payments Othersale Getproducts', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pumperdashboard_pump_operator_payments_othersale_list', 'label' => 'Pump Operator Payments Othersale List', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pumperdashboard_pump_operator_payments_othersales_list', 'label' => 'Pump Operator Payments Othersales List', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pumperdashboard_pump_operator_payments_metersale_list', 'label' => 'Pump Operator Payments Metersale List', 'type' => 'page', 'source' => 'module_pages'],
];
