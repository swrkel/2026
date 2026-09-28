INSERT INTO permissions (name, guard_name, created_at, updated_at) VALUES
('restaurantnew.reports.view', 'web', NOW(), NOW()),
('restaurantnew.reports.sales', 'web', NOW(), NOW()),
('restaurantnew.reports.items', 'web', NOW(), NOW()),
('restaurantnew.reports.operations', 'web', NOW(), NOW()),
('restaurantnew.reports.payments', 'web', NOW(), NOW()),
('restaurantnew.reports.tax_service', 'web', NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);
