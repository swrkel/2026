# Simple Audit — Installation

## Recommended installation method

1. Back up the application and every tenant database that will receive the module.
2. Upload the **SimpleAudit** folder to the application's `Modules/SimpleAudit` directory.
3. From the Laravel project root, **enable the module globally** in the existing Nwidart module registry. This step is required for the main sidebar/automatic module registry to discover Simple Audit:

   ```bash
   php artisan module:enable SimpleAudit
   ```

   Do not manually replace `modules_statuses.json`; the command safely updates only the SimpleAudit status.

4. Clear Laravel caches:

   ```bash
   php artisan optimize:clear
   ```

5. Install Simple Audit in one tenant first:

   ```bash
   php artisan simple-audit:install --tenant=<TENANT_ID>
   ```

   For a current tenant application where the default DB connection already points to the tenant database:

   ```bash
   php artisan simple-audit:install
   ```

6. Diagnose the same tenant:

   ```bash
   php artisan simple-audit:diagnose --tenant=<TENANT_ID>
   ```

7. Open `/simple-audit/purchase-audit` and test Tenant, Business, Location, Store, date ranges, hover/click details, exports, Print, PDF, Email and WhatsApp.
8. After the test tenant is confirmed, install all central tenants:

   ```bash
   php artisan simple-audit:install --all-tenants
   ```

## SQL-only alternative (phpMyAdmin)

If Artisan deployment is not convenient, use these exact steps for **each tenant database**:

1. In phpMyAdmin, click the actual **tenant database name** in the left sidebar first.
2. Confirm the database name shown in the page header is the tenant database. **Do not select `information_schema`, `mysql`, `performance_schema`, `sys`, or the central/master database.**
3. Open **Import** and import `Database/Sql/simple_audit_tenant_install.sql`. The file now contains a safety preflight and stops before creating anything if the selected database is a system database or does not contain the required ERP tenant tables.
4. When the installation finishes, keep the same tenant database selected and import `Database/Sql/simple_audit_verify.sql`.
5. Verification should report all five `sau_` tables as `OK` and list the `sau_` triggers.

The central/master SQL file is intentionally non-destructive and creates no table:

`Database/Sql/simple_audit_master_install.sql`

The verification file is read-only and is safe if the wrong database is accidentally selected: it will show a `WRONG DATABASE`/`MISSING` message instead of querying a nonexistent `sau_settings` table.

## Central/master connection

The module auto-detects an existing connection that contains a populated `tenants` table. If the application's central connection has a different configured name, set:

```env
SAU_CENTRAL_CONNECTION=<central_connection_name>
```

Tenant database connection information is read from the existing tenant record where available. Supported `tenants.data` aliases are configurable in `Config/config.php`.

## Host layout

The module ships with its own standalone POS-style layout and assets. If the application has a preferred POS/system layout that should wrap this module without copying any core view into Simple Audit, set:

```env
SAU_HOST_LAYOUT=<blade.layout.name>
```

The module remains self-contained if this is not set.

## Share link expiry

Email and WhatsApp use a secure random report link. Default expiry is 72 hours. It can be changed with:

```env
SAU_SHARE_EXPIRY_HOURS=72
```

## Rollback

The destructive uninstall SQL is supplied separately:

`Database/Sql/simple_audit_tenant_uninstall.sql`

It removes the 21 Simple Audit triggers and the five `sau_` tables. Do not run it unless Simple Audit data is intentionally being removed.


## SQL verification v1.0.2

Use `Database/Sql/simple_audit_verify.sql`. It reads only `INFORMATION_SCHEMA`. Expected final result: 5/5 tables, 21/21 triggers, PASS.


## Sidebar verification (v1.0.3)

If Simple Audit does not appear in the main sidebar after deployment, run:

```bash
php Modules/SimpleAudit/Tools/check_sidebar.php
php artisan route:list | grep simple-audit
```

Expected: `modules_statuses.json entry ... true`, `Globally enabled ... YES`, and the `simple-audit` routes listed.

This release also ships module-owned sidebar partials at:

- `Resources/views/layouts/partials/sidebar.blade.php`
- `Resources/views/layouts_v2/partials/sidebar.blade.php`

They are intentionally inside SimpleAudit so no core sidebar file needs to be changed.


## Central system / route bootstrap (v1.0.4)

After extracting the full v1.0.4 parcel at the Laravel project root, run:

```bash
php artisan module:enable SimpleAudit
php artisan optimize:clear
php Modules/SimpleAudit/Tools/check_sidebar.php
php artisan route:list | grep simple-audit
php artisan route:list --name=simpleaudit
```

The route list must include `/simple-audit` and `/simple-audit/purchase-audit`. The central Super
Admin sidebar will then list **Simple Audit** in **All Installed Modules**. No SQL re-import is needed.

## Low-memory verification (v1.0.13 - current)

Keep the server's existing PHP memory limit. For Simple Audit verification, do not run the global Laravel `route:list` command on this large ERP.

Run:

```bash
php artisan module:enable SimpleAudit
php artisan optimize:clear
php artisan view:clear
php Modules/SimpleAudit/Tools/check_sidebar.php
php artisan simple-audit:routes
php artisan simple-audit:diagnose --tenant=100
```

Expected route check result: `PASS - Simple Audit routes are registered.`

The protected Central routes remain under `/superadmin/simple-audit`. No SQL import is required when upgrading from v1.0.12 to v1.0.13.

**This v1.0.13 section supersedes older route-list commands elsewhere in this historical installation document.**
