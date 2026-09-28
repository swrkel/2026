-- Statement 5
INSERT INTO permissions (`name`,`guard_name`,`created_at`,`updated_at`) SELECT permission_name,'web',NOW(),NOW() FROM (SELECT 'products_new.access' permission_name UNION ALL SELECT 'products_new.view' UNION ALL SELECT 'products_new.create' UNION ALL SELECT 'products_new.update' UNION ALL SELECT 'products_new.delete' UNION ALL SELECT 'products_new.dashboard' UNION ALL SELECT 'products_new.stock_center' UNION ALL SELECT 'products_new.barcode_center' UNION ALL SELECT 'products_new.import_export' UNION ALL SELECT 'products_new.reports' UNION ALL SELECT 'products_new.settings') p WHERE NOT EXISTS (SELECT 1 FROM permissions x WHERE x.name=p.permission_name AND x.guard_name='web');

-- Statement 6
-- ProductsNew_STAGE002_PRODUCTSNEW_002_SETTINGS_CENTRE.sql
-- Purpose: Settings Centre permissions for Products New standalone module.
-- Run inside each tenant database. Do not prefix a database name.

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.settings.categories.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.settings.categories.view' AND guard_name = 'web');

-- Statement 7
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.settings.categories.create', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.settings.categories.create' AND guard_name = 'web');

-- Statement 8
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.settings.brands.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.settings.brands.view' AND guard_name = 'web');

-- Statement 9
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.settings.brands.create', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.settings.brands.create' AND guard_name = 'web');

-- Statement 10
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.settings.units.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.settings.units.view' AND guard_name = 'web');

-- Statement 11
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.settings.units.create', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.settings.units.create' AND guard_name = 'web');

-- Statement 12
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.settings.variations.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.settings.variations.view' AND guard_name = 'web');

-- Statement 13
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.settings.variations.create', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.settings.variations.create' AND guard_name = 'web');

-- Statement 14
-- ProductsNew_STAGE003_PRODUCTSNEW_003_PRODUCT_MASTER.sql
-- Purpose: Product Master permissions and safe standalone metadata compatibility for Products New.
-- Run inside each tenant database. Do not prefix a database name.

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.products.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.products.view' AND guard_name = 'web');

-- Statement 15
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.products.create', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.products.create' AND guard_name = 'web');

-- Statement 16
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.products.update', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.products.update' AND guard_name = 'web');

-- Statement 17
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.products.disable', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.products.disable' AND guard_name = 'web');

-- Statement 18
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.products.360_view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.products.360_view' AND guard_name = 'web');

-- Statement 23
INSERT IGNORE INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
('products_new.inventory.view', 'web', NOW(), NOW()),
('products_new.inventory.create', 'web', NOW(), NOW()),
('products_new.opening_stock.view', 'web', NOW(), NOW()),
('products_new.opening_stock.create', 'web', NOW(), NOW()),
('products_new.price_center.view', 'web', NOW(), NOW()),
('products_new.price_center.create', 'web', NOW(), NOW());

-- Statement 27
INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'products_new.media.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'products_new.media.view');

-- Statement 28
INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'products_new.media.create', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'products_new.media.create');

-- Statement 29
INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'products_new.barcode.templates', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'products_new.barcode.templates');

-- Statement 38
INSERT IGNORE INTO permissions (name, guard_name, created_at, updated_at) VALUES
('products_new.intelligence.view','web',NOW(),NOW()),
('products_new.relationships.manage','web',NOW(),NOW()),
('products_new.workflow.manage','web',NOW(),NOW()),
('products_new.duplicates.manage','web',NOW(),NOW()),
('products_new.notes.manage','web',NOW(),NOW()),
('products_new.availability.view','web',NOW(),NOW());

-- Statement 43
INSERT IGNORE INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
('products_new.batch.view','web',NOW(),NOW()),
('products_new.batch.create','web',NOW(),NOW()),
('products_new.batch.adjust','web',NOW(),NOW()),
('products_new.expiry.view','web',NOW(),NOW()),
('products_new.expiry.resolve','web',NOW(),NOW()),
('products_new.recall.view','web',NOW(),NOW()),
('products_new.recall.create','web',NOW(),NOW()),
('products_new.recall.close','web',NOW(),NOW()),
('products_new.reports.batch','web',NOW(),NOW());

