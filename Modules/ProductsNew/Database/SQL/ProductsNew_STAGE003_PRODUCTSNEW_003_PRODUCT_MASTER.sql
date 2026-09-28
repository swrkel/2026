-- ProductsNew_STAGE003_PRODUCTSNEW_003_PRODUCT_MASTER.sql
-- Purpose: Product Master permissions and safe standalone metadata compatibility for Products New.
-- Run inside each tenant database. Do not prefix a database name.

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.products.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.products.view' AND guard_name = 'web');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.products.create', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.products.create' AND guard_name = 'web');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.products.update', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.products.update' AND guard_name = 'web');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.products.disable', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.products.disable' AND guard_name = 'web');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.products.360_view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.products.360_view' AND guard_name = 'web');
