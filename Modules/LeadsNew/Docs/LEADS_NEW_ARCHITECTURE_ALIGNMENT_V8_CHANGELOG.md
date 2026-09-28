# Leads-New Architecture Alignment V8

## Purpose
Fix the page error: `Route [leads-new.leads.create] not defined` on Leads Register.

## Changes
- Declared Leads CRUD routes explicitly instead of relying only on `Route::resource`.
- Replaced route helper usage inside critical Lead views with URL-based links so stale route-name cache cannot break the page.
- Replaced controller redirects from route-name redirects to tenant-safe URL redirects.
- Kept all changes inside `Modules/LeadsNew`.
- No main `routes/web.php` or `routes/tenant.php` changes.

## Database
No database changes in this parcel.
