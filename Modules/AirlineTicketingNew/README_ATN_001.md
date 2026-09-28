# ATN-001 — Airline Ticketing New Foundation

## Installation
1. Copy `Modules/AirlineTicketingNew` into the application `Modules` folder.
2. Enable the module through the application's module loader.
3. Run `composer dump-autoload`.
4. Run the included migration or execute:
   `Database/SQL/MASTER/MASTER_ATN_001_FRESH_INSTALL.sql`
5. Copy module public assets to:
   `public/modules/airline-ticketing-new/css`
   `public/modules/airline-ticketing-new/js`
6. Clear caches:
   `php artisan optimize:clear`
7. Assign `airline_ticketing_new.access` and the required page permissions.

## Isolation guarantees
- Exclusive route prefix: `/airline-ticketing-new`
- Exclusive route-name prefix: `airline-ticketing-new.`
- Exclusive table prefix: `atn_`
- Exclusive permission prefix: `airline_ticketing_new.`
- No references to the legacy Airline module.
- No generic route names such as `agents.index`.
- All operational queries must carry business/location/store scope.

## ATN-001 contents
- Module/provider registration
- Isolated web and API routes
- Access and business-scope middleware
- Base module model
- Settings, sequence and audit entities
- Dashboard service and page
- General settings page
- POS-style isolated CSS and JS
- English language file
- Migration and separate/master SQL
- Foundation permissions
