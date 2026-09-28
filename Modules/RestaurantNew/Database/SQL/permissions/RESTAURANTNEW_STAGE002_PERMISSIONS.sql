INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT p.name, 'web', NOW(), NOW()
FROM (
    SELECT 'restaurant_new.settings.view' AS name UNION ALL
    SELECT 'restaurant_new.settings.update' UNION ALL
    SELECT 'restaurant_new.dining_area.manage' UNION ALL
    SELECT 'restaurant_new.table.manage' UNION ALL
    SELECT 'restaurant_new.kitchen_section.manage' UNION ALL
    SELECT 'restaurant_new.order_type.manage' UNION ALL
    SELECT 'restaurant_new.numbering.manage'
) p
WHERE NOT EXISTS (SELECT 1 FROM permissions existing WHERE existing.name = p.name AND existing.guard_name = 'web');
