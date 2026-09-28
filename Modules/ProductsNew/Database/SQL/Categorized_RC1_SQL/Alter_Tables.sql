-- Statement 26
ALTER TABLE `products_new_barcode_queue`
  ADD COLUMN IF NOT EXISTS `template_id` BIGINT UNSIGNED NULL AFTER `variation_id`,
  ADD COLUMN IF NOT EXISTS `barcode_value` VARCHAR(191) NULL AFTER `template_id`;

-- Statement 30
-- Products New Stage 006 - Product Intelligence Centre
-- Global SQL: run inside each tenant database. No database name is hardcoded.

ALTER TABLE products
    ADD COLUMN IF NOT EXISTS products_new_status VARCHAR(50) NULL AFTER status,
    ADD INDEX IF NOT EXISTS products_new_products_status_idx (business_id, products_new_status);
