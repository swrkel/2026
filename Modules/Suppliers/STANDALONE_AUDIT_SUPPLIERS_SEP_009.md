# SUPPLIERS-SEP-009 - Dependency Cleanup

Completed in this stage:

1. Removed old Contact/Product compatibility routes from `Modules/Suppliers/Routes/web.php`.
   - Supplier pages now expose only `suppliers.*` module routes.
   - Old routes such as `/contacts/suppliers`, `/contacts/payments`, `/contacts/ledger`, and `/product/product-bind-supplier` are no longer registered by the Suppliers module.

2. Replaced the last legacy-named service:
   - Removed `SupplierStandaloneContactService.php`.
   - Added `SupplierRecordService.php`.
   - Updated controllers to use the Suppliers-named service.

3. Hardened module routes.
   - Added numeric constraints for `{supplier}` parameters so module static routes are not accidentally interpreted as supplier IDs.

4. Re-ran dependency scan for main-system route/view references.
   - No `contact::`, `contacts::`, `purchase::`, `finance::`, or old compatibility route registrations remain in active Suppliers route/view/controller code.

Notes:
- The module still reads shared tenant database tables such as `contacts`, `transactions`, `transaction_payments`, `products`, `business_locations`, and accounting tables through Suppliers module entities/services. This is database reuse, not PHP file dependency.
- `SupplierMorphTypeUtil` keeps legacy morph string support for existing stored attachments/notes/audit data. This does not load or extend the main Contact PHP class.

Status after SEP-009:
- Controllers: module-local
- Services: module-local
- Routes: module-local, no Contact compatibility routes
- Views/layouts: module-local
- JS/CSS: module-local
- Language files: module-local
- Permissions utility: module-local
- Reports/ledger/statement services: module-local

Remaining final step:
- `SUPPLIERS-AUDIT-001` final standalone verification before deleting any original supplier/contact-related code from the main system.
