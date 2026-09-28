# CUS_OPT_003 Customer Code Cleanup & Service Refactoring

## Changed files

1. `Modules/Customers/Http/Controllers/CustomerPortalOrderController.php`
2. `Modules/Customers/Services/CustomerPortalOrderService.php`

## What was optimized

- Moved dealer order/product lookup helper methods out of the controller and into a dedicated service.
- Reduced repeated product pricing, stock, unit, order line, order event, favourite-product, and workflow helper logic inside the controller.
- Kept the same route names, views, table names, and session keys to avoid changing working functionality.
- Kept Distribution Dealer portal flow unchanged.

## Safe areas intentionally not changed

- Customer ledger calculations
- Customer statement calculations
- Dealer login/session logic
- Petro/PetroPD/Finance/Contact modules
- Existing database structure

## After upload

Run:

```bash
php artisan optimize:clear
php artisan route:clear
php artisan view:clear
```
