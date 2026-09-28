/* HOTELMGT_006 - Hotel Inventory stock movement and reorder support
   Run this inside EACH tenant database. No database name is hard-coded.
   This file contains only SQL related to ZIP HOTELMGT_006.
*/

CREATE TABLE IF NOT EXISTS `hm_store_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `item_code` VARCHAR(50) NULL,
  `name` VARCHAR(191) NOT NULL,
  `category` VARCHAR(100) NULL,
  `unit` VARCHAR(30) NULL DEFAULT 'unit',
  `purchase_price` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `selling_price` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `current_stock` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `reorder_level` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(30) NOT NULL DEFAULT 'active',
  `last_movement_at` DATETIME NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_store_items_business_idx` (`business_id`),
  KEY `hm_store_items_location_idx` (`business_location_id`),
  KEY `hm_store_items_code_idx` (`item_code`),
  KEY `hm_store_items_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_store_movements` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `store_item_id` BIGINT UNSIGNED NOT NULL,
  `movement_no` VARCHAR(50) NULL,
  `movement_type` VARCHAR(30) NOT NULL,
  `movement_date` DATE NULL,
  `direction` TINYINT NOT NULL DEFAULT 1,
  `quantity` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `unit_cost` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `total_cost` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `stock_before` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `stock_after` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `reference_no` VARCHAR(100) NULL,
  `note` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_store_movements_business_idx` (`business_id`),
  KEY `hm_store_movements_location_idx` (`business_location_id`),
  KEY `hm_store_movements_item_idx` (`store_item_id`),
  KEY `hm_store_movements_date_idx` (`movement_date`),
  KEY `hm_store_movements_no_idx` (`movement_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP PROCEDURE IF EXISTS hm_hotelmgt_006_add_column;
DELIMITER $$
CREATE PROCEDURE hm_hotelmgt_006_add_column(
    IN p_table_name VARCHAR(128),
    IN p_column_name VARCHAR(128),
    IN p_column_definition TEXT
)
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = p_table_name)
       AND NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = p_table_name AND column_name = p_column_name) THEN
        SET @ddl = CONCAT('ALTER TABLE `', p_table_name, '` ADD COLUMN `', p_column_name, '` ', p_column_definition);
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

CALL hm_hotelmgt_006_add_column('hm_store_items', 'business_id', 'BIGINT UNSIGNED NULL AFTER id');
CALL hm_hotelmgt_006_add_column('hm_store_items', 'business_location_id', 'BIGINT UNSIGNED NULL AFTER business_id');
CALL hm_hotelmgt_006_add_column('hm_store_items', 'purchase_price', 'DECIMAL(22,4) NOT NULL DEFAULT 0.0000 AFTER unit');
CALL hm_hotelmgt_006_add_column('hm_store_items', 'selling_price', 'DECIMAL(22,4) NOT NULL DEFAULT 0.0000 AFTER purchase_price');
CALL hm_hotelmgt_006_add_column('hm_store_items', 'current_stock', 'DECIMAL(22,4) NOT NULL DEFAULT 0.0000 AFTER selling_price');
CALL hm_hotelmgt_006_add_column('hm_store_items', 'last_movement_at', 'DATETIME NULL AFTER status');
CALL hm_hotelmgt_006_add_column('hm_store_movements', 'business_id', 'BIGINT UNSIGNED NULL AFTER id');
CALL hm_hotelmgt_006_add_column('hm_store_movements', 'business_location_id', 'BIGINT UNSIGNED NULL AFTER business_id');
CALL hm_hotelmgt_006_add_column('hm_store_movements', 'movement_no', 'VARCHAR(50) NULL AFTER store_item_id');
CALL hm_hotelmgt_006_add_column('hm_store_movements', 'movement_date', 'DATE NULL AFTER movement_type');
CALL hm_hotelmgt_006_add_column('hm_store_movements', 'direction', 'TINYINT NOT NULL DEFAULT 1 AFTER movement_date');
CALL hm_hotelmgt_006_add_column('hm_store_movements', 'unit_cost', 'DECIMAL(22,4) NOT NULL DEFAULT 0.0000 AFTER quantity');
CALL hm_hotelmgt_006_add_column('hm_store_movements', 'total_cost', 'DECIMAL(22,4) NOT NULL DEFAULT 0.0000 AFTER unit_cost');
CALL hm_hotelmgt_006_add_column('hm_store_movements', 'stock_before', 'DECIMAL(22,4) NOT NULL DEFAULT 0.0000 AFTER total_cost');
CALL hm_hotelmgt_006_add_column('hm_store_movements', 'stock_after', 'DECIMAL(22,4) NOT NULL DEFAULT 0.0000 AFTER stock_before');
CALL hm_hotelmgt_006_add_column('hm_store_movements', 'reference_no', 'VARCHAR(100) NULL AFTER stock_after');
CALL hm_hotelmgt_006_add_column('hm_store_movements', 'note', 'TEXT NULL AFTER reference_no');

DROP PROCEDURE IF EXISTS hm_hotelmgt_006_add_column;

DROP PROCEDURE IF EXISTS hm_hotelmgt_006_add_index;
DELIMITER $$
CREATE PROCEDURE hm_hotelmgt_006_add_index(
    IN p_table_name VARCHAR(128),
    IN p_index_name VARCHAR(128),
    IN p_index_columns TEXT
)
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = p_table_name)
       AND NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = p_table_name AND index_name = p_index_name) THEN
        SET @ddl = CONCAT('ALTER TABLE `', p_table_name, '` ADD INDEX `', p_index_name, '` (', p_index_columns, ')');
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

CALL hm_hotelmgt_006_add_index('hm_store_items', 'hm_store_items_business_idx', '`business_id`');
CALL hm_hotelmgt_006_add_index('hm_store_items', 'hm_store_items_location_idx', '`business_location_id`');
CALL hm_hotelmgt_006_add_index('hm_store_items', 'hm_store_items_code_idx', '`item_code`');
CALL hm_hotelmgt_006_add_index('hm_store_movements', 'hm_store_movements_business_idx', '`business_id`');
CALL hm_hotelmgt_006_add_index('hm_store_movements', 'hm_store_movements_location_idx', '`business_location_id`');
CALL hm_hotelmgt_006_add_index('hm_store_movements', 'hm_store_movements_item_idx', '`store_item_id`');
CALL hm_hotelmgt_006_add_index('hm_store_movements', 'hm_store_movements_date_idx', '`movement_date`');

DROP PROCEDURE IF EXISTS hm_hotelmgt_006_add_index;
