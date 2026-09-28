# CUS_OPT_001 - Customer Module Performance Optimization

## Changed files

1. `Customers/Services/CustomerLedgerService.php`
2. `Customers/Services/CustomerDashboardService.php`

## What was optimized

### CustomerLedgerService

- Removed N+1 payment total queries from Dealer Portal Orders.
- Removed N+1 quantity total queries from Dealer Portal Orders.
- Removed N+1 payment total queries from Outstanding Invoices.
- Added batch helpers:
  - `paidForTransactions()`
  - `quantitiesForTransactions()`

This keeps the same output but reduces database round trips on large customer portals.

### CustomerDashboardService

- Replaced `whereBetween(DATE(column), ...)` style filtering with direct datetime ranges.
- This allows MySQL to use indexes on `transactions.transaction_date` and `transaction_payments.paid_on`.

## Optional SQL

`Customers/Database/sql/CUS_OPT_001_optional_indexes.sql` contains optional index recommendations.

Run the SQL only if these indexes do not already exist. If an index already exists, MySQL may show `Duplicate key name`; that line can be skipped.

## Safety notes

This package does not change:

- Customer table structure
- Ledger calculation rules
- Dealer portal routes
- Contact module
- Petro / PetroPD / Finance modules
- Payment posting logic

## After upload

Run:

```bash
php artisan optimize:clear
php artisan route:clear
php artisan view:clear
```
