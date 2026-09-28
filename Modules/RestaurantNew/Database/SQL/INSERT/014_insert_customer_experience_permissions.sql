INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurantnew.qr_menu.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurantnew.qr_menu.view');
INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurantnew.qr_menu.create', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurantnew.qr_menu.create');
INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurantnew.digital_receipt.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurantnew.digital_receipt.view');
INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurantnew.feedback.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurantnew.feedback.view');
