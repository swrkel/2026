# HOTELMGT_034 Delivery Notes

## Focus
Cashier Control / Shift Reconciliation for hotel front desk and cashier operations.

## Added
- Cashier shift open/close workflow
- Opening float and expected drawer cash tracking
- Safe drop recording with safe bag number
- Cash denomination count lines
- Shortage/excess variance creation on shift close
- Variance review status flow
- POS-standard UI page and navigation link

## SQL
- `Docs/HOTELMGT_034_SQL.sql` contains only SQL introduced in this parcel.
- `Docs/HOTELMGT_MASTER_SQL.sql` is updated cumulatively up to parcel 034.

## Multi-tenant / business notes
All new tables use `business_id` and optional `business_location_id`. Queries are scoped by current tenant database and business/session location.
