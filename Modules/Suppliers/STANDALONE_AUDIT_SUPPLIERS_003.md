# SUPPLIERS-SEP-003 Standalone Audit

Continued Suppliers module separation.

## Completed in this stage
- Added module-owned entity wrappers for shared ERP tables used by Suppliers:
  - SupplierMedia
  - SupplierNote
  - SupplierActivityLog
  - SupplierAccountTransaction
  - SupplierFinancialMovement
- Updated document, notes, audit, and payment posting guard services to use Suppliers module entity classes instead of raw main-model dependencies.
- Fixed communication page asset tag syntax for module-local JS loading.
- Added Suppliers module table utility for future dependency cleanup.

## Remaining allowed shared database tables
The module still reads ERP shared tables such as contacts, transactions, transaction_payments, purchase_lines, products, business_locations, and account_transactions through Suppliers module entity/query classes where practical. This avoids dependency on main-system PHP files while preserving existing tenant data.

## Do not remove yet
Do not remove original Contact/shared ERP tables or unrelated main system files until final runtime testing confirms every Supplier page, report, import, export, ledger, payment, and purchase-history flow works only from `Modules/Suppliers`.
