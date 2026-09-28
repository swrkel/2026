DELETE FROM permissions WHERE name IN (
 'distributionnew.server_testing.view',
 'distributionnew.server_testing.run',
 'distributionnew.server_testing.export'
);
DROP TABLE IF EXISTS `disnew_server_testing_runs`;
