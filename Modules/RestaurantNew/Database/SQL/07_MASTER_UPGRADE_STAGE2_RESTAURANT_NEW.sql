-- Restaurant-New Stage 2 upgrade master
-- Run in each existing tenant database after deploying Stage 2 code.

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


-- Restaurant-New Stage 2 idempotent ALTER statements
-- Safe for fresh or upgraded tenant databases.
-- No shared/core table is altered.

DELIMITER $$
DROP PROCEDURE IF EXISTS `restnew_add_column_if_missing`$$
CREATE PROCEDURE `restnew_add_column_if_missing`(
    IN p_table VARCHAR(64),
    IN p_column VARCHAR(64),
    IN p_definition TEXT
)
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = DATABASE() AND table_name = p_table
    ) AND NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = p_table AND column_name = p_column
    ) THEN
        SET @restnew_sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_column, '` ', p_definition);
        PREPARE restnew_stmt FROM @restnew_sql;
        EXECUTE restnew_stmt;
        DEALLOCATE PREPARE restnew_stmt;
    END IF;
END$$

DROP PROCEDURE IF EXISTS `restnew_add_index_if_missing`$$
CREATE PROCEDURE `restnew_add_index_if_missing`(
    IN p_table VARCHAR(64),
    IN p_index VARCHAR(64),
    IN p_columns TEXT
)
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = DATABASE() AND table_name = p_table
    ) AND NOT EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = DATABASE() AND table_name = p_table AND index_name = p_index
    ) THEN
        SET @restnew_sql = CONCAT('ALTER TABLE `', p_table, '` ADD INDEX `', p_index, '` (', p_columns, ')');
        PREPARE restnew_stmt FROM @restnew_sql;
        EXECUTE restnew_stmt;
        DEALLOCATE PREPARE restnew_stmt;
    END IF;
END$$
DELIMITER ;

CALL restnew_add_column_if_missing('restnew_orders','reservation_id','BIGINT UNSIGNED NULL AFTER `table_id`');
CALL restnew_add_column_if_missing('restnew_orders','delivery_zone_id','BIGINT UNSIGNED NULL AFTER `reservation_id`');
CALL restnew_add_column_if_missing('restnew_orders','discount_rule_id','BIGINT UNSIGNED NULL AFTER `delivery_zone_id`');
CALL restnew_add_column_if_missing('restnew_orders','delivery_address','TEXT NULL AFTER `customer_email`');
CALL restnew_add_column_if_missing('restnew_orders','delivery_fee','DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `service_charge_total`');
CALL restnew_add_column_if_missing('restnew_orders','discount_reason','VARCHAR(255) NULL AFTER `discount_total`');
CALL restnew_add_column_if_missing('restnew_orders','discount_authorized_by','BIGINT UNSIGNED NULL AFTER `discount_reason`');
CALL restnew_add_column_if_missing('restnew_orders','discount_authorized_at','DATETIME NULL AFTER `discount_authorized_by`');
CALL restnew_add_index_if_missing('restnew_orders','rn_order_reservation_idx','`reservation_id`');
CALL restnew_add_index_if_missing('restnew_orders','rn_order_delivery_zone_idx','`delivery_zone_id`');
CALL restnew_add_index_if_missing('restnew_orders','rn_order_discount_rule_idx','`discount_rule_id`');

CALL restnew_add_column_if_missing('restnew_reservations','customer_email','VARCHAR(160) NULL AFTER `customer_phone`');
CALL restnew_add_column_if_missing('restnew_reservations','duration_minutes','INT UNSIGNED NOT NULL DEFAULT 90 AFTER `reserved_at`');
CALL restnew_add_column_if_missing('restnew_reservations','source','VARCHAR(30) NOT NULL DEFAULT ''phone'' AFTER `status`');
CALL restnew_add_column_if_missing('restnew_reservations','deposit_amount','DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `source`');
CALL restnew_add_column_if_missing('restnew_reservations','seated_order_id','BIGINT UNSIGNED NULL AFTER `deposit_amount`');
CALL restnew_add_column_if_missing('restnew_reservations','confirmed_at','DATETIME NULL');
CALL restnew_add_column_if_missing('restnew_reservations','seated_at','DATETIME NULL');
CALL restnew_add_column_if_missing('restnew_reservations','cancelled_at','DATETIME NULL');
CALL restnew_add_index_if_missing('restnew_reservations','rn_reservation_seated_order_idx','`seated_order_id`');

