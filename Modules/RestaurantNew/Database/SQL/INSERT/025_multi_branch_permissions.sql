INSERT INTO permissions (name, guard_name, created_at, updated_at) VALUES
('restaurantnew.multi_branch.view', 'web', NOW(), NOW()),
('restaurantnew.multi_branch.manage', 'web', NOW(), NOW()),
('restaurantnew.branch_transfer.create', 'web', NOW(), NOW()),
('restaurantnew.branch_transfer.approve', 'web', NOW(), NOW()),
('restaurantnew.branch_transfer.dispatch', 'web', NOW(), NOW()),
('restaurantnew.branch_transfer.receive', 'web', NOW(), NOW()),
('restaurantnew.branch_comparison.view', 'web', NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);
