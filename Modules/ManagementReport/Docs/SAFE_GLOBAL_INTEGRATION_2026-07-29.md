# Safe global sidebar and Manage integration

This build uses the application's latest global framework without copying or
changing global sidebar/Manage files.

## Root cause removed

The prior module contained complete copies of the application's main sidebar at:

- `Resources/views/layouts/partials/sidebar.blade.php`
- `Resources/views/layouts/sidebar.blade.php`

Those copies included `layouts.partials.automatic-module-sidebar`. The global
automatic registry discovered the copied module sidebar and included it while
rendering the main sidebar; the copied sidebar then included the automatic
registry again. This recursive Blade rendering exhausted the 2 GB PHP memory
limit and produced slow refreshes and HTTP 500 errors.

## Current integration

- One compact module-owned sidebar partial contains only Management Report links.
- `Config/module_permissions.php` declares the five user-facing pages to the
  current global Manage discovery system.
- New business/page keys default to enabled under the existing global rule.
- The module does not ship any global sidebar, global service, global provider,
  root status, or public entry file.
