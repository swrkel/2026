# Leads-New Architecture Alignment V4

## Scope
Module-only parcel. No main route files are included or changed.

## Completed in this parcel
- Improved Leads-New dashboard to professional ERP card/panel layout.
- Improved Lead List page with toolbar, action dropdown, status badges, pagination, and empty state.
- Improved Add/Edit/View Lead pages with four-column form structure and professional panels.
- Added standard Leads-New toolbar partial with Search, Date Range, Excel, CSV, PDF, Print, Column Visibility, and Add button support.
- Added missing Follow-ups page view and controller data loading.
- Added missing generic safe views for legacy controller fallbacks.
- Added missing translation keys to avoid raw language keys showing in UI.
- Added business/location context on Lead create/update.
- Improved dashboard summary service with tenant/business filtering and correct status handling.
- Added UI CSS for consistent Leads-New pages.
- Fixed LeadsNewUiController index route compatibility.

## Deployment
Replace only:
`Modules/LeadsNew`

Then run:
- `/clear`
- Test `/leads-new`
- Test `/leads-new/leads`
- Test `/leads-new/leads/create`
- Test `/leads-new/followups`
- Test `/leads-new/settings`

## Important
This parcel does not include or change `routes/web.php` or `routes/tenant.php`.
