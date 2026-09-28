INSERT INTO permissions(name,guard_name,created_at,updated_at)
SELECT p.name,'web',NOW(),NOW() FROM (SELECT 'airline_ticketing_new.reissue_quotes.create' AS name
UNION ALL SELECT 'airline_ticketing_new.ticket_exchanges.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.refund_rules.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.adm_acm.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.emd.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.travel_policies.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.approval_matrices.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.exceptions.view' AS name
UNION ALL SELECT 'airline_ticketing_new.exceptions.resolve' AS name) p
WHERE NOT EXISTS (
    SELECT 1 FROM permissions x WHERE x.name=p.name AND x.guard_name='web'
);
