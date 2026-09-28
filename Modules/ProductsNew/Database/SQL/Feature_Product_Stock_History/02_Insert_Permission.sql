INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'products_new.stock_history','web',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='products_new.stock_history' AND `guard_name`='web');
