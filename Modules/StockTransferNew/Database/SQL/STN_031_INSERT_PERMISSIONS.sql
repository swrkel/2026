INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'stock_transfer_new.carrier_invoice.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stock_transfer_new.carrier_invoice.view');

INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'stock_transfer_new.carrier_invoice.create', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stock_transfer_new.carrier_invoice.create');

INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'stock_transfer_new.carrier_invoice.approve', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stock_transfer_new.carrier_invoice.approve');

INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'stock_transfer_new.carrier_invoice.cancel', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stock_transfer_new.carrier_invoice.cancel');
