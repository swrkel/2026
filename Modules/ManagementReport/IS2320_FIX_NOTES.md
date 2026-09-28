# IS2320 – Management Report / Daily Management Report – 24 Sep 2026

## Reported issue

`Management Report / Daily Management Report` returned **503 Service Unavailable** in the 129 system, while the same page worked in `sonalinew`.

The affected 129 installation can keep normal businesses directly inside the same central/master application database. Its deployment hostname is not necessarily listed in `tenancy.central_domains`.

## Root cause

The previous central-business correction depended primarily on `tenancy.central_domains`.

When an authenticated business physically lived in the current master database but the current host was not registered as a central domain, Management Report could continue into the Stancl/legacy tenant fallback. A stale or legacy tenant mapping could then move the request to another database. `EnsureManagementReportTables` subsequently found the Management Report tables missing in that selected database and returned 503.

## Correction

1. Management Report now recognises a valid central/shared business database in either of these cases:
   - the host is configured in `tenancy.central_domains`; or
   - tenancy is not already initialized, authentication has succeeded, and the authenticated `business_id` physically exists in the current `mysql` database.
2. This check runs before Stancl domain/business fallback, so an old tenant mapping cannot move a genuine central/master business to another database.
3. Dynamic tenant requests that are already initialized remain untouched.
4. Legacy copied tenant databases retain their previous handling.
5. The actual selected database is still verified with `SELECT DATABASE()` before module queries.
6. If the valid central/shared business DB is missing any of the seven module-owned `mgmt_*` tables, the existing idempotent installer creates only those missing Management Report tables on first access.
7. Existing ERP tables and data are not altered or deleted.
8. Every Management Report operational query remains scoped by `business_id` and the existing location/store/date filters.
9. The module's cached database verification is reset at the beginning of every request, preventing cross-request reuse in long-running PHP workers.

## Files changed

- `Support/TenantConnection.php`
- `Http/Middleware/InitializeManagementReportTenant.php`
- `Http/Middleware/EnsureManagementReportTables.php`
- `Config/config.php`

## Deployment

Replace the full `Modules/ManagementReport` folder with this parcel, then run:

```bash
php artisan optimize:clear
composer dump-autoload
```

Then open:

`/management-report/daily`

No manual SQL should be required if the application's database user has CREATE TABLE permission. The module creates only missing `mgmt_*` tables in the verified current central/shared business database.
