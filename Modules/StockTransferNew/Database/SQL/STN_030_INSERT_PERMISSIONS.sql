INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'stock_transfer_new.delivery.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stock_transfer_new.delivery.view');

INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'stock_transfer_new.delivery.confirm', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stock_transfer_new.delivery.confirm');

INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'stock_transfer_new.delivery.damage', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stock_transfer_new.delivery.damage');
