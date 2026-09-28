-- Stock Transfer-New final permission insert SQL
-- Tenant database only. Uses duplicate-safe insert pattern.

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stock_transfer_new.readiness.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stock_transfer_new.readiness.view');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stock_transfer_new.sql_checklist.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stock_transfer_new.sql_checklist.view');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stock_transfer_new.export_checklist.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stock_transfer_new.export_checklist.view');
