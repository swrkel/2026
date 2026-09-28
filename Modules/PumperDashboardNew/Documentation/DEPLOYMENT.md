# Deployment Guide

## Release structure

Pumper Dashboard-New is installed under `Modules/PumperDashboardNew` and is registered as `PumperDashboardNew` in `modules_statuses.json`.

The module owns its routes, controllers, requests, middleware, services, integration services, entities, reports, views, layouts, assets, language files, permissions, migrations, SQL scripts, commands, support classes, and utilities.

## Central application changes

The release includes only these central application changes:

1. `resources/views/auth/login.blade.php`
2. `app/Http/Middleware/UserLocationAccess.php`
3. `modules_statuses.json`

Back up these files before extraction.

## Tenant database options

### Full idempotent installer

`SQL/00_MASTER_INSTALL_PUMPER_DASHBOARD_NEW.sql`

Use this for a fresh tenant or when the previous Pumper Dashboard-New database state is uncertain. It covers all current tables, hardening columns, and permissions.

### Focused upgrade

`SQL/01_UPGRADE_EXISTING_INSTALLATION.sql`

Use this after the earlier Pumper Dashboard-New base release. It adds full-operation support tables and missing columns without deleting data.

### Permission-only installer

`SQL/02_INSERT_PERMISSIONS.sql`

Use this only when the schema already exists and permission rows need to be reconciled.

### Verification

`SQL/04_VERIFY_INSTALLATION.sql`

The verification result should show `OK` for all 31 PONE tables, required release columns, and all 36 permissions.

## Post-upload commands

```bash
php artisan optimize:clear
```

When Composer autoloading is not already configured for Laravel modules, also run the application's normal module discovery/autoload command. The supplied application already uses module discovery through `module.json` and `modules_statuses.json`.

## Initial administration

1. Sign in as an authorized administrator.
2. Grant the required `pumper_dashboard_new.*` permissions to the appropriate roles.
3. Open `/pumper-dashboard-new/admin/operators` and synchronize Petro PD operators.
4. Confirm operator location mappings and login status.
5. Review `/pumper-dashboard-new/admin/settings` before operational use.
6. Test one complete shift in a non-production tenant before enabling the module for live operators.

## Petro PD-New pairing safety

- Pumper Dashboard-New is paired exclusively with Petro PD-New through `Services/Integration/PonePetroPdNewPublisher.php`.
- The publisher marks module-owned `pone_` records as source-ready; it does not write to legacy Petro PD tables.
- Petro PD-New imports only closed PONE shifts through an immutable, hash-verified source snapshot.
- Finalized settlement references are written only to `pone_shift_settlement_references`.
- Historical PONE integration-link rows are audit-only and are retired by the Petro PD-New pairing installer.
- Business, location, customer, supplier, store, tank, account, product, pump, shift, and operator scope checks are applied before transactions are stored.
