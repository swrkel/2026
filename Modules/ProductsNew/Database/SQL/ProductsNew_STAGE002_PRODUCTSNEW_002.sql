-- ProductsNew_STAGE002_PRODUCTSNEW_002_SETTINGS_CENTRE.sql
-- Purpose: Settings Centre permissions for Products New standalone module.
-- Run inside each tenant database. Do not prefix a database name.

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.settings.categories.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.settings.categories.view' AND guard_name = 'web');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.settings.categories.create', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.settings.categories.create' AND guard_name = 'web');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.settings.brands.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.settings.brands.view' AND guard_name = 'web');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.settings.brands.create', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.settings.brands.create' AND guard_name = 'web');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.settings.units.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.settings.units.view' AND guard_name = 'web');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.settings.units.create', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.settings.units.create' AND guard_name = 'web');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.settings.variations.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.settings.variations.view' AND guard_name = 'web');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.settings.variations.create', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.settings.variations.create' AND guard_name = 'web');
