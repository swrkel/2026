# Leads-New Production Consolidation RC2

## Purpose
This parcel continues the Customers-style architecture alignment and functional hardening for the standalone Leads-New module.

## Deployment
1. Replace only `Modules/LeadsNew`.
2. Do not replace main `routes/web.php` or `routes/tenant.php`.
3. Run the RC2 SQL package on the active tenant database only.
4. Open `/clear`.
5. Test `/leads-new/route-ok`, `/leads-new`, `/leads-new/leads`, `/leads-new/leads/create`.

## RC2 changes
- Keeps Leads-New routes inside the module.
- Aligns route provider namespace pattern with the working Customers module.
- Keeps compatibility route aliases for older cached sidebar links.
- Keeps view and language namespace registration in the module provider.
- Keeps tenant-context routing and tenant-safe database table guards.
- Adds numbered RC2 SQL and cumulative MASTER SQL package.

## Important
No main route files are changed in this parcel.
