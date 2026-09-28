<?php

/*
 * MA-004 - page permissions for the PetroGeneral module.
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
 *   63 pages found in PetroGeneral.
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
    ['key' => 'petrogeneral_settings', 'label' => 'Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_tank_transfers_general', 'label' => 'Tank Transfers General', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_tank_transfers_general_create', 'label' => 'Tank Transfers General Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_user_activity_general', 'label' => 'User Activity General', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_sms_notifications_general', 'label' => 'Sms Notifications General', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_sms_notifications_general_create', 'label' => 'Sms Notifications General Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_sms_notifications_general_sms_templates', 'label' => 'Sms Notifications General Sms Templates', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_sms_notifications_general_whatsapp_templates', 'label' => 'Sms Notifications General Whatsapp Templates', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_petro_dashboard_new', 'label' => 'Petro Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_dashboard', 'label' => 'Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_daily_status_general', 'label' => 'Daily Status Report', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_print_report', 'label' => 'Print Report', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_dip_management_general', 'label' => 'Dip Management General', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_add_dip_chart', 'label' => 'Add Dip Chart', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_add_new_dip', 'label' => 'Add New Dip', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_add_resetting_dip', 'label' => 'Add Resetting Dip', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_pump_management', 'label' => 'Pump Management', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_pump_management_create', 'label' => 'Pump Management Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_pumps_import', 'label' => 'Pumps Import', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_tank_management', 'label' => 'Tank Management', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_tank_management_create', 'label' => 'Tank Management Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_tank_import', 'label' => 'Tank Import', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_pumper_management', 'label' => 'Pumper Management', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_pump_operators_update_passcode', 'label' => 'Pump Operators Update Passcode', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_pump_operators_ledger', 'label' => 'Pump Operators Ledger', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_pump_operators_setting_dash', 'label' => 'Pump Operators Setting Dash', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_pump_operators_dashboard', 'label' => 'Pump Operators Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_pump_operators_check_passcode', 'label' => 'Pump Operators Check Passcode', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_pump_operators_check_username', 'label' => 'Pump Operators Check Username', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_pump_operators_unblock_pumper_login_attempts', 'label' => 'Pump Operators Unblock Pumper Login Attempts', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_pump_operators_login_attempt_history', 'label' => 'Pump Operators Login Block / Unblock History', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_adjust_dates', 'label' => 'Adjust Dates', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_settlement_sw', 'label' => 'Settlement Sw', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_settlement_sw_create', 'label' => 'Settlement Sw Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_day_end_settlement', 'label' => 'Day End – Settlement', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_day_end_settlement_pumps', 'label' => 'Day End Settlement Pumps', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_day_end_settlement_pos_totals', 'label' => 'Day End Settlement Pos Totals', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_tanks_transaction_summary', 'label' => 'Tanks Transaction Summary', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_pump_operators_import', 'label' => 'Pump Operators Import', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_day_entries', 'label' => 'Day Entries', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_day_entry_summary', 'label' => 'Day Entry Summary', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_pump_operators_set_main_system_session', 'label' => 'Pump Operators Set Main System Session', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_dailycollection_summary', 'label' => 'Dailycollection Summary', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_dailycollection_shortage_excess', 'label' => 'Dailycollection Shortage Excess', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_dailycollection_cheques', 'label' => 'Dailycollection Cheques', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_dailycollection_others', 'label' => 'Dailycollection Others', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_pump_operator_payments_meters_with_payments', 'label' => 'Pump Operator Payments Meters With Payments', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_pump_operator_payments_othersale', 'label' => 'Pump Operator Payments Othersale', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_pump_operator_payments_othersale_getproducts', 'label' => 'Pump Operator Payments Othersale Getproducts', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_pump_operator_payments_othersale_list', 'label' => 'Pump Operator Payments Othersale List', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_pump_operator_payments_othersales_list', 'label' => 'Pump Operator Payments Othersales List', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_pump_operator_payments_metersale_list', 'label' => 'Pump Operator Payments Metersale List', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_settlement_activity_report', 'label' => 'Settlement Activity Report', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_settlement_get_pumps_by_location', 'label' => 'Settlement Get Pumps By Location', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_settlement_update_credit_sales', 'label' => 'Settlement Update Credit Sales', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_settlement_check_prev_settlement', 'label' => 'Settlement Check Prev Settlement', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_settlement_check_slip_no', 'label' => 'Settlement Check Slip No', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_pdsettlement_pd_check_prev_settlement', 'label' => 'Pdsettlement Pd Check Prev Settlement', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_settlement_payment_check_order_number', 'label' => 'Settlement Payment Check Order Number', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_settlement_payment', 'label' => 'Settlement Payment', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_vehicles', 'label' => 'Vehicles', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_vehicle', 'label' => 'Vehicle', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_daily_cash_status', 'label' => 'Daily Cash Status', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_daily_cash_status_data', 'label' => 'Daily Cash Status Data', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petrogeneral_daily_cash_status_data_by_date', 'label' => 'Daily Cash Status Data By Date', 'type' => 'page', 'source' => 'module_pages'],
];
