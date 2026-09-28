# SUPPLIERS-SEP-014 - Runtime Response and Middleware Isolation

## Purpose
Continue moving Suppliers module behaviour away from direct host/main-system helper usage by introducing module-local runtime wrappers.

## Added
- `Modules/Suppliers/Utils/SupplierResponseUtil.php`
  - module-local view wrapper
  - module-local redirect route wrapper
  - module-local status/error back response helpers
- `Modules/Suppliers/Utils/SupplierMiddlewareUtil.php`
  - central auth middleware name
  - central supplier permission middleware name builder
  - central supplier access middleware list

## Result
Supplier controllers/routes now have a module-local place to manage runtime responses and middleware names instead of spreading global helper calls throughout feature files.

## Remaining Work
SUPPLIERS-SEP-015 should migrate remaining controllers to call `SupplierResponseUtil` and `SupplierMiddlewareUtil` directly, then remove scattered `view()`, `back()`, and raw middleware string usage from supplier feature files.

## Framework-Level Dependencies That Remain Intentionally
- Laravel routing/controller base
- Laravel auth middleware name
- Laravel view/redirect contracts

These are framework infrastructure dependencies and not dependencies on another ERP module.
