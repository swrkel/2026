<?php

/*
 * MA-004 - page permissions for the HotelManagement module.
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
 *   30 pages found in HotelManagement.
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
    ['key' => 'hotelmanagement_occupancy', 'label' => 'Occupancy', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hotelmanagement_revenue', 'label' => 'Revenue', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hotelmanagement_reservation_register', 'label' => 'Reservation Register', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hotelmanagement_checkin_checkout', 'label' => 'Checkin Checkout', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hotelmanagement_housekeeping', 'label' => 'Housekeeping', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hotelmanagement_guest_ledger', 'label' => 'Guest Ledger', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hotelmanagement_room_revenue', 'label' => 'Room Revenue', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hotelmanagement_analytics', 'label' => 'Analytics', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hotelmanagement_manager', 'label' => 'Manager', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hotelmanagement_front_office', 'label' => 'Front Office', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hotelmanagement_finance', 'label' => 'Finance', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hotelmanagement_system_check', 'label' => 'System Check', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hotelmanagement_maintenance', 'label' => 'Maintenance', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hotelmanagement_billing', 'label' => 'Billing', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hotelmanagement_pos', 'label' => 'Pos', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hotelmanagement_banquets', 'label' => 'Banquets', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hotelmanagement_conference', 'label' => 'Conference', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hotelmanagement_night_audit', 'label' => 'Night Audit', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hotelmanagement_room_service', 'label' => 'Room Service', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hotelmanagement_inventory', 'label' => 'Inventory', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hotelmanagement_guest_crm', 'label' => 'Guest Crm', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hotelmanagement_login', 'label' => 'Login', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hotelmanagement_reservations', 'label' => 'Reservations', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hotelmanagement_profile', 'label' => 'Profile', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hotelmanagement_folios', 'label' => 'Folios', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hotelmanagement_requests', 'label' => 'Requests', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hotelmanagement_branches', 'label' => 'Branches', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hotelmanagement_room_types', 'label' => 'Room Types', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hotelmanagement_availability', 'label' => 'Availability', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'hotelmanagement_notifications', 'label' => 'Notifications', 'type' => 'page', 'source' => 'module_pages'],
];
