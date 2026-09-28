<?php

/*
 * MA-004 - page permissions for the DailyCollectionSW module.
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
 *   15 pages found in DailyCollectionSW.
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
    ['key' => 'dailycollectionsw_daily_collection_sw_daily_shift_options', 'label' => 'Daily Collection Sw Daily Shift Options', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'dailycollectionsw_daily_collection_sw_product_price', 'label' => 'Daily Collection Sw Product Price', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'dailycollectionsw_daily_collection_sw_summary', 'label' => 'Daily Collection Sw Summary', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'dailycollectionsw_daily_collection_sw_shortage_excess', 'label' => 'Daily Collection Sw Shortage Excess', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'dailycollectionsw_daily_collection_sw_cheques', 'label' => 'Daily Collection Sw Cheques', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'dailycollectionsw_daily_collection_sw_others', 'label' => 'Daily Collection Sw Others', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'dailycollectionsw_check_slip_no', 'label' => 'Check Slip No', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'dailycollectionsw_daily_shifts_open', 'label' => 'Daily Shifts Open', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'dailycollectionsw_settings', 'label' => 'Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'dailycollectionsw_daily_cash_status', 'label' => 'Daily Cash Status', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'dailycollectionsw_daily_cash_status_data', 'label' => 'Daily Cash Status Data', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'dailycollectionsw_daily_cash_status_data_by_date', 'label' => 'Daily Cash Status Data By Date', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'dailycollectionsw_daily_cash_status_sw', 'label' => 'Daily Cash Status Sw', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'dailycollectionsw_daily_cash_status_data_sw', 'label' => 'Daily Cash Status Data Sw', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'dailycollectionsw_daily_cash_status_data_by_date_sw', 'label' => 'Daily Cash Status Data By Date Sw', 'type' => 'page', 'source' => 'module_pages'],
];
