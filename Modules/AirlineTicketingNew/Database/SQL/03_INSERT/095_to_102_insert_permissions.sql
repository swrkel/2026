INSERT INTO permissions(name,guard_name,created_at,updated_at) SELECT p.name,'web',NOW(),NOW() FROM (SELECT 'airline_ticketing_new.certification.run' AS name
UNION ALL SELECT 'airline_ticketing_new.final_install.run' AS name
UNION ALL SELECT 'airline_ticketing_new.production_readiness.run' AS name
UNION ALL SELECT 'airline_ticketing_new.final_optimization.run' AS name) p WHERE NOT EXISTS (SELECT 1 FROM permissions x WHERE x.name=p.name AND x.guard_name='web');
