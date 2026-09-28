INSERT IGNORE INTO permissions (name, guard_name, created_at, updated_at) VALUES
('restaurantnew.command_center.view', 'web', NOW(), NOW()),
('restaurantnew.command_center.restaurant', 'web', NOW(), NOW()),
('restaurantnew.command_center.kitchen', 'web', NOW(), NOW()),
('restaurantnew.command_center.cashier', 'web', NOW(), NOW()),
('restaurantnew.command_center.waiter', 'web', NOW(), NOW()),
('restaurantnew.command_center.manager', 'web', NOW(), NOW()),
('restaurantnew.command_center.executive', 'web', NOW(), NOW()),
('restaurantnew.dashboard_alerts.manage', 'web', NOW(), NOW());
