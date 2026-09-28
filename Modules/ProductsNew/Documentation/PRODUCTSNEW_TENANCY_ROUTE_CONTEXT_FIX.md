# Products New — Tenant Route Context Fix

## Root cause
Products New routes were registered as ordinary global web routes. Therefore the Stancl tenancy middleware never initialized the tenant before database queries. The application consequently used the central `mysql` database (`nivasa_base`).

The earlier `mysql_tenant` middleware workaround was not aligned with this ERP. This ERP changes `database.connections.mysql.database` to the active tenant database during `TenancyBootstrapped`.

## Fix
- Added the same tenancy middleware used by `routes/tenant.php` to Products New web and report routes:
  - `InitializeTenancyByDomain`
  - `PreventAccessFromCentralDomains`
  - `ScopeSessions`
- Added tenancy initialization to Products New API routes.
- Removed duplicate route loading from `ProductsNewServiceProvider`.
- Kept one authoritative route registration through `RouteServiceProvider`.

## Upload
Copy the included `Modules` directory over the project root.

Remove the previous custom file if it exists:
`Modules/ProductsNew/Http/Middleware/UseProductsNewTenantDatabase.php`

## Commands
```bash
php artisan optimize:clear
php artisan route:clear
php artisan config:clear
php artisan view:clear
composer dump-autoload
```

Verify:
```bash
php artisan route:list --path=products-new
```

The exception should now show tenant data through `Connection: mysql`, because this ERP intentionally repoints the `mysql` connection to the active tenant database.
