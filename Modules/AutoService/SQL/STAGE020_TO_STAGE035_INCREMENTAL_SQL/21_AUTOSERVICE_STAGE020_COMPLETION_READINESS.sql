-- Auto Service Stage 020 - Completion & Server Readiness
-- Run this against each tenant database. Do not hard-code a database name.
-- Purpose: add safe performance indexes used by the completion/readiness page and mark the stage in settings.

DELIMITER $$
DROP PROCEDURE IF EXISTS autoservice_add_index_if_missing $$
CREATE PROCEDURE autoservice_add_index_if_missing(
    IN p_table_name VARCHAR(191),
    IN p_index_name VARCHAR(191),
    IN p_index_columns TEXT
)
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = DATABASE() AND table_name = p_table_name
    ) AND NOT EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = DATABASE() AND table_name = p_table_name AND index_name = p_index_name
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table_name, '` ADD INDEX `', p_index_name, '` (', p_index_columns, ')');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END $$
DELIMITER ;

CALL autoservice_add_index_if_missing('auto_service_jobs', 'as_jobs_business_location_status_idx', '`business_id`, `location_id`, `status`');
CALL autoservice_add_index_if_missing('auto_service_jobs', 'as_jobs_business_date_idx', '`business_id`, `job_date`');
CALL autoservice_add_index_if_missing('auto_service_vehicles', 'as_vehicles_business_location_idx', '`business_id`, `location_id`');
CALL autoservice_add_index_if_missing('auto_service_reminders', 'as_reminders_business_status_send_idx', '`business_id`, `status`, `send_on`');
CALL autoservice_add_index_if_missing('auto_service_notification_logs', 'as_notif_business_status_idx', '`business_id`, `status`');
CALL autoservice_add_index_if_missing('auto_service_communications', 'as_comms_business_status_idx', '`business_id`, `status`');
CALL autoservice_add_index_if_missing('auto_service_payments', 'as_payments_business_date_idx', '`business_id`, `payment_date`');
CALL autoservice_add_index_if_missing('auto_service_invoices', 'as_invoices_business_status_idx', '`business_id`, `status`');

DROP PROCEDURE IF EXISTS autoservice_add_index_if_missing;

DELIMITER $$
DROP PROCEDURE IF EXISTS autoservice_mark_stage020 $$
CREATE PROCEDURE autoservice_mark_stage020()
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = DATABASE() AND table_name = 'auto_service_settings'
    ) THEN
        IF NOT EXISTS (SELECT 1 FROM auto_service_settings WHERE business_id IS NULL AND `key` = 'stage_020_completion_readiness') THEN
            INSERT INTO auto_service_settings (`business_id`, `key`, `value`, `created_at`, `updated_at`)
            VALUES (NULL, 'stage_020_completion_readiness', 'installed', NOW(), NOW());
        END IF;
    END IF;
END $$
DELIMITER ;

CALL autoservice_mark_stage020();
DROP PROCEDURE IF EXISTS autoservice_mark_stage020;
