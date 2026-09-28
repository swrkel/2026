# SUPPLIERS-SEP-002 Standalone Audit

Completed in this package:
- Static module routes moved before dynamic `{supplier}` routes so payments/imports/reports/mappings are not captured as supplier IDs.
- Added missing `SupplierController::data()` route handler.
- Added module-local actions partial for AJAX/list usage.
- Converted remaining controllers away from direct `Illuminate\Routing\Controller` imports to the module-local `BaseSupplierController`.
- Enhanced `BaseSupplierController` with shared tenant/type access and permission helpers.
- Replaced remaining visible Purchase/Sale/Messages language references in supplier views with `suppliers::lang` keys.

Still intentionally shared with ERP infrastructure:
- Laravel framework, auth/session/middleware, tenant session, DataTables/Form package, and existing database tables.
- Supplier entity maps to the existing `contacts` table because the ERP stores suppliers in that table. This is database compatibility, not a dependency on the old Contact module code.
