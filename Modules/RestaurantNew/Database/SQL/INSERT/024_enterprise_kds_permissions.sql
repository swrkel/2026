INSERT INTO permissions (name, guard_name, created_at, updated_at) VALUES
('restaurantnew.kds_enterprise.view', 'web', NOW(), NOW()),
('restaurantnew.kds_enterprise.manage', 'web', NOW(), NOW()),
('restaurantnew.kds_enterprise.status', 'web', NOW(), NOW()),
('restaurantnew.kds_enterprise.reprint_kot', 'web', NOW(), NOW()),
('restaurantnew.kds_enterprise.cancel_item', 'web', NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);
