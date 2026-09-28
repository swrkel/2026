INSERT INTO permissions (name, guard_name, created_at, updated_at) VALUES
('restaurantnew.ai.view', 'web', NOW(), NOW()),
('restaurantnew.ai.forecast', 'web', NOW(), NOW()),
('restaurantnew.ai.recommendations', 'web', NOW(), NOW()),
('restaurantnew.ai.inventory_signals', 'web', NOW(), NOW()),
('restaurantnew.ai.anomaly_logs', 'web', NOW(), NOW()),
('restaurantnew.ai.resolve', 'web', NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);
