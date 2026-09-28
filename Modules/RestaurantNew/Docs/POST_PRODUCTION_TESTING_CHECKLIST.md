# RestaurantNew Stage 021 - Post Production Testing Checklist

## Purpose
This checklist is for first server testing after uploading `RestaurantNew_RESTNEW_020_FULL_PRODUCTION.zip`.

## Upload Verification
- Confirm `Modules/RestaurantNew` exists.
- Confirm module is enabled in module status / business feature controls.
- Confirm RestaurantNew service provider loads without error.
- Confirm RestaurantNew routes are visible.

## Database Verification
Run the SQL in this order:
1. Master database SQL / module registration / permissions.
2. Tenant database CREATE SQL.
3. Tenant database ALTER SQL.
4. Tenant database INSERT SQL.
5. Optional indexes and feature seed SQL.

## First Functional Test Flow
1. Open RestaurantNew dashboard.
2. Create restaurant settings.
3. Create dining area.
4. Create restaurant table.
5. Create kitchen section.
6. Create menu category.
7. Create menu item.
8. Create waiter/cashier sale.
9. Confirm kitchen received order appears.
10. Print KOT from kitchen.
11. Mark order preparing, ready, served.
12. Finalize bill.
13. Print receipt.
14. Check reports.

## Multi Business Checks
- Business A data should not show in Business B.
- Location A orders should not show in Location B unless user has access.
- Direct URL must be blocked when user lacks permission.

## Important Screens to Test
- Dashboard / Command Center
- Restaurant POS
- Waiter sale screen
- Cashier sale screen
- Kitchen received orders
- KOT print
- Bill print
- Menu setup
- Table setup
- Reports
- Feature controls
- Audit logs
