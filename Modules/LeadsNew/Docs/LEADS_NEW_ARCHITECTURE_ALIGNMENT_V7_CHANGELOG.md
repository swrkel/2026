# Leads-New Architecture Alignment V7

This parcel continues the Customers-style standalone module alignment.

## Key updates

- Added `Providers/ServiceProvider.php` compatibility wrapper, matching the pattern used by working standalone modules such as Customers.
- Hardened `LeadsNewServiceProvider` so view, language, config and migration registration do not break the host ERP during bootstrap.
- Registered both `leadsnew` and `leads_new` view/translation namespaces to protect against cached or older published views.
- Added route compatibility aliases for older `leadsnew.*` links while keeping the primary route prefix as `leads-new.*`.
- Kept all changes inside `Modules/LeadsNew`; no main route files are changed.

## Deployment

Replace only:

`Modules/LeadsNew`

Then open `/clear` and test:

- `/leads-new/route-ok`
- `/leads-new`
- `/leads-new/leads`
- `/leads-new/leads/create`
- `/leads-new/settings`
