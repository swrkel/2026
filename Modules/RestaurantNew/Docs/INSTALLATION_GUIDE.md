# Restaurant-New 2.0 Installation Guide

## Before deployment

1. Back up the application files, `modules_statuses.json`, central database and every tenant database.
2. Confirm tenant-domain login and tenant database switching are working before adding the module.
3. Copy the contents of this parcel's `1. latest-code` folder into the Laravel project root, preserving paths.

## Fresh tenant installation

Run against **each tenant database only**:

`Modules/RestaurantNew/Database/SQL/00_MASTER_INSTALL_RESTAURANT_NEW.sql`

## Upgrade from the Stage 1 Restaurant-New parcel

Run against each Stage 1 tenant database:

`Modules/RestaurantNew/Database/SQL/07_MASTER_UPGRADE_STAGE2_RESTAURANT_NEW.sql`

The upgrade creates the 15 Stage 2 tables, applies missing Stage 2 columns/indexes and inserts all missing permissions without duplicating existing data.

## Laravel migration alternative

Use only through the application's tenant-aware migration workflow:

```bash
php artisan module:migrate RestaurantNew
```

Do not run tenant operational migrations on the central connection.

## Application commands

```bash
cd /path/to/laravel/project
composer dump-autoload
php artisan module:enable RestaurantNew
php artisan optimize:clear
php artisan route:list --name=restaurant-new
```

The supplied `modules_statuses.json` preserves the supplied module states and adds `"RestaurantNew": true`.

## Permissions and access

1. Open **Super Admin → All Businesses → Manage**.
2. Search for **Restaurant-New Module**.
3. Enable the required pages and report tabs for the business.
4. Assign the required `restaurant_new.*` role permissions.
5. Open **Restaurant-New → Settings** to assign waiter, cashier, kitchen, takeaway or collection screens to users. Kitchen users may be restricted to a station.

## Initial setup order

1. Restaurant settings and printers
2. Floors, tables and kitchen stations
3. Screen assignments
4. Suppliers and ingredients
5. Menu categories, items and modifiers
6. Recipes
7. Delivery zones and discount rules, when used
8. Opening ingredient stock or goods receipts
9. Open shift and create test orders

## Verification

Run against every tenant database:

`Modules/RestaurantNew/Database/SQL/05_VERIFY_INSTALLATION.sql`

Expected results:

- 45 `restnew_` tables
- 55 `restaurant_new.*` permissions
- no missing table or permission rows

Set `RESTAURANT_NEW_AUTO_INSTALL=false` after controlled database deployment when automatic tenant schema installation is not required.
