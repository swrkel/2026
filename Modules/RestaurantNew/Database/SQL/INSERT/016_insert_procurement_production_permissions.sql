INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.procurement.view', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.procurement.view');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.purchase_orders.manage', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.purchase_orders.manage');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.goods_receipts.manage', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.goods_receipts.manage');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.production_batches.manage', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.production_batches.manage');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'restaurantnew.commissary_transfers.manage', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='restaurantnew.commissary_transfers.manage');
