<?php

/*
 * MA-004 - page permissions for the LeadsNew module.
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
 *   35 pages found in LeadsNew.
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
    ['key' => 'leadsnew_route_ok', 'label' => 'Route Ok', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leadsnew_health_check', 'label' => 'Health Check', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leadsnew_dashboard', 'label' => 'Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leadsnew_executive_dashboard', 'label' => 'Executive Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leadsnew_leads', 'label' => 'Leads', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leadsnew_leads_create', 'label' => 'Leads Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leadsnew_opportunity_centre', 'label' => 'Opportunity Centre', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leadsnew_followups', 'label' => 'Followups', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leadsnew_workflow', 'label' => 'Workflow', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leadsnew_workflow_kanban', 'label' => 'Workflow Kanban', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leadsnew_calendar', 'label' => 'Calendar', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leadsnew_calendar_feed', 'label' => 'Calendar Feed', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leadsnew_calendar_centre', 'label' => 'Calendar Centre', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leadsnew_advanced_search', 'label' => 'Advanced Search', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leadsnew_advanced_search_results', 'label' => 'Advanced Search Results', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leadsnew_import', 'label' => 'Import', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leadsnew_import_export', 'label' => 'Import Export', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leadsnew_notifications', 'label' => 'Notifications', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leadsnew_administration_centre', 'label' => 'Administration Centre', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leadsnew_api_docs', 'label' => 'Api Docs', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leadsnew_ui_standards', 'label' => 'Ui Standards', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leadsnew_release_checklist', 'label' => 'Release Checklist', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leadsnew_reports', 'label' => 'Reports', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leadsnew_reports_lead_register', 'label' => 'Reports Lead Register', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leadsnew_reports_conversion', 'label' => 'Reports Conversion', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leadsnew_reports_pipeline', 'label' => 'Reports Pipeline', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leadsnew_reports_followups', 'label' => 'Reports Followups', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leadsnew_reports_executive', 'label' => 'Reports Executive', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leadsnew_reports_source', 'label' => 'Reports Source', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leadsnew_reports_ageing', 'label' => 'Reports Ageing', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leadsnew_reports_team_performance', 'label' => 'Reports Team Performance', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leadsnew_settings', 'label' => 'Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leadsnew_add_leads', 'label' => 'Add Leads', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leadsnew_list_leads', 'label' => 'List Leads', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'leadsnew_summary', 'label' => 'Summary', 'type' => 'page', 'source' => 'module_pages'],
];
