# HOTELMGT_018 Delivery Notes

## Purpose
Production hardening and ERP integration audit for the Hotel Management module.

## Added
- Production Hardening page: `/hotel-management/production-hardening`
- Route, scope, index and ERP integration readiness checks
- Safe record counts for important Hotel Management tables
- POS-style page/card/table layout
- Production audit snapshot table
- Conditional performance index SQL

## SQL files
- `Docs/HOTELMGT_018_SQL.sql` contains only SQL introduced in parcel 018.
- `Docs/HOTELMGT_MASTER_SQL.sql` is the cumulative master SQL continued up to parcel 018.

## Notes
Run SQL inside each tenant database, without adding database names, as requested for multi-tenant deployment.
