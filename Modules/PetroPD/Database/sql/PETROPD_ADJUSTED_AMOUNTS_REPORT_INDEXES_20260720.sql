-- Petro PD - Adjusted Amounts Report performance indexes
-- Safe to run more than once on each tenant database.

DELIMITER $$

DROP PROCEDURE IF EXISTS petropd_add_report_index_if_missing$$
CREATE PROCEDURE petropd_add_report_index_if_missing(
    IN p_table_name VARCHAR(128),
    IN p_index_name VARCHAR(128),
    IN p_ddl TEXT
)
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = p_table_name
    ) AND NOT EXISTS (
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = p_table_name
          AND index_name = p_index_name
    ) THEN
        SET @petropd_report_ddl = p_ddl;
        PREPARE petropd_report_stmt FROM @petropd_report_ddl;
        EXECUTE petropd_report_stmt;
        DEALLOCATE PREPARE petropd_report_stmt;
    END IF;
END$$

CALL petropd_add_report_index_if_missing(
    'petro_pd_amount_adjustment_requests',
    'ppd_adj_req_business_requested_idx',
    'ALTER TABLE `petro_pd_amount_adjustment_requests` ADD INDEX `ppd_adj_req_business_requested_idx` (`business_id`, `requested_at`)'
)$$

CALL petropd_add_report_index_if_missing(
    'petro_pd_amount_adjustment_requests',
    'ppd_adj_req_business_applied_idx',
    'ALTER TABLE `petro_pd_amount_adjustment_requests` ADD INDEX `ppd_adj_req_business_applied_idx` (`business_id`, `applied_at`)'
)$$

CALL petropd_add_report_index_if_missing(
    'petro_pd_amount_adjustment_requests',
    'ppd_adj_req_business_operator_idx',
    'ALTER TABLE `petro_pd_amount_adjustment_requests` ADD INDEX `ppd_adj_req_business_operator_idx` (`business_id`, `pump_operator_id`)'
)$$

DROP PROCEDURE IF EXISTS petropd_add_report_index_if_missing$$

DELIMITER ;
