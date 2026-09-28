<?php

/*
 * MA-004 - page permissions for the Tailoring module.
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
 *   27 pages found in Tailoring.
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
    ['key' => 'tailoring_quotations', 'label' => 'Quotations', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'tailoring_quotations_create', 'label' => 'Quotations Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'tailoring_orders', 'label' => 'Orders', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'tailoring_orders_create', 'label' => 'Orders Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'tailoring_job_cards', 'label' => 'Job Cards', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'tailoring_reports_dashboard', 'label' => 'Reports Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'tailoring_reports_production', 'label' => 'Reports Production', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'tailoring_reports_employee', 'label' => 'Reports Employee', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'tailoring_reports_material', 'label' => 'Reports Material', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'tailoring_reports_customer', 'label' => 'Reports Customer', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'tailoring_customer_portal', 'label' => 'Customer Portal', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'tailoring_customer_centre', 'label' => 'Customer Centre', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'tailoring_customer_centre_create', 'label' => 'Customer Centre Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'tailoring_measurement_centre', 'label' => 'Measurement Centre', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'tailoring_measurement_centre_create', 'label' => 'Measurement Centre Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'tailoring_reports_centre', 'label' => 'Reports Centre', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'tailoring_executive_bi_dashboard', 'label' => 'Executive Bi Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'tailoring_configuration_centre_v2', 'label' => 'Configuration Centre V2', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'tailoring_permission_audit', 'label' => 'Permission Audit', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'tailoring_release_readiness', 'label' => 'Release Readiness', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'tailoring_production_centre', 'label' => 'Production Centre', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'tailoring_workshop', 'label' => 'Workshop', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'tailoring_material_centre', 'label' => 'Material Centre', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'tailoring_trial_centre', 'label' => 'Trial Centre', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'tailoring_alteration_centre', 'label' => 'Alteration Centre', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'tailoring_delivery_centre', 'label' => 'Delivery Centre', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'tailoring_operations_reports', 'label' => 'Operations Reports', 'type' => 'page', 'source' => 'module_pages'],
];
