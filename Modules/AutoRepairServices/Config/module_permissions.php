<?php

/*
 * MA-004 - page permissions for the AutoRepairServices module.
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
 *   14 pages found in AutoRepairServices.
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
    ['key' => 'autorepairservices_repair_status', 'label' => 'Repair Status', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autorepairservices_install', 'label' => 'Install', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autorepairservices_install_uninstall', 'label' => 'Install Uninstall', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autorepairservices_install_update', 'label' => 'Install Update', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autorepairservices_device_category_modal', 'label' => 'Device Category Modal', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autorepairservices_sales_report_detail', 'label' => 'Sales Report Detail', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autorepairservices_sales_report_detail_daily', 'label' => 'Sales Report Detail Daily', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autorepairservices_models_repair_checklist', 'label' => 'Models Repair Checklist', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autorepairservices_device', 'label' => 'Device', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autorepairservices_servicereport_service_report_print', 'label' => 'Servicereport Service Report Print', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autorepairservices_vehicle_brand_table', 'label' => 'Vehicle Brand Table', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autorepairservices_vehicle_brand_savedata', 'label' => 'Vehicle Brand Savedata', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autorepairservices_vehicle_brand_deletedata', 'label' => 'Vehicle Brand Deletedata', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'autorepairservices_vehicle_brand_populatedata', 'label' => 'Vehicle Brand Populatedata', 'type' => 'page', 'source' => 'module_pages'],
];
