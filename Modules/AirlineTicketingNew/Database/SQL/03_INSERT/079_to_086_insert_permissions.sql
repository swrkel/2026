INSERT INTO permissions(name,guard_name,created_at,updated_at)
SELECT p.name,'web',NOW(),NOW() FROM (SELECT 'airline_ticketing_new.ui.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.permission_profiles.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.security_audit.view' AS name
UNION ALL SELECT 'airline_ticketing_new.encrypted_settings.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.api_security.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.mobile.view' AS name
UNION ALL SELECT 'airline_ticketing_new.enterprise_settings.view' AS name
UNION ALL SELECT 'airline_ticketing_new.enterprise_settings.manage' AS name) p
WHERE NOT EXISTS(
 SELECT 1 FROM permissions x WHERE x.name=p.name AND x.guard_name='web'
);
