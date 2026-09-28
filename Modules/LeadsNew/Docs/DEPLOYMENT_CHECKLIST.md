# Leads-New Deployment Checklist

1. Upload/replace the `Modules/LeadsNew` folder.
2. Confirm module is enabled in module registration.
3. Run migrations for the tenant database.
4. Run Leads-New seeders for statuses, sources, permissions, and settings.
5. Clear application, route, config, and view cache.
6. Enable Leads-New in Super Admin business manage page if required.
7. Assign Leads-New permissions to the relevant roles.
8. Test `/leads-new`, `/leads-new/dashboard`, `/leads-new/leads`, and `/leads-new/settings`.
9. Review `storage/logs/laravel.log` after first page load.
10. Confirm no existing Leads module files are changed.
