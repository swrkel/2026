# ATN-003 — Passenger and Customer Profiles

## Included
- Passenger profiles
- Corporate customer profiles
- Passport and travel document records
- Visa records
- Airline loyalty accounts
- Emergency contacts
- Corporate contacts
- Document and visa expiry report
- Dedicated models, services, controllers, validation, views, routes, permissions, assets, migration and SQL

## Installation
1. Merge this parcel over ATN-001 and ATN-002.
2. Add the following inside the authenticated AirlineTicketingNew route group:
   `require module_path('AirlineTicketingNew', 'Routes/profiles.php');`
3. Register or merge `Resources/lang/en/profiles.php`.
4. Copy the ATN profile CSS and JS into the module public asset path.
5. Run the migration or `Database/SQL/MASTER/MASTER_ATN_003_PASSENGERS_CUSTOMERS.sql`.
6. Assign ATN-003 permissions.
7. Run `php artisan optimize:clear`.

All profile records are scoped by business, with location and store columns reserved for operational isolation.
