# HOTELMGT_040 Delivery Notes

## Added
- Hotel Staff Payroll / Payroll Cost Control page.
- Payroll rule setup for monthly/daily/hourly salary types.
- Payroll run generation from active rules and hotel attendance logs.
- Payroll line adjustment for OT, allowances and deductions.
- Payroll approval / posted / cancelled status workflow.

## SQL
- `Docs/HOTELMGT_040_SQL.sql` contains only Parcel 040 SQL.
- `Docs/HOTELMGT_MASTER_SQL.sql` is updated cumulatively up to Parcel 040.

## Scope
- All new tables are tenant database tables with `business_id` and `business_location_id`.
- This does not replace the standalone HR Manager module. It adds hotel operational payroll costing and can later be bridged to Finance/Accounting after approval.
