INSERT INTO permissions(name,guard_name,created_at,updated_at)
SELECT p.name,'web',NOW(),NOW() FROM (SELECT 'airline_ticketing_new.performance.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.cache.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.queue.monitor' AS name
UNION ALL SELECT 'airline_ticketing_new.diagnostics.view' AS name
UNION ALL SELECT 'airline_ticketing_new.health_monitor.view' AS name
UNION ALL SELECT 'airline_ticketing_new.upgrade.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.installer.run' AS name
UNION ALL SELECT 'airline_ticketing_new.deployment.validate' AS name) p
WHERE NOT EXISTS (
    SELECT 1 FROM permissions x WHERE x.name=p.name AND x.guard_name='web'
);
