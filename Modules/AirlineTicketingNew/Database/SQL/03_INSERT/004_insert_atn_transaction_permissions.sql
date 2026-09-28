-- ATN-004 permissions
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT p.name,'web',NOW(),NOW()
FROM (
    SELECT 'airline_ticketing_new.quotations.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.quotations.create' AS name
    UNION ALL SELECT 'airline_ticketing_new.quotations.edit' AS name
    UNION ALL SELECT 'airline_ticketing_new.quotations.cancel' AS name
    UNION ALL SELECT 'airline_ticketing_new.reservations.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.reservations.create' AS name
    UNION ALL SELECT 'airline_ticketing_new.reservations.edit' AS name
    UNION ALL SELECT 'airline_ticketing_new.reservations.status' AS name
    UNION ALL SELECT 'airline_ticketing_new.reservations.cancel' AS name
) p
WHERE NOT EXISTS (
    SELECT 1 FROM `permissions` existing
    WHERE existing.name=p.name AND existing.guard_name='web'
);
