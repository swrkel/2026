# DIST-324 - Distribution Final Cross-Module Cleanup

Purpose: reduce remaining direct main ERP `\App\...` model references inside Distribution while keeping behavior unchanged.

## What this package does

- Adds a targeted root-level tool: `tools/apply_dist324_distribution_cross_module_cleanup.php`
- The tool only scans and updates files under `Modules/Distribution`.
- It replaces direct fully-qualified main ERP model references such as `\App\Transaction`, `\App\Contact`, `\App\Product`, `\App\User`, etc. with Distribution-owned compatibility wrappers under `Modules\Distribution\Entities\Core`.
- It backs up every changed file as `.dist324.bak` before modifying it.
- It removes the remaining fallback include to the main `contact.create` view from the Distribution quick-create partial where the Distribution-owned contact partial exists.

## Why this is safe

This stage does not change tables, routes, request validation, or business logic. The wrapper classes still point to the same database tables but make Distribution own the reference path. This is a controlled step toward standalone ownership.

## Run from Laravel project root

```bash
php tools/apply_dist324_distribution_cross_module_cleanup.php
php artisan optimize:clear
php artisan route:clear
php artisan view:clear
```

## Next stage

DIST-325 should run another dependency audit and then remove remaining shared Blade / JS / utility references one by one.
