-- ATN-003 permission SQL
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT p.name, 'web', NOW(), NOW()
FROM (
    SELECT 'airline_ticketing_new.passengers.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.passenger_documents.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.passenger_visas.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.passenger_loyalty.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.passenger_emergency_contacts.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.corporate_customers.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.corporate_contacts.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.reports.document_expiry' AS name
) p
WHERE NOT EXISTS (
    SELECT 1 FROM `permissions` existing
    WHERE existing.name = p.name AND existing.guard_name = 'web'
);
