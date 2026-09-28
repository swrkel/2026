# RestaurantNew Stage 021 - Recommended Install Order

1. Backup current server code and database.
2. Upload full Stage 020 production package.
3. Upload Stage 021 support files if required.
4. Run master database SQL first.
5. Run tenant database SQL for each tenant database.
6. Clear Laravel cache.
7. Login as Super Admin and enable RestaurantNew for the test business.
8. Assign RestaurantNew permissions to test users.
9. Login as business user and start setup.

## Cache Commands
Use your normal deployment process. Common Laravel commands are:

```bash
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

Do not run commands directly on production unless your server process allows it.
