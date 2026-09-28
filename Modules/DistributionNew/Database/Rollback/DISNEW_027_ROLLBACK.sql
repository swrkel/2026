DROP TABLE IF EXISTS `disnew_server_fix_pack_logs`;
DELETE FROM `permissions` WHERE `name` IN (
  'distribution_new.server_testing.view',
  'distribution_new.server_testing.run'
);
