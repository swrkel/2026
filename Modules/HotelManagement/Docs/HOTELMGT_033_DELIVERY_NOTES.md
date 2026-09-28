# HOTELMGT_033 Delivery Notes

## Added
- City Ledger / Accounts Receivable page.
- Corporate, travel agent, government and house account ledger setup.
- Direct billing invoice posting.
- Receipt recording with invoice balance update.
- Debit / credit adjustment support.
- POS-standard cards, KPI boxes, forms and tables.

## SQL
- `Docs/HOTELMGT_033_SQL.sql` contains only Parcel 033 SQL.
- `Docs/HOTELMGT_MASTER_SQL.sql` has been updated cumulatively up to Parcel 033.

## Multi-tenant / Multi-business
- All tables include `business_id` and `business_location_id`.
- All service queries are scoped by the current tenant database and current business session.
