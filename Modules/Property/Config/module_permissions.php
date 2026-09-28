<?php

/*
 * MA-004 - page permissions for the Property module.
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
 *   7 pages found in Property.
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
    ['key' => 'property_list_price_changes', 'label' => 'List Price Changes', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'property_contacts_ledger', 'label' => 'Contacts Ledger', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'property_easy_payments_aging_report', 'label' => 'Easy Payments Aging Report', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'property_sale_and_customer_payment_access_main_system', 'label' => 'Sale And Customer Payment Access Main System', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'property_sale_and_customer_payment_dashboard', 'label' => 'Sale And Customer Payment Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'property_reports', 'label' => 'Reports', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'property_reports_daily_report', 'label' => 'Reports Daily Report', 'type' => 'page', 'source' => 'module_pages'],
];
