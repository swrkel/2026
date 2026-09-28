# ZIP 270 – Customers Permissions Hardening

This package adds a Customers-owned `CustomerPermissionService` and applies it to the Customers controllers.

## Purpose
- Centralize Customers permissions inside `Modules/Customers`.
- Avoid depending on legacy Contact module controllers/helpers for access checks.
- Keep backwards compatibility with existing ERP permissions such as `customer.view` and `contact.view`.

## Files changed
- `Modules/Customers/Services/CustomerPermissionService.php`
- `Modules/Customers/Http/Controllers/CustomerController.php`
- `Modules/Customers/Http/Controllers/CustomerReportController.php`
- `Modules/Customers/Http/Controllers/DashboardController.php`
- `Modules/Customers/Http/Controllers/CustomerProfileController.php`

## After upload
Run:

```bash
php artisan optimize:clear
php artisan view:clear
php artisan route:clear
```
