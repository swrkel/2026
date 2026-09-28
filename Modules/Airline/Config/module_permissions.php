<?php

/*
 * MA-004 - page permissions for the Airline module.
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
 *   38 pages found in Airline.
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
    ['key' => 'airline_get_airport_table', 'label' => 'Get Airport Table', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'airline_location', 'label' => 'Location', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'airline_check_location_exist', 'label' => 'Check Location Exist', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'airline_create_invoice', 'label' => 'Create Invoice', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'airline_create', 'label' => 'Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'airline_list_commision', 'label' => 'List Commision', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'airline_get_wallet_amount', 'label' => 'Get Wallet Amount', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'airline_create_passenger', 'label' => 'Create Passenger', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'airline_create_commission', 'label' => 'Create Commission', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'airline_add_commision_store', 'label' => 'Add Commision Store', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'airline_create_payment', 'label' => 'Create Payment', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'airline_create_payment_supplier', 'label' => 'Create Payment Supplier', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'airline_airport_suppliers', 'label' => 'Airport Suppliers', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'airline_airport_commision_type', 'label' => 'Airport Commision Type', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'airline_save_account', 'label' => 'Save Account', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'airline_airport_commision_type_get', 'label' => 'Airport Commision Type Get', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'airline_getinvoiceno', 'label' => 'Getinvoiceno', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'airline_getinvoicenumbers', 'label' => 'Getinvoicenumbers', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'airline_get_airline_commission_print', 'label' => 'Get Airline Commission Print', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'airline_airport_linked_account_get', 'label' => 'Airport Linked Account Get', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'airline_get_commission_types', 'label' => 'Get Commission Types', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'airline_passenger_type_get', 'label' => 'Passenger Type Get', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'airline_additional_service_get', 'label' => 'Additional Service Get', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'airline_locations', 'label' => 'Locations', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'airline_airline_classes_get', 'label' => 'Airline Classes Get', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'airline_customers_by_group_id', 'label' => 'Customers By Group Id', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'airline_customers_all', 'label' => 'Customers All', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'airline_get_customer_fin_data', 'label' => 'Get Customer Fin Data', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'airline_airlines', 'label' => 'Airlines', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'airline_airline_agents', 'label' => 'Airline Agents', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'airline_airline_airports', 'label' => 'Airline Airports', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'airline_form_settings', 'label' => 'Form Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'airline_form_settings_check_form_settings_customers', 'label' => 'Form Settings Check Form Settings Customers', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'airline_form_settings_check_form_settings_suppliers', 'label' => 'Form Settings Check Form Settings Suppliers', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'airline_form_settings_check_form_settings_passengers', 'label' => 'Form Settings Check Form Settings Passengers', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'airline_airline_linked_supplier_account', 'label' => 'Airline Linked Supplier Account', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'airline_submit_linked_supplier_account', 'label' => 'Submit Linked Supplier Account', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'airline_get_linked_supplier_accounts', 'label' => 'Get Linked Supplier Accounts', 'type' => 'page', 'source' => 'module_pages'],
];
