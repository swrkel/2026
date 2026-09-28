INSERT INTO permissions(name,guard_name,created_at,updated_at)
SELECT p.name,'web',NOW(),NOW() FROM (SELECT 'airline_ticketing_new.visa_applications.status' AS name
UNION ALL SELECT 'airline_ticketing_new.hotel_availability.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.insurance.view' AS name
UNION ALL SELECT 'airline_ticketing_new.insurance.issue' AS name
UNION ALL SELECT 'airline_ticketing_new.transfer_routes.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.tour_departures.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.ancillary.view' AS name
UNION ALL SELECT 'airline_ticketing_new.ancillary.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.supplier_services.manage' AS name
UNION ALL SELECT 'airline_ticketing_new.bundles.view' AS name
UNION ALL SELECT 'airline_ticketing_new.bundles.manage' AS name) p
WHERE NOT EXISTS (
    SELECT 1 FROM permissions x WHERE x.name=p.name AND x.guard_name='web'
);
