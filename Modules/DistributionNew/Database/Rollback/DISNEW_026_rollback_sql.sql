-- DISNEW_026 rollback SQL
DELETE FROM permissions WHERE name IN ('disnew.stabilization.view', 'disnew.stabilization.repair');
DROP TABLE IF EXISTS `disnew_server_checks`;
