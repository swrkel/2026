# RestaurantNew RESTNEW_020 Production Release

This package consolidates RestaurantNew stages RESTNEW_001 to RESTNEW_019 into one clean module delivery.

## Module standard
- Standalone module: `Modules/RestaurantNew`
- Table prefix: `restaurant_new_`
- Tenant database tables only for tenant/business operational data
- Own controllers, services, routes, views, JS, CSS, lang files, reports, permissions, middleware, SQL and documentation
- POS-standard UI direction retained across dashboard, POS, kitchen, reports and setup pages

## Major functionality included
1. Foundation, routing, config, providers and module registration base
2. Restaurant settings, dining areas, tables, kitchen sections and order types
3. Menu categories, items, variants, modifiers/add-ons and recipe base
4. Waiter/cashier sale creation, POS, dine-in/takeaway/delivery order flow
5. Kitchen received-orders screen, KOT creation, KOT/bill print and status flow
6. Billing, payments, receipts, refunds and void flow base
7. Inventory, ingredients, stock movements, recipe costing and wastage
8. Staff, cashier shifts, tips and service-charge distribution
9. Delivery zones, riders, delivery orders and COD/card tracking
10. Reports suite
11. Advanced table and POS operations
12. Live kitchen production board
13. QR menu/customer order status/digital receipt/customer feedback
14. Promotions, happy hours, combo meals, buffet/packages, banquets and catering
15. Procurement, GRN, production batches and commissary transfers
16. Restaurant/Kitchen/Cashier/Waiter/Manager/Executive command centers
17. Super Admin feature controls, access rules, audit logs and URL protection
18. Standalone hardening, tenant/business checks and integrity logging

## Installation order
1. Backup code and tenant database.
2. Copy `Modules/RestaurantNew` into the application.
3. Ensure the module is enabled in the module status configuration if required by your system.
4. Run SQL scripts from `Modules/RestaurantNew/Database/SQL` in this order:
   - `create`
   - `alter`
   - `insert`
   - `permissions`
   - `master`
5. Clear Laravel caches.
6. Test with one tenant and one business before rolling out to all tenant databases.

## Important
This package is prepared as a clean consolidated code delivery for RestaurantNew. Run the SQL per tenant database where the RestaurantNew module will be used.
