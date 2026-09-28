<?php

/*
 * MA-004 - page permissions for the CommunicationHub module.
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
 *   91 pages found in CommunicationHub.
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
    ['key' => 'communicationhub_communication_hub_central', 'label' => 'Communication Hub Central', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_readiness_check', 'label' => 'Readiness Check', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_diagnostics', 'label' => 'Diagnostics', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_production_qa', 'label' => 'Production Qa', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_operations_support', 'label' => 'Operations Support', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_queue', 'label' => 'Queue', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_otp', 'label' => 'Otp', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_compose', 'label' => 'Compose', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_bulk', 'label' => 'Bulk', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_email_dashboard', 'label' => 'Email Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_compose_email', 'label' => 'Compose Email', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_bulk_email', 'label' => 'Bulk Email', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_email_queue', 'label' => 'Email Queue', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_reports', 'label' => 'Reports', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_settings', 'label' => 'Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_enterprise_centre', 'label' => 'Enterprise Centre', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_provider_monitor', 'label' => 'Provider Monitor', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_queue_monitor', 'label' => 'Queue Monitor', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_template_designer', 'label' => 'Template Designer', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_otp_centre', 'label' => 'Otp Centre', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_communication_audit', 'label' => 'Communication Audit', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_standalone_audit', 'label' => 'Standalone Audit', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_marketplace', 'label' => 'Marketplace', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_production_hardening', 'label' => 'Production Hardening', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_production_hardening_standalone', 'label' => 'Production Hardening Standalone', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_production_hardening_security', 'label' => 'Production Hardening Security', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_enterprise_certification', 'label' => 'Enterprise Certification', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_enterprise_certification_standalone', 'label' => 'Enterprise Certification Standalone', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_enterprise_certification_security', 'label' => 'Enterprise Certification Security', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_enterprise_certification_api', 'label' => 'Enterprise Certification Api', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_enterprise_certification_release_notes', 'label' => 'Enterprise Certification Release Notes', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_sms_dashboard', 'label' => 'Sms Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_send_sms', 'label' => 'Send Sms', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_bulk_sms', 'label' => 'Bulk Sms', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_scheduled_sms', 'label' => 'Scheduled Sms', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_sms_packages', 'label' => 'Sms Packages', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_business_wallets', 'label' => 'Business Wallets', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_credit_refills', 'label' => 'Credit Refills', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_refills', 'label' => 'Refills', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_credit_transactions', 'label' => 'Credit Transactions', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_sms_clients', 'label' => 'Sms Clients', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_reseller_dashboard', 'label' => 'Reseller Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_api_tokens', 'label' => 'Api Tokens', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_api_logs', 'label' => 'Api Logs', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_api_documentation', 'label' => 'Api Documentation', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_whatsapp_dashboard', 'label' => 'Whatsapp Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_send_whatsapp', 'label' => 'Send Whatsapp', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_bulk_whatsapp', 'label' => 'Bulk Whatsapp', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_scheduled_whatsapp', 'label' => 'Scheduled Whatsapp', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_whatsapp_templates', 'label' => 'Whatsapp Templates', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_whatsapp_profiles', 'label' => 'Whatsapp Profiles', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_push_dashboard', 'label' => 'Push Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_send_push', 'label' => 'Send Push', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_bulk_push', 'label' => 'Bulk Push', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_scheduled_push', 'label' => 'Scheduled Push', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_push_devices', 'label' => 'Push Devices', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_push_templates', 'label' => 'Push Templates', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_in_app_dashboard', 'label' => 'In App Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_send_in_app', 'label' => 'Send In App', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_in_app_inbox', 'label' => 'In App Inbox', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_in_app_templates', 'label' => 'In App Templates', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_chat_dashboard', 'label' => 'Chat Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_live_chat', 'label' => 'Live Chat', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_internal_messaging', 'label' => 'Internal Messaging', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_chat_templates', 'label' => 'Chat Templates', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_automation_dashboard', 'label' => 'Automation Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_automation_rules', 'label' => 'Automation Rules', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_automation_events', 'label' => 'Automation Events', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_workflow_dashboard', 'label' => 'Workflow Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_workflow_events', 'label' => 'Workflow Events', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_workflow_rules', 'label' => 'Workflow Rules', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_workflow_event_log', 'label' => 'Workflow Event Log', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_analytics_dashboard', 'label' => 'Analytics Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_message_history_report', 'label' => 'Message History Report', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_provider_performance_report', 'label' => 'Provider Performance Report', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_campaign_performance_report', 'label' => 'Campaign Performance Report', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_api_gateway_dashboard', 'label' => 'Api Gateway Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_communication_audit_centre', 'label' => 'Communication Audit Centre', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_delivery_reports', 'label' => 'Delivery Reports', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_profit_reports', 'label' => 'Profit Reports', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_executive_centre', 'label' => 'Executive Centre', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_notification_centre', 'label' => 'Notification Centre', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_event_registry', 'label' => 'Event Registry', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_provider_health', 'label' => 'Provider Health', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_cost_centre', 'label' => 'Cost Centre', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_advanced_scheduler', 'label' => 'Advanced Scheduler', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_public_api_platform', 'label' => 'Public Api Platform', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_mobile_hooks', 'label' => 'Mobile Hooks', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_marketplace_architecture', 'label' => 'Marketplace Architecture', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_final_audit', 'label' => 'Final Audit', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'communicationhub_health', 'label' => 'Health', 'type' => 'page', 'source' => 'module_pages'],
];
