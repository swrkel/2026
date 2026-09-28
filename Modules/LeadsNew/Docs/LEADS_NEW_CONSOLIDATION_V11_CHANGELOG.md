# Leads-New Consolidation V11 - 2026-07-04

## Purpose
This parcel consolidates the active Leads-New runtime after the V10 deployment showed no visible change on the server. The focus is to remove dependency on public asset publishing for UI changes and make the module behave consistently after replacing `Modules/LeadsNew`.

## Main changes
- Added module inline style partial: `Resources/views/partials/styles.blade.php`.
- Replaced module CSS asset links with inline style includes so the Communication Hub aligned design appears immediately after module replacement.
- Removed tenant table names from the Add Lead setup warning; users now see a clean setup message only.
- Standardized Leads Register record count label.
- Reworked Opportunities index to the same professional card/table style.
- Reworked Reports landing page to professional card layout.
- Kept routes module-only; no main route changes are required.

## Deployment
1. Replace only `Modules/LeadsNew`.
2. Run the SQL package on the active tenant database if tables are missing.
3. Open `/clear`.
4. Test `/leads-new`, `/leads-new/leads`, `/leads-new/leads/create`, `/leads-new/opportunities`, `/leads-new/reports`.
