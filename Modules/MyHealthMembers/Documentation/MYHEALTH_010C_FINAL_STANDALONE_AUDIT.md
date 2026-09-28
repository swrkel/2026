# MYHEALTH_010C Final Standalone Hardening

## Scope
- Keep every MyHealth report inside the `MyHealthMembers` module.
- Strengthen sidebar discovery for ERP layouts that support dynamic module menus.
- Keep direct sidebar include available for ERP layouts that do not support dynamic module menu registration.

## Sidebar integration
Preferred automatic integration is handled by:
- `Providers/MyHealthMembersServiceProvider.php`
- `Services/Support/MyHealthSidebarRegistrar.php`
- `Config/menu.php`
- `Resources/views/partials/sidebar.blade.php`

If the host ERP sidebar is static and does not render `module_menus` or `module_sidebar_partials`, add this one line inside the main sidebar `<ul>`:

```blade
@includeIf('myhealthmembers::partials.sidebar')
```

This is the only required host-layout change for a static sidebar.

## Reports location
All reports remain inside this module:
- `Routes/reports.php`
- `Http/Controllers/Reports/MyHealthReportController.php`
- `Services/Reports/MyHealthReportService.php`
- `Resources/views/reports/*`

## Standalone status
The module now contains its own routes, controllers, services, entities, migrations, views, language file, config, sidebar partial and reports.
