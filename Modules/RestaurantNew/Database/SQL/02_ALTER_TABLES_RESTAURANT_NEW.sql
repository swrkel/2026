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
