# ZIP 254 - Customers Standalone Dependency Audit

Generated: 2026-06-13 06:54:35

Scope: Modules/Customers and sidebar-customers.blade.php.

Audit target:
- Remove runtime dependency on legacy Contact module controllers, views, and utilities.
- Keep shared ERP database tables such as contacts, transactions, accounts, business, users as data sources where needed.

Result after cleanup preparation:
- Legacy App\Contact direct imports: removed from module runtime code.
- Legacy App\ContactGroup direct imports: removed from module runtime code.
- Legacy App\User direct imports: removed from module runtime code.
- Legacy ContactController / ContactUtil / resources/views/contact references: none found in module runtime code after cleanup.
- Remaining references, if any, are listed in customers_dependency_audit_zip254.csv.

Next package:
ZIP 255 - Customers Controller / Service / Entity Separation.
