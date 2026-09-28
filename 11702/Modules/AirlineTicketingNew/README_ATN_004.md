# ATN-004 — Quotations, Reservations and PNR

## Included
- Quotation creation and listing
- Multi-segment itinerary builder
- Passenger and corporate-customer AJAX lookups
- Fare, tax, service-fee and discount calculations
- Quotation numbering by business/location/store
- Conversion from quotation to reservation
- Reservation and PNR management
- Ticketing deadlines
- Reservation passengers
- Status workflow and status history
- Dedicated models, services, requests, controllers, routes, views, assets, migration and SQL

## Installation
1. Merge over ATN-001 to ATN-003.
2. Add this inside the authenticated AirlineTicketingNew route group:
   `require module_path('AirlineTicketingNew', 'Routes/transactions.php');`
3. Register or merge `Resources/lang/en/transactions.php`.
4. Publish/copy `atn-transactions.css` and `atn-transactions.js`.
5. Run the migration or `Database/SQL/MASTER/MASTER_ATN_004_QUOTES_RESERVATIONS_PNR.sql`.
6. Assign ATN-004 permissions.
7. Run `php artisan optimize:clear`.

All transaction tables include business, business location and store scope fields.
