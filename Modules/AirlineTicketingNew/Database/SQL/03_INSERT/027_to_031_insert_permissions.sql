INSERT INTO permissions(name,guard_name,created_at,updated_at)
SELECT p.name,'web',NOW(),NOW() FROM (SELECT 'airline_ticketing_new.gds.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.flight_schedules.view' AS name
UNION ALL SELECT 'airline_ticketing_new.flight_schedules.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.flight_disruptions.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.documents.view' AS name
UNION ALL SELECT 'airline_ticketing_new.documents.create' AS name
UNION ALL SELECT 'airline_ticketing_new.documents.delete' AS name
UNION ALL SELECT 'airline_ticketing_new.workflow.view' AS name
UNION ALL SELECT 'airline_ticketing_new.workflow.approve' AS name
UNION ALL SELECT 'airline_ticketing_new.workflow.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.admin.features' AS name
UNION ALL SELECT 'airline_ticketing_new.admin.api_credentials' AS name) p
WHERE NOT EXISTS(SELECT 1 FROM permissions x WHERE x.name=p.name AND x.guard_name='web');
