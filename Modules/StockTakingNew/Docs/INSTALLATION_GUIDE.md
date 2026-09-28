# Installation Guide

## Recommended installation

1. Back up application files and every tenant database.
2. Extract the parcel into the Laravel project root, preserving folders.
3. Run `Modules/StockTakingNew/Database/SQL/00_MASTER_INSTALL_STOCK_TAKING_NEW.sql` on each tenant database.
4. From the project root run:

```bash
composer dump-autoload
php artisan module:enable StockTakingNew
php artisan optimize:clear
```

5. Confirm `StockTakingNew` is `true` in `modules_statuses.json`.
6. Open **Super Admin → All Businesses → Manage** and save the Stock Taking - New module/page/tab choices for each business.
7. Assign the `stock_taking_new.*` user permissions to non-admin roles that require access. Business Admin users retain the application’s normal administrator bypass.

## Alternative migration installation

Run the module migrations in each tenant context. The module also contains a schema self-check that can create missing `stk_` tables when the first authenticated module request is made.

## Scheduled counts

The standalone command processes schedules in the currently selected database connection:

```bash
php artisan stock-taking-new:run-schedules
```

For Stancl multi-database tenancy, run that command through the application’s existing tenant-aware command runner or invoke it separately in each tenant context from cron. Recommended frequency: every five minutes.

## Communications

Email uses the existing Laravel mail configuration. Without an SMS/WhatsApp gateway, the module generates a secure downloadable link and opens the device’s native SMS or WhatsApp compose URL. API delivery can be enabled with the settings page or these environment values:

```dotenv
STK_SMS_ENDPOINT=
STK_SMS_TOKEN=
STK_SMS_SENDER_ID=
STK_WHATSAPP_ENDPOINT=
STK_WHATSAPP_TOKEN=
STK_WHATSAPP_PHONE_NUMBER_ID=
```

## Automatic registration verification

After `php artisan module:enable StockTakingNew` and `php artisan optimize:clear`:

1. Open Super Admin → All Businesses → Manage.
2. Search for `Stock Taking - New`.
3. Confirm the module section and its separate page/action permissions are listed.
4. Save the module for the required business.
5. Sign in to that business and confirm the Stock Taking - New menu appears automatically.

The menu is owned by `Modules/StockTakingNew/Resources/views/layouts/partials/sidebar.blade.php`; do not manually add it to a shared sidebar file.

## STK_003 route verification

After deploying STK_003, run:

```bash
composer dump-autoload
php artisan module:enable StockTakingNew
php artisan optimize:clear
php artisan route:list --name=stock-taking-new
```

Open `/stock-taking-new` on the same tenant domain where the user logged in.
