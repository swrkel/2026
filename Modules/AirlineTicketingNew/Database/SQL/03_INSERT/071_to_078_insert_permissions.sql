INSERT INTO permissions(name,guard_name,created_at,updated_at)
SELECT p.name,'web',NOW(),NOW() FROM (SELECT 'airline_ticketing_new.reporting.executive' AS name
UNION ALL SELECT 'airline_ticketing_new.reporting.centre' AS name
UNION ALL SELECT 'airline_ticketing_new.reporting.bi' AS name
UNION ALL SELECT 'airline_ticketing_new.reporting.forecasting' AS name
UNION ALL SELECT 'airline_ticketing_new.reporting.scheduled' AS name
UNION ALL SELECT 'airline_ticketing_new.reporting.builder' AS name
UNION ALL SELECT 'airline_ticketing_new.reporting.kpi_alerts' AS name
UNION ALL SELECT 'airline_ticketing_new.reporting.export' AS name) p
WHERE NOT EXISTS(
 SELECT 1 FROM permissions x WHERE x.name=p.name AND x.guard_name='web'
);
