INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.support.readiness', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.support.readiness');
