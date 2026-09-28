-- Products New - Product Stock History
-- Run in each tenant database.
ALTER TABLE `products_new_inventory_movements`
    ADD COLUMN IF NOT EXISTS `store_id` BIGINT UNSIGNED NULL AFTER `location_id`,
    ADD COLUMN IF NOT EXISTS `source_table` VARCHAR(100) NULL AFTER `notes`,
    ADD COLUMN IF NOT EXISTS `source_id` BIGINT UNSIGNED NULL AFTER `source_table`;

CREATE INDEX IF NOT EXISTS `pn_im_store_idx` ON `products_new_inventory_movements` (`business_id`,`store_id`);
CREATE INDEX IF NOT EXISTS `pn_im_source_idx` ON `products_new_inventory_movements` (`source_table`,`source_id`);
