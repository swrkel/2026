# RestaurantNew Installation Guide - RESTNEW 030

## Purpose
This package is the consolidated enterprise release for the standalone `RestaurantNew` module.

## Deployment order
1. Backup application files and all databases.
2. Copy `Modules/RestaurantNew` into the Laravel `Modules` directory.
3. Copy public assets if your deployment process publishes module assets manually.
4. Run SQL scripts in this order:
   - `SQL/CREATE`
   - `SQL/ALTER`
   - `SQL/INDEX`
   - `SQL/INSERT`
   - `SQL/PERMISSIONS`
   - `SQL/SEEDERS`
5. Clear Laravel cache/config/routes/views.
6. Enable the module in `modules_statuses.json` if your system requires manual module activation.
7. Login as Super Admin and enable RestaurantNew for the required businesses.
8. Assign permissions to users/roles.
9. Test with one tenant, one business, and one location before enabling all locations.

## Important tenant note
All RestaurantNew operational tables must be created in each tenant database where the module will be used. Central/master database scripts should only be used for module registration and Super Admin activation where applicable.
