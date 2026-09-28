<?php

/*
 * MA-004 - page permissions for the Essentials module.
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
 *   19 pages found in Essentials.
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
    ['key' => 'essentials_dashboard', 'label' => 'Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'essentials_install', 'label' => 'Install', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'essentials_install_update', 'label' => 'Install Update', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'essentials_install_uninstall', 'label' => 'Install Uninstall', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'essentials_user_sales_targets', 'label' => 'User Sales Targets', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'essentials_user_leave_summary', 'label' => 'User Leave Summary', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'essentials_settings', 'label' => 'Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'essentials_essential_settings', 'label' => 'Essential Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'essentials_user_attendance_summary', 'label' => 'User Attendance Summary', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'essentials_advances', 'label' => 'Advances', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'essentials_location_employees', 'label' => 'Location Employees', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'essentials_my_payrolls', 'label' => 'My Payrolls', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'essentials_payroll_group_datatable', 'label' => 'Payroll Group Datatable', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'essentials_fetch_employee_ledger', 'label' => 'Fetch Employee Ledger', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'essentials_summarised_ledger', 'label' => 'Summarised Ledger', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'essentials_detailed_ledger', 'label' => 'Detailed Ledger', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'essentials_sales_target', 'label' => 'Sales Target', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'essentials_hrm_department_update', 'label' => 'Hrm Department Update', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'essentials_shift', 'label' => 'Shift', 'type' => 'page', 'source' => 'module_pages'],
];
