# CUS_SEP_007 - Customer Dashboard & Menu Separation

## Purpose
Move Customer dashboard/menu ownership further into `Modules/Customers` and reduce direct UI dependency on Contact module menu/dashboard code.

## Included
- Customers-owned dashboard controller
- Customers-owned register entry controller alias
- Customers-owned menu resolver service
- Professional dashboard page with separate partials
- Reusable Customers menu partial
- Sidebar cleanup using Customers menu service
- Safe `/customers/register` route alias while keeping existing `/customers` route intact

## Safety Notes
This package does not change:
- Contact/customer database structure
- Ledger calculations
- Distribution Dealer portal routes/views
- Petro / PetroPD / Finance modules

## Upload Steps
1. Replace included files using the same paths.
2. Run:

```bash
php artisan optimize:clear
php artisan route:clear
php artisan view:clear
```

## Test Steps
1. Open Customers module sidebar.
2. Check Dashboard menu.
3. Open Customer Register.
4. Open Reports / Ledger / Statement / Master Data.
5. Confirm existing Customer Register action buttons still work.
