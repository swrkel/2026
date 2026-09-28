<?php

/*
 * MA-004 - page permissions for the SettlementSW module.
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
 *   9 pages found in SettlementSW.
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
    ['key' => 'settlementsw_reports_settlements', 'label' => 'Reports Settlements', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'settlementsw_create', 'label' => 'Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'settlementsw_pump_operator_other_sales_list', 'label' => 'Pump Operator Other Sales List', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'settlementsw_pump_operator_meter_sales_list', 'label' => 'Pump Operator Meter Sales List', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'settlementsw_check_slip_no', 'label' => 'Check Slip No', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'settlementsw_check_prev_settlement', 'label' => 'Check Prev Settlement', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'settlementsw_sw_add_payment_create', 'label' => 'Sw Add Payment Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'settlementsw_sw_add_payment_check_order_number', 'label' => 'Sw Add Payment Check Order Number', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'settlementsw_sw_add_payment_product_price', 'label' => 'Sw Add Payment Product Price', 'type' => 'page', 'source' => 'module_pages'],
];
