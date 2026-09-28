Distribution New - DISNEW_012 UI Stabilization

Purpose:
This parcel is to make the already completed Stage 1-11 module visible and testable from the UI before adding further Phase 2 functionality.

Important replacements:
1. Replace Modules/DistributionNew/Providers/DistributionNewServiceProvider.php
2. Replace Modules/DistributionNew/Routes/web.php
3. Add/replace Modules/DistributionNew/Routes/api.php
4. Add Modules/DistributionNew/Config/menu.php
5. Add Modules/DistributionNew/Resources/views/layouts/sidebar.blade.php
6. Add/replace Modules/DistributionNew/Resources/lang/en/lang.php
7. Run Database/Sql/DISNEW_012_UI_STABILIZATION.sql in every tenant database.

Sidebar visibility:
- If the main application sidebar supports module includes, include:
  @includeIf('distributionnew::layouts.sidebar')
- If not, copy the menu block from Resources/views/layouts/sidebar.blade.php into the main sidebar where other module menus are listed.

Routes to test after upload:
/distribution-new
/distribution-new/sales-orders
/distribution-new/sales-invoices
/distribution-new/vehicles
/distribution-new/loading-plans
/distribution-new/loading
/distribution-new/unloading
/distribution-new/vehicle-stock
/distribution-new/territories
/distribution-new/routes
/distribution-new/sales-reps
/distribution-new/deliveries
/distribution-new/collections
/distribution-new/settlements
/distribution-new/returns
/distribution-new/credit-notes
/distribution-new/reports
/distribution-new/settings

Super Admin test URLs:
/superadmin/distribution-new/business/{businessId}/vehicle-limit
/superadmin/distribution-new/business/{businessId}/limits

Recommended after upload:
- Clear route/config/view cache if your server uses cached Laravel files.
- Enable Distribution New permissions for the test role/user.
- Then test the dashboard and each sidebar menu item.
