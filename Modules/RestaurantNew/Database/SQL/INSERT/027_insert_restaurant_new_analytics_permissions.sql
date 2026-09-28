INSERT INTO permissions (name, guard_name, created_at, updated_at) VALUES
('restaurantnew.analytics.view', 'web', NOW(), NOW()),
('restaurantnew.analytics.forecast', 'web', NOW(), NOW()),
('restaurantnew.analytics.export', 'web', NOW(), NOW()),
('restaurantnew.analytics.menu_profitability', 'web', NOW(), NOW()),
('restaurantnew.analytics.table_utilization', 'web', NOW(), NOW()),
('restaurantnew.analytics.food_cost', 'web', NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);
