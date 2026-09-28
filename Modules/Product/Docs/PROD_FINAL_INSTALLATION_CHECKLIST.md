# PROD-FINAL Installation Checklist

## Before upload
- Backup current `Modules/Product` folder.
- Backup database.
- Confirm current working Product module version is PROD-012 or later.

## Upload
- Replace `Modules/Product` with the included `Product` folder.
- Do not delete other modules.
- Do not overwrite main-system files unless your project explicitly maps modules differently.

## After upload
Run:

```bash
php artisan optimize:clear
php artisan route:clear
php artisan view:clear
php artisan config:clear
php artisan cache:clear
```

## Permission refresh
Run the Product permission seeder if your system uses DB permissions:

```bash
php artisan db:seed --class="Modules\\Product\\Database\\Seeders\\ProductPermissionSeeder"
```

## Quick checks
- Sidebar Product menu opens.
- Product list opens.
- Add/Edit Product works.
- Categories, Brands, Units, Variations save.
- Reports open.
- JS/CSS load from Product module paths.
