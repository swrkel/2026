-- ATN-017 TO ATN-021 PERMISSIONS
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT p.name,'web',NOW(),NOW() FROM (
    SELECT 'airline_ticketing_new.corporate_agreements.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.corporate_agreements.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.corporate_ledger.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.visa_applications.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.visa_applications.create' AS name
    UNION ALL SELECT 'airline_ticketing_new.tour_packages.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.tour_packages.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.tour_bookings.create' AS name
    UNION ALL SELECT 'airline_ticketing_new.hotels.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.hotels.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.hotel_reservations.create' AS name
    UNION ALL SELECT 'airline_ticketing_new.transport_vehicles.view' AS name
    UNION ALL SELECT 'airline_ticketing_new.transport_vehicles.manage' AS name
    UNION ALL SELECT 'airline_ticketing_new.transfer_bookings.create' AS name
) p
WHERE NOT EXISTS (
    SELECT 1 FROM `permissions` x WHERE x.name=p.name AND x.guard_name='web'
);
