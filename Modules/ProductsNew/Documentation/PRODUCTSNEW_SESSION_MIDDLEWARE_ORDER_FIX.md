# Products New — Session/Tenancy Middleware Order Fix

## Error fixed

`RuntimeException: Session store not set on request`

## Root cause

`Stancl\Tenancy\Middleware\ScopeSessions` was executed before Laravel's `web` middleware group had started the session.

## Correct order

1. `web` (includes `StartSession`)
2. `InitializeTenancyByDomain`
3. `PreventAccessFromCentralDomains`
4. `ScopeSessions`
5. `auth`
6. `check.route.permission`

## Files changed

- `Modules/ProductsNew/Routes/web.php`
- `Modules/ProductsNew/Routes/reports.php`

The API route file is included unchanged for completeness and does not use `ScopeSessions`.

## After upload

Run:

```bash
php artisan optimize:clear
php artisan route:clear
php artisan config:clear
php artisan view:clear
composer dump-autoload
```

Then verify:

```bash
php artisan route:list --path=products-new
```
