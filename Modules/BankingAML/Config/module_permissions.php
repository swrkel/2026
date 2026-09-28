<?php

/*
 * MA-004 - page permissions for the BankingAML module.
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
 *   7 pages found in BankingAML.
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
    ['key' => 'bankingaml_kyc_reviews', 'label' => 'Kyc Reviews', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bankingaml_screening', 'label' => 'Screening', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bankingaml_cases', 'label' => 'Cases', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bankingaml_alerts', 'label' => 'Alerts', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bankingaml_regulatory_reports', 'label' => 'Regulatory Reports', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bankingaml_audit', 'label' => 'Audit', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bankingaml_settings', 'label' => 'Settings', 'type' => 'page', 'source' => 'module_pages'],
];
