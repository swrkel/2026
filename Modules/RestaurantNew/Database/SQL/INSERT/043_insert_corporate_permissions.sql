-- RestaurantNew Stage 043 Corporate Account permissions
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.corporate.view','web',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.corporate.view');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.corporate.create','web',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.corporate.create');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.corporate.invoice','web',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.corporate.invoice');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.corporate.payment','web',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.corporate.payment');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.corporate.statement','web',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.corporate.statement');
