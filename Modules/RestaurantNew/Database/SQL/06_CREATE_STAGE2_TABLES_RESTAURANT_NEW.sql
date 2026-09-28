-- Restaurant-New Stage 2 advanced operations tables
-- Safe to run repeatedly: every table uses CREATE TABLE IF NOT EXISTS.
-- Run in each TENANT database only.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `restnew_suppliers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `supplier_code` VARCHAR(60) NOT NULL,
  `name` VARCHAR(180) NOT NULL,
  `contact_person` VARCHAR(160) NULL DEFAULT NULL,
  `phone` VARCHAR(60) NULL DEFAULT NULL,
  `email` VARCHAR(160) NULL DEFAULT NULL,
  `address` TEXT NULL,
  `tax_no` VARCHAR(80) NULL DEFAULT NULL,
  `credit_limit` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `credit_days` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_supplier_business_code_uq` (`business_id`,`supplier_code`),
  KEY `rn_supplier_location_idx` (`location_id`),
  KEY `rn_supplier_scope_name_idx` (`business_id`,`location_id`,`name`),
  KEY `rn_supplier_active_idx` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_goods_receipts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `supplier_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `receipt_no` VARCHAR(80) NOT NULL,
  `supplier_invoice_no` VARCHAR(100) NULL DEFAULT NULL,
  `received_date` DATE NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'draft',
  `subtotal` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `discount_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `tax_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `total_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `notes` TEXT NULL,
  `received_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `posted_at` DATETIME NULL DEFAULT NULL,
  `posted_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_goods_receipt_business_no_uq` (`business_id`,`receipt_no`),
  KEY `rn_gr_supplier_idx` (`supplier_id`),
  KEY `rn_gr_scope_date_idx` (`business_id`,`location_id`,`received_date`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_goods_receipt_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `goods_receipt_id` BIGINT UNSIGNED NOT NULL,
  `ingredient_id` BIGINT UNSIGNED NOT NULL,
  `quantity` DECIMAL(22,4) NOT NULL,
  `unit_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `tax_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `line_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `batch_no` VARCHAR(100) NULL DEFAULT NULL,
  `expiry_date` DATE NULL DEFAULT NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rn_grl_business_idx` (`business_id`),
  KEY `rn_grl_receipt_item_idx` (`goods_receipt_id`,`ingredient_id`),
  KEY `rn_grl_expiry_idx` (`expiry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_stock_transfers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `from_location_id` BIGINT UNSIGNED NOT NULL,
  `to_location_id` BIGINT UNSIGNED NOT NULL,
  `transfer_no` VARCHAR(80) NOT NULL,
  `transfer_date` DATE NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'draft',
  `notes` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `dispatched_at` DATETIME NULL DEFAULT NULL,
  `dispatched_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `received_at` DATETIME NULL DEFAULT NULL,
  `received_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_stock_transfer_business_no_uq` (`business_id`,`transfer_no`),
  KEY `rn_transfer_date_idx` (`transfer_date`),
  KEY `rn_transfer_scope_idx` (`business_id`,`from_location_id`,`to_location_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_stock_transfer_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `stock_transfer_id` BIGINT UNSIGNED NOT NULL,
  `ingredient_id` BIGINT UNSIGNED NOT NULL,
  `requested_qty` DECIMAL(22,4) NOT NULL,
  `dispatched_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `received_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `unit_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_stock_transfer_ingredient_uq` (`stock_transfer_id`,`ingredient_id`),
  KEY `rn_transfer_line_business_idx` (`business_id`),
  KEY `rn_transfer_line_ingredient_idx` (`ingredient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_stocktakes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `stocktake_no` VARCHAR(80) NOT NULL,
  `stocktake_date` DATE NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'draft',
  `notes` TEXT NULL,
  `counted_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `posted_at` DATETIME NULL DEFAULT NULL,
  `posted_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_stocktake_business_no_uq` (`business_id`,`stocktake_no`),
  KEY `rn_stocktake_scope_date_idx` (`business_id`,`location_id`,`stocktake_date`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_stocktake_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `stocktake_id` BIGINT UNSIGNED NOT NULL,
  `ingredient_id` BIGINT UNSIGNED NOT NULL,
  `system_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `counted_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `variance_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `unit_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `variance_value` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_stocktake_ingredient_uq` (`stocktake_id`,`ingredient_id`),
  KEY `rn_stocktake_line_business_idx` (`business_id`),
  KEY `rn_stocktake_line_ingredient_idx` (`ingredient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_wastages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `wastage_no` VARCHAR(80) NOT NULL,
  `wastage_date` DATE NOT NULL,
  `reason_code` VARCHAR(50) NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'posted',
  `notes` TEXT NULL,
  `reported_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `approved_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `approved_at` DATETIME NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_wastage_business_no_uq` (`business_id`,`wastage_no`),
  KEY `rn_wastage_scope_date_idx` (`business_id`,`location_id`,`wastage_date`,`reason_code`),
  KEY `rn_wastage_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_wastage_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `wastage_id` BIGINT UNSIGNED NOT NULL,
  `ingredient_id` BIGINT UNSIGNED NOT NULL,
  `quantity` DECIMAL(22,4) NOT NULL,
  `unit_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `value` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `batch_no` VARCHAR(100) NULL DEFAULT NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rn_wastage_line_business_idx` (`business_id`),
  KEY `rn_wastage_line_item_idx` (`wastage_id`,`ingredient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_delivery_zones` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `zone_code` VARCHAR(50) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `minimum_order` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `delivery_fee` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `estimated_minutes` INT UNSIGNED NOT NULL DEFAULT 30,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_delivery_zone_scope_code_uq` (`business_id`,`location_id`,`zone_code`),
  KEY `rn_delivery_zone_active_idx` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_delivery_dispatches` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `delivery_zone_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `driver_user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `dispatch_no` VARCHAR(80) NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'waiting',
  `delivery_address` TEXT NOT NULL,
  `customer_phone` VARCHAR(60) NULL DEFAULT NULL,
  `instructions` TEXT NULL,
  `assigned_at` DATETIME NULL DEFAULT NULL,
  `dispatched_at` DATETIME NULL DEFAULT NULL,
  `delivered_at` DATETIME NULL DEFAULT NULL,
  `cash_to_collect` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `cash_collected` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `created_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_delivery_dispatch_business_no_uq` (`business_id`,`dispatch_no`),
  UNIQUE KEY `restnew_delivery_dispatch_order_uq` (`order_id`),
  KEY `rn_dispatch_zone_idx` (`delivery_zone_id`),
  KEY `rn_dispatch_driver_idx` (`driver_user_id`),
  KEY `rn_dispatch_queue_idx` (`business_id`,`location_id`,`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_discount_rules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `rule_code` VARCHAR(50) NOT NULL,
  `name` VARCHAR(140) NOT NULL,
  `discount_type` VARCHAR(20) NOT NULL DEFAULT 'percentage',
  `discount_value` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `maximum_discount` DECIMAL(22,4) NULL DEFAULT NULL,
  `minimum_order` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `starts_on` DATE NULL DEFAULT NULL,
  `ends_on` DATE NULL DEFAULT NULL,
  `starts_at` TIME NULL DEFAULT NULL,
  `ends_at` TIME NULL DEFAULT NULL,
  `days_json` JSON NULL,
  `order_type` VARCHAR(30) NULL DEFAULT NULL,
  `requires_manager` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restnew_discount_rule_scope_code_uq` (`business_id`,`location_id`,`rule_code`),
  KEY `rn_discount_rule_active_idx` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_discount_usages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `discount_rule_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `rule_code` VARCHAR(50) NULL DEFAULT NULL,
  `rule_name` VARCHAR(140) NULL DEFAULT NULL,
  `discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `reason` VARCHAR(255) NULL DEFAULT NULL,
  `applied_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `approved_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rn_discount_usage_order_idx` (`order_id`),
  KEY `rn_discount_usage_rule_idx` (`discount_rule_id`),
  KEY `rn_discount_usage_date_idx` (`business_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_order_adjustments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `order_item_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `adjustment_type` VARCHAR(40) NOT NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `reason` TEXT NOT NULL,
  `before_json` JSON NULL,
  `after_json` JSON NULL,
  `requested_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `approved_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `approved_at` DATETIME NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rn_adjustment_order_idx` (`order_id`),
  KEY `rn_adjustment_item_idx` (`order_item_id`),
  KEY `rn_adjustment_type_date_idx` (`business_id`,`adjustment_type`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restnew_manager_approvals` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `approval_type` VARCHAR(50) NOT NULL,
  `entity_type` VARCHAR(80) NOT NULL,
  `entity_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'approved',
  `reason` TEXT NULL,
  `payload_json` JSON NULL,
  `requested_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `approved_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `approved_at` DATETIME NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rn_approval_status_idx` (`status`),
  KEY `rn_approval_entity_idx` (`entity_type`,`entity_id`),
  KEY `rn_approval_scope_idx` (`business_id`,`location_id`,`approval_type`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
