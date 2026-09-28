<?php

/*
 * MA-004 - page permissions for the SMS module.
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
 *   10 pages found in SMS.
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
    ['key' => 'sms_interest_ajax', 'label' => 'Interest Ajax', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'sms_view_ledger', 'label' => 'View Ledger', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'sms_sms_delivery', 'label' => 'Sms Delivery', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'sms_quick_send', 'label' => 'Quick Send', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'sms_sms_campaign', 'label' => 'Sms Campaign', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'sms_sms_from_file', 'label' => 'Sms From File', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'sms_sms_groups', 'label' => 'Sms Groups', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'sms_sms_groups_create', 'label' => 'Sms Groups Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'sms_execute_sms_campaign', 'label' => 'Execute Sms Campaign', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'sms_cron_sms_campaign', 'label' => 'Cron Sms Campaign', 'type' => 'page', 'source' => 'module_pages'],
];
