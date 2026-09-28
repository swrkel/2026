INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT p.name, 'web', NOW(), NOW()
FROM (
    SELECT 'restaurant_new.staff.view' AS name UNION ALL
    SELECT 'restaurant_new.staff.create' UNION ALL
    SELECT 'restaurant_new.staff.update' UNION ALL
    SELECT 'restaurant_new.shift.view' UNION ALL
    SELECT 'restaurant_new.shift.open' UNION ALL
    SELECT 'restaurant_new.shift.close' UNION ALL
    SELECT 'restaurant_new.shift.cash_movement' UNION ALL
    SELECT 'restaurant_new.tips.manage' UNION ALL
    SELECT 'restaurant_new.service_charge.distribute' UNION ALL
    SELECT 'restaurant_new.report.staff_performance'
) p
WHERE NOT EXISTS (SELECT 1 FROM permissions existing WHERE existing.name = p.name AND existing.guard_name = 'web');
