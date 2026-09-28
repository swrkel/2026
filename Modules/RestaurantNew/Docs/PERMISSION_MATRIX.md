# Permission Matrix

Restaurant-New defines 55 permissions under the `restaurant_new.*` namespace.

## Role-screen permissions

- Waiter: `restaurant_new.waiter.use`, `restaurant_new.orders.create`
- Cashier: `restaurant_new.cashier.use`, `restaurant_new.payments.create`
- Kitchen: `restaurant_new.kitchen.use`
- Takeaway: `restaurant_new.takeaway.use`
- Collection: `restaurant_new.collection.use`
- Delivery: `restaurant_new.delivery.use`; supervisors also use `restaurant_new.delivery.manage`
- Manager: `restaurant_new.manager.view` plus selected approval/adjustment permissions

## Administrative groups

- Orders and payments
- Shifts
- Menu, modifiers and discounts
- Restaurant setup and recipes
- Stock, transfers, stocktakes and wastage
- Procurement
- Reports and export
- Settings and screen assignments

The exact permission names and descriptions are maintained in `Permissions/permissions.php`. Report tabs require their individual `restaurant_new.report.*` permission; the generic reports page does not bypass those checks.
