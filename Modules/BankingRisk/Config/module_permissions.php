<?php

/*
 * MA-004 - page permissions for the BankingRisk module.
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
 *   8 pages found in BankingRisk.
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
    ['key' => 'bankingrisk_credit_risk', 'label' => 'Credit Risk', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bankingrisk_liquidity_risk', 'label' => 'Liquidity Risk', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bankingrisk_operational_risk', 'label' => 'Operational Risk', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bankingrisk_market_risk', 'label' => 'Market Risk', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bankingrisk_early_warning', 'label' => 'Early Warning', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bankingrisk_stress_tests', 'label' => 'Stress Tests', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bankingrisk_reports', 'label' => 'Reports', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'bankingrisk_settings', 'label' => 'Settings', 'type' => 'page', 'source' => 'module_pages'],
];
