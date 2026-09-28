# Leads-New Architecture Alignment V6

This parcel continues from V5 and keeps the fix module-only.

## Included
- Added legacy route-name compatibility aliases for cached/sidebar links using `leadsnew.*`.
- Hardened Add Lead page and store action when tenant SQL tables have not yet been created.
- Added user-friendly database setup warning text.
- Cleaned raw labels in language files.
- Removed bundled `error_log` files from the module package.

## Deployment
Replace only `Modules/LeadsNew`. Do not replace main routes. Then open `/clear`.
