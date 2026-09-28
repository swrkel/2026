<?php

/*
 * MA-004 - page permissions for the Superadmin module.
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
 *   44 pages found in Superadmin.
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
    ['key' => 'superadmin_notify_expired', 'label' => 'Notify Expired', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_update_permissions', 'label' => 'Update Permissions', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_default_business_types', 'label' => 'Default Business Types', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_default_business_types_create', 'label' => 'Default Business Types Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_all_business_types', 'label' => 'All Business Types', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_locations', 'label' => 'Locations', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_add_country', 'label' => 'Add Country', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_countries', 'label' => 'Countries', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_provinces', 'label' => 'Provinces', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_add_province', 'label' => 'Add Province', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_districts', 'label' => 'Districts', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_add_district', 'label' => 'Add District', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_areas', 'label' => 'Areas', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_add_areas', 'label' => 'Add Areas', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_sms_summary', 'label' => 'Sms Summary', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_install', 'label' => 'Install', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_install_update', 'label' => 'Install Update', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_stats', 'label' => 'Stats', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_business_back_to_superadmin', 'label' => 'Business Back To Superadmin', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_business_location_overview', 'label' => 'Business Location Overview', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_packages_get_option_variables', 'label' => 'Packages Get Option Variables', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_settings', 'label' => 'Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_settings_product_categories', 'label' => 'Settings Product Categories', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_settings_add_product_category', 'label' => 'Settings Add Product Category', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_settings_expense_categories', 'label' => 'Settings Expense Categories', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_settings_add_expense_category', 'label' => 'Settings Add Expense Category', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_settings_search_tenants', 'label' => 'Settings Search Tenants', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_pages', 'label' => 'Pages', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_landing_languages', 'label' => 'Landing Languages', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_landing_settings', 'label' => 'Landing Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_landing_pages', 'label' => 'Landing Pages', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_delete_ad', 'label' => 'Delete Ad', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_communicator', 'label' => 'Communicator', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_tank_dip_chart_import', 'label' => 'Tank Dip Chart Import', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_family_subscription_patient', 'label' => 'Family Subscription Patient', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_export_file', 'label' => 'Export File', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_list_edit_account_entries', 'label' => 'List Edit Account Entries', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_list_edit_contact_entries', 'label' => 'List Edit Contact Entries', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_agents_next_referral_code', 'label' => 'Agents Next Referral Code', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_petro_quota_setting', 'label' => 'Petro Quota Setting', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_petro_qouta_setting_ajax', 'label' => 'Petro Qouta Setting Ajax', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_user_locations', 'label' => 'User Locations', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_all_subscriptions', 'label' => 'All Subscriptions', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'superadmin_pricing', 'label' => 'Pricing', 'type' => 'page', 'source' => 'module_pages'],
];
