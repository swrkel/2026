# Leads-New Deployment Checklist RC6

1. Backup code and tenant databases.
2. Replace `Modules/LeadsNew` files from this package.
3. Clear Laravel caches.
4. Run migrations for tenant database if not already done.
5. Run permission seeder if not already done.
6. Enable Leads-New from Super Admin / All Business / Manage.
7. Assign Leads-New permissions to the required roles.
8. Verify sidebar appears only for enabled businesses.
9. Test Add/Edit/View/Delete Lead.
10. Test Follow-ups, Documents, Reports, Dashboard, Settings.
11. Run `php artisan leads-new:release-audit`.
