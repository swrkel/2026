# HOTELMGT_030 Delivery Notes

## Completed
- Added Hotel Channel Manager page.
- Added sales channel setup for OTA/direct/corporate/agent channels.
- Added channel rate mapping.
- Added availability and stop-sell controls.
- Added external booking capture with duplicate reference protection per business/channel.
- Added POS-style KPI cards, forms, toolbar and registers.

## SQL
- `Docs/HOTELMGT_030_SQL.sql` contains only Parcel 030 SQL.
- `Docs/HOTELMGT_MASTER_SQL.sql` is updated cumulatively.

## Tenant/business notes
- All new records include `business_id` and `business_location_id`.
- No database names are hardcoded in SQL.
