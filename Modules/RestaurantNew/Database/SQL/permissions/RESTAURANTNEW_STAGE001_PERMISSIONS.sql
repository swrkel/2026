-- Idempotent permission insert template for systems using the permissions table.
INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT p.name, 'web', NOW(), NOW()
FROM (
    SELECT 'restaurant_new.view' AS name UNION ALL
    SELECT 'restaurant_new.settings.view' UNION ALL
    SELECT 'restaurant_new.settings.update' UNION ALL
    SELECT 'restaurant_new.dining_area.manage' UNION ALL
    SELECT 'restaurant_new.table.manage' UNION ALL
    SELECT 'restaurant_new.menu.manage' UNION ALL
    SELECT 'restaurant_new.order.view' UNION ALL
    SELECT 'restaurant_new.order.create' UNION ALL
    SELECT 'restaurant_new.order.update' UNION ALL
    SELECT 'restaurant_new.order.cancel' UNION ALL
    SELECT 'restaurant_new.kot.view' UNION ALL
    SELECT 'restaurant_new.kot.print' UNION ALL
    SELECT 'restaurant_new.bill.view' UNION ALL
    SELECT 'restaurant_new.bill.finalize' UNION ALL
    SELECT 'restaurant_new.report.view'
) p
WHERE NOT EXISTS (SELECT 1 FROM permissions existing WHERE existing.name = p.name AND existing.guard_name = 'web');
