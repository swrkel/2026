# Restaurant-New 2.0

Restaurant-New is a standalone Laravel/Nwidart restaurant-operations module for a multi-tenant, multiple-database ERP. Restaurant business logic, database tables, models, controllers, requests, services, routes, views, CSS, JavaScript, language files, permissions, reports, print layouts, migrations and raw SQL are kept inside `Modules/RestaurantNew`.

## Main screens

- Dashboard and manager control centre
- Waiter touch ordering
- Cashier billing and split payments
- Kitchen display/KDS and KOT printing
- Takeaway operations
- Collection-centre token board
- Reservations
- Delivery dispatch and driver workflow
- Menu, modifiers, discounts, recipes and setup
- Suppliers, goods receipts, stock, transfers, stocktakes and wastage
- Thirteen permission-controlled report tabs
- Settings and per-user screen assignments

## Isolation and tenancy

- All **45** module-owned tables use the exact `restnew_` prefix.
- All operational records carry `business_id`; location-sensitive records also carry `location_id`.
- Restaurant-New does not write into another feature module's tables.
- No database foreign keys point to another module's tables.
- The module uses the application's core contracts only for authenticated users, current tenant/business/location, permissions and the global application layout.
- Tenant middleware and a central-host guard prevent operational tables from being installed intentionally on a recognised central domain.

## Main workflow

1. Open a location-specific restaurant shift.
2. Create a dine-in, takeaway or delivery order from the waiter screen.
3. Validate item availability and required/optional modifiers in the browser and again on the server.
4. Split order items into station-specific KOTs.
5. Accept, prepare and mark kitchen tickets ready on KDS.
6. Consume recipe ingredients once when an item becomes ready; reverse consumption when an already-prepared item is validly voided.
7. Receive controlled split payments and print the bill.
8. Issue a collection token only for takeaway orders; delivery orders use the separate dispatch workflow.
9. Maintain receiving, transfers, stocktakes and wastage through transaction-locked stock movements.
10. Record sensitive changes in the Restaurant-New audit/adjustment history.

## Database choices

For a fresh tenant installation, run:

`Database/SQL/00_MASTER_INSTALL_RESTAURANT_NEW.sql`

For a tenant already running the Stage 1 parcel, run:

`Database/SQL/07_MASTER_UPGRADE_STAGE2_RESTAURANT_NEW.sql`

Both paths are idempotent. Laravel migration files are also included for tenant-aware deployments.
