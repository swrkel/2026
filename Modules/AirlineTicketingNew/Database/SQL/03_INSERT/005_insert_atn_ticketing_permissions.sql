-- ATN-005 permissions
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT p.name,'web',NOW(),NOW()
FROM (
    SELECT 'airline_ticketing_new.tickets.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.tickets.issue' AS name
    UNION ALL SELECT 'airline_ticketing_new.invoices.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.invoices.create' AS name
    UNION ALL SELECT 'airline_ticketing_new.payments.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.payments.create' AS name
    UNION ALL SELECT 'airline_ticketing_new.receipts.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.receipts.print' AS name
) p
WHERE NOT EXISTS (
    SELECT 1 FROM `permissions` existing
    WHERE existing.name=p.name AND existing.guard_name='web'
);
