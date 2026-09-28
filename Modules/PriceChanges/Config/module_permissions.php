<?php

/*
 * MA-004 - page permissions for the PriceChanges module.
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
 *   25 pages found in PriceChanges.
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
    ['key' => 'pricechanges_get_last_verified_form_f22', 'label' => 'Get Last Verified Form F22', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pricechanges_get_form_f22_list', 'label' => 'Get Form F22 List', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pricechanges_get_form_f22', 'label' => 'Get Form F22', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pricechanges_f22_stock_taking', 'label' => 'F22 Stock Taking', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pricechanges_form_set_1', 'label' => 'Form Set 1', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pricechanges_get_form_16a', 'label' => 'Get Form 16a', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pricechanges_get_previous_value_16a', 'label' => 'Get Previous Value 16a', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pricechanges_f14', 'label' => 'F14', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pricechanges_get_previous_value_9c', 'label' => 'Get Previous Value 9c', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pricechanges_get_9c_form', 'label' => 'Get 9c Form', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pricechanges_f15_9abc', 'label' => 'F15 9abc', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pricechanges_f21', 'label' => 'F21', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pricechanges_get_21_c_form_all_query', 'label' => 'Get 21 C Form All Query', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pricechanges_frm_21_query', 'label' => 'Frm 21 Query', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pricechanges_get_opening_stock_21_form', 'label' => 'Get Opening Stock 21 Form', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pricechanges_get_form_14b', 'label' => 'Get Form 14b', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pricechanges_get_form_20', 'label' => 'Get Form 20', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pricechanges_list_f17', 'label' => 'List F17', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pricechanges_list_prices_change_settings', 'label' => 'List Prices Change Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pricechanges_details', 'label' => 'Details', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pricechanges_forms_setting_formf33', 'label' => 'Forms Setting Formf33', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pricechanges_forms_setting_form21c', 'label' => 'Forms Setting Form21c', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pricechanges_forms_setting_form159abc', 'label' => 'Forms Setting Form159abc', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pricechanges_forms_setting_form16a', 'label' => 'Forms Setting Form16a', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pricechanges_forms_setting_form9c', 'label' => 'Forms Setting Form9c', 'type' => 'page', 'source' => 'module_pages'],
];
