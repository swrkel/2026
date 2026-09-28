-- ATN-007-to-011 PERMISSIONS
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT p.name,'web',NOW(),NOW() FROM (
    SELECT 'airline_ticketing_new.supplier_settlements.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.supplier_settlements.create' AS name
    UNION ALL SELECT 'airline_ticketing_new.agent_commissions.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.profitability.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.operations.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.operations.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.notifications.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.reports.ticket_sales' AS name
    UNION ALL SELECT 'airline_ticketing_new.reports.profitability' AS name
    UNION ALL SELECT 'airline_ticketing_new.admin.health' AS name
) p
WHERE NOT EXISTS (
 SELECT 1 FROM `permissions` x WHERE x.name=p.name AND x.guard_name='web'
);
