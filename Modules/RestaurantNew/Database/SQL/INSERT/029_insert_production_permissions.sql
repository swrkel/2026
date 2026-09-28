-- RestaurantNew Stage 029 INSERT SQL - idempotent permissions
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.production.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.production.view');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.production.create', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.production.create');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.production.approve', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.production.approve');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.production.distribute', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.production.distribute');
