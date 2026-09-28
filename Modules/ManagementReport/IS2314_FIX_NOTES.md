# IS2314 – Management Report / Daily Management Report – 23 Sep 2026

## Reported issue

The Daily Management Report returned **503 Service Unavailable** in the 129 system while the same page worked in `sonalinew`. The affected installation can keep businesses directly in the central application database rather than in a separate tenant DB.

## Root cause fixed

The module previously assumed that every business must resolve to either:

- a Stancl tenant database, or
- a copied standalone tenant database.

On a configured central domain it could therefore follow a legacy `business_id` → tenant mapping and switch away from the central business database. The following `EnsureManagementReportTables` middleware then returned 503 when the seven `mgmt_*` tables were absent in that wrongly selected/older database.

## Correction

1. Added explicit **central-business database mode** based on the application's configured `tenancy.central_domains`.
2. Central-domain requests now keep the current `mysql` database instead of being forced through Stancl tenant fallback.
3. Dynamic tenant domains and copied standalone tenant roots retain their existing behavior.
4. Database selection is still verified with `SELECT DATABASE()` before module queries.
5. If a valid central-business database is missing Management Report tables, the module safely creates only its seven `mgmt_*` tables on first access through the existing idempotent `TenantSchemaInstaller`.
6. Existing ERP tables and existing records are not altered or deleted.
7. Every operational report query remains filtered by `business_id`, location/store/date scope as before.

## Files changed

- `Support/TenantConnection.php`
- `Http/Middleware/InitializeManagementReportTenant.php`
- `Http/Middleware/EnsureManagementReportTables.php`
- `Config/config.php`

## SQL

No manual SQL is required for this fix when the application DB user has normal CREATE TABLE permission. The first central-business request auto-creates only missing Management Report `mgmt_*` tables. Existing tenant-only installer/SQL remains available for tenant databases.
