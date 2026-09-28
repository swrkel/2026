# MYHEALTH_010E Final Verification

## Scope
This pass keeps all MyHealth reports inside the `MyHealthMembers` module and adds a practical sidebar installation helper for ERP layouts that do not automatically render module sidebar partials.

## Reports location
All report routes, controllers, services, and views remain inside the module:

- `Modules/MyHealthMembers/Routes/reports.php`
- `Modules/MyHealthMembers/Http/Controllers/Reports/MyHealthReportController.php`
- `Modules/MyHealthMembers/Services/Reports/MyHealthReportService.php`
- `Modules/MyHealthMembers/Resources/views/reports/*`

## Sidebar visibility
The module already registers sidebar variables through the service provider. Some ERP layouts do not consume module sidebar variables automatically. For those systems, run this once from the Laravel project root:

```bash
php Modules/MyHealthMembers/Tools/install_sidebar_patch.php
```

The command inserts this line inside the main sidebar `<ul>` and creates a backup before writing:

```blade
@includeIf('myhealthmembers::partials.sidebar')
```

## Manual fallback
If the helper cannot locate your sidebar file, add this line manually inside the main ERP sidebar `<ul class="sidebar-menu">`:

```blade
@includeIf('myhealthmembers::partials.sidebar')
```

## Clear cache commands after replacement

```bash
php artisan optimize:clear
php artisan module:enable MyHealthMembers
php artisan route:clear
php artisan view:clear
php artisan config:clear
```

If `module:enable` is not available in the ERP, skip that command.
