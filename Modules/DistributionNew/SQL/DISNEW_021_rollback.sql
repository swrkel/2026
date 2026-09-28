-- DISNEW_021 rollback. Run inside each tenant database only if this stage must be removed.
DROP TABLE IF EXISTS `disnew_performance_cache`;
DROP TABLE IF EXISTS `disnew_management_dashboard_widgets`;
DROP TABLE IF EXISTS `disnew_visit_plan_lines`;
DROP TABLE IF EXISTS `disnew_visit_plans`;
DROP TABLE IF EXISTS `disnew_stock_reconciliation_lines`;
DROP TABLE IF EXISTS `disnew_stock_reconciliation_runs`;
DROP TABLE IF EXISTS `disnew_workflow_validation_items`;
DROP TABLE IF EXISTS `disnew_workflow_validation_runs`;
DELETE FROM `permissions` WHERE `name` IN (
 'distribution_new.workflow_validation.view',
 'distribution_new.stock_reconciliation.view',
 'distribution_new.stock_reconciliation.create',
 'distribution_new.visit_plan.view',
 'distribution_new.visit_plan.create',
 'distribution_new.management_dashboard.view',
 'distribution_new.performance_cache.manage'
);
