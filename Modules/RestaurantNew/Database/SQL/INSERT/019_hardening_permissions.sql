INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.hardening.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.hardening.view');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.hardening.run', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.hardening.run');
