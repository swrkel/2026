# HOTELMGT_029 Delivery Notes

## Added
- Hotel Procurement page following POS-style cards, toolbar, tables, form spacing and button style.
- Supplier setup for hotel-specific purchasing vendors.
- Purchase Request workflow with priority/status control.
- Purchase Order workflow with supplier/request linking, discount/tax/net calculations.
- Goods Received Note posting with accepted/rejected quantity and received-value tracking.
- Tenant/business/location scoped records for every procurement table.

## SQL
- `Docs/HOTELMGT_029_SQL.sql` contains only the SQL introduced in this parcel.
- `Docs/HOTELMGT_MASTER_SQL.sql` is updated as the cumulative master SQL.

## Main URLs
- `/hotel-management/procurement`

## Permissions
- `hotel.procurement.view`
- `hotel.procurement.manage`
