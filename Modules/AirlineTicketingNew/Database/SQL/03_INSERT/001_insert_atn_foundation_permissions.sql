-- ATN-001 PERMISSIONS
-- Uses INSERT ... SELECT to remain safe when permissions already exist.
INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT p.name, 'web', NOW(), NOW()
FROM (
    SELECT 'airline_ticketing_new.access' AS name
    UNION ALL SELECT 'airline_ticketing_new.dashboard.view'
    UNION ALL SELECT 'airline_ticketing_new.settings.manage'
    UNION ALL SELECT 'airline_ticketing_new.audit.view'
) p
WHERE NOT EXISTS (
    SELECT 1 FROM `permissions` existing
    WHERE existing.name = p.name AND existing.guard_name = 'web'
);
