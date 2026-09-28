# CUS_SEP_005 Customer Master Data Separation v2

## Purpose
Moves customer master-data screens into `Modules/Customers` with separate controllers, services, routes, views, JS and language keys.

## Included Areas
- Customer Groups
- Customer Types
- Customer Categories
- Customer Classifications
- Customer Custom Fields
- Customer Settings
- Customer Opening Balance Tools

## Important Safety Notes
- Does not change existing customer records.
- Does not change ledger calculations.
- Does not touch Dealer Portal, Petro, PetroPD, Finance or Contact controllers.
- Customer Groups still use the existing `contact_groups` table for compatibility, but the UI and controller are now owned by Customers Module.

## Install Steps
1. Upload included files.
2. Run SQL file on each tenant database:
   `Customers/Database/sql/CUS_SEP_005_customer_master_data.sql`
3. Clear cache:
   `php artisan optimize:clear`
   `php artisan route:clear`
   `php artisan view:clear`

## Test
- Customers Module → Customer Master Data
- Open each master-data page
- Add / Edit / Delete one test record
- Confirm Customer Register and Dealer Portal continue loading normally.
