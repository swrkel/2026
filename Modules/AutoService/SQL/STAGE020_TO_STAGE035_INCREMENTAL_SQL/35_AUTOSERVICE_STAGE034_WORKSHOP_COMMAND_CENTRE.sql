-- AutoService Stage 034 - Workshop Command Centre 2.0
-- Run this SQL on each tenant database. No database name is hardcoded.
-- Purpose: permission/menu stabilization and dashboard performance indexes.

INSERT IGNORE INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
('autoservice.command_centre.view', 'web', NOW(), NOW()),
('autoservice.command_centre.live', 'web', NOW(), NOW());

DROP PROCEDURE IF EXISTS autoservice_stage034_add_index;
DELIMITER $$
CREATE PROCEDURE autoservice_stage034_add_index(
    IN p_table_name VARCHAR(128),
    IN p_index_name VARCHAR(128),
    IN p_col1 VARCHAR(128),
    IN p_col2 VARCHAR(128),
    IN p_col3 VARCHAR(128),
    IN p_index_sql TEXT
)
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = p_table_name)
       AND NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = p_table_name AND index_name = p_index_name)
       AND (p_col1 IS NULL OR EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = p_table_name AND column_name = p_col1))
       AND (p_col2 IS NULL OR EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = p_table_name AND column_name = p_col2))
       AND (p_col3 IS NULL OR EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = p_table_name AND column_name = p_col3))
    THEN
        SET @sql_text = p_index_sql;
        PREPARE stmt FROM @sql_text;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

CALL autoservice_stage034_add_index('auto_service_jobs', 'as_jobs_business_status_updated_idx', 'business_id', 'status', 'updated_at', 'ALTER TABLE `auto_service_jobs` ADD INDEX `as_jobs_business_status_updated_idx` (`business_id`, `status`, `updated_at`)');
CALL autoservice_stage034_add_index('auto_service_jobs', 'as_jobs_business_workflow_updated_idx', 'business_id', 'workflow_stage', 'updated_at', 'ALTER TABLE `auto_service_jobs` ADD INDEX `as_jobs_business_workflow_updated_idx` (`business_id`, `workflow_stage`, `updated_at`)');
CALL autoservice_stage034_add_index('auto_service_job_mechanics', 'as_job_mechanics_business_status_idx', 'business_id', 'status', NULL, 'ALTER TABLE `auto_service_job_mechanics` ADD INDEX `as_job_mechanics_business_status_idx` (`business_id`, `status`)');
CALL autoservice_stage034_add_index('auto_service_bay_allocations', 'as_bay_allocations_business_released_idx', 'business_id', 'released_at', NULL, 'ALTER TABLE `auto_service_bay_allocations` ADD INDEX `as_bay_allocations_business_released_idx` (`business_id`, `released_at`)');
CALL autoservice_stage034_add_index('auto_service_approval_requests', 'as_approval_business_status_idx', 'business_id', 'status', NULL, 'ALTER TABLE `auto_service_approval_requests` ADD INDEX `as_approval_business_status_idx` (`business_id`, `status`)');
CALL autoservice_stage034_add_index('auto_service_quality_checks', 'as_qc_business_status_idx', 'business_id', 'status', NULL, 'ALTER TABLE `auto_service_quality_checks` ADD INDEX `as_qc_business_status_idx` (`business_id`, `status`)');
CALL autoservice_stage034_add_index('auto_service_deliveries', 'as_deliveries_business_status_idx', 'business_id', 'status', NULL, 'ALTER TABLE `auto_service_deliveries` ADD INDEX `as_deliveries_business_status_idx` (`business_id`, `status`)');

DROP PROCEDURE IF EXISTS autoservice_stage034_add_index;