CALL restnew_add_column_if_missing('restnew_ingredients','supplier_id','BIGINT UNSIGNED NULL AFTER `business_id`');
CALL restnew_add_column_if_missing('restnew_ingredients','barcode','VARCHAR(100) NULL AFTER `ingredient_code`');
CALL restnew_add_column_if_missing('restnew_ingredients','purchase_unit','VARCHAR(40) NULL AFTER `unit`');
CALL restnew_add_column_if_missing('restnew_ingredients','purchase_conversion','DECIMAL(22,4) NOT NULL DEFAULT 1 AFTER `purchase_unit`');
CALL restnew_add_column_if_missing('restnew_ingredients','track_expiry','TINYINT(1) NOT NULL DEFAULT 0 AFTER `reorder_level`');
CALL restnew_add_index_if_missing('restnew_ingredients','rn_ingredient_supplier_idx','`supplier_id`');
CALL restnew_add_index_if_missing('restnew_ingredients','rn_ingredient_barcode_idx','`barcode`');

CALL restnew_add_column_if_missing('restnew_menu_items','is_delivery','TINYINT(1) NOT NULL DEFAULT 1 AFTER `is_takeaway`');

CALL restnew_add_column_if_missing('restnew_stock_movements','batch_no','VARCHAR(100) NULL AFTER `reference_no`');
CALL restnew_add_column_if_missing('restnew_stock_movements','expiry_date','DATE NULL AFTER `batch_no`');
CALL restnew_add_index_if_missing('restnew_stock_movements','rn_stock_movement_expiry_idx','`expiry_date`');

DROP PROCEDURE IF EXISTS `restnew_add_index_if_missing`;
DROP PROCEDURE IF EXISTS `restnew_add_column_if_missing`;

SELECT 'Restaurant-New Stage 2 ALTER checks completed' AS information;


-- Restaurant-New permissions
-- Safe to run repeatedly: every insert uses a NOT EXISTS condition.

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.access', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.access' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.dashboard.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.dashboard.view' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.manager.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.manager.view' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.waiter.use', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.waiter.use' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.cashier.use', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.cashier.use' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.kitchen.use', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.kitchen.use' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.takeaway.use', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.takeaway.use' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.collection.use', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.collection.use' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.reservations.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.reservations.view' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.reservations.manage', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.reservations.manage' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.delivery.use', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.delivery.use' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.delivery.manage', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.delivery.manage' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.orders.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.orders.view' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.orders.create', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.orders.create' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.orders.edit', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.orders.edit' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.orders.cancel', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.orders.cancel' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.orders.void_item', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.orders.void_item' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.payments.create', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.payments.create' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.payments.refund', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.payments.refund' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.shifts.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.shifts.view' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.shifts.open', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.shifts.open' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.shifts.close', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.shifts.close' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.menu.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.menu.view' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.menu.manage', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.menu.manage' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.discounts.apply', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.discounts.apply' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.discounts.manage', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.discounts.manage' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.discounts.approve', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.discounts.approve' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.setup.manage', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.setup.manage' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.recipes.manage', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.recipes.manage' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.stock.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.stock.view' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.stock.adjust', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.stock.adjust' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.stock.transfer', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.stock.transfer' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.stock.stocktake', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.stock.stocktake' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.stock.wastage', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.stock.wastage' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.procurement.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.procurement.view' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.procurement.manage', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.procurement.manage' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.procurement.post', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.procurement.post' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.reports.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.reports.view' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.reports.export', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.reports.export' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.report.sales', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.report.sales' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.report.item_sales', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.report.item_sales' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.report.kitchen', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.report.kitchen' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.report.cashier', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.report.cashier' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.report.takeaway', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.report.takeaway' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.report.hourly', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.report.hourly' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.report.waiter', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.report.waiter' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.report.discounts', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.report.discounts' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.report.voids', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.report.voids' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.report.stock_usage', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.report.stock_usage' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.report.purchases', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.report.purchases' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.report.wastage', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.report.wastage' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.report.profitability', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.report.profitability' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.documents.print', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.documents.print' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.settings.manage', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.settings.manage' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'restaurant_new.screen_assignments.manage', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'restaurant_new.screen_assignments.manage' AND `guard_name` = 'web');


-- Restaurant-New installation verification
-- Expected module-owned tables: 45
-- Expected permissions: 55

SELECT COUNT(*) AS restnew_table_count, 45 AS expected_table_count
FROM information_schema.tables
WHERE table_schema = DATABASE() AND table_name LIKE 'restnew\_%';

