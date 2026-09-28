# Management Report Module

Standalone management reporting for the Laravel multi-tenant, multi-business, multi-location and multi-store ERP.

## Business database rule

Every Management Report operational query and every `mgmt_*` table belongs to the database that actually owns the selected business.

Supported modes:

1. Dynamic tenant domain: Stancl resolves the tenant and the module uses `TENANT_DATABASE_PREFIX + tenant_id`.
2. Legacy copied tenant root: `mysql` already points directly at the tenant database.
3. Configured central domain: businesses that intentionally live in the central application's database remain on that database and are isolated by `business_id`.
4. Management Report purges/reconnects and verifies `SELECT DATABASE()` before its first module query.

For central-domain businesses, missing module-owned `mgmt_*` tables are created idempotently on first access. Existing ERP/central tables are not altered.

## Installation

All tenants:

```bash
php artisan management-report:install-tenants
```

Selected tenant:

```bash
php artisan management-report:install-tenants --tenant=TENANT_ID
```

Verification:

```bash
php artisan management-report:tenant-check TENANT_ID
```

Manual SQL is available at:

`Database/SQL/MGMT_MASTER_SQL_TENANT_DATABASE.sql`

Select the required tenant database before running it. The SQL has no fixed `USE` statement.

## Scope

- Module-owned tables use the `mgmt_` prefix.
- Existing ERP and central tables are not altered.
- Generated reports are tenant-local snapshots.
- Output channels include preview, print, PDF, SMS link, email and WhatsApp link.
