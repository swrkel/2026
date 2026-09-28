CREATE INDEX `rn_orders_cmd_status_idx` ON `restaurant_new_orders` (`business_id`, `location_id`, `order_status`);
CREATE INDEX `rn_kot_cmd_status_idx` ON `restaurant_new_kitchen_tickets` (`business_id`, `business_location_id`, `status`);
CREATE INDEX `rn_bills_cmd_status_idx` ON `restaurant_new_bills` (`business_id`, `location_id`, `bill_status`);
