INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.delivery.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.delivery.view');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.delivery.manage', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.delivery.manage');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.delivery.reports', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.delivery.reports');
