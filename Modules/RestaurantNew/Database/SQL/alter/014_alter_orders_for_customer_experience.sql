ALTER TABLE `restaurant_new_orders`
  ADD COLUMN `customer_status_token` VARCHAR(100) NULL AFTER `order_status`,
  ADD COLUMN `customer_visible_status` VARCHAR(50) NULL AFTER `customer_status_token`;
