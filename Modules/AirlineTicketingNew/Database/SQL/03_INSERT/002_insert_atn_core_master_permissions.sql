-- ATN-002 permissions
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT p.name,'web',NOW(),NOW()
FROM (
    SELECT 'airline_ticketing_new.airlines.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.airports.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.aircraft-types.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.travel-classes.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.routes.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.suppliers.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.agents.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.currencies.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.tax-rules.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.commission-rules.manage' AS name
) p
WHERE NOT EXISTS (
    SELECT 1 FROM `permissions` x WHERE x.name=p.name AND x.guard_name='web'
);
