<?php

/*
 * MA-004 - page permissions for the PetroPD module.
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
 *   55 pages found in PetroPD.
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
    ['key' => 'petropd_day_end_settlements', 'label' => 'Day End Settlements', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_day_end_settlements_create', 'label' => 'Day End Settlements Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_day_end_settlement_pumps', 'label' => 'Day End Settlement Pumps', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_day_end_settlement_pos_totals', 'label' => 'Day End Settlement Pos Totals', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_pd_settlement', 'label' => 'Pd Settlement', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_add_payment_create', 'label' => 'Add Payment Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_settlement_check_slip_no', 'label' => 'Settlement Check Slip No', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_pd_operators', 'label' => 'Pd Operators', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_list_assigned_operators', 'label' => 'List Assigned Operators', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_pd_operators_blocked_login_attempts', 'label' => 'Pd Operators Blocked Login Attempts', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_pd_operators_login_attempt_history', 'label' => 'Pd Operators Login Block / Unblock History', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_sms_notifications', 'label' => 'Sms Notifications', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_user_activity_report', 'label' => 'User Activity Report', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_adjusted_amounts_report', 'label' => 'Adjusted Amounts Report', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_payment_reconciliation_report', 'label' => 'Payment Reconciliation Report', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_list_pd_settlement', 'label' => 'List Pd Settlement', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_pd_operators_dashboard_settings', 'label' => 'Pd Operators Dashboard Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_pump_assignments_create', 'label' => 'Pump Assignments Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_pump_assignments_operators_for_select', 'label' => 'Pump Assignments Operators For Select', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_pd_operators_receive_pump', 'label' => 'Pd Operators Receive Pump', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_pd_operators_create', 'label' => 'Pd Operators Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_pd_operators_import', 'label' => 'Pd Operators Import', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_pd_operators_check_username', 'label' => 'Pd Operators Check Username', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_pd_operators_check_passcode', 'label' => 'Pd Operators Check Passcode', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_pd_operators_update_passcode', 'label' => 'Pd Operators Update Passcode', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_pd_operators_dashboard', 'label' => 'Pd Operators Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_pd_operators_my_auto_dashboard', 'label' => 'Pd Operators My Auto Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_pd_operators_ledger', 'label' => 'Pd Operators Ledger', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_day_entries', 'label' => 'Day Entries', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_day_entry_filter_options', 'label' => 'Day Entry Filter Options', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_day_entry_summary', 'label' => 'Day Entry Summary', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_pump_operators_shift_summary', 'label' => 'Pump Operators Shift Summary', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_pump_operator_payments_totals', 'label' => 'Pump Operator Payments Totals', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_pump_operator_payments', 'label' => 'Pump Operator Payments', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_pump_operator_payments_create', 'label' => 'Pump Operator Payments Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_pump_operator_pmts_other_sales', 'label' => 'Pump Operator Pmts Other Sales', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_pump_operator_pmts_other_sales_list', 'label' => 'Pump Operator Pmts Other Sales List', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_pump_operator_pmts_meter_sales_list', 'label' => 'Pump Operator Pmts Meter Sales List', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_pump_operator_payments_othersales_list', 'label' => 'Pump Operator Payments Othersales List', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_pump_operator_pmts_other_sale_products', 'label' => 'Pump Operator Pmts Other Sale Products', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_pump_operators_current_meter', 'label' => 'Pump Operators Current Meter', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_pump_operators_current_meter_create', 'label' => 'Pump Operators Current Meter Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_pump_operators_closing_shift', 'label' => 'Pump Operators Closing Shift', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_pump_operators_closing_shift_filter_options', 'label' => 'Pump Operators Closing Shift Filter Options', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_pump_operators_closing_shift_summary', 'label' => 'Pump Operators Closing Shift Summary', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_pd_operators_close_shift_summary', 'label' => 'Pd Operators Close Shift Summary', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_pump_operators_unload_stock', 'label' => 'Pump Operators Unload Stock', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_pump_operators_unload_stock_create', 'label' => 'Pump Operators Unload Stock Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_pump_operators_unload_stock_details', 'label' => 'Pump Operators Unload Stock Details', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_pump_operators_excess_shortage_payments', 'label' => 'Pump Operators Excess Shortage Payments', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_pump_operators_daily_collection', 'label' => 'Pump Operators Daily Collection', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_pump_operators_meters_with_payments', 'label' => 'Pump Operators Meters With Payments', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_excess_comission_create', 'label' => 'Excess Comission Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_recover_shortage_create', 'label' => 'Recover Shortage Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_settlement_pd_create', 'label' => 'Settlement Pd Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_settlement_pd_check_previous_settlement', 'label' => 'Settlement Pd Check Previous Settlement', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'petropd_pumper_dashboard_close_shift_summary', 'label' => 'Pumper Dashboard Close Shift Summary', 'type' => 'page', 'source' => 'module_pages'],
];
