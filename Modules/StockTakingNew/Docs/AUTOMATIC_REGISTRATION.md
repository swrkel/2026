# Automatic Sidebar and Super Admin Manage Registration

`StockTakingNew` uses the application's existing `App\Services\AutomaticModuleRegistry`.
No permanent hand-written Stock Taking entry is required in a shared application sidebar or in the Super Admin Manage Blade file.

## How the module is discovered

- `Modules/StockTakingNew/module.json` supplies the module name and route alias.
- `Modules/StockTakingNew/Routes/*.php` supplies page/action metadata for Super Admin → All Businesses → Manage.
- `Modules/StockTakingNew/Resources/views/layouts/partials/sidebar.blade.php` supplies the complete module-owned sidebar menu.
- `modules_statuses.json` or `php artisan module:enable StockTakingNew` controls whether the installed module participates in discovery.

## Expected behaviour

1. The module section appears automatically on Super Admin → All Businesses → Manage.
2. The parent module and discovered page/action permissions are enabled by default when no saved value exists.
3. The Manage page's Select All and Clear All controls work with the discovered permissions.
4. Saving the parent module as disabled hides the sidebar and blocks direct module URLs for that business.
5. Saving an individual page permission as disabled hides the related child sidebar link and blocks its direct route.
6. The module menu is rendered from the module folder, so a future module update does not require editing a shared sidebar file.

## Cache refresh after deployment

Run:

```bash
php artisan module:enable StockTakingNew
php artisan optimize:clear
```

The registry fingerprint will then rebuild automatically on the next request.
