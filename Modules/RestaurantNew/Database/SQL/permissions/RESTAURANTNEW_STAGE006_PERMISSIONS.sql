INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurantnew.billing.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.billing.view');
INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurantnew.billing.create', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.billing.create');
INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurantnew.billing.payment', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.billing.payment');
INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurantnew.billing.void', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.billing.void');
INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurantnew.billing.refund', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'restaurantnew.billing.refund');
