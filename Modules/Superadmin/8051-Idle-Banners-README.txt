TASK 8051 - IDLE SCREEN BANNERS
================================

Scope implemented
-----------------
1. Adds a new "Banners Management" section to Super Admin > All Businesses > Manage New.
2. Adds "Show the Banners in Users' screen when the screen is idle more than" (minutes).
3. Adds searchable, scrollable multi-select Tenant dropdown showing Database Name + Tenant UID.
4. Adds dependent searchable, scrollable multi-select Business dropdown.
5. "All" is the first/default option for both Tenant and Business.
6. On eligible authenticated tenant screens, the idle viewer merges:
   - central banners applicable to the current tenant; and
   - banners stored directly in the current tenant database (when a tenant banners table exists).
7. Multiple banners rotate using each banner's existing display_duration value.
8. Existing central banner pause setting is respected.
9. User activity dismisses the idle viewer and resets the idle timer.
10. The new settings are stored separately from subscription/package permissions.

Database change
---------------
One CENTRAL table is added: banner_idle_settings.

Preferred deployment:
  php artisan migrate

If this installation does not run module migrations against the central database,
run this supplied file manually against the CENTRAL database only:
  Modules/Superadmin/Database/Sql/8051_banner_idle_management.sql

Use EITHER the migration or the manual SQL route according to your normal deployment process.
No tenant database schema change is required for this feature.

Deployment
----------
1. Back up the current Modules/Superadmin folder and CENTRAL database.
2. Upload/extract this package preserving paths.
3. Apply the central database change as described above.
4. Run:
     php artisan optimize:clear
5. Open Super Admin > All Businesses > Manage New > Banners Management.
6. Enter the idle time in minutes, choose Tenant(s) and Business(es), and Save.
7. Test first with 1 minute on a staging/test business.

Important behavior
------------------
- idle_minutes = 0 keeps the idle banner viewer disabled.
- Default targeting is All Tenants + All Businesses.
- Central banners with no banner_tenants rows apply to all tenants, matching the existing banner manager behavior.
- Central banners with tenant associations apply only to those tenants.
- Tenant-local banners are additive; they do not replace central banners.
- Inactive banners are excluded. Tenant soft-deleted banners are excluded when deleted_at exists.
- Duplicate central/tenant banners are de-duplicated by banner code, or image/title fallback.
- Only HTTP/HTTPS banner links are exposed to the idle viewer.
- The runtime payload is read-only and uncached.

Changed / new files
-------------------
Modules/Superadmin/Http/Controllers/BusinessController.php
Modules/Superadmin/Http/Controllers/BannerManagementController.php
Modules/Superadmin/Http/Middleware/InjectIdleBanner.php
Modules/Superadmin/Http/routes.php
Modules/Superadmin/Providers/SuperadminServiceProvider.php
Modules/Superadmin/Services/BannerIdleService.php
Modules/Superadmin/Resources/views/business/manage_module.blade.php
Modules/Superadmin/Resources/views/business/partials/banner_management.blade.php
Modules/Superadmin/Resources/views/idle_banner/runtime.blade.php
Modules/Superadmin/Database/Migrations/2026_09_06_000100_create_banner_idle_settings_table.php
Modules/Superadmin/Database/Sql/8051_banner_idle_management.sql

Safety notes
------------
- No payment, settlement, ledger, stock, product-price, POS transaction, or accounting logic was modified.
- No existing banners or banner_tenants schema was changed.
- Banners Management does not modify subscription package_details or Manage New permission keys.
