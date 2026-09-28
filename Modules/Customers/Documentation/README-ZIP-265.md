# ZIP 265 – Customers Reports Finalization

## Purpose
This package continues the Customers standalone module separation by moving report-specific data preparation into Customers-owned report classes.

## Added
- `Modules/Customers/Reports/CustomerLedgerReport.php`
- `Modules/Customers/Reports/CustomerStatementReport.php`
- `Modules/Customers/Reports/CustomerAgingReport.php`
- `Modules/Customers/Reports/CustomerActivityReport.php`

## Updated
- `Modules/Customers/Services/CustomerReportService.php`

## Notes
- No legacy Contact module controllers/views are used.
- Shared ERP tables such as `contacts`, `transactions`, and accounting tables remain shared intentionally.
- This package does not change PetroPD.

## After upload
Run:

```bash
php artisan optimize:clear
php artisan view:clear
php artisan route:clear
```
