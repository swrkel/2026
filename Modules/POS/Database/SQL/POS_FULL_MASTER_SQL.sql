-- POS Standalone Production v1.0 Full Master SQL
-- Global SQL, no hardcoded database names.
-- Apply to each tenant database where POS must be enabled.
SET FOREIGN_KEY_CHECKS=0;


-- =============================================================
-- Source: pos_standalone_master.sql
-- =============================================================
CREATE TABLE IF NOT EXISTS `pos_categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `name` VARCHAR(191) NOT NULL,
  `code` VARCHAR(191) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_categories_business_id_index` (`business_id`),
  KEY `pos_categories_name_index` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_brands` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `name` VARCHAR(191) NOT NULL,
  `code` VARCHAR(191) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_brands_business_id_index` (`business_id`),
  KEY `pos_brands_name_index` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_products` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `category_id` BIGINT UNSIGNED NULL,
  `brand_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NOT NULL,
  `sku` VARCHAR(191) NULL,
  `barcode` VARCHAR(191) NULL,
  `sell_price` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `stock_qty` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `enable_stock` TINYINT(1) NOT NULL DEFAULT 1,
  `image` VARCHAR(191) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_products_business_id_index` (`business_id`),
  KEY `pos_products_category_id_index` (`category_id`),
  KEY `pos_products_brand_id_index` (`brand_id`),
  KEY `pos_products_sku_index` (`sku`),
  KEY `pos_products_barcode_index` (`barcode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_customers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `name` VARCHAR(191) NOT NULL,
  `mobile` VARCHAR(191) NULL,
  `customer_code` VARCHAR(191) NULL,
  `email` VARCHAR(191) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_customers_business_id_index` (`business_id`),
  KEY `pos_customers_mobile_index` (`mobile`),
  KEY `pos_customers_customer_code_index` (`customer_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
