<?php

/*
 * MA-004 - page permissions for the Membership module.
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
 *   24 pages found in Membership.
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
    ['key' => 'membership_business_types_create', 'label' => 'Business Types Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'membership_point_settings_create', 'label' => 'Point Settings Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'membership_card_settings_create', 'label' => 'Card Settings Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'membership_signatures_create', 'label' => 'Signatures Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'membership_business_names_create', 'label' => 'Business Names Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'membership_business_types', 'label' => 'Business Types', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'membership_point_settings', 'label' => 'Point Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'membership_card_settings', 'label' => 'Card Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'membership_signatures', 'label' => 'Signatures', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'membership_membership_settings', 'label' => 'Membership Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'membership_members', 'label' => 'Members', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'membership_members_activities', 'label' => 'Members Activities', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'membership_members_create', 'label' => 'Members Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'membership_next_member_number', 'label' => 'Next Member Number', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'membership_add_points', 'label' => 'Add Points', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'membership_list_points', 'label' => 'List Points', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'membership_member_points', 'label' => 'Member Points', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'membership_member_bills', 'label' => 'Member Bills', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'membership_dividends_add', 'label' => 'Dividends Add', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'membership_dividends_filtered_members', 'label' => 'Dividends Filtered Members', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'membership_dividends_check_lock_status', 'label' => 'Dividends Check Lock Status', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'membership_dividends_list', 'label' => 'Dividends List', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'membership_dividends_last', 'label' => 'Dividends Last', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'membership_dividends_issued', 'label' => 'Dividends Issued', 'type' => 'page', 'source' => 'module_pages'],
];
