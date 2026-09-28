# Stock Adjustment New — LA-1096 Readiness Fix

## What changed

- The module now initializes the correct tenant/custom-domain database context.
- The required `san_*` tables are checked before every module controller runs.
- Missing tables/columns are created idempotently on the active tenant/business database when the database user has DDL permission.
- If automatic setup is unavailable, the module shows a readable setup page instead of a raw SQL exception.
- All Stock Adjustment pages are business-scoped.

## Recommended deployment

1. Upload the module into `Modules/StockAdjustmentNew`.
2. Run `php tools/activate_stock_modules.php` from the Laravel project root.
3. Run `php artisan optimize:clear`.
4. Open `/stock-adjustment-new` once for each tenant database.

## Manual SQL fallback

Run this file on every tenant database:

`Modules/StockAdjustmentNew/SQL/10_MASTER_INSTALL.sql`

The file is a complete executable master SQL file and is safe to run repeatedly. It does not rely on MySQL `SOURCE` commands.

## S 577 settings upgrade

After deploying the 30 July 2026 settings update, run:

```bash
php artisan optimize:clear
php artisan migrate --force
```

Alternatively run `SQL/11_Settings_Upgrade.sql` on each tenant database.
