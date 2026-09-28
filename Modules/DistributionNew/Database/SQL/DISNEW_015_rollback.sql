-- DISNEW_015 rollback only. Run with care.
DROP TABLE IF EXISTS `disnew_export_jobs`;
DROP TABLE IF EXISTS `disnew_report_delivery_logs`;
DROP TABLE IF EXISTS `disnew_scheduled_reports`;
DROP TABLE IF EXISTS `disnew_kpi_metrics`;
DROP TABLE IF EXISTS `disnew_analytics_snapshots`;
DELETE FROM `disnew_module_permissions` WHERE `permission_key` IN (
'distribution_new.analytics.view',
'distribution_new.analytics.export',
'distribution_new.scheduled_reports.manage',
'distribution_new.executive_dashboard.view'
);
