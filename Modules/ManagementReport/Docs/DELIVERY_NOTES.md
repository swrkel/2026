# Delivery Notes

## New module-owned database tables

- `mgmt_report_templates`
- `mgmt_report_runs`
- `mgmt_report_run_sections`
- `mgmt_report_shares`
- `mgmt_report_share_recipients`
- `mgmt_report_settings`
- `mgmt_report_review_statuses`

## Report sections implemented

1. Sales
2. Sales by Cashiers / Pump Operators
3. Add / Less
4. Sales Return / Purchase Return
5. Financial Status
6. Financial Status II
7. Financial Status Breakups
8. Outstanding Details
9. Stock Value Status
10. Pump Operator Shortage / Excess
11. Dip Details
12. Final Review Status

## Integration files changed

- `modules_statuses.json`
- `Modules/Superadmin/Services/ModulePermissionService.php`
- `resources/views/layouts/sidebar.blade.php`

No other existing module file is used by the Management Report module.
