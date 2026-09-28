ALTER TABLE `restaurant_new_orders` ADD COLUMN `is_hardened` TINYINT(1) NOT NULL DEFAULT 1 AFTER `order_status`;
CREATE INDEX `restaurant_new_orders_is_hardened_idx` ON `restaurant_new_orders` (`is_hardened`);
