# RestaurantNew Stage 021 - Troubleshooting

## Sidebar not visible
Check module status, Super Admin feature enablement, and assigned permissions.

## 404 route not found
Clear route cache and confirm RestaurantNew route provider is loaded.

## 403 unauthorized
Confirm user has RestaurantNew permission and feature is enabled for the selected business/location.

## Tables not created
Confirm SQL was run in the correct tenant database, not only in the master database.

## Kitchen orders not showing
Confirm waiter/cashier sale was saved and KOT/order status is `received` or later. Also check business_id and location_id match the logged-in user.

## Bill/KOT print blank
Check print route permission, order id, business_id, location_id, and whether order items exist.

## DataTables processing forever
Check Ajax route, permission middleware, database table exists, and server log for SQL errors.