SELECT expected.table_name AS missing_table
FROM (
  SELECT 'restnew_settings' AS table_name
UNION ALL
  SELECT 'restnew_number_sequences' AS table_name
UNION ALL
  SELECT 'restnew_floors' AS table_name
UNION ALL
  SELECT 'restnew_tables' AS table_name
UNION ALL
  SELECT 'restnew_kitchen_stations' AS table_name
UNION ALL
  SELECT 'restnew_printers' AS table_name
UNION ALL
  SELECT 'restnew_user_screen_assignments' AS table_name
UNION ALL
  SELECT 'restnew_categories' AS table_name
UNION ALL
  SELECT 'restnew_menu_items' AS table_name
UNION ALL
  SELECT 'restnew_modifier_groups' AS table_name
UNION ALL
  SELECT 'restnew_modifiers' AS table_name
UNION ALL
  SELECT 'restnew_menu_item_modifier_groups' AS table_name
UNION ALL
  SELECT 'restnew_ingredients' AS table_name
UNION ALL
  SELECT 'restnew_recipes' AS table_name
UNION ALL
  SELECT 'restnew_recipe_lines' AS table_name
UNION ALL
  SELECT 'restnew_inventory_balances' AS table_name
UNION ALL
  SELECT 'restnew_stock_movements' AS table_name
UNION ALL
  SELECT 'restnew_shifts' AS table_name
UNION ALL
  SELECT 'restnew_orders' AS table_name
UNION ALL
  SELECT 'restnew_order_items' AS table_name
UNION ALL
  SELECT 'restnew_order_item_modifiers' AS table_name
UNION ALL
  SELECT 'restnew_kitchen_tickets' AS table_name
UNION ALL
  SELECT 'restnew_kitchen_ticket_items' AS table_name
UNION ALL
  SELECT 'restnew_order_status_logs' AS table_name
UNION ALL
  SELECT 'restnew_collection_tokens' AS table_name
UNION ALL
  SELECT 'restnew_reservations' AS table_name
UNION ALL
  SELECT 'restnew_print_jobs' AS table_name
UNION ALL
  SELECT 'restnew_payments' AS table_name
UNION ALL
  SELECT 'restnew_daily_closures' AS table_name
UNION ALL
  SELECT 'restnew_audit_logs' AS table_name
UNION ALL
  SELECT 'restnew_suppliers' AS table_name
UNION ALL
  SELECT 'restnew_goods_receipts' AS table_name
UNION ALL
  SELECT 'restnew_goods_receipt_lines' AS table_name
UNION ALL
  SELECT 'restnew_stock_transfers' AS table_name
UNION ALL
  SELECT 'restnew_stock_transfer_lines' AS table_name
UNION ALL
  SELECT 'restnew_stocktakes' AS table_name
UNION ALL
  SELECT 'restnew_stocktake_lines' AS table_name
UNION ALL
  SELECT 'restnew_wastages' AS table_name
UNION ALL
  SELECT 'restnew_wastage_lines' AS table_name
UNION ALL
  SELECT 'restnew_delivery_zones' AS table_name
UNION ALL
  SELECT 'restnew_delivery_dispatches' AS table_name
UNION ALL
  SELECT 'restnew_discount_rules' AS table_name
UNION ALL
  SELECT 'restnew_discount_usages' AS table_name
UNION ALL
  SELECT 'restnew_order_adjustments' AS table_name
UNION ALL
  SELECT 'restnew_manager_approvals' AS table_name
) expected
LEFT JOIN information_schema.tables actual
  ON actual.table_schema = DATABASE() AND actual.table_name = expected.table_name
WHERE actual.table_name IS NULL
ORDER BY expected.table_name;

SELECT COUNT(*) AS restaurant_permission_count, 55 AS expected_permission_count
FROM `permissions`
WHERE `guard_name`='web' AND `name` LIKE 'restaurant\_new.%';

