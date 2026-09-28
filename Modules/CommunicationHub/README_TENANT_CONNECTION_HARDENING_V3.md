# Communication Hub Tenant Connection Hardening V3

This package fixes Communication Hub operational pages trying to read tenant tables from the central database.

## Main fixes
- Added `Modules/CommunicationHub/Support/TenantConnection.php`.
- Communication Hub DB reads now prefer the ERP `mysql_tenant` connection.
- Eloquent entities dynamically use the tenant connection.
- Dashboard and commercial pages are guarded if tenant tables are not installed.
- Missing tables now show setup guidance instead of crashing.

## Deploy
Replace only:

`Modules/CommunicationHub/`

Then run:

```bash
php artisan optimize:clear
```

## SQL
Run this in each tenant database, not central:

`Modules/CommunicationHub/Database/SQL/00_RUN_THIS_IN_EACH_TENANT_DATABASE.sql`
