-- HOTELMGT_003_SQL.sql
-- Run this inside EACH tenant database that uses Hotel Management.
-- No database name is hard-coded. This script uses DATABASE() so it is safe for multi-tenant execution.

DROP PROCEDURE IF EXISTS hm_add_index_if_not_exists;
DELIMITER $$
CREATE PROCEDURE hm_add_index_if_not_exists(
    IN p_table_name VARCHAR(128),
    IN p_index_name VARCHAR(128),
    IN p_index_columns TEXT
)
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = p_table_name) THEN
        IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = p_table_name AND index_name = p_index_name) THEN
            SET @sql = CONCAT('ALTER TABLE `', p_table_name, '` ADD INDEX `', p_index_name, '` (', p_index_columns, ')');
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        END IF;
    END IF;
END$$
DELIMITER ;

CALL hm_add_index_if_not_exists('hm_reservation_rooms', 'hm_rr_business_reservation_idx', '`business_id`, `reservation_id`');
CALL hm_add_index_if_not_exists('hm_reservation_rooms', 'hm_rr_business_room_idx', '`business_id`, `room_id`');
CALL hm_add_index_if_not_exists('hm_checkins', 'hm_checkins_business_status_idx', '`business_id`, `status`');
CALL hm_add_index_if_not_exists('hm_checkins', 'hm_checkins_business_room_idx', '`business_id`, `room_id`');
CALL hm_add_index_if_not_exists('hm_checkouts', 'hm_checkouts_business_room_idx', '`business_id`, `room_id`');
CALL hm_add_index_if_not_exists('hm_folios', 'hm_folios_business_status_idx', '`business_id`, `status`');
CALL hm_add_index_if_not_exists('hm_folios', 'hm_folios_business_reservation_idx', '`business_id`, `reservation_id`');
CALL hm_add_index_if_not_exists('hm_folio_lines', 'hm_folio_lines_folio_date_idx', '`folio_id`, `charge_date`');
CALL hm_add_index_if_not_exists('hm_guest_payments', 'hm_guest_payments_folio_date_idx', '`folio_id`, `payment_date`');

DROP PROCEDURE IF EXISTS hm_add_index_if_not_exists;
