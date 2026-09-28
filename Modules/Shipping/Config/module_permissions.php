<?php

/*
 * MA-004 - page permissions for the Shipping module.
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
 *   12 pages found in Shipping.
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
    ['key' => 'shipping_scan_code', 'label' => 'Scan Code', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'shipping_shipment_terms_condition', 'label' => 'Shipment Terms Condition', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'shipping_locations', 'label' => 'Locations', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'shipping_add_country', 'label' => 'Add Country', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'shipping_countries', 'label' => 'Countries', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'shipping_provinces', 'label' => 'Provinces', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'shipping_add_province', 'label' => 'Add Province', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'shipping_districts', 'label' => 'Districts', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'shipping_add_district', 'label' => 'Add District', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'shipping_areas', 'label' => 'Areas', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'shipping_add_areas', 'label' => 'Add Areas', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'shipping_printreceipt', 'label' => 'Printreceipt', 'type' => 'page', 'source' => 'module_pages'],
];
