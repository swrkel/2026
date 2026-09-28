<?php

/*
 |------------------------------------------------------------------------------
 | Role permission sections - the single ordered list of what the Role screen shows
 |------------------------------------------------------------------------------
 |
 | role/create.blade.php and role/edit.blade.php were 6,174 and 6,237 lines and
 | 98.8% identical. Every drift bug found in the MA-002 review came from that:
 | permissions present on one page but not the other, duplicated checkboxes,
 | four names for two permissions. Both pages now render the SAME partials from
 | this one list, so they cannot drift again.
 |
 | EACH ENTRY
 |   view     partial under resources/views/role/sections/
 |   package  optional. Any one of these keys present in the subscription's
 |            package_details (Superadmin -> All Business -> Manage) shows it.
 |   sidebar  optional. Key checked against Manage Side Bar. Omit to skip.
 |   when     optional named condition resolved by the driver.
 |   mode     optional. 'edit' renders only on the edit page.
 |
 | ADDING A MODULE: drop a partial in sections/ and add one line here. It then
 | appears on both pages with the correct gating, automatically.
 |
 | Order is significant - it is the on-screen order, taken from the original
 | create.blade.php.
 */

return [
    ['view' => '00_service_staff', 'when' => 'service_staff'],
    ['view' => '01_common'],
    ['view' => '02_bakery_module', 'package' => ['bakery_module'], 'sidebar' => 'bakery_module'],
    ['view' => '03_common'],
    ['view' => '04_sales_agent_module', 'package' => ['sales_agent_module'], 'sidebar' => 'sales_agent_module'],
    ['view' => '05_list_sms', 'package' => ['list_sms'], 'sidebar' => 'list_sms'],
    ['view' => '06_bakery_module', 'package' => ['bakery_module'], 'sidebar' => 'bakery_module'],
    ['view' => '07_smsmodule_module', 'package' => ['smsmodule_module'], 'sidebar' => 'smsmodule_module'],
    ['view' => '08_common'],
    ['view' => '09_vat_module', 'package' => ['vat_module'], 'sidebar' => 'vat_module'],
    ['view' => '10_pos_sale', 'package' => ['pos_sale'], 'sidebar' => 'pos_sale'],
    ['view' => '11_crm_module', 'package' => ['crm_module'], 'sidebar' => 'crm_module'],
    ['view' => '12_common'],
    ['view' => '13_hms_module', 'package' => ['hms_module'], 'sidebar' => 'hms_module'],
    ['view' => '14_asset_module', 'package' => ['asset_module'], 'sidebar' => 'asset_module'],
    ['view' => '15_shipping_module', 'package' => ['shipping_module'], 'sidebar' => 'shipping_module'],
    ['view' => '16_ezyinvoice_module', 'package' => ['ezyinvoice_module'], 'sidebar' => 'ezyinvoice_module'],
    ['view' => '17_common'],
    ['view' => '18_spreadsheet', 'package' => ['spreadsheet'], 'sidebar' => 'spreadsheet'],
    ['view' => '19_common'],
    ['view' => '20_contact_supplier', 'package' => ['contact_supplier'], 'sidebar' => 'contact_supplier'],
    ['view' => '21_contact_customer', 'package' => ['contact_customer'], 'sidebar' => 'contact_customer'],
    ['view' => '22_products', 'package' => ['products'], 'sidebar' => 'products'],
    ['view' => '23_purchase', 'package' => ['purchase'], 'sidebar' => 'purchase'],
    ['view' => '24_common'],
    ['view' => '25_sale_module', 'package' => ['sale_module'], 'sidebar' => 'sale_module'],
    ['view' => '26_brands', 'package' => ['brands'], 'sidebar' => 'brands'],
    ['view' => '27_visitors_registration_module', 'package' => ['visitors_registration_module'], 'sidebar' => 'visitors_registration_module'],
    ['view' => '28_common'],
    ['view' => '29_products_units', 'package' => ['products_units'], 'sidebar' => 'products_units'],
    ['view' => '30_products_categories', 'package' => ['products_categories'], 'sidebar' => 'products_categories'],
    ['view' => '31_crm_module', 'package' => ['crm_module'], 'sidebar' => 'crm_module'],
    ['view' => '32_common'],
    ['view' => '33_product_report', 'package' => ['product_report'], 'sidebar' => 'product_report'],
    // Standalone StockReports: Manage Side Bar is the parent authority. It must
    // not depend on the older Product Reports package switch.
    ['view' => '33a_stock_reports', 'sidebar' => 'stock_reports'],
    ['view' => '34_stock_adjustment', 'package' => ['stock_adjustment'], 'sidebar' => 'stock_adjustment'],
    ['view' => '35_payment_status_report', 'package' => ['payment_status_report'], 'sidebar' => 'payment_status_report'],
    ['view' => '36_management_reports', 'package' => ['management_reports'], 'sidebar' => 'management_reports'],
    ['view' => '37_verification_report', 'package' => ['verification_report'], 'sidebar' => 'verification_report'],
    ['view' => '38_activity_report', 'package' => ['activity_report'], 'sidebar' => 'activity_report'],
    ['view' => '39_contact_report', 'package' => ['contact_report'], 'sidebar' => 'contact_report'],
    ['view' => '40_common'],
    ['view' => '41_unfinished_form', 'package' => ['unfinished_form'], 'sidebar' => 'unfinished_form'],
    ['view' => '42_common'],
    ['view' => '43_payroll', 'package' => ['payroll'], 'sidebar' => 'payroll'],
    ['view' => '44_common'],
    ['view' => '45_deposits_module', 'package' => ['deposits_module'], 'sidebar' => 'deposits_module'],
    ['view' => '46_contact_module', 'package' => ['contact_module'], 'sidebar' => 'contact_module'],
    ['view' => '47_products_new', 'package' => ['products_new_module', 'products_new_products', 'products_new_dashboard'], 'sidebar' => 'products_new_module'],
    ['view' => '47_edit_received_outstanding', 'package' => ['edit_received_outstanding'], 'sidebar' => 'edit_received_outstanding'],
    ['view' => '48_customers_module', 'when' => 'customers_module_enabled'],
    ['view' => '49_suppliers_module', 'package' => ['suppliers_module', 'supplier_module', 'suppliers'], 'sidebar' => 'suppliers_module'],
    ['view' => '50_purchase_module', 'package' => ['purchase']],
    ['view' => '48_common'],
    ['view' => '49_mpcs_module', 'package' => ['mpcs_module'], 'sidebar' => 'mpcs_module'],
    ['view' => '50_price_changes_module', 'package' => ['price_changes_module'], 'sidebar' => 'price_changes_module'],
    ['view' => '51_fleet_module', 'package' => ['fleet_module'], 'sidebar' => 'fleet_module'],
    ['view' => '52_ran_module', 'package' => ['ran_module'], 'sidebar' => 'ran_module'],
    ['view' => '53_catalogue_qr', 'package' => ['catalogue_qr'], 'sidebar' => 'catalogue_qr'],
    ['view' => '54_repair_module', 'package' => ['repair_module'], 'sidebar' => 'repair_module'],
    ['view' => '55_auto_services_and_repair_module', 'package' => ['auto_services_and_repair_module'], 'sidebar' => 'auto_services_and_repair_module'],
    /*
     | S678-RETIRE: shared fuel-operations permissions.
     |
     | Every one of the 43 permissions in this section is enforced by code
     | OUTSIDE the Petro module - PetroPD, PetroGeneral, PetroDirect,
     | PumperDashboard, EVCharging, DailyCollectionSW, SettlementSW and core
     | all check names declared here. bulk_assign_pumps alone gates the Assign
     | button in five different modules.
     |
     | While this section was gated on enable_petro_module only, a business
     | that had retired Petro could not grant ANY of them: the checkboxes were
     | not rendered, so the permissions could not be given, so buttons stayed
     | hidden and endpoints stayed refused in modules that have nothing to do
     | with Petro. That is what kept petro_module = 1 switched on at ep127.
     |
     | Listing every successor package key means the section appears for any
     | business running any fuel module. Nothing is granted automatically -
     | it only becomes possible to grant.
     */
    ['view' => '56_enable_petro_module', 'package' => [
        'enable_petro_module',
        'petro_pd_module',
        'petro_general_module',
        'petro_direct_module',
        'petro_direct_new_module',
        'petro_pd_new_module',
        'pumper_dashboard_module',
        'pumper_dashboard_new_module',
        'ev_charging_module',
        'daily_collection_sw_module',
        'settlement_sw_module',
    ], 'sidebar' => [
        'petro',
        'petro_pd',
        'petro_general',
        'petro_direct',
        'pumper_dashboard',
        'ev_charging',
        'daily_collection_sw',
    ]],
    ['view' => '57_petro_pd_module', 'package' => ['petro_pd_module'], 'sidebar' => 'petro_pd_module'],
    ['view' => '58_common'],
    ['view' => '59_finance_reports_module', 'package' => ['access_account', 'accounting_module', 'banking_module', 'finance_module', 'finance_reports_module'], 'sidebar' => 'access_account'],
    ['view' => '60_chequer_module', 'package' => ['chequer_module'], 'sidebar' => 'chequer_module'],
    ['view' => '61_issue_customer_bill', 'package' => ['issue_customer_bill'], 'sidebar' => 'issue_customer_bill'],
    ['view' => '62_customer_settings', 'package' => ['customer_settings'], 'sidebar' => 'customer_settings'],
    ['view' => '63_tasks_management', 'package' => ['tasks_management'], 'sidebar' => 'tasks_management'],
    ['view' => '64_member_registration', 'package' => ['member_registration'], 'sidebar' => 'member_registration'],
    ['view' => '65_leads_module', 'package' => ['leads_module'], 'sidebar' => 'leads_module'],
    ['view' => '66_property_module', 'package' => ['property_module'], 'sidebar' => 'property_module'],
    ['view' => '67_sms_module', 'package' => ['sms_module'], 'sidebar' => 'sms_module'],
    ['view' => '68_enable_cheque_writing', 'package' => ['enable_cheque_writing'], 'sidebar' => 'enable_cheque_writing'],
    ['view' => '69_service_staff', 'when' => 'tables_and_service_staff'],
    ['view' => '70_access_selling_price', 'package' => ['access_selling_price'], 'sidebar' => 'access_selling_price'],
    ['view' => '71_set_minimum_price', 'package' => ['set_minimum_price'], 'sidebar' => 'set_minimum_price'],
    ['view' => '72_view_sales_commission', 'package' => ['view_sales_commission'], 'sidebar' => 'view_sales_commission'],
    ['view' => '73_essentials_module', 'package' => ['essentials_module'], 'sidebar' => 'essentials_module'],
    ['view' => '74_day_end_module', 'package' => ['day_end_module'], 'sidebar' => 'day_end_module'],
    ['view' => '75_upload_images', 'package' => ['upload_images'], 'sidebar' => 'upload_images'],
    ['view' => '76_sms_enable', 'package' => ['sms_enable'], 'sidebar' => 'sms_enable'],
    ['view' => '77_enable_restaurant', 'package' => ['enable_restaurant'], 'sidebar' => 'enable_restaurant'],
    ['view' => '78_cache_clear', 'package' => ['cache_clear'], 'sidebar' => 'cache_clear'],
    ['view' => '79_common'],
    ['view' => '80_customized_reports_module', 'package' => ['customized_reports_module'], 'sidebar' => 'customized_reports_module'],
    ['view' => '99_assigned_users', 'when' => 'has_users', 'mode' => 'edit'],
];
