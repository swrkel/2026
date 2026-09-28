-- Restaurant-New installation verification
-- Expected module-owned tables: 45
-- Expected permissions: 55

SELECT COUNT(*) AS restnew_table_count, 45 AS expected_table_count
FROM information_schema.tables
WHERE table_schema = DATABASE() AND table_name LIKE 'restnew\_%';

SELECT expected.table_name AS missing_table
FROM (
  SELECT 'restnew_settings' AS table_name
UNION ALL
  SELECT 'restnew_number_sequences' AS table_name
UNION ALL
  SELECT 'restnew_floors' AS table_name
UNION ALL
  SELECT 'restnew_tables' AS table_name
UNION ALL
  SELECT 'restnew_kitchen_stations' AS table_name
UNION ALL
  SELECT 'restnew_printers' AS table_name
UNION ALL
  SELECT 'restnew_user_screen_assignments' AS table_name
UNION ALL
  SELECT 'restnew_categories' AS table_name
UNION ALL
  SELECT 'restnew_menu_items' AS table_name
UNION ALL
  SELECT 'restnew_modifier_groups' AS table_name
UNION ALL
  SELECT 'restnew_modifiers' AS table_name
UNION ALL
  SELECT 'restnew_menu_item_modifier_groups' AS table_name
UNION ALL
  SELECT 'restnew_ingredients' AS table_name
UNION ALL
  SELECT 'restnew_recipes' AS table_name
UNION ALL
  SELECT 'restnew_recipe_lines' AS table_name
UNION ALL
  SELECT 'restnew_inventory_balances' AS table_name
UNION ALL
  SELECT 'restnew_stock_movements' AS table_name
UNION ALL
  SELECT 'restnew_shifts' AS table_name
UNION ALL
  SELECT 'restnew_orders' AS table_name
UNION ALL
  SELECT 'restnew_order_items' AS table_name
UNION ALL
  SELECT 'restnew_order_item_modifiers' AS table_name
UNION ALL
  SELECT 'restnew_kitchen_tickets' AS table_name
UNION ALL
  SELECT 'restnew_kitchen_ticket_items' AS table_name
UNION ALL
  SELECT 'restnew_order_status_logs' AS table_name
UNION ALL
  SELECT 'restnew_collection_tokens' AS table_name
UNION ALL
  SELECT 'restnew_reservations' AS table_name
UNION ALL
  SELECT 'restnew_print_jobs' AS table_name
UNION ALL
  SELECT 'restnew_payments' AS table_name
UNION ALL
  SELECT 'restnew_daily_closures' AS table_name
UNION ALL
  SELECT 'restnew_audit_logs' AS table_name
UNION ALL
  SELECT 'restnew_suppliers' AS table_name
UNION ALL
  SELECT 'restnew_goods_receipts' AS table_name
UNION ALL
  SELECT 'restnew_goods_receipt_lines' AS table_name
UNION ALL
  SELECT 'restnew_stock_transfers' AS table_name
UNION ALL
  SELECT 'restnew_stock_transfer_lines' AS table_name
UNION ALL
  SELECT 'restnew_stocktakes' AS table_name
UNION ALL
  SELECT 'restnew_stocktake_lines' AS table_name
UNION ALL
  SELECT 'restnew_wastages' AS table_name
UNION ALL
  SELECT 'restnew_wastage_lines' AS table_name
UNION ALL
  SELECT 'restnew_delivery_zones' AS table_name
UNION ALL
  SELECT 'restnew_delivery_dispatches' AS table_name
UNION ALL
  SELECT 'restnew_discount_rules' AS table_name
UNION ALL
  SELECT 'restnew_discount_usages' AS table_name
UNION ALL
  SELECT 'restnew_order_adjustments' AS table_name
UNION ALL
  SELECT 'restnew_manager_approvals' AS table_name
) expected
LEFT JOIN information_schema.tables actual
  ON actual.table_schema = DATABASE() AND actual.table_name = expected.table_name
WHERE actual.table_name IS NULL
ORDER BY expected.table_name;

SELECT COUNT(*) AS restaurant_permission_count, 55 AS expected_permission_count
FROM `permissions`
WHERE `guard_name`='web' AND `name` LIKE 'restaurant\_new.%';

