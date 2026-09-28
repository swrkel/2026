-- DISNEW_012_ROLLBACK.sql
DELETE FROM `disnew_menu_items` WHERE `menu_key` LIKE 'distributionnew.%';
DELETE FROM `disnew_module_ui_status` WHERE `module_key` = 'distribution_new';
-- Permissions are not deleted by default because they may already be assigned to roles.
