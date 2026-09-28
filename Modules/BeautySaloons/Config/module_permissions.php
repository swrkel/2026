<?php

/*
 * MA-004 - page permissions for the BeautySaloons module.
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
 *   59 pages found in BeautySaloons.
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
    ['key' => 'beautysaloons_appointments', 'label' => 'Appointments', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_sales', 'label' => 'Sales', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_retail_sales', 'label' => 'Retail Sales', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_customers', 'label' => 'Customers', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_staff', 'label' => 'Staff', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_commissions', 'label' => 'Commissions', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_inventory', 'label' => 'Inventory', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_memberships', 'label' => 'Memberships', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_vouchers', 'label' => 'Vouchers', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_loyalty', 'label' => 'Loyalty', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_payments', 'label' => 'Payments', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_reports_membership_summary', 'label' => 'Reports Membership Summary', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_reports_package_consumption', 'label' => 'Reports Package Consumption', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_create', 'label' => 'Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_branch_reports', 'label' => 'Branch Reports', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_branch_reports_branch_sales', 'label' => 'Branch Reports Branch Sales', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_branch_reports_resource_utilization', 'label' => 'Branch Reports Resource Utilization', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_executive', 'label' => 'Executive', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_branch', 'label' => 'Branch', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_reception', 'label' => 'Reception', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_finance', 'label' => 'Finance', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_reports', 'label' => 'Reports', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_reports_daily_sales', 'label' => 'Reports Daily Sales', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_reports_staff_performance', 'label' => 'Reports Staff Performance', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_settings', 'label' => 'Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_final_audit', 'label' => 'Final Audit', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_release_candidate', 'label' => 'Release Candidate', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_login', 'label' => 'Login', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_profile', 'label' => 'Profile', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_wallet', 'label' => 'Wallet', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_membership', 'label' => 'Membership', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_packages', 'label' => 'Packages', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_templates', 'label' => 'Templates', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_templates_create', 'label' => 'Templates Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_reports_delivery', 'label' => 'Reports Delivery', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_tiers', 'label' => 'Tiers', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_reports_prepaid_package_utilization', 'label' => 'Reports Prepaid Package Utilization', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_me', 'label' => 'Me', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_dashboard', 'label' => 'Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_branches', 'label' => 'Branches', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_services', 'label' => 'Services', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_staff_availability', 'label' => 'Staff Availability', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_appointment_calendar', 'label' => 'Appointment Calendar', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_appointment_calendar_events', 'label' => 'Appointment Calendar Events', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_pos', 'label' => 'Pos', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_reports_daily_collection', 'label' => 'Reports Daily Collection', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_reports_appointments', 'label' => 'Reports Appointments', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_reports_service_sales', 'label' => 'Reports Service Sales', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_reports_staff_commissions', 'label' => 'Reports Staff Commissions', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_mappings', 'label' => 'Mappings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_postings', 'label' => 'Postings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_billings', 'label' => 'Billings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_cashier_settlements', 'label' => 'Cashier Settlements', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_stock', 'label' => 'Stock', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_retail_sales_create', 'label' => 'Retail Sales Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_reports_stock', 'label' => 'Reports Stock', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_reports_product_sales', 'label' => 'Reports Product Sales', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_reports_expiry', 'label' => 'Reports Expiry', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'beautysaloons_reports_profitability', 'label' => 'Reports Profitability', 'type' => 'page', 'source' => 'module_pages'],
];
