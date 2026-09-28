INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.online_ordering.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.online_ordering.view');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.online_ordering.create', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.online_ordering.create');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.online_kitchen_queue.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.online_kitchen_queue.view');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.online_ordering.change_status', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.online_ordering.change_status');
