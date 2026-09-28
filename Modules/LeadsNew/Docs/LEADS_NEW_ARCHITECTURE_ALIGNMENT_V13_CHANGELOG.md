# Leads-New Architecture Alignment V13

## Scope

This parcel continues the consolidation work from V12 and focuses on making the module route loading deterministic in both ERP module-loader modes:

1. Normal module ServiceProvider loading.
2. Legacy compatibility loading through `Http/routes.php`.

## Runtime fixes

- Added a route-load guard to prevent duplicate route definitions when both the module provider and legacy route loader are active.
- Ensured Leads-New routes are always wrapped with the tenant/web middleware stack even if loaded by a legacy module loader.
- Kept all changes inside `Modules/LeadsNew`; no main `routes/web.php` or `routes/tenant.php` changes are required.
- Kept both `leads-new.*` and `leadsnew.*` route-name aliases to avoid breaking cached sidebar/menu links.

## Deployment

1. Replace only `Modules/LeadsNew`.
2. Run the V13 SQL package on the active tenant database if V12 SQL was not already run.
3. Open `/clear`.
4. Test `/leads-new/route-ok`, `/leads-new`, `/leads-new/leads`, and `/leads-new/leads/create`.

