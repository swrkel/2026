# HOTELMGT_005 Delivery Notes

## Completed in this parcel
- Fixed Hotel Management module layout wrapper to extend the main ERP layout instead of recursively extending itself.
- Reworked Hotel Reports home page to POS-style report cards.
- Added POS-style filters, search, export buttons, print button and table structure for reports.
- Added CSV / Excel-compatible exports for report pages.
- Improved business/location scoped report calculations for Occupancy and Revenue / ADR / RevPAR.
- Added report performance indexes and export log table SQL.

## SQL files
- `Docs/HOTELMGT_005_SQL.sql` contains only SQL related to this parcel.
- `Docs/HOTELMGT_MASTER_SQL.sql` is the continuing master SQL file.

## Notes
- SQL statements do not include tenant database names, so they can be executed after selecting each tenant database.
- If a MySQL version rejects duplicate index creation, skip the duplicate index line for that tenant DB.
