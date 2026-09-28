INSERT INTO permissions(name,guard_name,created_at,updated_at)
SELECT p.name,'web',NOW(),NOW() FROM (SELECT 'airline_ticketing_new.accounting.journals.view' AS name
UNION ALL SELECT 'airline_ticketing_new.accounting.journals.post' AS name
UNION ALL SELECT 'airline_ticketing_new.account_mappings.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.currency_rates.view' AS name
UNION ALL SELECT 'airline_ticketing_new.currency_rates.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.currency_revaluation.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.bsp.periods.view' AS name
UNION ALL SELECT 'airline_ticketing_new.bsp.periods.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.bsp.adjustments.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.crm.interactions.view' AS name
UNION ALL SELECT 'airline_ticketing_new.crm.interactions.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.loyalty.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.management_dashboard.view' AS name) p
WHERE NOT EXISTS(SELECT 1 FROM permissions x WHERE x.name=p.name AND x.guard_name='web');
