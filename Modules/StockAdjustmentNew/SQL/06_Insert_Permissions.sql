INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'stock_adjustment_new.view','web',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='stock_adjustment_new.view' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'stock_adjustment_new.create','web',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='stock_adjustment_new.create' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'stock_adjustment_new.edit','web',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='stock_adjustment_new.edit' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'stock_adjustment_new.submit','web',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='stock_adjustment_new.submit' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'stock_adjustment_new.approve','web',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='stock_adjustment_new.approve' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'stock_adjustment_new.reject','web',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='stock_adjustment_new.reject' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'stock_adjustment_new.post','web',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='stock_adjustment_new.post' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'stock_adjustment_new.reports','web',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='stock_adjustment_new.reports' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'stock_adjustment_new.settings','web',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='stock_adjustment_new.settings' AND `guard_name`='web');
