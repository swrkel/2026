# ZIP 267 – Customers Export / PDF / Print Isolation

## Purpose
This package continues the Customers standalone module work by adding Customers-owned export and print support for the report pages.

## Added
- `Modules/Customers/Exports/CustomerCsvExport.php`
- `Modules/Customers/Exports/CustomerExport.php`
- `Modules/Customers/Exports/CustomerLedgerExport.php`
- `Modules/Customers/Exports/CustomerStatementExport.php`
- `Modules/Customers/Exports/CustomerAgingExport.php`
- `Modules/Customers/Exports/CustomerActivityExport.php`

## Updated
- `Modules/Customers/Http/Controllers/CustomerReportController.php`
- `Modules/Customers/Routes/web.php`
- Customer report blade files now include Customers-owned CSV and Print buttons.

## Notes
- CSV export is implemented without depending on the old Contact module or external export controllers.
- Print uses the browser print action, keeping it inside Customers views.
- PDF export should be added later only if the server has the PDF library enabled; this package avoids adding a new risky dependency.

## After Upload
Run:

```bash
php artisan optimize:clear
php artisan view:clear
php artisan route:clear
```
