-- ATN-006 permissions
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT p.name,'web',NOW(),NOW()
FROM (
    SELECT 'airline_ticketing_new.reissues.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.reissues.create' AS name
    UNION ALL SELECT 'airline_ticketing_new.reissues.approve' AS name
    UNION ALL SELECT 'airline_ticketing_new.voids.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.voids.create' AS name
    UNION ALL SELECT 'airline_ticketing_new.voids.approve' AS name
    UNION ALL SELECT 'airline_ticketing_new.cancellations.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.cancellations.create' AS name
    UNION ALL SELECT 'airline_ticketing_new.cancellations.approve' AS name
    UNION ALL SELECT 'airline_ticketing_new.refunds.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.refunds.approve' AS name
    UNION ALL SELECT 'airline_ticketing_new.refunds.process' AS name
    UNION ALL SELECT 'airline_ticketing_new.credit_notes.view' AS name
) p
WHERE NOT EXISTS (
    SELECT 1 FROM `permissions` existing
    WHERE existing.name=p.name AND existing.guard_name='web'
);
