INSERT INTO permissions (name, guard_name, created_at, updated_at) VALUES
('restaurantnew.reservations.view','web',NOW(),NOW()),
('restaurantnew.reservations.create','web',NOW(),NOW()),
('restaurantnew.reservations.update','web',NOW(),NOW()),
('restaurantnew.reservations.cancel','web',NOW(),NOW()),
('restaurantnew.reservations.check_in','web',NOW(),NOW()),
('restaurantnew.floor_plans.manage','web',NOW(),NOW()),
('restaurantnew.waitlist.manage','web',NOW(),NOW()),
('restaurantnew.reservation_reports.view','web',NOW(),NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);
