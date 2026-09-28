# HOTELMGT_025 Delivery Notes

## Added
- Hotel Transport / Airport Transfer page.
- Vehicle and driver register.
- Airport pickup, airport drop, local transfer and tour booking register.
- Transport status workflow: requested, confirmed, assigned, started, completed, cancelled.
- Transport payments with cash/card/bank/room-charge method support.
- POS-style cards, KPI boxes, toolbar and table layout.

## SQL
- `HOTELMGT_025_SQL.sql` includes only parcel 025 SQL.
- `HOTELMGT_MASTER_SQL.sql` has been updated cumulatively up to parcel 025.

## Tenant Safety
- All tables include `business_id` and `business_location_id`.
- Controller/service layer scopes reads and writes by current business.
