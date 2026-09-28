# Management Report Daily Layout Fix - 25 Sep 2026

## Corrected items
- Financial Status and Financial Status II now use the same existing `mgmt-two-column` row layout as Total Add and Total Out.
- Previous Balance remains renamed to B/F Balance.
- Financial Status table uses explicit fixed column widths in the Blade markup so widths work even when an older public CSS asset is cached/deployed.
- Financial Status II table uses explicit fixed column widths in the Blade markup.
- Matching CSS was also added to both source assets and packaged public assets for screen/print consistency.

## Important
No report calculation, query, service, permission, saved-report key, or data source was changed.
