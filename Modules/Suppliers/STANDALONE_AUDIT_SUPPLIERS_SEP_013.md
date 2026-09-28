# SUPPLIERS-SEP-013 - View Runtime Boundary Separation

## Completed
- Added `SupplierViewRuntimeUtil` as a Suppliers module-local view runtime wrapper.
- Removed direct view-level `app()->getLocale()` usage from the Suppliers layout.
- Removed direct view-level `session('status')` flash dependency from the Suppliers layout.
- Removed direct view-level `request()->route(...)` usage from Suppliers tab partials.
- Removed direct view-level `request()->routeIs(...)` usage from Suppliers communication navigation partials.
- Removed direct view-level `request()->query()` usage from Suppliers pagination.

## Purpose
This stage keeps helper/runtime access centralized inside Suppliers module utility files instead of spreading main-system/framework helper calls across many Blade files. This makes the module easier to move, audit, and maintain independently.

## Remaining Boundaries
The module still requires Laravel infrastructure for routing, authentication/session, database connection, translations, assets, and tenant context. These are framework/runtime boundaries, not dependencies on another business module.
