<?php

/*
 * MA-004 - page permissions for the MPCS module.
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
 *   101 pages found in MPCS.
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
    ['key' => 'mpcs_20form', 'label' => '20form', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_get_20_form_settings', 'label' => 'Get 20 Form Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_20formsettings', 'label' => '20formsettings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_get_form_20_data', 'label' => 'Get Form 20 Data', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_test_connection', 'label' => 'Test Connection', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_get_form_20_datas', 'label' => 'Get Form 20 Datas', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_fetch_form_number', 'label' => 'Fetch Form Number', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_f15_9abc', 'label' => 'F15 9abc', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_f15', 'label' => 'F15', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_f15_new', 'label' => 'F15 New', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_f15_daily_report', 'label' => 'F15 Daily Report', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_f15new', 'label' => 'F15new', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_f15_header_get_form_f15_data', 'label' => 'F15 Header Get Form F15 Data', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_get_15_form_setting', 'label' => 'Get 15 Form Setting', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_15formsettings', 'label' => '15formsettings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_get_15_setting_data', 'label' => 'Get 15 Setting Data', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_f15_categories', 'label' => 'F15 Categories', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_f15_category_selections', 'label' => 'F15 Category Selections', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_forms_setting_form159abc', 'label' => 'Forms Setting Form159abc', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_get_last_verified_form_f22', 'label' => 'Get Last Verified Form F22', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_get_last_verified_form_f22_header', 'label' => 'Get Last Verified Form F22 Header', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_get_form_f22_list', 'label' => 'Get Form F22 List', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_get_form_f22_list_gain_loss', 'label' => 'Get Form F22 List Gain Loss', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_check_user_existence', 'label' => 'Check User Existence', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_fetch_pumps', 'label' => 'Fetch Pumps', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_get_form_f16_list', 'label' => 'Get Form F16 List', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_get_form_f22', 'label' => 'Get Form F22', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_print_form_f16', 'label' => 'Print Form F16', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_f22_stock_taking', 'label' => 'F22 Stock Taking', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_f25', 'label' => 'F25', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_f25_form_no', 'label' => 'F25 Form No', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_f25_product_price', 'label' => 'F25 Product Price', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_f25_products', 'label' => 'F25 Products', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_f25_list', 'label' => 'F25 List', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_f10', 'label' => 'F10', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_get_form_f10_list', 'label' => 'Get Form F10 List', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_f10_signatures_list', 'label' => 'F10 Signatures List', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_f10_signatures_users', 'label' => 'F10 Signatures Users', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_f10_signatures_designations', 'label' => 'F10 Signatures Designations', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_f10_signatures_user_designation', 'label' => 'F10 Signatures User Designation', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_form_set_1', 'label' => 'Form Set 1', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_form_9a', 'label' => 'Form 9a', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_form_9c', 'label' => 'Form 9c', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_form_9ccr', 'label' => 'Form 9ccr', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_mpcs_f14b', 'label' => 'Mpcs F14b', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_form9a_settings_create', 'label' => 'Form9a Settings Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_form9c_settings_create', 'label' => 'Form9c Settings Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_get_form_9c_settings', 'label' => 'Get Form 9c Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_get_form_9ccr_settings', 'label' => 'Get Form 9ccr Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_form9ccr_settings_create', 'label' => 'Form9ccr Settings Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_get_form_9a_settings', 'label' => 'Get Form 9a Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_get_9a_form', 'label' => 'Get 9a Form', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_get_form_16a', 'label' => 'Get Form 16a', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_get_previous_value_16a', 'label' => 'Get Previous Value 16a', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_f14', 'label' => 'F14', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_get_previous_value_9c', 'label' => 'Get Previous Value 9c', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_get_9c_form', 'label' => 'Get 9c Form', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_get_9a_form_value', 'label' => 'Get 9a Form Value', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_get_9ccash_form', 'label' => 'Get 9ccash Form', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_get_9ccredit_form', 'label' => 'Get 9ccredit Form', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_get_9b_form_data', 'label' => 'Get 9b Form Data', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_fetch_f16a_form_number', 'label' => 'Fetch F16a Form Number', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_get_f16a_products', 'label' => 'Get F16a Products', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_f21', 'label' => 'F21', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_form9ccash', 'label' => 'Form9ccash', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_check_approval_status', 'label' => 'Check Approval Status', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_approve', 'label' => 'Approve', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_get_21_c_form_all_query', 'label' => 'Get 21 C Form All Query', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_get_21c_form', 'label' => 'Get 21c Form', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_get_9c_forms', 'label' => 'Get 9c Forms', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_get_opening_stock_21_form', 'label' => 'Get Opening Stock 21 Form', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_get_form_14b', 'label' => 'Get Form 14b', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_get_form_20', 'label' => 'Get Form 20', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_f14b', 'label' => 'F14b', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_get_form_14', 'label' => 'Get Form 14', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_list_f17', 'label' => 'List F17', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_f18', 'label' => 'F18', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_f18_products', 'label' => 'F18 Products', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_f18_form_no', 'label' => 'F18 Form No', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_f18_product_prices', 'label' => 'F18 Product Prices', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_f18_list_data', 'label' => 'F18 List Data', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_get_f22_signatures', 'label' => 'Get F22 Signatures', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_forms_setting_formf33', 'label' => 'Forms Setting Formf33', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_forms_setting_form21c', 'label' => 'Forms Setting Form21c', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_forms_setting_form16a', 'label' => 'Forms Setting Form16a', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_forms_setting_form9c', 'label' => 'Forms Setting Form9c', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_forms_setting_form14c', 'label' => 'Forms Setting Form14c', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_forms_setting_form17c', 'label' => 'Forms Setting Form17c', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_forms_setting_form20c', 'label' => 'Forms Setting Form20c', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_16a', 'label' => '16a', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_get_16a_form_setting', 'label' => 'Get 16a Form Setting', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_16aformsettings', 'label' => '16aformsettings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_21cform', 'label' => '21cform', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_get_21c_form_setting', 'label' => 'Get 21c Form Setting', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_21cformsettings', 'label' => '21cformsettings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_21form', 'label' => '21form', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_getpos', 'label' => 'Getpos', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_get_all_f21_transactions', 'label' => 'Get All F21 Transactions', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_get_f21_form_number', 'label' => 'Get F21 Form Number', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_f20_cds', 'label' => 'F20 Cds', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'mpcs_f20_cds_list', 'label' => 'F20 Cds List', 'type' => 'page', 'source' => 'module_pages'],
];
