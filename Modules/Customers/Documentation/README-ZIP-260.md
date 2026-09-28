# ZIP 260 – Customers Reports / Ledger Service Separation

## Purpose
This package continues the Customers standalone-module work by moving customer report and ledger calculations into Customers-owned service classes.

## Added
- `Modules/Customers/Services/CustomerLedgerService.php`

## Updated
- `Modules/Customers/Services/CustomerReportService.php`
- `Modules/Customers/Http/Controllers/CustomerReportController.php`
- `Modules/Customers/Resources/views/reports/index.blade.php`
- `Modules/Customers/Resources/views/reports/customer-list.blade.php`
- `Modules/Customers/Resources/views/reports/customer-ledger.blade.php`
- `Modules/Customers/Resources/views/reports/customer-statement.blade.php`
- `Modules/Customers/Resources/views/reports/customer-aging.blade.php`
- `Modules/Customers/Resources/views/reports/inactive-customers.blade.php`

## Notes
- This keeps Customers report logic inside `Modules/Customers`.
- It does not call Contact module controllers, Contact views, or Contact utilities.
- It still uses shared ERP tables such as `contacts`, `transactions`, and `transaction_payments`, which is expected for ERP accounting/customer data.

## After Upload
Run:

```bash
php artisan optimize:clear
php artisan view:clear
php artisan route:clear
```

## Quick Test
1. Open Customers → Reports.
2. Open Customer List.
3. Open Customer Ledger.
4. Open Customer Statement.
5. Open Customer Aging.
6. Open Inactive Customers.
