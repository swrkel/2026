INSERT INTO permissions(name,guard_name,created_at,updated_at)
SELECT p.name,'web',NOW(),NOW() FROM (SELECT 'airline_ticketing_new.portal.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.b2b_agents.view' AS name
UNION ALL SELECT 'airline_ticketing_new.b2b_agents.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.b2b_wallet.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.api.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.mobile.view' AS name
UNION ALL SELECT 'airline_ticketing_new.analytics.view' AS name
UNION ALL SELECT 'airline_ticketing_new.analytics.generate' AS name) p
WHERE NOT EXISTS(SELECT 1 FROM permissions x WHERE x.name=p.name AND x.guard_name='web');