-- POS Standalone S348 - Products & Stock
-- Run this inside each tenant database. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `pos_categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `short_name` VARCHAR(50) NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`), KEY `pos_categories_deleted_at_index` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_brands` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `short_name` VARCHAR(50) NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`), KEY `pos_brands_deleted_at_index` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_units` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `short_name` VARCHAR(50) NULL,
  `allow_decimal` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`), KEY `pos_units_deleted_at_index` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_products` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `sku` VARCHAR(100) NULL,
  `barcode` VARCHAR(100) NULL,
  `category_id` BIGINT UNSIGNED NULL,
  `brand_id` BIGINT UNSIGNED NULL,
  `unit_id` BIGINT UNSIGNED NULL,
  `cost_price` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `selling_price` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `tax_rate` DECIMAL(8,4) NOT NULL DEFAULT 0.0000,
  `current_stock` DECIMAL(22,3) NOT NULL DEFAULT 0.000,
  `alert_quantity` DECIMAL(22,3) NOT NULL DEFAULT 0.000,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `description` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pos_products_sku_unique` (`sku`),
  KEY `pos_products_barcode_index` (`barcode`),
  KEY `pos_products_category_index` (`category_id`),
  KEY `pos_products_brand_index` (`brand_id`),
  KEY `pos_products_unit_index` (`unit_id`),
  KEY `pos_products_deleted_at_index` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_stock_movements` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `movement_type` VARCHAR(50) NOT NULL,
  `reference_type` VARCHAR(100) NULL,
  `reference_id` BIGINT UNSIGNED NULL,
  `quantity` DECIMAL(22,3) NOT NULL DEFAULT 0.000,
  `stock_before` DECIMAL(22,3) NOT NULL DEFAULT 0.000,
  `stock_after` DECIMAL(22,3) NOT NULL DEFAULT 0.000,
  `unit_cost` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_stock_movements_product_index` (`product_id`),
  KEY `pos_stock_movements_type_index` (`movement_type`),
  KEY `pos_stock_movements_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `pos_units` (`name`, `short_name`, `allow_decimal`, `created_at`, `updated_at`)
SELECT 'Pieces', 'Pcs', 0, NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM `pos_units` WHERE `short_name` = 'Pcs');
INSERT INTO `pos_units` (`name`, `short_name`, `allow_decimal`, `created_at`, `updated_at`)
SELECT 'Kilogram', 'Kg', 1, NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM `pos_units` WHERE `short_name` = 'Kg');
INSERT INTO `pos_categories` (`name`, `short_name`, `created_at`, `updated_at`)
SELECT 'General', 'GEN', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM `pos_categories` WHERE `name` = 'General');
-- POS Standalone S349 - Customers, Credit Sales, Ledger, Statements
-- Database agnostic: run after selecting the correct tenant database. No hard-coded DB name.

CREATE TABLE IF NOT EXISTS `pos_customers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `customer_code` VARCHAR(50) NULL,
  `name` VARCHAR(191) NOT NULL,
  `mobile` VARCHAR(50) NULL,
  `email` VARCHAR(191) NULL,
  `nic_no` VARCHAR(100) NULL,
  `address` TEXT NULL,
  `customer_type` ENUM('walk_in','credit','loyalty') NOT NULL DEFAULT 'walk_in',
  `credit_limit` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `opening_balance` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `balance_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `pos_customers_business_idx` (`business_id`),
  KEY `pos_customers_code_idx` (`customer_code`),
  KEY `pos_customers_mobile_idx` (`mobile`),
  KEY `pos_customers_type_idx` (`customer_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_customer_ledgers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `customer_id` BIGINT UNSIGNED NOT NULL,
  `sale_id` BIGINT UNSIGNED NULL,
  `transaction_date` DATETIME NULL,
  `entry_type` VARCHAR(60) NOT NULL,
  `debit` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `credit` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `balance` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `reference_no` VARCHAR(191) NULL,
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `pos_customer_ledgers_business_idx` (`business_id`),
  KEY `pos_customer_ledgers_customer_idx` (`customer_id`),
  KEY `pos_customer_ledgers_sale_idx` (`sale_id`),
  KEY `pos_customer_ledgers_date_idx` (`transaction_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ensure existing POS sales/cart tables can link POS standalone customers.
ALTER TABLE `pos_carts` ADD COLUMN IF NOT EXISTS `customer_id` BIGINT UNSIGNED NULL AFTER `session_id`;
ALTER TABLE `pos_carts` ADD COLUMN IF NOT EXISTS `customer_name` VARCHAR(191) NULL AFTER `customer_id`;
ALTER TABLE `pos_sales` ADD COLUMN IF NOT EXISTS `customer_id` BIGINT UNSIGNED NULL AFTER `invoice_no`;
ALTER TABLE `pos_sales` ADD COLUMN IF NOT EXISTS `customer_name` VARCHAR(191) NULL AFTER `customer_id`;
ALTER TABLE `pos_payments` ADD COLUMN IF NOT EXISTS `customer_id` BIGINT UNSIGNED NULL AFTER `sale_id`;
-- POS Standalone S351 - Returns, Void Bills and Cash Register Hardening
-- Run after selecting the tenant database. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `pos_sale_returns` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `sale_id` BIGINT UNSIGNED NOT NULL,
  `return_no` VARCHAR(191) NOT NULL,
  `return_date` DATETIME NULL,
  `refund_method` VARCHAR(50) NOT NULL DEFAULT 'cash',
  `total_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(40) NOT NULL DEFAULT 'final',
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `pos_sale_returns_business_idx` (`business_id`),
  KEY `pos_sale_returns_sale_idx` (`sale_id`),
  KEY `pos_sale_returns_no_idx` (`return_no`),
  KEY `pos_sale_returns_date_idx` (`return_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_sale_return_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `return_id` BIGINT UNSIGNED NOT NULL,
  `sale_line_id` BIGINT UNSIGNED NULL,
  `product_id` BIGINT UNSIGNED NULL,
  `product_name` VARCHAR(191) NULL,
  `quantity` DECIMAL(22,3) NOT NULL DEFAULT 0.000,
  `unit_price` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `line_total` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `pos_sale_return_lines_return_idx` (`return_id`),
  KEY `pos_sale_return_lines_product_idx` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_cash_movements` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `register_session_id` BIGINT UNSIGNED NULL,
  `movement_type` VARCHAR(50) NOT NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `transaction_date` DATETIME NULL,
  `reference_no` VARCHAR(191) NULL,
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `pos_cash_movements_business_idx` (`business_id`),
  KEY `pos_cash_movements_session_idx` (`register_session_id`),
  KEY `pos_cash_movements_type_idx` (`movement_type`),
  KEY `pos_cash_movements_date_idx` (`transaction_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `pos_sales` ADD COLUMN IF NOT EXISTS `void_reason` TEXT NULL;
ALTER TABLE `pos_sales` ADD COLUMN IF NOT EXISTS `voided_at` DATETIME NULL;
ALTER TABLE `pos_sales` ADD COLUMN IF NOT EXISTS `voided_by` BIGINT UNSIGNED NULL;
ALTER TABLE `pos_sales` ADD COLUMN IF NOT EXISTS `status` VARCHAR(40) NOT NULL DEFAULT 'final';
ALTER TABLE `pos_cash_movements` ADD COLUMN IF NOT EXISTS `reference_no` VARCHAR(191) NULL;
ALTER TABLE `pos_cash_movements` ADD COLUMN IF NOT EXISTS `business_location_id` BIGINT UNSIGNED NULL;
-- POS Standalone S360 - Settings & Security
-- Global tenant SQL. Run on each tenant database. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `pos_settings` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `business_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `location_id` BIGINT UNSIGNED NULL,
    `setting_group` VARCHAR(80) NOT NULL,
    `setting_key` VARCHAR(120) NOT NULL,
    `setting_value` TEXT NULL,
    `value_type` VARCHAR(30) NOT NULL DEFAULT 'string',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` BIGINT UNSIGNED NULL,
    `updated_by` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `pos_settings_unique_key` (`business_id`,`location_id`,`setting_group`,`setting_key`),
    KEY `pos_settings_group_idx` (`business_id`,`setting_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_audit_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `business_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `location_id` BIGINT UNSIGNED NULL,
    `user_id` BIGINT UNSIGNED NULL,
    `action` VARCHAR(120) NOT NULL,
    `auditable_type` VARCHAR(160) NULL,
    `auditable_id` BIGINT UNSIGNED NULL,
    `old_values` LONGTEXT NULL,
    `new_values` LONGTEXT NULL,
    `ip_address` VARCHAR(64) NULL,
    `user_agent` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `pos_audit_logs_business_action_idx` (`business_id`,`action`),
    KEY `pos_audit_logs_auditable_idx` (`auditable_type`,`auditable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- S365 - POS Customers Module Integration
-- No compulsory schema changes.
-- POS uses existing Customers module customer master/ledger tables.

-- Optional indexes for faster POS customer search when not already present.
ALTER TABLE `contacts` ADD INDEX `idx_pos_customer_search_business_type_active` (`business_id`, `type`, `active`);
ALTER TABLE `contact_ledgers` ADD INDEX `idx_pos_customer_ledger_business_contact` (`business_id`, `contact_id`);
-- POS Standalone S366 - Products, Inventory Purchase Stock, Setup and Barcode Labels
-- Global SQL: run inside each tenant database. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `pos_purchase_headers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reference_no` varchar(100) DEFAULT NULL,
  `supplier_name` varchar(191) DEFAULT NULL,
  `purchase_date` date NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'posted',
  `total_amount` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `note` text NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_purchase_headers_reference_no_index` (`reference_no`),
  KEY `pos_purchase_headers_purchase_date_index` (`purchase_date`),
  KEY `pos_purchase_headers_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_purchase_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `quantity` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `unit_cost` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `line_total` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_purchase_lines_purchase_id_index` (`purchase_id`),
  KEY `pos_purchase_lines_product_id_index` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `pos_stock_movements`
  ADD INDEX `pos_stock_movements_type_reference_index` (`movement_type`, `reference_id`);
-- POS Standalone S367 - Register, Shift and Cash Operations
-- Run after selecting the tenant database. No database name is hardcoded.

ALTER TABLE `pos_register_sessions` ADD COLUMN IF NOT EXISTS `approval_status` VARCHAR(40) NOT NULL DEFAULT 'approved' AFTER `variance_amount`;
ALTER TABLE `pos_register_sessions` ADD COLUMN IF NOT EXISTS `approved_by` BIGINT UNSIGNED NULL AFTER `closed_by`;
ALTER TABLE `pos_register_sessions` ADD COLUMN IF NOT EXISTS `approved_at` DATETIME NULL AFTER `approved_by`;
ALTER TABLE `pos_register_sessions` ADD COLUMN IF NOT EXISTS `closing_note` TEXT NULL;
ALTER TABLE `pos_register_sessions` ADD COLUMN IF NOT EXISTS `business_location_id` BIGINT UNSIGNED NULL;
ALTER TABLE `pos_register_sessions` ADD INDEX IF NOT EXISTS `pos_register_sessions_status_idx` (`status`);
ALTER TABLE `pos_register_sessions` ADD INDEX IF NOT EXISTS `pos_register_sessions_register_status_idx` (`register_id`,`status`);

ALTER TABLE `pos_cash_movements` ADD COLUMN IF NOT EXISTS `approval_status` VARCHAR(40) NOT NULL DEFAULT 'approved' AFTER `amount`;
ALTER TABLE `pos_cash_movements` ADD COLUMN IF NOT EXISTS `approved_by` BIGINT UNSIGNED NULL AFTER `created_by`;
ALTER TABLE `pos_cash_movements` ADD COLUMN IF NOT EXISTS `approved_at` DATETIME NULL AFTER `approved_by`;
ALTER TABLE `pos_cash_movements` ADD INDEX IF NOT EXISTS `pos_cash_movements_approval_idx` (`approval_status`);
ALTER TABLE `pos_cash_movements` ADD INDEX IF NOT EXISTS `pos_cash_movements_session_type_idx` (`register_session_id`,`movement_type`);

CREATE TABLE IF NOT EXISTS `pos_shift_handover_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `register_session_id` BIGINT UNSIGNED NOT NULL,
  `from_user_id` BIGINT UNSIGNED NULL,
  `to_user_id` BIGINT UNSIGNED NULL,
  `handover_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `pos_shift_handover_session_idx` (`register_session_id`),
  KEY `pos_shift_handover_business_idx` (`business_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- POS Standalone S368 - Returns, Refunds & Exchanges
-- Run this against each tenant database. No hardcoded database name is used.

CREATE TABLE IF NOT EXISTS `pos_sale_exchanges` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `sale_id` BIGINT UNSIGNED NOT NULL,
  `exchange_no` VARCHAR(100) NOT NULL,
  `exchange_date` DATETIME NULL,
  `return_total` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `new_total` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `difference_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `payment_method` VARCHAR(50) NULL,
  `note` TEXT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'final',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pos_sale_exchanges_exchange_no_unique` (`exchange_no`),
  KEY `pos_sale_exchanges_sale_id_index` (`sale_id`),
  KEY `pos_sale_exchanges_business_date_index` (`business_id`, `exchange_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_sale_exchange_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `exchange_id` BIGINT UNSIGNED NOT NULL,
  `line_type` VARCHAR(20) NOT NULL DEFAULT 'return',
  `sale_line_id` BIGINT UNSIGNED NULL,
  `product_id` BIGINT UNSIGNED NULL,
  `product_name` VARCHAR(255) NULL,
  `quantity` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `unit_price` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `line_total` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `pos_sale_exchange_lines_exchange_id_index` (`exchange_id`),
  KEY `pos_sale_exchange_lines_product_id_index` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `pos_sale_returns`
  ADD COLUMN IF NOT EXISTS `approval_status` VARCHAR(30) NOT NULL DEFAULT 'approved' AFTER `status`,
  ADD COLUMN IF NOT EXISTS `approval_note` TEXT NULL AFTER `approval_status`,
  ADD COLUMN IF NOT EXISTS `approved_by` BIGINT UNSIGNED NULL AFTER `approval_note`,
  ADD COLUMN IF NOT EXISTS `approved_at` DATETIME NULL AFTER `approved_by`;

ALTER TABLE `pos_sale_returns`
  ADD INDEX IF NOT EXISTS `pos_sale_returns_approval_status_index` (`approval_status`),
  ADD INDEX IF NOT EXISTS `pos_sale_returns_business_date_index` (`business_id`, `return_date`);

-- Compatibility note:
-- If your MySQL/MariaDB version does not support ADD COLUMN IF NOT EXISTS or ADD INDEX IF NOT EXISTS,
-- check the column/index first and run the ALTER statements manually only when missing.
-- POS Standalone S370 - Configuration & Administration
-- Global tenant SQL. Run inside each tenant database. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `pos_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `location_id` BIGINT UNSIGNED NULL,
  `setting_group` VARCHAR(80) NOT NULL,
  `setting_key` VARCHAR(120) NOT NULL,
  `setting_value` TEXT NULL,
  `value_type` VARCHAR(30) NOT NULL DEFAULT 'string',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pos_settings_unique_key` (`business_id`,`location_id`,`setting_group`,`setting_key`),
  KEY `pos_settings_group_idx` (`business_id`,`setting_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `location_id` BIGINT UNSIGNED NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `module_area` VARCHAR(80) NOT NULL DEFAULT 'pos',
  `action` VARCHAR(120) NOT NULL,
  `reference_type` VARCHAR(120) NULL,
  `reference_id` BIGINT UNSIGNED NULL,
  `description` TEXT NULL,
  `before_payload` LONGTEXT NULL,
  `after_payload` LONGTEXT NULL,
  `ip_address` VARCHAR(80) NULL,
  `user_agent` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_audit_business_action_idx` (`business_id`,`action`),
  KEY `pos_audit_reference_idx` (`reference_type`,`reference_id`),
  KEY `pos_audit_created_idx` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- S371 Final Audit
-- =========================================================
-- No structural database changes required.
-- POS Standalone S379 - Offline / Online Sync Foundation
-- Run in every tenant database that will use POS offline mode.
-- No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `pos_offline_sync_queue` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `device_uuid` VARCHAR(80) NOT NULL,
  `terminal_code` VARCHAR(80) NULL,
  `client_token` VARCHAR(120) NOT NULL,
  `offline_invoice_no` VARCHAR(120) NULL,
  `server_invoice_no` VARCHAR(120) NULL,
  `transaction_type` VARCHAR(40) NOT NULL DEFAULT 'sale',
  `payload` LONGTEXT NOT NULL,
  `server_response` LONGTEXT NULL,
  `status` ENUM('pending','synced','failed','conflict') NOT NULL DEFAULT 'pending',
  `attempt_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `last_error` TEXT NULL,
  `created_offline_at` DATETIME NULL,
  `synced_at` DATETIME NULL,
  `failed_at` DATETIME NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pos_offline_sync_queue_client_token_unique` (`client_token`),
  KEY `pos_offline_sync_queue_device_status_idx` (`device_uuid`, `status`),
  KEY `pos_offline_sync_queue_invoice_idx` (`offline_invoice_no`),
  KEY `pos_offline_sync_queue_business_status_idx` (`business_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_offline_sync_conflicts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue_id` BIGINT UNSIGNED NULL,
  `business_id` BIGINT UNSIGNED NULL,
  `device_uuid` VARCHAR(80) NULL,
  `offline_invoice_no` VARCHAR(120) NULL,
  `conflict_type` VARCHAR(80) NOT NULL,
  `conflict_message` TEXT NOT NULL,
  `payload` LONGTEXT NULL,
  `server_snapshot` LONGTEXT NULL,
  `resolution_status` ENUM('open','resolved','ignored') NOT NULL DEFAULT 'open',
  `resolution_note` TEXT NULL,
  `resolved_by` BIGINT UNSIGNED NULL,
  `resolved_at` DATETIME NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_offline_sync_conflicts_queue_idx` (`queue_id`),
  KEY `pos_offline_sync_conflicts_status_idx` (`resolution_status`),
  KEY `pos_offline_sync_conflicts_invoice_idx` (`offline_invoice_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `pos_devices`
  ADD COLUMN IF NOT EXISTS `offline_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `offline_device_uuid` VARCHAR(80) NULL,
  ADD COLUMN IF NOT EXISTS `offline_invoice_prefix` VARCHAR(20) NULL,
  ADD COLUMN IF NOT EXISTS `last_sync_at` DATETIME NULL;

CREATE UNIQUE INDEX IF NOT EXISTS `pos_devices_offline_uuid_unique` ON `pos_devices` (`offline_device_uuid`);

-- ============================================================
-- POS Standalone S380 - Offline Sales Sync
-- ============================================================
ALTER TABLE `pos_sales`
  ADD COLUMN IF NOT EXISTS `offline_client_token` VARCHAR(120) NULL,
  ADD COLUMN IF NOT EXISTS `offline_invoice_no` VARCHAR(120) NULL,
  ADD COLUMN IF NOT EXISTS `offline_device_uuid` VARCHAR(80) NULL,
  ADD COLUMN IF NOT EXISTS `offline_synced_at` DATETIME NULL;
CREATE UNIQUE INDEX IF NOT EXISTS `pos_sales_offline_client_token_unique` ON `pos_sales` (`offline_client_token`);
CREATE INDEX IF NOT EXISTS `pos_sales_offline_invoice_idx` ON `pos_sales` (`offline_invoice_no`);
CREATE INDEX IF NOT EXISTS `pos_sales_offline_device_idx` ON `pos_sales` (`offline_device_uuid`);
ALTER TABLE `pos_offline_sync_queue`
  ADD COLUMN IF NOT EXISTS `locked_at` DATETIME NULL,
  ADD COLUMN IF NOT EXISTS `locked_by` VARCHAR(120) NULL;
CREATE INDEX IF NOT EXISTS `pos_offline_sync_queue_type_status_idx` ON `pos_offline_sync_queue` (`transaction_type`, `status`);
-- POS Standalone S381 - Offline Cache, Stock Snapshot and Conflict Validation
-- Global SQL. Do not add database names.

ALTER TABLE pos_offline_sync_queue
    ADD COLUMN IF NOT EXISTS cache_version_hash VARCHAR(191) NULL AFTER server_invoice_no,
    ADD COLUMN IF NOT EXISTS preflight_status VARCHAR(50) NULL AFTER cache_version_hash,
    ADD COLUMN IF NOT EXISTS preflight_warnings JSON NULL AFTER preflight_status;

ALTER TABLE pos_offline_sync_conflicts
    ADD COLUMN IF NOT EXISTS resolved_by BIGINT UNSIGNED NULL AFTER resolution_status,
    ADD COLUMN IF NOT EXISTS resolution_note TEXT NULL AFTER resolved_by;

CREATE INDEX IF NOT EXISTS idx_pos_offline_queue_device_status ON pos_offline_sync_queue (device_uuid, status);
CREATE INDEX IF NOT EXISTS idx_pos_offline_queue_invoice ON pos_offline_sync_queue (offline_invoice_no);
CREATE INDEX IF NOT EXISTS idx_pos_offline_conflict_status ON pos_offline_sync_conflicts (resolution_status, resolved_at);
-- POS Standalone S382 - Sync Conflict Manager
-- Global SQL. Run inside each tenant database. Do not add database names.

ALTER TABLE pos_offline_sync_conflicts
    ADD COLUMN IF NOT EXISTS resolution_action VARCHAR(80) NULL AFTER resolution_status,
    ADD COLUMN IF NOT EXISTS manager_decision_payload JSON NULL AFTER resolution_note;

ALTER TABLE pos_offline_sync_queue
    ADD COLUMN IF NOT EXISTS dependency_key VARCHAR(191) NULL AFTER transaction_type,
    ADD COLUMN IF NOT EXISTS parent_client_token VARCHAR(120) NULL AFTER dependency_key,
    ADD COLUMN IF NOT EXISTS sync_priority INT NOT NULL DEFAULT 100 AFTER parent_client_token,
    ADD COLUMN IF NOT EXISTS manager_resolution JSON NULL AFTER preflight_warnings;

CREATE TABLE IF NOT EXISTS pos_offline_sync_audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id BIGINT UNSIGNED NULL,
    queue_id BIGINT UNSIGNED NULL,
    conflict_id BIGINT UNSIGNED NULL,
    device_uuid VARCHAR(80) NULL,
    action VARCHAR(80) NOT NULL,
    action_note TEXT NULL,
    before_payload LONGTEXT NULL,
    after_payload LONGTEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_pos_offline_audit_queue (queue_id),
    KEY idx_pos_offline_audit_conflict (conflict_id),
    KEY idx_pos_offline_audit_device (device_uuid),
    KEY idx_pos_offline_audit_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX IF NOT EXISTS idx_pos_offline_queue_dependency ON pos_offline_sync_queue (parent_client_token, status, sync_priority);
CREATE INDEX IF NOT EXISTS idx_pos_offline_conflict_type_status ON pos_offline_sync_conflicts (conflict_type, resolution_status);


-- S383 Background Synchronization Engine
-- Run file: Modules/POS/Database/SQL/pos_standalone_s383_background_sync_engine.sql

-- =========================================================
-- S384 - Enterprise Monitoring & Multi-Terminal Synchronization
-- =========================================================
SOURCE Modules/POS/Database/SQL/pos_standalone_s384_enterprise_monitoring_multiterminal.sql;


-- =====================================================================
-- S385 - Enterprise Reliability & Disaster Recovery
-- =====================================================================
-- POS Standalone S385 - Enterprise Reliability & Disaster Recovery
-- Global SQL: run inside each tenant database. Do not hardcode database names.

CREATE TABLE IF NOT EXISTS `pos_sync_reliability_tests` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `device_uuid` VARCHAR(191) NULL,
  `scenario` VARCHAR(191) NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `notes` TEXT NULL,
  `tested_by` BIGINT UNSIGNED NULL,
  `tested_at` DATETIME NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_sync_reliability_tests_device_status_idx` (`device_uuid`, `status`),
  KEY `pos_sync_reliability_tests_business_status_idx` (`business_id`, `location_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_sync_integrity_snapshots` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `device_uuid` VARCHAR(191) NULL,
  `snapshot_type` VARCHAR(80) NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `summary_json` LONGTEXT NULL,
  `checked_at` DATETIME NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_sync_integrity_snapshots_device_status_idx` (`device_uuid`, `status`),
  KEY `pos_sync_integrity_snapshots_checked_idx` (`checked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional safety columns for existing queue table.
ALTER TABLE `pos_offline_sync_queue`
  ADD COLUMN IF NOT EXISTS `transaction_hash` VARCHAR(191) NULL AFTER `client_token`,
  ADD COLUMN IF NOT EXISTS `dependency_key` VARCHAR(191) NULL AFTER `transaction_hash`,
  ADD COLUMN IF NOT EXISTS `batch_id` VARCHAR(191) NULL AFTER `dependency_key`,
  ADD COLUMN IF NOT EXISTS `locked_at` DATETIME NULL AFTER `batch_id`,
  ADD COLUMN IF NOT EXISTS `locked_by` VARCHAR(191) NULL AFTER `locked_at`;

CREATE INDEX IF NOT EXISTS `pos_offline_sync_queue_hash_idx` ON `pos_offline_sync_queue` (`transaction_hash`);
CREATE INDEX IF NOT EXISTS `pos_offline_sync_queue_dependency_idx` ON `pos_offline_sync_queue` (`dependency_key`);
CREATE INDEX IF NOT EXISTS `pos_offline_sync_queue_batch_idx` ON `pos_offline_sync_queue` (`batch_id`);


-- =============================================================
-- Source: pos_standalone_s346.sql
-- =============================================================
CREATE TABLE IF NOT EXISTS `pos_categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `name` VARCHAR(191) NOT NULL,
  `code` VARCHAR(191) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_categories_business_id_index` (`business_id`),
  KEY `pos_categories_name_index` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_brands` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `name` VARCHAR(191) NOT NULL,
  `code` VARCHAR(191) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_brands_business_id_index` (`business_id`),
  KEY `pos_brands_name_index` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_products` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `category_id` BIGINT UNSIGNED NULL,
  `brand_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NOT NULL,
  `sku` VARCHAR(191) NULL,
  `barcode` VARCHAR(191) NULL,
  `sell_price` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `stock_qty` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `enable_stock` TINYINT(1) NOT NULL DEFAULT 1,
  `image` VARCHAR(191) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_products_business_id_index` (`business_id`),
  KEY `pos_products_category_id_index` (`category_id`),
  KEY `pos_products_brand_id_index` (`brand_id`),
  KEY `pos_products_sku_index` (`sku`),
  KEY `pos_products_barcode_index` (`barcode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_customers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `name` VARCHAR(191) NOT NULL,
  `mobile` VARCHAR(191) NULL,
  `customer_code` VARCHAR(191) NULL,
  `email` VARCHAR(191) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_customers_business_id_index` (`business_id`),
  KEY `pos_customers_mobile_index` (`mobile`),
  KEY `pos_customers_customer_code_index` (`customer_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================================
-- Source: pos_standalone_s347_core_sales.sql
-- =============================================================
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


-- =============================================================
-- Source: pos_standalone_s348_products_stock.sql
-- =============================================================
-- POS Standalone S348 - Products & Stock
-- Run this inside each tenant database. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `pos_categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `short_name` VARCHAR(50) NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`), KEY `pos_categories_deleted_at_index` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_brands` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `short_name` VARCHAR(50) NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`), KEY `pos_brands_deleted_at_index` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_units` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `short_name` VARCHAR(50) NULL,
  `allow_decimal` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`), KEY `pos_units_deleted_at_index` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_products` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `sku` VARCHAR(100) NULL,
  `barcode` VARCHAR(100) NULL,
  `category_id` BIGINT UNSIGNED NULL,
  `brand_id` BIGINT UNSIGNED NULL,
  `unit_id` BIGINT UNSIGNED NULL,
  `cost_price` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `selling_price` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `tax_rate` DECIMAL(8,4) NOT NULL DEFAULT 0.0000,
  `current_stock` DECIMAL(22,3) NOT NULL DEFAULT 0.000,
  `alert_quantity` DECIMAL(22,3) NOT NULL DEFAULT 0.000,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `description` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pos_products_sku_unique` (`sku`),
  KEY `pos_products_barcode_index` (`barcode`),
  KEY `pos_products_category_index` (`category_id`),
  KEY `pos_products_brand_index` (`brand_id`),
  KEY `pos_products_unit_index` (`unit_id`),
  KEY `pos_products_deleted_at_index` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_stock_movements` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `movement_type` VARCHAR(50) NOT NULL,
  `reference_type` VARCHAR(100) NULL,
  `reference_id` BIGINT UNSIGNED NULL,
  `quantity` DECIMAL(22,3) NOT NULL DEFAULT 0.000,
  `stock_before` DECIMAL(22,3) NOT NULL DEFAULT 0.000,
  `stock_after` DECIMAL(22,3) NOT NULL DEFAULT 0.000,
  `unit_cost` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_stock_movements_product_index` (`product_id`),
  KEY `pos_stock_movements_type_index` (`movement_type`),
  KEY `pos_stock_movements_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `pos_units` (`name`, `short_name`, `allow_decimal`, `created_at`, `updated_at`)
SELECT 'Pieces', 'Pcs', 0, NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM `pos_units` WHERE `short_name` = 'Pcs');
INSERT INTO `pos_units` (`name`, `short_name`, `allow_decimal`, `created_at`, `updated_at`)
SELECT 'Kilogram', 'Kg', 1, NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM `pos_units` WHERE `short_name` = 'Kg');
INSERT INTO `pos_categories` (`name`, `short_name`, `created_at`, `updated_at`)
SELECT 'General', 'GEN', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM `pos_categories` WHERE `name` = 'General');


-- =============================================================
-- Source: pos_standalone_s349_customers_credit_ledger.sql
-- =============================================================
-- POS Standalone S349 - Customers, Credit Sales, Ledger, Statements
-- Database agnostic: run after selecting the correct tenant database. No hard-coded DB name.

CREATE TABLE IF NOT EXISTS `pos_customers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `customer_code` VARCHAR(50) NULL,
  `name` VARCHAR(191) NOT NULL,
  `mobile` VARCHAR(50) NULL,
  `email` VARCHAR(191) NULL,
  `nic_no` VARCHAR(100) NULL,
  `address` TEXT NULL,
  `customer_type` ENUM('walk_in','credit','loyalty') NOT NULL DEFAULT 'walk_in',
  `credit_limit` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `opening_balance` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `balance_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `pos_customers_business_idx` (`business_id`),
  KEY `pos_customers_code_idx` (`customer_code`),
  KEY `pos_customers_mobile_idx` (`mobile`),
  KEY `pos_customers_type_idx` (`customer_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_customer_ledgers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `customer_id` BIGINT UNSIGNED NOT NULL,
  `sale_id` BIGINT UNSIGNED NULL,
  `transaction_date` DATETIME NULL,
  `entry_type` VARCHAR(60) NOT NULL,
  `debit` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `credit` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `balance` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `reference_no` VARCHAR(191) NULL,
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `pos_customer_ledgers_business_idx` (`business_id`),
  KEY `pos_customer_ledgers_customer_idx` (`customer_id`),
  KEY `pos_customer_ledgers_sale_idx` (`sale_id`),
  KEY `pos_customer_ledgers_date_idx` (`transaction_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ensure existing POS sales/cart tables can link POS standalone customers.
ALTER TABLE `pos_carts` ADD COLUMN IF NOT EXISTS `customer_id` BIGINT UNSIGNED NULL AFTER `session_id`;
ALTER TABLE `pos_carts` ADD COLUMN IF NOT EXISTS `customer_name` VARCHAR(191) NULL AFTER `customer_id`;
ALTER TABLE `pos_sales` ADD COLUMN IF NOT EXISTS `customer_id` BIGINT UNSIGNED NULL AFTER `invoice_no`;
ALTER TABLE `pos_sales` ADD COLUMN IF NOT EXISTS `customer_name` VARCHAR(191) NULL AFTER `customer_id`;
ALTER TABLE `pos_payments` ADD COLUMN IF NOT EXISTS `customer_id` BIGINT UNSIGNED NULL AFTER `sale_id`;


-- =============================================================
-- Source: pos_standalone_s351_returns_void_cash_register.sql
-- =============================================================
-- POS Standalone S351 - Returns, Void Bills and Cash Register Hardening
-- Run after selecting the tenant database. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `pos_sale_returns` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `sale_id` BIGINT UNSIGNED NOT NULL,
  `return_no` VARCHAR(191) NOT NULL,
  `return_date` DATETIME NULL,
  `refund_method` VARCHAR(50) NOT NULL DEFAULT 'cash',
  `total_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(40) NOT NULL DEFAULT 'final',
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `pos_sale_returns_business_idx` (`business_id`),
  KEY `pos_sale_returns_sale_idx` (`sale_id`),
  KEY `pos_sale_returns_no_idx` (`return_no`),
  KEY `pos_sale_returns_date_idx` (`return_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_sale_return_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `return_id` BIGINT UNSIGNED NOT NULL,
  `sale_line_id` BIGINT UNSIGNED NULL,
  `product_id` BIGINT UNSIGNED NULL,
  `product_name` VARCHAR(191) NULL,
  `quantity` DECIMAL(22,3) NOT NULL DEFAULT 0.000,
  `unit_price` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `line_total` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `pos_sale_return_lines_return_idx` (`return_id`),
  KEY `pos_sale_return_lines_product_idx` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_cash_movements` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `register_session_id` BIGINT UNSIGNED NULL,
  `movement_type` VARCHAR(50) NOT NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `transaction_date` DATETIME NULL,
  `reference_no` VARCHAR(191) NULL,
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `pos_cash_movements_business_idx` (`business_id`),
  KEY `pos_cash_movements_session_idx` (`register_session_id`),
  KEY `pos_cash_movements_type_idx` (`movement_type`),
  KEY `pos_cash_movements_date_idx` (`transaction_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `pos_sales` ADD COLUMN IF NOT EXISTS `void_reason` TEXT NULL;
ALTER TABLE `pos_sales` ADD COLUMN IF NOT EXISTS `voided_at` DATETIME NULL;
ALTER TABLE `pos_sales` ADD COLUMN IF NOT EXISTS `voided_by` BIGINT UNSIGNED NULL;
ALTER TABLE `pos_sales` ADD COLUMN IF NOT EXISTS `status` VARCHAR(40) NOT NULL DEFAULT 'final';
ALTER TABLE `pos_cash_movements` ADD COLUMN IF NOT EXISTS `reference_no` VARCHAR(191) NULL;
ALTER TABLE `pos_cash_movements` ADD COLUMN IF NOT EXISTS `business_location_id` BIGINT UNSIGNED NULL;


-- =============================================================
-- Source: pos_standalone_s360_settings_security.sql
-- =============================================================
-- POS Standalone S360 - Settings & Security
-- Global tenant SQL. Run on each tenant database. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `pos_settings` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `business_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `location_id` BIGINT UNSIGNED NULL,
    `setting_group` VARCHAR(80) NOT NULL,
    `setting_key` VARCHAR(120) NOT NULL,
    `setting_value` TEXT NULL,
    `value_type` VARCHAR(30) NOT NULL DEFAULT 'string',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` BIGINT UNSIGNED NULL,
    `updated_by` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `pos_settings_unique_key` (`business_id`,`location_id`,`setting_group`,`setting_key`),
    KEY `pos_settings_group_idx` (`business_id`,`setting_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_audit_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `business_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `location_id` BIGINT UNSIGNED NULL,
    `user_id` BIGINT UNSIGNED NULL,
    `action` VARCHAR(120) NOT NULL,
    `auditable_type` VARCHAR(160) NULL,
    `auditable_id` BIGINT UNSIGNED NULL,
    `old_values` LONGTEXT NULL,
    `new_values` LONGTEXT NULL,
    `ip_address` VARCHAR(64) NULL,
    `user_agent` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `pos_audit_logs_business_action_idx` (`business_id`,`action`),
    KEY `pos_audit_logs_auditable_idx` (`auditable_type`,`auditable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================================
-- Source: pos_standalone_s365_customers_module_integration.sql
-- =============================================================
-- S365 - POS Customers Module Integration
-- No compulsory schema changes.
-- POS uses existing Customers module customer master/ledger tables.

-- Optional indexes for faster POS customer search when not already present.
ALTER TABLE `contacts` ADD INDEX `idx_pos_customer_search_business_type_active` (`business_id`, `type`, `active`);
ALTER TABLE `contact_ledgers` ADD INDEX `idx_pos_customer_ledger_business_contact` (`business_id`, `contact_id`);


-- =============================================================
-- Source: pos_standalone_s366_products_inventory_purchase_barcode.sql
-- =============================================================
-- POS Standalone S366 - Products, Inventory Purchase Stock, Setup and Barcode Labels
-- Global SQL: run inside each tenant database. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `pos_purchase_headers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reference_no` varchar(100) DEFAULT NULL,
  `supplier_name` varchar(191) DEFAULT NULL,
  `purchase_date` date NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'posted',
  `total_amount` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `note` text NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_purchase_headers_reference_no_index` (`reference_no`),
  KEY `pos_purchase_headers_purchase_date_index` (`purchase_date`),
  KEY `pos_purchase_headers_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_purchase_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `quantity` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `unit_cost` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `line_total` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_purchase_lines_purchase_id_index` (`purchase_id`),
  KEY `pos_purchase_lines_product_id_index` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `pos_stock_movements`
  ADD INDEX `pos_stock_movements_type_reference_index` (`movement_type`, `reference_id`);


-- =============================================================
-- Source: pos_standalone_s367_register_shift_cash_operations.sql
-- =============================================================
-- POS Standalone S367 - Register, Shift and Cash Operations
-- Run after selecting the tenant database. No database name is hardcoded.

ALTER TABLE `pos_register_sessions` ADD COLUMN IF NOT EXISTS `approval_status` VARCHAR(40) NOT NULL DEFAULT 'approved' AFTER `variance_amount`;
ALTER TABLE `pos_register_sessions` ADD COLUMN IF NOT EXISTS `approved_by` BIGINT UNSIGNED NULL AFTER `closed_by`;
ALTER TABLE `pos_register_sessions` ADD COLUMN IF NOT EXISTS `approved_at` DATETIME NULL AFTER `approved_by`;
ALTER TABLE `pos_register_sessions` ADD COLUMN IF NOT EXISTS `closing_note` TEXT NULL;
ALTER TABLE `pos_register_sessions` ADD COLUMN IF NOT EXISTS `business_location_id` BIGINT UNSIGNED NULL;
ALTER TABLE `pos_register_sessions` ADD INDEX IF NOT EXISTS `pos_register_sessions_status_idx` (`status`);
ALTER TABLE `pos_register_sessions` ADD INDEX IF NOT EXISTS `pos_register_sessions_register_status_idx` (`register_id`,`status`);

ALTER TABLE `pos_cash_movements` ADD COLUMN IF NOT EXISTS `approval_status` VARCHAR(40) NOT NULL DEFAULT 'approved' AFTER `amount`;
ALTER TABLE `pos_cash_movements` ADD COLUMN IF NOT EXISTS `approved_by` BIGINT UNSIGNED NULL AFTER `created_by`;
ALTER TABLE `pos_cash_movements` ADD COLUMN IF NOT EXISTS `approved_at` DATETIME NULL AFTER `approved_by`;
ALTER TABLE `pos_cash_movements` ADD INDEX IF NOT EXISTS `pos_cash_movements_approval_idx` (`approval_status`);
ALTER TABLE `pos_cash_movements` ADD INDEX IF NOT EXISTS `pos_cash_movements_session_type_idx` (`register_session_id`,`movement_type`);

CREATE TABLE IF NOT EXISTS `pos_shift_handover_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `register_session_id` BIGINT UNSIGNED NOT NULL,
  `from_user_id` BIGINT UNSIGNED NULL,
  `to_user_id` BIGINT UNSIGNED NULL,
  `handover_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `pos_shift_handover_session_idx` (`register_session_id`),
  KEY `pos_shift_handover_business_idx` (`business_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================================
-- Source: pos_standalone_s368_returns_exchanges_refunds.sql
-- =============================================================
-- POS Standalone S368 - Returns, Refunds & Exchanges
-- Run this against each tenant database. No hardcoded database name is used.

CREATE TABLE IF NOT EXISTS `pos_sale_exchanges` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `sale_id` BIGINT UNSIGNED NOT NULL,
  `exchange_no` VARCHAR(100) NOT NULL,
  `exchange_date` DATETIME NULL,
  `return_total` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `new_total` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `difference_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `payment_method` VARCHAR(50) NULL,
  `note` TEXT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'final',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pos_sale_exchanges_exchange_no_unique` (`exchange_no`),
  KEY `pos_sale_exchanges_sale_id_index` (`sale_id`),
  KEY `pos_sale_exchanges_business_date_index` (`business_id`, `exchange_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_sale_exchange_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `exchange_id` BIGINT UNSIGNED NOT NULL,
  `line_type` VARCHAR(20) NOT NULL DEFAULT 'return',
  `sale_line_id` BIGINT UNSIGNED NULL,
  `product_id` BIGINT UNSIGNED NULL,
  `product_name` VARCHAR(255) NULL,
  `quantity` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `unit_price` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `line_total` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `pos_sale_exchange_lines_exchange_id_index` (`exchange_id`),
  KEY `pos_sale_exchange_lines_product_id_index` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `pos_sale_returns`
  ADD COLUMN IF NOT EXISTS `approval_status` VARCHAR(30) NOT NULL DEFAULT 'approved' AFTER `status`,
  ADD COLUMN IF NOT EXISTS `approval_note` TEXT NULL AFTER `approval_status`,
  ADD COLUMN IF NOT EXISTS `approved_by` BIGINT UNSIGNED NULL AFTER `approval_note`,
  ADD COLUMN IF NOT EXISTS `approved_at` DATETIME NULL AFTER `approved_by`;

ALTER TABLE `pos_sale_returns`
  ADD INDEX IF NOT EXISTS `pos_sale_returns_approval_status_index` (`approval_status`),
  ADD INDEX IF NOT EXISTS `pos_sale_returns_business_date_index` (`business_id`, `return_date`);

-- Compatibility note:
-- If your MySQL/MariaDB version does not support ADD COLUMN IF NOT EXISTS or ADD INDEX IF NOT EXISTS,
-- check the column/index first and run the ALTER statements manually only when missing.


-- =============================================================
-- Source: pos_standalone_s370_configuration_administration.sql
-- =============================================================
-- POS Standalone S370 - Configuration & Administration
-- Global tenant SQL. Run inside each tenant database. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `pos_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `location_id` BIGINT UNSIGNED NULL,
  `setting_group` VARCHAR(80) NOT NULL,
  `setting_key` VARCHAR(120) NOT NULL,
  `setting_value` TEXT NULL,
  `value_type` VARCHAR(30) NOT NULL DEFAULT 'string',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pos_settings_unique_key` (`business_id`,`location_id`,`setting_group`,`setting_key`),
  KEY `pos_settings_group_idx` (`business_id`,`setting_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `location_id` BIGINT UNSIGNED NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `module_area` VARCHAR(80) NOT NULL DEFAULT 'pos',
  `action` VARCHAR(120) NOT NULL,
  `reference_type` VARCHAR(120) NULL,
  `reference_id` BIGINT UNSIGNED NULL,
  `description` TEXT NULL,
  `before_payload` LONGTEXT NULL,
  `after_payload` LONGTEXT NULL,
  `ip_address` VARCHAR(80) NULL,
  `user_agent` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_audit_business_action_idx` (`business_id`,`action`),
  KEY `pos_audit_reference_idx` (`reference_type`,`reference_id`),
  KEY `pos_audit_created_idx` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================================
-- Source: pos_standalone_s371_final_audit.sql
-- =============================================================
-- POS Standalone S371 Final Audit
-- No structural database changes required in this parcel.
-- Keep this file for package numbering consistency.
-- Apply pos_standalone_master.sql only if previous POS SQL files were not already applied.


-- =============================================================
-- Source: pos_standalone_s379_offline_sync_foundation.sql
-- =============================================================
-- POS Standalone S379 - Offline / Online Sync Foundation
-- Run in every tenant database that will use POS offline mode.
-- No database name is hardcoded.

CREATE TABLE IF NOT EXISTS `pos_offline_sync_queue` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `device_uuid` VARCHAR(80) NOT NULL,
  `terminal_code` VARCHAR(80) NULL,
  `client_token` VARCHAR(120) NOT NULL,
  `offline_invoice_no` VARCHAR(120) NULL,
  `server_invoice_no` VARCHAR(120) NULL,
  `transaction_type` VARCHAR(40) NOT NULL DEFAULT 'sale',
  `payload` LONGTEXT NOT NULL,
  `server_response` LONGTEXT NULL,
  `status` ENUM('pending','synced','failed','conflict') NOT NULL DEFAULT 'pending',
  `attempt_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `last_error` TEXT NULL,
  `created_offline_at` DATETIME NULL,
  `synced_at` DATETIME NULL,
  `failed_at` DATETIME NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pos_offline_sync_queue_client_token_unique` (`client_token`),
  KEY `pos_offline_sync_queue_device_status_idx` (`device_uuid`, `status`),
  KEY `pos_offline_sync_queue_invoice_idx` (`offline_invoice_no`),
  KEY `pos_offline_sync_queue_business_status_idx` (`business_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_offline_sync_conflicts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue_id` BIGINT UNSIGNED NULL,
  `business_id` BIGINT UNSIGNED NULL,
  `device_uuid` VARCHAR(80) NULL,
  `offline_invoice_no` VARCHAR(120) NULL,
  `conflict_type` VARCHAR(80) NOT NULL,
  `conflict_message` TEXT NOT NULL,
  `payload` LONGTEXT NULL,
  `server_snapshot` LONGTEXT NULL,
  `resolution_status` ENUM('open','resolved','ignored') NOT NULL DEFAULT 'open',
  `resolution_note` TEXT NULL,
  `resolved_by` BIGINT UNSIGNED NULL,
  `resolved_at` DATETIME NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_offline_sync_conflicts_queue_idx` (`queue_id`),
  KEY `pos_offline_sync_conflicts_status_idx` (`resolution_status`),
  KEY `pos_offline_sync_conflicts_invoice_idx` (`offline_invoice_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `pos_devices`
  ADD COLUMN IF NOT EXISTS `offline_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `offline_device_uuid` VARCHAR(80) NULL,
  ADD COLUMN IF NOT EXISTS `offline_invoice_prefix` VARCHAR(20) NULL,
  ADD COLUMN IF NOT EXISTS `last_sync_at` DATETIME NULL;

CREATE UNIQUE INDEX IF NOT EXISTS `pos_devices_offline_uuid_unique` ON `pos_devices` (`offline_device_uuid`);


-- =============================================================
-- Source: pos_standalone_s380_offline_sales_sync.sql
-- =============================================================
-- POS Standalone S380 - Offline Sales Sync
-- Run in every tenant database that will use POS offline sales.
-- No database name is hardcoded.

ALTER TABLE `pos_sales`
  ADD COLUMN IF NOT EXISTS `offline_client_token` VARCHAR(120) NULL,
  ADD COLUMN IF NOT EXISTS `offline_invoice_no` VARCHAR(120) NULL,
  ADD COLUMN IF NOT EXISTS `offline_device_uuid` VARCHAR(80) NULL,
  ADD COLUMN IF NOT EXISTS `offline_synced_at` DATETIME NULL;

CREATE UNIQUE INDEX IF NOT EXISTS `pos_sales_offline_client_token_unique` ON `pos_sales` (`offline_client_token`);
CREATE INDEX IF NOT EXISTS `pos_sales_offline_invoice_idx` ON `pos_sales` (`offline_invoice_no`);
CREATE INDEX IF NOT EXISTS `pos_sales_offline_device_idx` ON `pos_sales` (`offline_device_uuid`);

ALTER TABLE `pos_offline_sync_queue`
  ADD COLUMN IF NOT EXISTS `locked_at` DATETIME NULL,
  ADD COLUMN IF NOT EXISTS `locked_by` VARCHAR(120) NULL;

CREATE INDEX IF NOT EXISTS `pos_offline_sync_queue_type_status_idx` ON `pos_offline_sync_queue` (`transaction_type`, `status`);

-- S380 enables server posting of offline sales only.
-- Offline returns, offline register close and offline cash reconciliation remain conflict-controlled until later stages.


-- =============================================================
-- Source: pos_standalone_s381_offline_cache_conflict_validation.sql
-- =============================================================
-- POS Standalone S381 - Offline Cache, Stock Snapshot and Conflict Validation
-- Global SQL. Do not add database names.

ALTER TABLE pos_offline_sync_queue
    ADD COLUMN IF NOT EXISTS cache_version_hash VARCHAR(191) NULL AFTER server_invoice_no,
    ADD COLUMN IF NOT EXISTS preflight_status VARCHAR(50) NULL AFTER cache_version_hash,
    ADD COLUMN IF NOT EXISTS preflight_warnings JSON NULL AFTER preflight_status;

ALTER TABLE pos_offline_sync_conflicts
    ADD COLUMN IF NOT EXISTS resolved_by BIGINT UNSIGNED NULL AFTER resolution_status,
    ADD COLUMN IF NOT EXISTS resolution_note TEXT NULL AFTER resolved_by;

CREATE INDEX IF NOT EXISTS idx_pos_offline_queue_device_status ON pos_offline_sync_queue (device_uuid, status);
CREATE INDEX IF NOT EXISTS idx_pos_offline_queue_invoice ON pos_offline_sync_queue (offline_invoice_no);
CREATE INDEX IF NOT EXISTS idx_pos_offline_conflict_status ON pos_offline_sync_conflicts (resolution_status, resolved_at);


-- =============================================================
-- Source: pos_standalone_s382_sync_conflict_manager.sql
-- =============================================================
-- POS Standalone S382 - Sync Conflict Manager
-- Global SQL. Run inside each tenant database. Do not add database names.

ALTER TABLE pos_offline_sync_conflicts
    ADD COLUMN IF NOT EXISTS resolution_action VARCHAR(80) NULL AFTER resolution_status,
    ADD COLUMN IF NOT EXISTS manager_decision_payload JSON NULL AFTER resolution_note;

ALTER TABLE pos_offline_sync_queue
    ADD COLUMN IF NOT EXISTS dependency_key VARCHAR(191) NULL AFTER transaction_type,
    ADD COLUMN IF NOT EXISTS parent_client_token VARCHAR(120) NULL AFTER dependency_key,
    ADD COLUMN IF NOT EXISTS sync_priority INT NOT NULL DEFAULT 100 AFTER parent_client_token,
    ADD COLUMN IF NOT EXISTS manager_resolution JSON NULL AFTER preflight_warnings;

CREATE TABLE IF NOT EXISTS pos_offline_sync_audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id BIGINT UNSIGNED NULL,
    queue_id BIGINT UNSIGNED NULL,
    conflict_id BIGINT UNSIGNED NULL,
    device_uuid VARCHAR(80) NULL,
    action VARCHAR(80) NOT NULL,
    action_note TEXT NULL,
    before_payload LONGTEXT NULL,
    after_payload LONGTEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_pos_offline_audit_queue (queue_id),
    KEY idx_pos_offline_audit_conflict (conflict_id),
    KEY idx_pos_offline_audit_device (device_uuid),
    KEY idx_pos_offline_audit_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX IF NOT EXISTS idx_pos_offline_queue_dependency ON pos_offline_sync_queue (parent_client_token, status, sync_priority);
CREATE INDEX IF NOT EXISTS idx_pos_offline_conflict_type_status ON pos_offline_sync_conflicts (conflict_type, resolution_status);


-- =============================================================
-- Source: pos_standalone_s383_background_sync_engine.sql
-- =============================================================
-- POS Standalone S383 - Background Synchronization Engine
-- Global SQL: run inside each tenant database. No database name is hardcoded.

CREATE TABLE IF NOT EXISTS pos_sync_network_samples (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    location_id BIGINT UNSIGNED NULL,
    device_uuid VARCHAR(120) NOT NULL,
    latency_ms INT NULL DEFAULT 0,
    online TINYINT(1) NOT NULL DEFAULT 1,
    quality VARCHAR(40) NULL,
    sampled_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_pos_sync_net_device (device_uuid),
    INDEX idx_pos_sync_net_sampled (sampled_at),
    INDEX idx_pos_sync_net_location (business_id, location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE pos_devices
    ADD COLUMN IF NOT EXISTS last_seen_at TIMESTAMP NULL,
    ADD COLUMN IF NOT EXISTS status VARCHAR(40) NULL,
    ADD COLUMN IF NOT EXISTS network_quality VARCHAR(40) NULL,
    ADD COLUMN IF NOT EXISTS queue_size INT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS browser_info TEXT NULL;

ALTER TABLE pos_offline_sync_queue
    ADD COLUMN IF NOT EXISTS dependency_key VARCHAR(120) NULL,
    ADD COLUMN IF NOT EXISTS parent_client_token VARCHAR(120) NULL,
    ADD COLUMN IF NOT EXISTS priority INT NOT NULL DEFAULT 100,
    ADD COLUMN IF NOT EXISTS locked_at TIMESTAMP NULL,
    ADD COLUMN IF NOT EXISTS locked_by VARCHAR(120) NULL,
    ADD COLUMN IF NOT EXISTS batch_id VARCHAR(120) NULL,
    ADD COLUMN IF NOT EXISTS payload_hash VARCHAR(128) NULL,
    ADD INDEX IF NOT EXISTS idx_pos_sync_queue_priority (status, priority, id),
    ADD INDEX IF NOT EXISTS idx_pos_sync_queue_batch (batch_id),
    ADD INDEX IF NOT EXISTS idx_pos_sync_queue_parent (parent_client_token);


-- =============================================================
-- Source: pos_standalone_s384_enterprise_monitoring_multiterminal.sql
-- =============================================================
-- POS Standalone S384 - Enterprise Monitoring & Multi-Terminal Synchronization
-- Global SQL: run inside each tenant database. No database name is hardcoded.

ALTER TABLE pos_devices
    ADD COLUMN IF NOT EXISTS register_id BIGINT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS cashier_id BIGINT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS pos_version VARCHAR(60) NULL,
    ADD COLUMN IF NOT EXISTS trust_status VARCHAR(40) NOT NULL DEFAULT 'trusted',
    ADD COLUMN IF NOT EXISTS trusted_by BIGINT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS trusted_at TIMESTAMP NULL,
    ADD COLUMN IF NOT EXISTS device_certificate_hash VARCHAR(128) NULL,
    ADD COLUMN IF NOT EXISTS last_successful_sync_at TIMESTAMP NULL,
    ADD INDEX IF NOT EXISTS idx_pos_devices_location_seen (business_id, location_id, last_seen_at),
    ADD INDEX IF NOT EXISTS idx_pos_devices_trust (trust_status),
    ADD INDEX IF NOT EXISTS idx_pos_devices_register (register_id);

CREATE TABLE IF NOT EXISTS pos_sync_device_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    location_id BIGINT UNSIGNED NULL,
    device_uuid VARCHAR(120) NOT NULL,
    event_type VARCHAR(80) NOT NULL,
    event_message VARCHAR(500) NULL,
    queue_size INT NULL DEFAULT 0,
    network_quality VARCHAR(40) NULL,
    payload JSON NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_pos_sync_device_events_device (device_uuid),
    INDEX idx_pos_sync_device_events_type (event_type),
    INDEX idx_pos_sync_device_events_location (business_id, location_id),
    INDEX idx_pos_sync_device_events_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE pos_offline_sync_queue
    ADD COLUMN IF NOT EXISTS branch_sync_scope VARCHAR(80) NULL,
    ADD COLUMN IF NOT EXISTS replay_nonce VARCHAR(120) NULL,
    ADD COLUMN IF NOT EXISTS request_signature VARCHAR(255) NULL,
    ADD INDEX IF NOT EXISTS idx_pos_sync_queue_scope (business_id, location_id, status),
    ADD INDEX IF NOT EXISTS idx_pos_sync_queue_replay (replay_nonce);


-- =============================================================
-- Source: pos_standalone_s385_enterprise_reliability_disaster_recovery.sql
-- =============================================================
-- POS Standalone S385 - Enterprise Reliability & Disaster Recovery
-- Global SQL: run inside each tenant database. Do not hardcode database names.

CREATE TABLE IF NOT EXISTS `pos_sync_reliability_tests` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `device_uuid` VARCHAR(191) NULL,
  `scenario` VARCHAR(191) NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `notes` TEXT NULL,
  `tested_by` BIGINT UNSIGNED NULL,
  `tested_at` DATETIME NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_sync_reliability_tests_device_status_idx` (`device_uuid`, `status`),
  KEY `pos_sync_reliability_tests_business_status_idx` (`business_id`, `location_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_sync_integrity_snapshots` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `device_uuid` VARCHAR(191) NULL,
  `snapshot_type` VARCHAR(80) NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `summary_json` LONGTEXT NULL,
  `checked_at` DATETIME NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_sync_integrity_snapshots_device_status_idx` (`device_uuid`, `status`),
  KEY `pos_sync_integrity_snapshots_checked_idx` (`checked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional safety columns for existing queue table.
ALTER TABLE `pos_offline_sync_queue`
  ADD COLUMN IF NOT EXISTS `transaction_hash` VARCHAR(191) NULL AFTER `client_token`,
  ADD COLUMN IF NOT EXISTS `dependency_key` VARCHAR(191) NULL AFTER `transaction_hash`,
  ADD COLUMN IF NOT EXISTS `batch_id` VARCHAR(191) NULL AFTER `dependency_key`,
  ADD COLUMN IF NOT EXISTS `locked_at` DATETIME NULL AFTER `batch_id`,
  ADD COLUMN IF NOT EXISTS `locked_by` VARCHAR(191) NULL AFTER `locked_at`;

CREATE INDEX IF NOT EXISTS `pos_offline_sync_queue_hash_idx` ON `pos_offline_sync_queue` (`transaction_hash`);
CREATE INDEX IF NOT EXISTS `pos_offline_sync_queue_dependency_idx` ON `pos_offline_sync_queue` (`dependency_key`);
CREATE INDEX IF NOT EXISTS `pos_offline_sync_queue_batch_idx` ON `pos_offline_sync_queue` (`batch_id`);

SET FOREIGN_KEY_CHECKS=1;
