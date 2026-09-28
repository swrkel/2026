# SUPPLIERS-SEP-010 - Main-System File Dependency Cleanup

## Completed
1. Removed the old legacy-contact service file name and replaced it with `SupplierStandaloneContactService`.
2. Removed remaining direct `DB::table()` calls from supplier services where a Suppliers module entity exists.
3. Updated audit, document, note, financial, purchase-summary, payment-posting, and ledger-summary services to use module-local entities.
4. Fixed incorrect entity relationship class references in `SupplierTransaction` and `SupplierTransactionPayment`.
5. Fixed ledger detail service references to module-local transaction and payment entities.
6. Removed the old main-system Contact morph class string from supplier morph utility so new supplier documents/notes/audit queries use only the Suppliers module entity type.
7. Rechecked PHP syntax for all module PHP files.

## Dependency Result
No direct PHP reference remains to other business modules or main-system application classes.

Allowed infrastructure only:
- Laravel framework classes
- Illuminate DB/Schema/Request/Controller infrastructure
- Session/auth/permission middleware strings
- Tenant/business database tables already used by the ERP schema

## Note
This stage focuses on removing file/class dependencies, not changing the tenant database schema. Existing database table names are still used through Suppliers module entity wrappers so the module can work inside the existing multi-tenant ERP databases.
