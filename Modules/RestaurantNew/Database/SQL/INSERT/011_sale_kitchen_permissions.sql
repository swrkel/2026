INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.sale.create', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.sale.create');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.sale.print_bill', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.sale.print_bill');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.kitchen.screen', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.kitchen.screen');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.kitchen.print_kot', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.kitchen.print_kot');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.kitchen.update_status', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.kitchen.update_status');