SELECT expected.permission_name AS missing_permission
FROM (
  SELECT 'restaurant_new.access' AS permission_name
UNION ALL
  SELECT 'restaurant_new.dashboard.view' AS permission_name
UNION ALL
  SELECT 'restaurant_new.manager.view' AS permission_name
UNION ALL
  SELECT 'restaurant_new.waiter.use' AS permission_name
UNION ALL
  SELECT 'restaurant_new.cashier.use' AS permission_name
UNION ALL
  SELECT 'restaurant_new.kitchen.use' AS permission_name
UNION ALL
  SELECT 'restaurant_new.takeaway.use' AS permission_name
UNION ALL
  SELECT 'restaurant_new.collection.use' AS permission_name
UNION ALL
  SELECT 'restaurant_new.reservations.view' AS permission_name
UNION ALL
  SELECT 'restaurant_new.reservations.manage' AS permission_name
UNION ALL
  SELECT 'restaurant_new.delivery.use' AS permission_name
UNION ALL
  SELECT 'restaurant_new.delivery.manage' AS permission_name
UNION ALL
  SELECT 'restaurant_new.orders.view' AS permission_name
UNION ALL
  SELECT 'restaurant_new.orders.create' AS permission_name
UNION ALL
  SELECT 'restaurant_new.orders.edit' AS permission_name
UNION ALL
  SELECT 'restaurant_new.orders.cancel' AS permission_name
UNION ALL
  SELECT 'restaurant_new.orders.void_item' AS permission_name
UNION ALL
  SELECT 'restaurant_new.payments.create' AS permission_name
UNION ALL
  SELECT 'restaurant_new.payments.refund' AS permission_name
UNION ALL
  SELECT 'restaurant_new.shifts.view' AS permission_name
UNION ALL
  SELECT 'restaurant_new.shifts.open' AS permission_name
UNION ALL
  SELECT 'restaurant_new.shifts.close' AS permission_name
UNION ALL
  SELECT 'restaurant_new.menu.view' AS permission_name
UNION ALL
  SELECT 'restaurant_new.menu.manage' AS permission_name
UNION ALL
  SELECT 'restaurant_new.discounts.apply' AS permission_name
UNION ALL
  SELECT 'restaurant_new.discounts.manage' AS permission_name
UNION ALL
  SELECT 'restaurant_new.discounts.approve' AS permission_name
UNION ALL
  SELECT 'restaurant_new.setup.manage' AS permission_name
UNION ALL
  SELECT 'restaurant_new.recipes.manage' AS permission_name
UNION ALL
  SELECT 'restaurant_new.stock.view' AS permission_name
UNION ALL
  SELECT 'restaurant_new.stock.adjust' AS permission_name
UNION ALL
  SELECT 'restaurant_new.stock.transfer' AS permission_name
UNION ALL
  SELECT 'restaurant_new.stock.stocktake' AS permission_name
UNION ALL
  SELECT 'restaurant_new.stock.wastage' AS permission_name
UNION ALL
  SELECT 'restaurant_new.procurement.view' AS permission_name
UNION ALL
  SELECT 'restaurant_new.procurement.manage' AS permission_name
UNION ALL
  SELECT 'restaurant_new.procurement.post' AS permission_name
UNION ALL
  SELECT 'restaurant_new.reports.view' AS permission_name
UNION ALL
  SELECT 'restaurant_new.reports.export' AS permission_name
UNION ALL
  SELECT 'restaurant_new.report.sales' AS permission_name
UNION ALL
  SELECT 'restaurant_new.report.item_sales' AS permission_name
UNION ALL
  SELECT 'restaurant_new.report.kitchen' AS permission_name
UNION ALL
  SELECT 'restaurant_new.report.cashier' AS permission_name
UNION ALL
  SELECT 'restaurant_new.report.takeaway' AS permission_name
UNION ALL
  SELECT 'restaurant_new.report.hourly' AS permission_name
UNION ALL
  SELECT 'restaurant_new.report.waiter' AS permission_name
UNION ALL
  SELECT 'restaurant_new.report.discounts' AS permission_name
UNION ALL
  SELECT 'restaurant_new.report.voids' AS permission_name
UNION ALL
  SELECT 'restaurant_new.report.stock_usage' AS permission_name
UNION ALL
  SELECT 'restaurant_new.report.purchases' AS permission_name
UNION ALL
  SELECT 'restaurant_new.report.wastage' AS permission_name
UNION ALL
  SELECT 'restaurant_new.report.profitability' AS permission_name
UNION ALL
  SELECT 'restaurant_new.documents.print' AS permission_name
UNION ALL
  SELECT 'restaurant_new.settings.manage' AS permission_name
UNION ALL
  SELECT 'restaurant_new.screen_assignments.manage' AS permission_name
) expected
LEFT JOIN `permissions` actual
  ON actual.guard_name='web' AND actual.name=expected.permission_name
WHERE actual.id IS NULL
ORDER BY expected.permission_name;
