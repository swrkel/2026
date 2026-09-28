# ZIP 263 - Customers Dashboard Isolation

## Purpose
Adds a Customers-owned dashboard service and dashboard view so the Customers module dashboard no longer redirects to the register page.

## Files added/updated
- Modules/Customers/Services/CustomerDashboardService.php
- Modules/Customers/Http/Controllers/DashboardController.php
- Modules/Customers/Resources/views/dashboard/index.blade.php

## Notes
- Uses Customers module services/entities.
- Uses shared ERP tables only where appropriate: contacts, transactions, transaction_payments.
- Does not call legacy Contact module controllers/views.

## After upload
Run:
php artisan optimize:clear
php artisan view:clear
php artisan route:clear
