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


-- AutoService Stage 021 - Job Card / Estimate Workflow Completion
-- Run on each tenant database. No database name is specified.

ALTER TABLE `auto_service_estimates`
    ADD COLUMN IF NOT EXISTS `job_id` BIGINT UNSIGNED NULL AFTER `vehicle_id`,
    ADD COLUMN IF NOT EXISTS `approved_at` DATETIME NULL AFTER `status`,
    ADD COLUMN IF NOT EXISTS `approved_by` BIGINT UNSIGNED NULL AFTER `approved_at`;

ALTER TABLE `auto_service_jobs`
    ADD COLUMN IF NOT EXISTS `started_at` DATETIME NULL AFTER `estimated_delivery_at`,
    ADD COLUMN IF NOT EXISTS `completed_at` DATETIME NULL AFTER `started_at`,
    ADD COLUMN IF NOT EXISTS `hold_reason` TEXT NULL AFTER `advisor_notes`;

CREATE TABLE IF NOT EXISTS `auto_service_stage021_health_checks` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `business_id` BIGINT UNSIGNED NULL,
    `check_key` VARCHAR(120) NOT NULL,
    `check_status` VARCHAR(40) NOT NULL DEFAULT 'pending',
    `details` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `auto_service_stage021_health_checks_business_id_index` (`business_id`),
    KEY `auto_service_stage021_health_checks_key_index` (`check_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'autoservice.estimates.convert_to_job', 'web', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'autoservice.estimates.convert_to_job');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'autoservice.jobs.workflow', 'web', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'autoservice.jobs.workflow');
/*
 AutoService Stage 022 - Parts & Labour Control
 Tenant database SQL only. Execute against each tenant database that uses Auto Service.
 This script is safe to run more than once where MySQL supports IF NOT EXISTS.
*/

CREATE TABLE IF NOT EXISTS auto_service_part_movements (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    location_id BIGINT UNSIGNED NULL,
    job_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NULL,
    movement_type VARCHAR(30) NOT NULL DEFAULT 'reserved',
    description VARCHAR(255) NULL,
    quantity DECIMAL(20,4) NOT NULL DEFAULT 0,
    unit_cost DECIMAL(20,4) NOT NULL DEFAULT 0,
    line_total DECIMAL(20,4) NOT NULL DEFAULT 0,
    movement_date DATE NULL,
    reference_no VARCHAR(100) NULL,
    note TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_aspm_business_job (business_id, job_id),
    INDEX idx_aspm_job_type (job_id, movement_type),
    INDEX idx_aspm_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE auto_service_job_lines
    ADD COLUMN IF NOT EXISTS line_type VARCHAR(30) NULL DEFAULT 'service' AFTER job_id,
    ADD COLUMN IF NOT EXISTS product_id BIGINT UNSIGNED NULL AFTER line_type,
    ADD INDEX IF NOT EXISTS idx_asjl_job_type (job_id, line_type),
    ADD INDEX IF NOT EXISTS idx_asjl_product (product_id);

ALTER TABLE auto_service_jobs
    ADD COLUMN IF NOT EXISTS subtotal DECIMAL(20,4) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(20,4) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS tax_amount DECIMAL(20,4) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS total_amount DECIMAL(20,4) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS paid_amount DECIMAL(20,4) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS balance_amount DECIMAL(20,4) NOT NULL DEFAULT 0;

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'autoservice.parts_labour.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'autoservice.parts_labour.view');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'autoservice.parts_labour.manage', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'autoservice.parts_labour.manage');

/* Optional verification */
SELECT 'AutoService Stage 022 Parts & Labour SQL completed' AS status;
