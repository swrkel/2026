-- RESTNEW 030 corrected index script. Run once after CREATE/ALTER scripts.
CREATE INDEX `idx_restnew_orders_business_location_status` ON `restaurant_new_orders` (`business_id`, `location_id`, `order_status`);
CREATE INDEX `idx_restnew_orders_business_date` ON `restaurant_new_orders` (`business_id`, `created_at`);
CREATE INDEX `idx_restnew_kitchen_tickets_business_status` ON `restaurant_new_kitchen_tickets` (`business_id`, `status`);
CREATE INDEX `idx_restnew_bills_business_date` ON `restaurant_new_bills` (`business_id`, `bill_date`);
CREATE INDEX `idx_restnew_order_payments_business_date` ON `restaurant_new_order_payments` (`business_id`, `paid_on`);
CREATE INDEX `idx_restnew_stock_movements_business_item` ON `restaurant_new_stock_movements` (`business_id`, `ingredient_id`);
