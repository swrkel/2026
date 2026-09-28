# CUS-035 Distribution Dealer AI Assistant

## Scope
Adds a standalone Dealer AI Assistant inside the Customers module portal.

## Included
- Dealer Assistant page
- Smart recommendations
- Outstanding / credit / invoice / payment / order / delivery / reward question handling
- AJAX-ready assistant endpoint
- Navigation link

## Safety
This package only changes the Customers module. It does not touch Contact, Petro, PetroPD, Finance, Distribution, PumperDashboard, or ERP sidebar files.

## Test
1. Login as Distribution Dealer.
2. Open `/distribution-dealer/assistant`.
3. Ask: `What is my outstanding balance?`
4. Ask: `How much credit do I have available?`
5. Ask: `Which invoices are overdue?`

## Clear Cache
```bash
php artisan optimize:clear
php artisan route:clear
php artisan view:clear
```
