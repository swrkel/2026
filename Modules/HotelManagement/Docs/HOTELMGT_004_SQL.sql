-- HOTELMGT_004_SQL.sql
-- Hotel Management 004: Maintenance work orders and room out-of-service tracking.
-- Run this inside EACH tenant database. No database name is hard-coded.

CREATE TABLE IF NOT EXISTS hm_maintenance_work_orders (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id BIGINT UNSIGNED NULL,
    business_location_id BIGINT UNSIGNED NULL,
    room_id BIGINT UNSIGNED NULL,
    work_order_no VARCHAR(50) NULL,
    category VARCHAR(50) NULL DEFAULT 'general',
    priority VARCHAR(30) NULL DEFAULT 'normal',
    status VARCHAR(30) NULL DEFAULT 'open',
    reported_at DATETIME NULL,
    assigned_to VARCHAR(100) NULL,
    description TEXT NULL,
    estimated_cost DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    completed_at DATETIME NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    KEY hm_mwo_business_id_index (business_id),
    KEY hm_mwo_location_id_index (business_location_id),
    KEY hm_mwo_room_id_index (room_id),
    KEY hm_mwo_status_index (status),
    KEY hm_mwo_work_order_no_index (work_order_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP PROCEDURE IF EXISTS hm_hotelmgt_004_add_column;
DELIMITER $$
CREATE PROCEDURE hm_hotelmgt_004_add_column(
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

CALL hm_hotelmgt_004_add_column('hm_rooms', 'maintenance_status', "VARCHAR(30) NULL DEFAULT 'clear' AFTER housekeeping_status");
CALL hm_hotelmgt_004_add_column('hm_rooms', 'last_maintenance_at', 'DATETIME NULL AFTER maintenance_status');

DROP PROCEDURE IF EXISTS hm_hotelmgt_004_add_column;
