INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.haccp.view', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.haccp.view');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.haccp.temperature', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.haccp.temperature');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.haccp.corrective_actions', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.haccp.corrective_actions');
