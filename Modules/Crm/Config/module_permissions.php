<?php

/*
 * MA-004 - page permissions for the Crm module.
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
 *   27 pages found in Crm.
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
    ['key' => 'crm_contact_profile', 'label' => 'Contact Profile', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'crm_contact_purchases', 'label' => 'Contact Purchases', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'crm_contact_sells', 'label' => 'Contact Sells', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'crm_contact_ledger', 'label' => 'Contact Ledger', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'crm_contact_get_ledger', 'label' => 'Contact Get Ledger', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'crm_products_list', 'label' => 'Products List', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'crm_commissions', 'label' => 'Commissions', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'crm_all_contacts_login', 'label' => 'All Contacts Login', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'crm_todays_follow_ups', 'label' => 'Todays Follow Ups', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'crm_lead_follow_ups', 'label' => 'Lead Follow Ups', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'crm_all_users_call_logs', 'label' => 'All Users Call Logs', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'crm_dashboard', 'label' => 'Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'crm_install', 'label' => 'Install', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'crm_install_uninstall', 'label' => 'Install Uninstall', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'crm_install_update', 'label' => 'Install Update', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'crm_reports', 'label' => 'Reports', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'crm_follow_ups_by_user', 'label' => 'Follow Ups By User', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'crm_follow_ups_by_contact', 'label' => 'Follow Ups By Contact', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'crm_lead_to_customer_report', 'label' => 'Lead To Customer Report', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'crm_call_log', 'label' => 'Call Log', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'crm_edit_proposal_template', 'label' => 'Edit Proposal Template', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'crm_view_proposal_template', 'label' => 'View Proposal Template', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'crm_send_proposal', 'label' => 'Send Proposal', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'crm_settings', 'label' => 'Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'crm_order_request', 'label' => 'Order Request', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'crm_b2b_marketplace', 'label' => 'B2b Marketplace', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'crm_import_leads', 'label' => 'Import Leads', 'type' => 'page', 'source' => 'module_pages'],
];
