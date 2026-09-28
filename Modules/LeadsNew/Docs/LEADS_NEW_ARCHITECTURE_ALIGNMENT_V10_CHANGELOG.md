# Leads-New Architecture Alignment V10

## Purpose
This parcel continues the module-only hardening after the module began loading successfully on the server.

## Main changes
- Route provider changed to use fully-qualified controller class references only. This avoids Laravel prepending `App\Http\Controllers\` to module controllers on older route loaders.
- Route contract reviewed for Leads Register / Add Lead / Dashboard / Reports / Settings.
- View links remain URL-based where possible to avoid cached named-route failures.
- Continued Communication-Hub aligned UI styling and language cleanup.
- SQL package included separately for tenant database execution.

## Deployment
1. Replace only `Modules/LeadsNew`.
2. Run SQL scripts only on the active tenant database.
3. Open `/clear`.
4. Test:
   - `/leads-new/route-ok`
   - `/leads-new`
   - `/leads-new/leads`
   - `/leads-new/leads/create`

## Important
Do not replace main `routes/web.php` or `routes/tenant.php` with this parcel.
