INSERT INTO permissions(name,guard_name,created_at,updated_at)
SELECT p.name,'web',NOW(),NOW() FROM (SELECT 'airline_ticketing_new.backups.view' AS name
UNION ALL SELECT 'airline_ticketing_new.backups.create' AS name
UNION ALL SELECT 'airline_ticketing_new.monitoring.view' AS name
UNION ALL SELECT 'airline_ticketing_new.deployment.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.scheduler.run' AS name
UNION ALL SELECT 'airline_ticketing_new.certification.run' AS name) p
WHERE NOT EXISTS(
    SELECT 1 FROM permissions x WHERE x.name=p.name AND x.guard_name='web'
);
