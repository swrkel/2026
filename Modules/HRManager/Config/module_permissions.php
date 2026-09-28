<?php

/*
 * MA-004 - page permissions for the HRManager module.
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
 *   14 pages found in HRManager.
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
    ['key' => 'hrmanager_hr_manager_reports', 'label' => 'Hr Manager Reports', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hrmanager_hr_reports', 'label' => 'Hr Reports', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hrmanager_hr_manager_setup', 'label' => 'Hr Manager Setup', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hrmanager_hr_setup', 'label' => 'Hr Setup', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hrmanager_hr_manager', 'label' => 'Hr Manager', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hrmanager_hr_manager_dashboard', 'label' => 'Hr Manager Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hrmanager_hr', 'label' => 'Hr', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hrmanager_hr_dashboard', 'label' => 'Hr Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hrmanager_hr_manager_employees', 'label' => 'Hr Manager Employees', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hrmanager_hr_employees', 'label' => 'Hr Employees', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hrmanager_hr_manager_attendance', 'label' => 'Hr Manager Attendance', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hrmanager_hr_attendance', 'label' => 'Hr Attendance', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hrmanager_hr_manager_employee_records', 'label' => 'Hr Manager Employee Records', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hrmanager_hr_employee_records', 'label' => 'Hr Employee Records', 'type' => 'page', 'source' => 'module_pages'],
];
