# Products New — Tenant Database Connection Fix

## Issue fixed
Products New routes were available, but Laravel's default connection could still point to the central database (`nivasa_base`). This caused module queries to search for tenant tables such as `products_new_product_meta` in the central database.

## Correct behavior
For every Products New web, report, API, and AJAX request:

1. Run the application's `tenant_db()` resolver.
2. Reconnect `mysql_tenant` using the resolved tenant database.
3. Set `mysql_tenant` as the default connection for the complete request.
4. Restore the former default connection when the request finishes.

## Deployment
Upload the changed files, then run:

```bash
php artisan optimize:clear
composer dump-autoload
php artisan route:clear
php artisan config:clear
php artisan view:clear
```

The Products New SQL tables must exist in each tenant database where the module is enabled. They must not be created in `nivasa_base`.
