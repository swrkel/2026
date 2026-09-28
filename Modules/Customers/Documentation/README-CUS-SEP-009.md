# CUS_SEP_009 - Customer Routes, Middleware & Permissions Separation

## Purpose
This package moves the Customers module security ownership further inside `Modules/Customers`.

## Included
- Customers-owned permissions config
- Dedicated Customers middleware aliases
- Customer access middleware
- Customer portal middleware
- Customer approval middleware
- Customer credit middleware
- Customer report middleware
- Customer policies
- Customer menu/permission service alignment

## Safety
This package does not change:
- customer database structure
- ledger calculations
- dealer portal data
- Petro / PetroPD / Finance modules

## After Upload
Run:

```bash
php artisan optimize:clear
php artisan route:clear
php artisan view:clear
```
