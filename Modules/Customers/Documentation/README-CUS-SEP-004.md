# CUS_SEP_004 - Customer Reports Separation

## Purpose
Move Customer Module reports away from one large shared report controller into separate report controllers/services inside `Modules/Customers`.

## Included
- Customer Report Index Controller
- Customer List Report Controller
- Customer Ledger Report Controller
- Customer Statement Report Controller
- Customer Ageing Report Controller
- Inactive Customer Report Controller
- Customer Balance Report Controller
- Customer Transaction Report Controller
- Customer Payment Report Controller

## New standalone report pages
- Customers → Reports → Customer Balance Report
- Customers → Reports → Customer Transaction Report
- Customers → Reports → Customer Payment Report

## Safety notes
This package does not change:
- customer database tables
- Customer ledger posting logic
- Distribution Dealer portal routes/views
- Contact module files
- Petro / PetroPD / Finance module files

## After upload
Run:

```bash
php artisan optimize:clear
php artisan route:clear
php artisan view:clear
```

## Test
1. Open Customers → Reports.
2. Open Customer List, Ledger, Statement, Aging, Inactive Customers.
3. Open Customer Balance Report.
4. Open Customer Transaction Report.
5. Open Customer Payment Report.
6. Test CSV and Print buttons.
