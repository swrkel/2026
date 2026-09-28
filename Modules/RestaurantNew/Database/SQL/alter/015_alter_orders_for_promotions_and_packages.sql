-- RestaurantNew Stage 015 ALTER SQL - fresh consolidated install version
ALTER TABLE `restaurant_new_orders`
  ADD COLUMN `promotion_id` BIGINT UNSIGNED NULL AFTER `order_type`,
  ADD COLUMN `combo_meal_id` BIGINT UNSIGNED NULL AFTER `promotion_id`,
  ADD COLUMN `banquet_event_id` BIGINT UNSIGNED NULL AFTER `combo_meal_id`,
  ADD COLUMN `catering_order_id` BIGINT UNSIGNED NULL AFTER `banquet_event_id`;
