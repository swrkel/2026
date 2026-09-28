<?php

/*
 * MA-004 - page permissions for the PetroDirect module.
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
 *   26 pages found in PetroDirect.
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
    ['key' => 'petrodirect_dashboard', 'label' => 'Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrodirect_tank_import', 'label' => 'Tank Import', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrodirect_pumps_import', 'label' => 'Pumps Import', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrodirect_add_resetting_dip', 'label' => 'Add Resetting Dip', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrodirect_add_new_dip', 'label' => 'Add New Dip', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrodirect_add_dip_chart', 'label' => 'Add Dip Chart', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrodirect_settlement_check_slip_no', 'label' => 'Settlement Check Slip No', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrodirect_settlement_get_pumps_by_location', 'label' => 'Settlement Get Pumps By Location', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrodirect_settlement_check_prev_settlement', 'label' => 'Settlement Check Prev Settlement', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrodirect_add_payment_create', 'label' => 'Add Payment Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrodirect_daily_shift_operators', 'label' => 'Daily Shift Operators', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrodirect_daily_shift_open_shifts', 'label' => 'Daily Shift Open Shifts', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrodirect_daily_collection_settings_data', 'label' => 'Daily Collection Settings Data', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrodirect_daily_collection_summary', 'label' => 'Daily Collection Summary', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrodirect_daily_collection_shortage_excess', 'label' => 'Daily Collection Shortage Excess', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrodirect_daily_collection_cheques', 'label' => 'Daily Collection Cheques', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrodirect_daily_collection_other', 'label' => 'Daily Collection Other', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrodirect_daily_collection_cash_status', 'label' => 'Daily Collection Cash Status', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrodirect_daily_collection_cash_status_data', 'label' => 'Daily Collection Cash Status Data', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrodirect_daily_card', 'label' => 'Daily Card', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrodirect_daily_card_create', 'label' => 'Daily Card Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrodirect_daily_voucher_create', 'label' => 'Daily Voucher Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrodirect_pump_operators_payment_summary_modal', 'label' => 'Pump Operators Payment Summary Modal', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrodirect_pump_operators_payment_modal', 'label' => 'Pump Operators Payment Modal', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrodirect_pump_operators_meters_with_payments', 'label' => 'Pump Operators Meters With Payments', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrodirect_reports', 'label' => 'Reports', 'type' => 'page', 'source' => 'module_pages'],
];
