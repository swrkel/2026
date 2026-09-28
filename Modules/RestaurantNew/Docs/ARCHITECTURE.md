# Restaurant-New Architecture

## Module boundaries

- `Entities`: 45 concrete Restaurant-New Eloquent models plus the module base model.
- `Http/Controllers`: small controllers grouped by operational responsibility.
- `Http/Requests`: permission-aware request validation.
- `Services`: ordering, pricing, kitchen, collection, delivery, shifts, payments, inventory, procurement, transfers, stocktakes, wastage, discounts, reports, settings, audit and schema installation.
- `Routes`: 79 named module routes under `/restaurant-new`.
- `Resources/views`: role screens, administration pages, reports and print layouts.
- `Resources/assets`: independent POS-style CSS and JavaScript published under `public/modules/restaurantnew`.
- `Permissions`: 55 `restaurant_new.*` capabilities.
- `Database`: idempotent migrations, permission seeder, fresh-install SQL and Stage 2 upgrade SQL.

## Tenant, business and location safety

The module initialises tenancy by domain only when tenancy is not already active. Known central hosts are marked so the schema middleware does not create Restaurant-New operational tables there. Every explicit `withoutGlobalScopes()` query is constrained by business and, where relevant, by the authenticated user's permitted locations.

Location-specific operations validate that related floors, tables, kitchen stations, printers, suppliers, delivery zones, reservations and inventory records belong to the same business/location. Source access is required to dispatch a stock transfer and destination access is required to receive it.

## Transaction and duplicate controls

- Business document numbers are generated through transaction-locked sequences.
- Order table allocation is rechecked while the table row is locked.
- Payment, kitchen status, delivery status, discount, void, stock receipt, transfer, stocktake and wastage operations use database transactions.
- Goods receipt, transfer, stocktake and recipe-consumption stock movements use repeat-safe source references.
- Delivery transitions, controlled discounts and void operations revalidate after row locking.
- `stock_posted_at` and movement source identifiers prevent duplicate recipe consumption.
- Collection, order, bill, receipt, payment, KOT and other module numbers have business-scoped uniqueness.
- Schema and permission installers are repeat-safe.

## Platform contracts

Restaurant-New contains its own feature implementation. It intentionally reuses only these host-application contracts:

- Laravel authentication and the application's `users` table
- active tenant database connection
- current business and permitted business-location context
- the standard permission middleware/table
- the global application Blade layout and shared icon/font assets

It has no dependency on POS, Expenses, Customers, Suppliers, Finance or another feature module's classes, routes, models or tables.
