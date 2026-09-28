-- ATN-012 TO ATN-016 PERMISSIONS
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT p.name,'web',NOW(),NOW() FROM (
    SELECT 'airline_ticketing_new.supplier_payments.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.supplier_payments.create' AS name
    UNION ALL SELECT 'airline_ticketing_new.bsp_remittances.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.bsp_remittances.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.staff_incentives.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.staff_incentives.create' AS name
    UNION ALL SELECT 'airline_ticketing_new.reports.airline_sales' AS name
    UNION ALL SELECT 'airline_ticketing_new.reports.outstanding' AS name
    UNION ALL SELECT 'airline_ticketing_new.notifications.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.notifications.dispatch' AS name
    UNION ALL SELECT 'airline_ticketing_new.testing.run' AS name
) p
WHERE NOT EXISTS (
    SELECT 1 FROM `permissions` x WHERE x.name=p.name AND x.guard_name='web'
);