-- Statement 49
INSERT IGNORE INTO products_new_permissions (name, display_name, module, created_at, updated_at) VALUES
('products_new.serial.view','Products New Serial View','Products New',NOW(),NOW()),
('products_new.serial.create','Products New Serial Create','Products New',NOW(),NOW()),
('products_new.warranty.view','Products New Warranty View','Products New',NOW(),NOW()),
('products_new.warranty.manage','Products New Warranty Manage','Products New',NOW(),NOW()),
('products_new.ownership.view','Products New Ownership View','Products New',NOW(),NOW());

-- Statement 57
INSERT IGNORE INTO permissions (name, guard_name, created_at, updated_at) VALUES
('products_new.import_export.view', 'web', NOW(), NOW()),
('products_new.import_export.import', 'web', NOW(), NOW()),
('products_new.import_export.export', 'web', NOW(), NOW()),
('products_new.data_cleanup.view', 'web', NOW(), NOW()),
('products_new.data_cleanup.manage', 'web', NOW(), NOW());

-- Statement 60
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.reports.index', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.reports.index' AND guard_name = 'web');

-- Statement 61
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.reports.movement', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.reports.movement' AND guard_name = 'web');

-- Statement 62
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.reports.profitability', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.reports.profitability' AND guard_name = 'web');

-- Statement 63
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.reports.aging', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.reports.aging' AND guard_name = 'web');

-- Statement 64
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.reports.fast_slow_dead', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.reports.fast_slow_dead' AND guard_name = 'web');

-- Statement 65
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.reports.negative_overstock', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.reports.negative_overstock' AND guard_name = 'web');

-- Statement 66
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.reports.expiry', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.reports.expiry' AND guard_name = 'web');

-- Statement 67
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.reports.serial', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.reports.serial' AND guard_name = 'web');

-- Statement 68
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.reports.category_brand', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.reports.category_brand' AND guard_name = 'web');

-- Statement 69
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.reports.price_history', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.reports.price_history' AND guard_name = 'web');

-- Statement 70
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.reports.inventory_turnover', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.reports.inventory_turnover' AND guard_name = 'web');

-- Statement 71
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.reports.abc_xyz', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.reports.abc_xyz' AND guard_name = 'web');

-- Statement 72
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.reports.reorder_recommendation', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.reports.reorder_recommendation' AND guard_name = 'web');

-- Statement 75
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.kpi.index', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.kpi.index' AND guard_name = 'web');

-- Statement 76
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.kpi.snapshot', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.kpi.snapshot' AND guard_name = 'web');

-- Statement 77
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.dashboard.management', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.dashboard.management' AND guard_name = 'web');

-- Statement 78
/*
PRODUCTSNEW_012 - Production Hardening & Final Standalone Audit
Run this in each tenant database where Products New is enabled.
No database name is hardcoded.
*/

-- Permissions for final audit and integration bridge pages
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.production_audit.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.production_audit.view' AND guard_name = 'web');

-- Statement 79
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.integration_bridge.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.integration_bridge.view' AND guard_name = 'web');

-- Statement 80
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.integration_bridge.api', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.integration_bridge.api' AND guard_name = 'web');

-- Statement 84
INSERT IGNORE INTO permissions (name, guard_name, created_at, updated_at) VALUES
('products_new.migration_readiness.view', 'web', NOW(), NOW()),
('products_new.legacy_comparison.view', 'web', NOW(), NOW()),
('products_new.testing_checklist.view', 'web', NOW(), NOW());

-- Statement 85
-- Products New Stage 014 - Deployment Support
-- Global tenant SQL. Run inside each tenant database only. No database name is hardcoded.

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'products_new.deployment_readiness', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'products_new.deployment_readiness');

-- Statement 102
INSERT IGNORE INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
('products_new.command_center.view', 'web', NOW(), NOW()),
('products_new.command_center.snapshot', 'web', NOW(), NOW()),
('products_new.command_center.actions', 'web', NOW(), NOW());

-- =====================================================================
-- 03 Aug 2026 - Products New purchase-price visibility permission
-- =====================================================================
INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'products_new.purchase_price.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1
    FROM `permissions`
    WHERE `name` = 'products_new.purchase_price.view'
      AND `guard_name` = 'web'
);

