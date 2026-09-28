<?php

/*
 * MA-004 - page permissions for the Petro module.
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
 *   48 pages found in Petro.
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
    ['key' => 'petro_adjust_dates', 'label' => 'Adjust Dates', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_settlement_sw', 'label' => 'Settlement Sw', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_settlement_sw_create', 'label' => 'Settlement Sw Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_products_list', 'label' => 'Products List', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_dashboard', 'label' => 'Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_tank_import', 'label' => 'Tank Import', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_day_end_settlement_pumps', 'label' => 'Day End Settlement Pumps', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_day_end_settlement_pos_totals', 'label' => 'Day End Settlement Pos Totals', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_tanks_transaction_summary', 'label' => 'Tanks Transaction Summary', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_pumps_import', 'label' => 'Pumps Import', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_pump_operators_update_passcode', 'label' => 'Pump Operators Update Passcode', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_pump_operators_import', 'label' => 'Pump Operators Import', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_pump_operators_ledger', 'label' => 'Pump Operators Ledger', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_day_entries', 'label' => 'Day Entries', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_day_entry_summary', 'label' => 'Day Entry Summary', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_pump_operators_setting_dash', 'label' => 'Pump Operators Setting Dash', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_pump_operators_dashboard', 'label' => 'Pump Operators Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_pump_operators_check_passcode', 'label' => 'Pump Operators Check Passcode', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_pump_operators_check_username', 'label' => 'Pump Operators Check Username', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_pump_operators_set_main_system_session', 'label' => 'Pump Operators Set Main System Session', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_pump_operators_unblock_pumper_login_attempts', 'label' => 'Pump Operators Unblock Pumper Login Attempts', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_dailycollection_summary', 'label' => 'Dailycollection Summary', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_dailycollection_shortage_excess', 'label' => 'Dailycollection Shortage Excess', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_dailycollection_cheques', 'label' => 'Dailycollection Cheques', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_dailycollection_others', 'label' => 'Dailycollection Others', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_pump_operator_payments_meters_with_payments', 'label' => 'Pump Operator Payments Meters With Payments', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_pump_operator_payments_othersale', 'label' => 'Pump Operator Payments Othersale', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_pump_operator_payments_othersale_getproducts', 'label' => 'Pump Operator Payments Othersale Getproducts', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_pump_operator_payments_othersale_list', 'label' => 'Pump Operator Payments Othersale List', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_pump_operator_payments_othersales_list', 'label' => 'Pump Operator Payments Othersales List', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_pump_operator_payments_metersale_list', 'label' => 'Pump Operator Payments Metersale List', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_print_report', 'label' => 'Print Report', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_settlement_activity_report', 'label' => 'Settlement Activity Report', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_settlement_get_pumps_by_location', 'label' => 'Settlement Get Pumps By Location', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_settlement_update_credit_sales', 'label' => 'Settlement Update Credit Sales', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_settlement_check_prev_settlement', 'label' => 'Settlement Check Prev Settlement', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_settlement_check_slip_no', 'label' => 'Settlement Check Slip No', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_pdsettlement_pd_check_prev_settlement', 'label' => 'Pdsettlement Pd Check Prev Settlement', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_settlement_payment_check_order_number', 'label' => 'Settlement Payment Check Order Number', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_settlement_payment', 'label' => 'Settlement Payment', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_add_resetting_dip', 'label' => 'Add Resetting Dip', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_add_new_dip', 'label' => 'Add New Dip', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_add_dip_chart', 'label' => 'Add Dip Chart', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_vehicles', 'label' => 'Vehicles', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_vehicle', 'label' => 'Vehicle', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_daily_cash_status', 'label' => 'Daily Cash Status', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_daily_cash_status_data', 'label' => 'Daily Cash Status Data', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petro_daily_cash_status_data_by_date', 'label' => 'Daily Cash Status Data By Date', 'type' => 'page', 'source' => 'module_pages'],
];
