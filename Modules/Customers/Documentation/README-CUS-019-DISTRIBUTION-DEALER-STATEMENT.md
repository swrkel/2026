# CUS_019 Distribution Dealer Statement / Invoices / Payments

## Scope
- Adds separate Customer Module portal files for Distribution Dealer statement, invoices, payments and profile.
- Keeps the portal isolated from Contacts module pages and ERP sidebars.
- Uses Contact / Customer Statement as the feature reference, but implements short standalone Customers module code.

## Replace Files
Upload the included `Customers` folder into `Modules/Customers`.

## Test URLs
- `/distribution-dealer/login`
- `/distribution-dealer/dashboard`
- `/distribution-dealer/statement`
- `/distribution-dealer/invoices`
- `/distribution-dealer/payments`
- `/distribution-dealer/profile`

## Clear Cache
```bash
php artisan optimize:clear
php artisan view:clear
```
