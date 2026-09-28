-- DANGER: Removes ALL Stock Taking - New data from the current tenant database.
-- This file is intentionally excluded from the Master Installer.
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS `stk_audit_logs`,`stk_import_batches`,`stk_schedules`,`stk_share_dispatches`,`stk_share_links`,`stk_inventory_movements`,`stk_approvals`,`stk_assignments`,`stk_counts`,`stk_session_lines`,`stk_sessions`,`stk_template_lines`,`stk_templates`,`stk_number_sequences`,`stk_settings`;
DELETE FROM `role_has_permissions` WHERE `permission_id` IN (SELECT `id` FROM `permissions` WHERE `name` LIKE 'stock_taking_new.%');
DELETE FROM `model_has_permissions` WHERE `permission_id` IN (SELECT `id` FROM `permissions` WHERE `name` LIKE 'stock_taking_new.%');
DELETE FROM `permissions` WHERE `name` LIKE 'stock_taking_new.%';
SET FOREIGN_KEY_CHECKS=1;
