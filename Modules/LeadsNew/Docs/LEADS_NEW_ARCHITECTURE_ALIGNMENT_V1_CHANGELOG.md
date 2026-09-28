# Leads-New Architecture Alignment V1

Reference module used: `Customers`.

## Purpose
This package aligns Leads-New with the existing standalone module pattern used by the ERP, instead of patching individual errors one-by-one.

## Main fixes
- Rebuilt module bootstrap to match the Customers module style.
- `LeadsNewServiceProvider` now registers config, views, translations, migrations and the route provider.
- Added `Providers/RouteServiceProvider.php` using the ERP tenant middleware stack: `web`, `auth`, `SetSessionData`, `language`, `timezone`, `tenant.context`.
- Removed direct route loading from `start.php` to avoid central database route loading.
- Cleaned `module.json` so only the module provider is registered; no direct start route loading.
- Rebuilt `Routes/web.php` with fully-qualified controller class references to prevent `App\Http\Controllers\Modules\...` namespace errors.
- Added defensive view and translation namespace registration to prevent `No hint path defined for [leadsnew]`.
- Preserved compatibility route aliases for older sidebar links.
- Fixed broken old route names such as `leadsnew.add-leads` by providing compatible aliases and updating sidebars.
- Added `Resources/lang/en/messages.php` for modern Leads-New translation keys.
- Corrected obvious language spelling issues in `Resources/lang/en/lang.php`.
- Fixed a PHP parse error in `LeadsNewDependencyAuditService`.
- Added a safe bulk action handler to prevent missing method failures.

## Deployment
Replace the complete `Modules/LeadsNew` folder with this package.
Do not replace global `routes/web.php` or `routes/tenant.php` with this package.

After upload, clear cache using your normal `/clear` route.
Then test:
- `/leads-new/route-ok`
- `/leads-new`
- `/leads-new/leads`
- `/leads-new/settings`
- `/leads-new/reports`

## Notes
The module should now load its own routes, views, translations and tenant-aware route stack from inside the module, similar to Customers.
