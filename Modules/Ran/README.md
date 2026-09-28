# Ran Jewellery Module - Consolidated

Standalone Laravel module for jewellery purchases, production, storage, sales, document sharing and reporting.

## Boundaries
- All module-owned tables use the `ran_` prefix.
- Customers and suppliers are accessed from the common `contacts` table through Ran integration services.
- Finance postings are created through Ran's finance adapter and tracked in `ran_finance_postings`.
- The module includes its own controllers, services, entities, routes, views, assets, permissions, reports, SQL and migration.

## Database
Use `Database/SQL/RAN_FULL_MASTER_SQL.sql` on each applicable tenant database, or run the included Laravel migration using the tenant-aware migration process used by the application.

## Permissions
After selecting the correct tenant database/context:

    php artisan db:seed --class="Modules\\Ran\\Database\\Seeders\\RanPermissionSeeder"
    php artisan permission:cache-reset

The same permission set is declared in `Config/permissions.php`.
