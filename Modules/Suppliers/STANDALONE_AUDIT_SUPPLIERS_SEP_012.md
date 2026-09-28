# SUPPLIERS-SEP-012 - Runtime Context & Main-System Dependency Reduction

## Completed

1. Fixed `SupplierContextUtil` recursion errors in `userId()` and `check()`.
2. Added module-local context methods for:
   - business id
   - location id
   - user id
   - current user
   - permission check
   - supplier tenant/type access check
3. Centralized Supplier controller authorization and supplier access checks inside `SuppliersBaseController`.
4. Converted `BaseSupplierController` into a module-local compatibility alias so old controllers do not need the host application's base controller.
5. Kept all Supplier business logic inside `Modules/Suppliers`.

## Important Note

A Laravel module cannot run without framework/runtime services such as routing, auth session, database connection, middleware, and tenant bootstrapping. SUPPLIERS-SEP-012 keeps those unavoidable runtime calls inside one module-local wrapper instead of spreading them across Supplier feature files.

## Remaining Target

SUPPLIERS-SEP-013 should continue with:
- replacing repeated inline supplier access checks with `ensureSupplierAccess()`
- scanning views for shared layout/includes
- moving any remaining shared blade partial references into `Modules/Suppliers/Resources/views`
- final report/dead-code cleanup
