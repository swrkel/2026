-- POS Standalone S347 Core Sales SQL
-- Database agnostic: run after selecting the tenant database. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `pos_carts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `register_id` BIGINT UNSIGNED NULL,
  `session_id` BIGINT UNSIGNED NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `customer_id` BIGINT UNSIGNED NULL,
  `customer_name` VARCHAR(191) NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'active',
  `subtotal` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `tax_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `total_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `note` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_carts_business_user_status_index` (`business_id`,`user_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_cart_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cart_id` BIGINT UNSIGNED NOT NULL,
  `product_id` BIGINT UNSIGNED NULL,
  `quantity` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `unit_price` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `tax_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `line_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `note` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_cart_lines_cart_id_index` (`cart_id`),
  KEY `pos_cart_lines_product_id_index` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `pos_products` ADD COLUMN IF NOT EXISTS `stock_quantity` DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `sell_price`;
ALTER TABLE `pos_products` ADD COLUMN IF NOT EXISTS `unit_price` DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `sell_price`;
ALTER TABLE `pos_products` ADD COLUMN IF NOT EXISTS `barcode` VARCHAR(191) NULL AFTER `sku`;
ALTER TABLE `pos_products` ADD COLUMN IF NOT EXISTS `is_active` TINYINT(1) NOT NULL DEFAULT 1;
UPDATE `pos_products` SET `stock_quantity` = COALESCE(NULLIF(`stock_quantity`,0), COALESCE(`stock_qty`,0)) WHERE `stock_quantity` = 0;
UPDATE `pos_products` SET `unit_price` = COALESCE(NULLIF(`unit_price`,0), COALESCE(`sell_price`,0)) WHERE `unit_price` = 0;

ALTER TABLE `pos_sales` ADD COLUMN IF NOT EXISTS `sale_no` VARCHAR(191) NULL AFTER `id`;
ALTER TABLE `pos_sales` ADD COLUMN IF NOT EXISTS `customer_id` BIGINT UNSIGNED NULL AFTER `sale_no`;
ALTER TABLE `pos_sales` ADD COLUMN IF NOT EXISTS `customer_name` VARCHAR(191) NULL AFTER `customer_id`;
ALTER TABLE `pos_sales` ADD COLUMN IF NOT EXISTS `session_id` BIGINT UNSIGNED NULL AFTER `register_id`;
ALTER TABLE `pos_sales` ADD COLUMN IF NOT EXISTS `subtotal` DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `customer_name`;
ALTER TABLE `pos_sales` ADD COLUMN IF NOT EXISTS `discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `subtotal`;
ALTER TABLE `pos_sales` ADD COLUMN IF NOT EXISTS `tax_amount` DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `discount_amount`;
ALTER TABLE `pos_sales` ADD COLUMN IF NOT EXISTS `balance_amount` DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `paid_amount`;
ALTER TABLE `pos_sales` ADD COLUMN IF NOT EXISTS `status` VARCHAR(40) NOT NULL DEFAULT 'final' AFTER `balance_amount`;
ALTER TABLE `pos_sales` ADD COLUMN IF NOT EXISTS `sale_date` DATETIME NULL AFTER `payment_status`;
ALTER TABLE `pos_sales` ADD COLUMN IF NOT EXISTS `note` TEXT NULL AFTER `sale_date`;
ALTER TABLE `pos_sales` ADD COLUMN IF NOT EXISTS `created_by` BIGINT UNSIGNED NULL AFTER `note`;
UPDATE `pos_sales` SET `sale_no` = COALESCE(`sale_no`, `invoice_no`, CONCAT('POS-', `id`));
UPDATE `pos_sales` SET `subtotal` = COALESCE(NULLIF(`subtotal`,0), `total_amount`) WHERE `subtotal` = 0;

ALTER TABLE `pos_sale_lines` ADD COLUMN IF NOT EXISTS `sale_id` BIGINT UNSIGNED NULL AFTER `id`;
ALTER TABLE `pos_sale_lines` ADD COLUMN IF NOT EXISTS `product_name` VARCHAR(191) NULL AFTER `product_id`;
ALTER TABLE `pos_sale_lines` ADD COLUMN IF NOT EXISTS `discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `unit_price`;
ALTER TABLE `pos_sale_lines` ADD COLUMN IF NOT EXISTS `tax_amount` DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `discount_amount`;
UPDATE `pos_sale_lines` SET `sale_id` = `pos_sale_id` WHERE `sale_id` IS NULL AND `pos_sale_id` IS NOT NULL;

ALTER TABLE `pos_payments` ADD COLUMN IF NOT EXISTS `sale_id` BIGINT UNSIGNED NULL AFTER `id`;
ALTER TABLE `pos_payments` ADD COLUMN IF NOT EXISTS `payment_method` VARCHAR(50) NULL AFTER `sale_id`;
ALTER TABLE `pos_payments` ADD COLUMN IF NOT EXISTS `payment_date` DATETIME NULL AFTER `reference_no`;
ALTER TABLE `pos_payments` ADD COLUMN IF NOT EXISTS `business_id` BIGINT UNSIGNED NULL AFTER `id`;
ALTER TABLE `pos_payments` ADD COLUMN IF NOT EXISTS `created_by` BIGINT UNSIGNED NULL AFTER `payment_date`;
UPDATE `pos_payments` SET `sale_id` = `pos_sale_id` WHERE `sale_id` IS NULL AND `pos_sale_id` IS NOT NULL;
UPDATE `pos_payments` SET `payment_method` = `method` WHERE `payment_method` IS NULL AND `method` IS NOT NULL;
UPDATE `pos_payments` SET `payment_date` = `paid_on` WHERE `payment_date` IS NULL AND `paid_on` IS NOT NULL;

INSERT INTO `pos_products` (`business_id`,`name`,`sku`,`barcode`,`sell_price`,`unit_price`,`stock_quantity`,`is_active`,`created_at`,`updated_at`)
SELECT NULL,'Sample POS Item','POS-SAMPLE-001','899000000001',250.0000,250.0000,100.0000,1,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `pos_products` WHERE `sku`='POS-SAMPLE-001' OR `barcode`='899000000001');
