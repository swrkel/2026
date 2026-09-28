# Leads-New Architecture Alignment V2

This parcel continues the Customers-module alignment work.

## Included hardening
- Added `leadsnew::layouts.app` wrapper so module pages extending the module layout no longer fail.
- Completed missing English translation keys used by controllers and Blade files.
- Preserved module-owned route loading through `Modules/LeadsNew/Providers/RouteServiceProvider.php`.
- No direct main `routes/web.php` changes included in this parcel.
- No direct main `routes/tenant.php` changes included in this parcel.

## Deployment
Replace only `Modules/LeadsNew`, then run `/clear` and test:
- `/leads-new/route-ok`
- `/leads-new`
- `/leads-new/leads`
- `/leads-new/settings`
