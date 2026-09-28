-- RESTNEW 030 FINAL PERMISSIONS
-- Use INSERT IGNORE / ON DUPLICATE KEY according to your permissions table structure.

INSERT IGNORE INTO permissions (name, guard_name, created_at, updated_at) VALUES
('restaurantnew.view', 'web', NOW(), NOW()),
('restaurantnew.create', 'web', NOW(), NOW()),
('restaurantnew.update', 'web', NOW(), NOW()),
('restaurantnew.delete', 'web', NOW(), NOW()),
('restaurantnew.pos.create_sale', 'web', NOW(), NOW()),
('restaurantnew.kitchen.view_received_orders', 'web', NOW(), NOW()),
('restaurantnew.kitchen.print_kot', 'web', NOW(), NOW()),
('restaurantnew.billing.finalize', 'web', NOW(), NOW()),
('restaurantnew.reports.view', 'web', NOW(), NOW()),
('restaurantnew.superadmin.manage', 'web', NOW(), NOW());
