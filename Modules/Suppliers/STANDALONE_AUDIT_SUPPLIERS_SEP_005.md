# SUPPLIERS-SEP-005 Standalone Audit

## Completed in this package
- Replaced recursive `suppliers::layouts.app` implementation with a real Suppliers module-local layout shell.
- Removed remaining shared translation references from Suppliers views (`messages`, `business`, `account`, `sale`, `purchase`, `lang_v1`) and mapped them to `suppliers::lang` keys.
- Added missing Suppliers module language keys used by ledger, mapping, stock, payments, and filter views.
- Added module-local layout CSS so the Suppliers module can render without depending on a main-system layout file.
- Corrected stale route references from `suppliers.index` to `suppliers.records.index`.

## Current dependency status
- Controllers, services, entities, views, JS, CSS, and language files are now inside `Modules/Suppliers`.
- Supplier business data still uses existing ERP tables such as contacts, transactions, transaction_payments, products, and business_locations through Suppliers module entity wrappers. This is database reuse, not code-file dependency.
- Compatibility routes to old Contact URLs are still present only for menu/bookmark migration. They can be removed after sidebar/menu links are fully changed to `suppliers.*` routes.

## Next recommended stage
SUPPLIERS-SEP-006 should focus on:
- Removing compatibility routes if the main sidebar has already been updated.
- Adding Suppliers module permission registration/config files.
- Final grep audit for any old route names and shared helper/view references.
