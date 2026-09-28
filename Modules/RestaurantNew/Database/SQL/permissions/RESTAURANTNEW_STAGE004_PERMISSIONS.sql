INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.pos.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.pos.view');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.pos.create_order', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.pos.create_order');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.pos.add_payment', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.pos.add_payment');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.pos.close_order', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.pos.close_order');
