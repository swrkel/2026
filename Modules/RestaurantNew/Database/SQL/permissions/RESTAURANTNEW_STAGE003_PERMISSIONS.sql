-- RestaurantNew Stage 003 PERMISSIONS SQL
-- Idempotent permission insert pattern; adjust column names if the base permissions table differs.
INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurantnew.menu.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.menu.view');
INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurantnew.menu.create', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.menu.create');
INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurantnew.menu.update', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.menu.update');
INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurantnew.menu.delete', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.menu.delete');