SELECT expected.permission_name AS missing_permission
FROM (
  SELECT 'restaurant_new.access' AS permission_name
UNION ALL
  SELECT 'restaurant_new.dashboard.view' AS permission_name
UNION ALL
  SELECT 'restaurant_new.manager.view' AS permission_name
UNION ALL
  SELECT 'restaurant_new.waiter.use' AS permission_name
UNION ALL
  SELECT 'restaurant_new.cashier.use' AS permission_name
UNION ALL
  SELECT 'restaurant_new.kitchen.use' AS permission_name
UNION ALL
  SELECT 'restaurant_new.takeaway.use' AS permission_name
UNION ALL
  SELECT 'restaurant_new.collection.use' AS permission_name
UNION ALL
  SELECT 'restaurant_new.reservations.view' AS permission_name
UNION ALL
  SELECT 'restaurant_new.reservations.manage' AS permission_name
UNION ALL
  SELECT 'restaurant_new.delivery.use' AS permission_name
UNION ALL
  SELECT 'restaurant_new.delivery.manage' AS permission_name
UNION ALL
  SELECT 'restaurant_new.orders.view' AS permission_name
UNION ALL
  SELECT 'restaurant_new.orders.create' AS permission_name
UNION ALL
  SELECT 'restaurant_new.orders.edit' AS permission_name
UNION ALL
  SELECT 'restaurant_new.orders.cancel' AS permission_name
UNION ALL
  SELECT 'restaurant_new.orders.void_item' AS permission_name
UNION ALL
  SELECT 'restaurant_new.payments.create' AS permission_name
UNION ALL
  SELECT 'restaurant_new.payments.refund' AS permission_name
UNION ALL
  SELECT 'restaurant_new.shifts.view' AS permission_name
UNION ALL
  SELECT 'restaurant_new.shifts.open' AS permission_name
UNION ALL
  SELECT 'restaurant_new.shifts.close' AS permission_name
UNION ALL
  SELECT 'restaurant_new.menu.view' AS permission_name
UNION ALL
  SELECT 'restaurant_new.menu.manage' AS permission_name
UNION ALL
  SELECT 'restaurant_new.discounts.apply' AS permission_name
UNION ALL
  SELECT 'restaurant_new.discounts.manage' AS permission_name
UNION ALL
  SELECT 'restaurant_new.discounts.approve' AS permission_name
UNION ALL
  SELECT 'restaurant_new.setup.manage' AS permission_name
UNION ALL
  SELECT 'restaurant_new.recipes.manage' AS permission_name
UNION ALL
  SELECT 'restaurant_new.stock.view' AS permission_name
UNION ALL
  SELECT 'restaurant_new.stock.adjust' AS permission_name
UNION ALL
  SELECT 'restaurant_new.stock.transfer' AS permission_name
UNION ALL
  SELECT 'restaurant_new.stock.stocktake' AS permission_name
UNION ALL
  SELECT 'restaurant_new.stock.wastage' AS permission_name
UNION ALL
  SELECT 'restaurant_new.procurement.view' AS permission_name
UNION ALL
  SELECT 'restaurant_new.procurement.manage' AS permission_name
UNION ALL
  SELECT 'restaurant_new.procurement.post' AS permission_name
UNION ALL
  SELECT 'restaurant_new.reports.view' AS permission_name
UNION ALL
  SELECT 'restaurant_new.reports.export' AS permission_name
UNION ALL
  SELECT 'restaurant_new.report.sales' AS permission_name
UNION ALL
  SELECT 'restaurant_new.report.item_sales' AS permission_name
UNION ALL
  SELECT 'restaurant_new.report.kitchen' AS permission_name
UNION ALL
  SELECT 'restaurant_new.report.cashier' AS permission_name
UNION ALL
  SELECT 'restaurant_new.report.takeaway' AS permission_name
UNION ALL
  SELECT 'restaurant_new.report.hourly' AS permission_name
UNION ALL
  SELECT 'restaurant_new.report.waiter' AS permission_name
UNION ALL
  SELECT 'restaurant_new.report.discounts' AS permission_name
UNION ALL
  SELECT 'restaurant_new.report.voids' AS permission_name
UNION ALL
  SELECT 'restaurant_new.report.stock_usage' AS permission_name
UNION ALL
  SELECT 'restaurant_new.report.purchases' AS permission_name
UNION ALL
  SELECT 'restaurant_new.report.wastage' AS permission_name
UNION ALL
  SELECT 'restaurant_new.report.profitability' AS permission_name
UNION ALL
  SELECT 'restaurant_new.documents.print' AS permission_name
UNION ALL
  SELECT 'restaurant_new.settings.manage' AS permission_name
UNION ALL
  SELECT 'restaurant_new.screen_assignments.manage' AS permission_name
) expected
LEFT JOIN `permissions` actual
  ON actual.guard_name='web' AND actual.name=expected.permission_name
WHERE actual.id IS NULL
ORDER BY expected.permission_name;
