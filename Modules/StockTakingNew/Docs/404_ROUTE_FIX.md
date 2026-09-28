# STK_003 — 404 Route Registration Fix

## Cause

On some transferred installations, the nwidart module-provider manifest or the
Laravel route cache was created before `StockTakingNew` was uploaded. The module
could therefore be discovered by the automatic sidebar/Manage registry while
its service provider and routes were not active, resulting in HTTP 404.

## Fix

- The module now owns one cache-independent `Support/RouteRegistrar.php`.
- The main module provider registers routes directly during boot.
- The module RouteServiceProvider uses the same registrar as a second safe path.
- `AppServiceProvider::register()` conditionally registers the module provider
  when the module folder exists. No module routes or business logic were moved
  into the application provider.
- A module provider manifest is included for transferred installations.
- Route registration uses absolute module-owned paths rather than `module_path()`.

## Deployment

Extract the package to the Laravel root, preserving paths, and run:

```bash
composer dump-autoload
php artisan module:enable StockTakingNew
php artisan optimize:clear
```

Verify:

```bash
php artisan route:list --name=stock-taking-new
```

The command must show the `stock-taking-new.dashboard` route and the other
Stock Taking - New routes.
