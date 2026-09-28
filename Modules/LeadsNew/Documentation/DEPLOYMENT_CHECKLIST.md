# Leads-New v1.0 Deployment Checklist

1. Upload the package to the Laravel root.
2. Replace only the included `Modules/LeadsNew` files and listed integration hooks.
3. Run composer dump-autoload if required by your deployment process.
4. Run module migrations for Leads-New only.
5. Clear config/view/route cache.
6. Enable Leads-New in Super Admin / All Businesses / Manage for the test business.
7. Assign Leads-New permissions to the test role.
8. Login as a normal business user.
9. Verify sidebar entry, dashboard, list, add, edit, view, settings, and reports.
10. Check `storage/logs/laravel.log` after every major test.
