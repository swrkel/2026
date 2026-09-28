# Leads-New Stage 11 RC3 Installation Notes

1. Replace/merge only `Modules/LeadsNew` files from this package.
2. Run migrations for tenant databases according to your normal deployment process.
3. Run the Leads-New permission seeder if permissions are not visible.
4. Clear Laravel caches: config, route, view, and permission cache.
5. Enable the module from Super Admin business/module permissions where applicable.
6. Test sidebar visibility, dashboard, add lead, edit lead, reports, settings, and audit.

This package is standalone and should not overwrite the existing Leads module.
