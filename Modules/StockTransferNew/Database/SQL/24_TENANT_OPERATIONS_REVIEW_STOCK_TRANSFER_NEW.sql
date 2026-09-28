-- StockTransferNew STN_024 - Tenant operations review permissions/menu helper
-- Run in every tenant database after previous StockTransferNew SQL parcels.

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stocktransfernew.admin.operations_review', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stocktransfernew.admin.operations_review');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'stocktransfernew.admin.operations_review.export', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'stocktransfernew.admin.operations_review.export');

-- Optional route/menu note:
-- Add route include for Modules/StockTransferNew/Routes/admin_operations_review.php in the module RouteServiceProvider if your installer does not auto-load route fragments.
