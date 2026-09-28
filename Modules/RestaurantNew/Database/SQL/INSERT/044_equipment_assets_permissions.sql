INSERT INTO permissions (name, guard_name, created_at, updated_at) VALUES
('restaurantnew.equipment.view','web',NOW(),NOW()),
('restaurantnew.equipment.create','web',NOW(),NOW()),
('restaurantnew.equipment.update','web',NOW(),NOW()),
('restaurantnew.equipment.work_orders','web',NOW(),NOW()),
('restaurantnew.equipment.schedules','web',NOW(),NOW()),
('restaurantnew.equipment.spare_parts','web',NOW(),NOW()),
('restaurantnew.equipment.alerts','web',NOW(),NOW()),
('restaurantnew.equipment.reports','web',NOW(),NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);
