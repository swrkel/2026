-- RestaurantNew RESTNEW_020 permissions
-- Use INSERT IGNORE to avoid duplicate permission rows.

INSERT IGNORE INTO permissions (name, guard_name, created_at, updated_at) VALUES
('restaurantnew.view', 'web', NOW(), NOW()),
('restaurantnew.setup.manage', 'web', NOW(), NOW()),
('restaurantnew.menu.manage', 'web', NOW(), NOW()),
('restaurantnew.pos.create_sale', 'web', NOW(), NOW()),
('restaurantnew.waiter.create_order', 'web', NOW(), NOW()),
('restaurantnew.cashier.create_sale', 'web', NOW(), NOW()),
('restaurantnew.kitchen.view_received_orders', 'web', NOW(), NOW()),
('restaurantnew.kitchen.print_kot', 'web', NOW(), NOW()),
('restaurantnew.kitchen.print_bill', 'web', NOW(), NOW()),
('restaurantnew.billing.manage', 'web', NOW(), NOW()),
('restaurantnew.inventory.manage', 'web', NOW(), NOW()),
('restaurantnew.reports.view', 'web', NOW(), NOW()),
('restaurantnew.command_center.view', 'web', NOW(), NOW()),
('restaurantnew.superadmin.manage', 'web', NOW(), NOW());
