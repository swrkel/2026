# Management Report Module v3 - Dynamic Tenant Database Installation

This parcel supports both tenant databases and businesses that intentionally live in the configured central-domain database. Every query uses the database that owns the selected business, and remains scoped by `business_id`.

## Copy the files

Copy the parcel contents into the Laravel project root and preserve all paths.

## Install the tables in tenant databases

### Recommended: install in every registered tenant

```bash
php artisan optimize:clear
composer dump-autoload
php artisan management-report:install-tenants
```

Install only selected tenants when required:

```bash
php artisan management-report:install-tenants --tenant=ishadi --tenant=cool2
```

The command initializes each Stancl tenant and creates the seven `mgmt_*` tables in that tenant's dynamically resolved database.

### Manual SQL for one tenant

1. Select the tenant database in phpMyAdmin/MySQL.
2. Run:

`Modules/ManagementReport/Database/SQL/MGMT_MASTER_SQL_TENANT_DATABASE.sql`

The SQL uses `CREATE TABLE IF NOT EXISTS` and `INSERT IGNORE` and contains no fixed `USE` statement. Confirm that phpMyAdmin/MySQL shows the intended tenant database before importing it.

## Important

Do not run any old SQL file containing a hard-coded `USE nivasa_base`.

For dynamic/copy tenant databases, use the tenant installer or tenant SQL above. For a business that legitimately lives in the configured central-domain database, this version automatically creates only missing Management Report `mgmt_*` tables on first access; no manual central SQL is required.

The module migration is not auto-loaded by the normal central `php artisan migrate` command.

## Final steps

```bash
php artisan optimize:clear
composer dump-autoload
```

Then open the module from a valid tenant domain, for example:

`https://<tenant-domain>/management-report`

If the tables are missing, the module now returns a clear tenant-database installation message instead of a raw SQL 500 exception.
