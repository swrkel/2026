-- AutoService Master SQL: Stage 020 to Stage 035
-- Run this against each tenant database after backing up.
-- Generated consolidated file includes all incremental SQL scripts in order.



-- ============================================================
-- 21: Database/SQL/21_AUTOSERVICE_STAGE020_COMPLETION_READINESS.sql
-- ============================================================

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


-- ============================================================
-- 22: Database/SQL/22_AUTOSERVICE_STAGE021_JOB_ESTIMATE_WORKFLOW_COMPLETION.sql
-- ============================================================

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


-- ============================================================
-- 23: Database/SQL/23_AUTOSERVICE_STAGE022_PARTS_LABOUR_CONTROL.sql
-- ============================================================

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


-- ============================================================
-- 24: Database/Migrations/24_AUTOSERVICE_STAGE023_SERVICE_FLOW_QC_DELIVERY_CONTROL.sql
-- ============================================================

/*
Auto Service Stage 023 - Service Flow, Technician Assignment, Inspection Checkpoint, QC/Delivery Readiness
Run this in each tenant database. It is safe to run multiple times.
*/

SET @db_name := DATABASE();

SET @sql := (SELECT IF(
    EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='auto_service_job_mechanics')
    AND NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='auto_service_job_mechanics' AND COLUMN_NAME='location_id'),
    'ALTER TABLE auto_service_job_mechanics ADD COLUMN location_id BIGINT UNSIGNED NULL AFTER business_id, ADD INDEX idx_as_job_mechanics_location_id (location_id)',
    'SELECT "auto_service_job_mechanics.location_id already exists or table missing" AS info'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(
    EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='auto_service_job_mechanics')
    AND NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='auto_service_job_mechanics' AND COLUMN_NAME='quality_note'),
    'ALTER TABLE auto_service_job_mechanics ADD COLUMN quality_note TEXT NULL AFTER note',
    'SELECT "auto_service_job_mechanics.quality_note already exists or table missing" AS info'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(
    EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='auto_service_jobs')
    AND NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='auto_service_jobs' AND COLUMN_NAME='service_flow_locked'),
    'ALTER TABLE auto_service_jobs ADD COLUMN service_flow_locked TINYINT(1) NOT NULL DEFAULT 0 AFTER job_progress, ADD INDEX idx_as_jobs_service_flow_locked (service_flow_locked)',
    'SELECT "auto_service_jobs.service_flow_locked already exists or table missing" AS info'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(
    EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='auto_service_jobs')
    AND NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='auto_service_jobs' AND COLUMN_NAME='ready_for_qc_at'),
    'ALTER TABLE auto_service_jobs ADD COLUMN ready_for_qc_at DATETIME NULL AFTER service_flow_locked',
    'SELECT "auto_service_jobs.ready_for_qc_at already exists or table missing" AS info'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'autoservice.service_flow.view', 'web', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='permissions')
AND NOT EXISTS (SELECT 1 FROM permissions WHERE name='autoservice.service_flow.view');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'autoservice.service_flow.manage', 'web', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='permissions')
AND NOT EXISTS (SELECT 1 FROM permissions WHERE name='autoservice.service_flow.manage');

SELECT 'Auto Service Stage 023 SQL completed' AS status;


-- ============================================================
-- 25: SQL/25_AUTOSERVICE_STAGE024_BILLING_PAYMENT_DELIVERY_HANDOVER.sql
-- ============================================================

-- AutoService Stage 024: Billing, Payment and Delivery Handover Control
-- Run this on every tenant database. No database name is specified intentionally.

CREATE TABLE IF NOT EXISTS auto_service_delivery_handover (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    location_id BIGINT UNSIGNED NULL,
    job_id BIGINT UNSIGNED NOT NULL,
    invoice_id BIGINT UNSIGNED NULL,
    released_by BIGINT UNSIGNED NULL,
    released_at DATETIME NULL,
    odometer_out DECIMAL(20,3) NULL,
    fuel_level_out VARCHAR(50) NULL,
    customer_signature TEXT NULL,
    warranty_note TEXT NULL,
    delivery_note TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX asdh_business_idx (business_id),
    INDEX asdh_location_idx (location_id),
    INDEX asdh_job_idx (job_id),
    INDEX asdh_invoice_idx (invoice_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE auto_service_jobs ADD COLUMN IF NOT EXISTS warranty_note TEXT NULL AFTER delivery_note;
ALTER TABLE auto_service_jobs ADD COLUMN IF NOT EXISTS customer_signature TEXT NULL AFTER warranty_note;
ALTER TABLE auto_service_payments ADD COLUMN IF NOT EXISTS location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE auto_service_payments ADD COLUMN IF NOT EXISTS payment_date DATE NULL AFTER invoice_id;
ALTER TABLE auto_service_payments ADD COLUMN IF NOT EXISTS method VARCHAR(50) NULL AFTER payment_date;
ALTER TABLE auto_service_payments ADD COLUMN IF NOT EXISTS reference_no VARCHAR(191) NULL AFTER amount;
ALTER TABLE auto_service_payments ADD COLUMN IF NOT EXISTS note TEXT NULL AFTER reference_no;

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'autoservice.billing_delivery.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'autoservice.billing_delivery.view');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'autoservice.billing_delivery.manage', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'autoservice.billing_delivery.manage');


-- ============================================================
-- 26: SQL/26_AUTOSERVICE_STAGE025_CUSTOMER_CARE_WARRANTY_FEEDBACK.sql
-- ============================================================

-- Auto Service Stage 025 - Customer Care, Warranty, Reminder and Feedback Completion
-- Safe to run on every tenant database. No database name is hard-coded.

SET @autoservice_stage025 := 'AutoService Stage 025 Customer Care Warranty Feedback Completion';

CREATE TABLE IF NOT EXISTS `auto_service_warranty_claims` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `contact_id` BIGINT UNSIGNED NULL,
  `vehicle_id` BIGINT UNSIGNED NULL,
  `job_id` BIGINT UNSIGNED NULL,
  `claim_no` VARCHAR(50) NOT NULL,
  `claim_type` VARCHAR(100) NULL DEFAULT 'service_warranty',
  `status` VARCHAR(50) NOT NULL DEFAULT 'open',
  `customer_complaint` TEXT NULL,
  `diagnosis` TEXT NULL,
  `resolution_note` TEXT NULL,
  `internal_note` TEXT NULL,
  `estimated_cost` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `approved_cost` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `created_by` BIGINT UNSIGNED NULL,
  `resolved_by` BIGINT UNSIGNED NULL,
  `resolved_at` DATETIME NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `as_warranty_claim_no_unique` (`claim_no`),
  KEY `as_warranty_business_status_idx` (`business_id`, `status`),
  KEY `as_warranty_vehicle_idx` (`vehicle_id`),
  KEY `as_warranty_job_idx` (`job_id`),
  KEY `as_warranty_location_idx` (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP PROCEDURE IF EXISTS autoservice_add_column_if_missing;
DELIMITER $$
CREATE PROCEDURE autoservice_add_column_if_missing(IN p_table VARCHAR(64), IN p_column VARCHAR(64), IN p_definition TEXT)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND COLUMN_NAME = p_column
  ) THEN
    SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_column, '` ', p_definition);
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END$$
DELIMITER ;

CALL autoservice_add_column_if_missing('auto_service_feedback', 'review_status', "VARCHAR(50) NOT NULL DEFAULT 'open' AFTER comments");
CALL autoservice_add_column_if_missing('auto_service_feedback', 'review_note', 'TEXT NULL AFTER review_status');
CALL autoservice_add_column_if_missing('auto_service_feedback', 'reviewed_by', 'BIGINT UNSIGNED NULL AFTER review_note');
CALL autoservice_add_column_if_missing('auto_service_feedback', 'reviewed_at', 'DATETIME NULL AFTER reviewed_by');
CALL autoservice_add_column_if_missing('auto_service_jobs', 'warranty_until', 'DATE NULL AFTER next_service_odometer');
CALL autoservice_add_column_if_missing('auto_service_jobs', 'delivery_signature_name', 'VARCHAR(191) NULL AFTER warranty_until');

DROP PROCEDURE IF EXISTS autoservice_add_index_if_missing;
DELIMITER $$
CREATE PROCEDURE autoservice_add_index_if_missing(IN p_table VARCHAR(64), IN p_index VARCHAR(64), IN p_columns TEXT)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND INDEX_NAME = p_index
  ) THEN
    SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD INDEX `', p_index, '` (', p_columns, ')');
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END$$
DELIMITER ;

CALL autoservice_add_index_if_missing('auto_service_feedback', 'as_feedback_business_status_idx', '`business_id`, `review_status`');
CALL autoservice_add_index_if_missing('auto_service_feedback', 'as_feedback_job_idx', '`job_id`');
CALL autoservice_add_index_if_missing('auto_service_reminders', 'as_reminders_business_status_due_idx', '`business_id`, `status`, `due_date`');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'autoservice.customer_care.view', 'web', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'permissions')
AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'autoservice.customer_care.view');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'autoservice.customer_care.manage', 'web', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'permissions')
AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'autoservice.customer_care.manage');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'autoservice.warranty.view', 'web', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'permissions')
AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'autoservice.warranty.view');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'autoservice.warranty.manage', 'web', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'permissions')
AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'autoservice.warranty.manage');

DROP PROCEDURE IF EXISTS autoservice_add_column_if_missing;
DROP PROCEDURE IF EXISTS autoservice_add_index_if_missing;


-- ============================================================
-- 27: Database/SQL/27_AUTOSERVICE_STAGE026_CUSTOMER_SERVICE_PORTAL_HISTORY.sql
-- ============================================================

/*
 AutoService Stage 026 - Customer Service Portal, Current Bill and Parts/Accessories History
 Execute against each tenant database that uses the Auto Service module.
 Safe to run repeatedly. No database name is hard-coded.
*/

SET @db_name := DATABASE();

DROP PROCEDURE IF EXISTS autoservice_stage026_add_column;
DELIMITER $$
CREATE PROCEDURE autoservice_stage026_add_column(IN p_table VARCHAR(100), IN p_column VARCHAR(100), IN p_definition TEXT)
BEGIN
    IF EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = p_table)
       AND NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = p_table AND COLUMN_NAME = p_column) THEN
        SET @sql := CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN ', p_definition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS autoservice_stage026_add_index;
DELIMITER $$
CREATE PROCEDURE autoservice_stage026_add_index(IN p_table VARCHAR(100), IN p_index VARCHAR(100), IN p_columns TEXT)
BEGIN
    IF EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = p_table)
       AND NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = p_table AND INDEX_NAME = p_index) THEN
        SET @sql := CONCAT('ALTER TABLE `', p_table, '` ADD INDEX `', p_index, '` (', p_columns, ')');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

CALL autoservice_stage026_add_column('auto_service_job_lines', 'discount_amount', '`discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `unit_price`');
CALL autoservice_stage026_add_column('auto_service_job_lines', 'tax_amount', '`tax_amount` DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `discount_amount`');
CALL autoservice_stage026_add_index('auto_service_job_lines', 'idx_asjl_customer_parts_filter', '`job_id`, `line_type`, `product_id`');

CALL autoservice_stage026_add_column('auto_service_invoice_lines', 'discount_amount', '`discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `unit_price`');
CALL autoservice_stage026_add_column('auto_service_invoice_lines', 'tax_amount', '`tax_amount` DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `discount_amount`');
CALL autoservice_stage026_add_index('auto_service_invoice_lines', 'idx_asil_customer_parts_filter', '`invoice_id`, `line_type`, `product_id`');

CALL autoservice_stage026_add_column('auto_service_part_movements', 'unit_price', '`unit_price` DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `quantity`');
CALL autoservice_stage026_add_column('auto_service_part_movements', 'discount_amount', '`discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `unit_price`');
CALL autoservice_stage026_add_index('auto_service_part_movements', 'idx_aspm_customer_parts_filter', '`job_id`, `movement_date`, `product_id`');

CALL autoservice_stage026_add_column('auto_service_jobs', 'customer_visible_note', '`customer_visible_note` TEXT NULL');
CALL autoservice_stage026_add_index('auto_service_jobs', 'idx_as_jobs_customer_portal', '`business_id`, `contact_id`, `vehicle_id`, `job_date`');
CALL autoservice_stage026_add_index('auto_service_invoices', 'idx_as_inv_customer_portal', '`business_id`, `contact_id`, `vehicle_id`, `job_id`, `invoice_date`');

INSERT INTO auto_service_settings (`business_id`, `key`, `value`, `created_at`, `updated_at`)
SELECT NULL, 'enable_customer_current_invoice_view', '1', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'auto_service_settings')
  AND NOT EXISTS (SELECT 1 FROM auto_service_settings WHERE `key` = 'enable_customer_current_invoice_view' AND business_id IS NULL);

INSERT INTO auto_service_settings (`business_id`, `key`, `value`, `created_at`, `updated_at`)
SELECT NULL, 'allow_customer_feedback', '1', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'auto_service_settings')
  AND NOT EXISTS (SELECT 1 FROM auto_service_settings WHERE `key` = 'allow_customer_feedback' AND business_id IS NULL);

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'autoservice.customer_portal.view', 'web', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'autoservice.customer_portal.view');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'autoservice.customer_portal.parts_history.view', 'web', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'autoservice.customer_portal.parts_history.view');

DROP PROCEDURE IF EXISTS autoservice_stage026_add_column;
DROP PROCEDURE IF EXISTS autoservice_stage026_add_index;

SELECT 'AutoService Stage 026 Customer Service Portal SQL completed' AS status;


-- ============================================================
-- 28: SQL/28_AUTOSERVICE_STAGE027_CUSTOMER_SELF_SERVICE_ACTIONS.sql
-- ============================================================

-- AutoService Stage 027 - Customer Self Service Actions
-- Apply this SQL to every tenant database that uses the Auto Service module.
-- The script is safe for multi-tenant single-code / multiple-database deployments.

SET @database_name = DATABASE();

-- Appointment portal request support
SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@database_name AND TABLE_NAME='auto_service_appointments' AND COLUMN_NAME='request_source') = 0,
    'ALTER TABLE auto_service_appointments ADD COLUMN request_source VARCHAR(50) NULL AFTER status',
    'SELECT "auto_service_appointments.request_source already exists" AS message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@database_name AND TABLE_NAME='auto_service_appointments' AND COLUMN_NAME='customer_name') = 0,
    'ALTER TABLE auto_service_appointments ADD COLUMN customer_name VARCHAR(191) NULL AFTER request_source',
    'SELECT "auto_service_appointments.customer_name already exists" AS message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@database_name AND TABLE_NAME='auto_service_appointments' AND COLUMN_NAME='customer_mobile') = 0,
    'ALTER TABLE auto_service_appointments ADD COLUMN customer_mobile VARCHAR(50) NULL AFTER customer_name',
    'SELECT "auto_service_appointments.customer_mobile already exists" AS message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@database_name AND TABLE_NAME='auto_service_appointments' AND COLUMN_NAME='customer_email') = 0,
    'ALTER TABLE auto_service_appointments ADD COLUMN customer_email VARCHAR(191) NULL AFTER customer_mobile',
    'SELECT "auto_service_appointments.customer_email already exists" AS message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Approval response traceability
SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@database_name AND TABLE_NAME='auto_service_approval_requests' AND COLUMN_NAME='customer_response_ip') = 0,
    'ALTER TABLE auto_service_approval_requests ADD COLUMN customer_response_ip VARCHAR(64) NULL AFTER customer_note',
    'SELECT "auto_service_approval_requests.customer_response_ip already exists" AS message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Portal feature flags
SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@database_name AND TABLE_NAME='auto_service_settings') > 0,
    'INSERT INTO auto_service_settings (business_id, `key`, `value`, created_at, updated_at) SELECT NULL, ''allow_customer_appointment_request'', ''1'', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM auto_service_settings WHERE `key`=''allow_customer_appointment_request'' AND business_id IS NULL)',
    'SELECT "auto_service_settings table missing - skipped allow_customer_appointment_request" AS message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@database_name AND TABLE_NAME='auto_service_settings') > 0,
    'INSERT INTO auto_service_settings (business_id, `key`, `value`, created_at, updated_at) SELECT NULL, ''allow_customer_portal_approval_response'', ''1'', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM auto_service_settings WHERE `key`=''allow_customer_portal_approval_response'' AND business_id IS NULL)',
    'SELECT "auto_service_settings table missing - skipped allow_customer_portal_approval_response" AS message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Optional permission keys for admin visibility/configuration.
SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@database_name AND TABLE_NAME='permissions') > 0,
    'INSERT INTO permissions (`name`, guard_name, created_at, updated_at) SELECT ''autoservice.customer_portal.self_service'', ''web'', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE `name`=''autoservice.customer_portal.self_service'')',
    'SELECT "permissions table missing - skipped autoservice.customer_portal.self_service" AS message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- ============================================================
-- 29: SQL/29_AUTOSERVICE_STAGE028_CUSTOMER_DOCUMENTS_APPROVALS_ALERTS.sql
-- ============================================================

/*
 AutoService Stage 028 - Customer Documents, Approval Evidence and Portal Alerts
 Run this on each tenant database. No database name is hard-coded.
*/

SET @database_name = DATABASE();

SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@database_name AND TABLE_NAME='auto_service_documents' AND COLUMN_NAME='uploaded_by_customer') = 0,
'ALTER TABLE auto_service_documents ADD COLUMN uploaded_by_customer TINYINT(1) NOT NULL DEFAULT 0 AFTER customer_download_allowed',
'SELECT "auto_service_documents.uploaded_by_customer already exists" AS message');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@database_name AND TABLE_NAME='auto_service_documents' AND COLUMN_NAME='customer_name') = 0,
'ALTER TABLE auto_service_documents ADD COLUMN customer_name VARCHAR(191) NULL AFTER uploaded_by_customer',
'SELECT "auto_service_documents.customer_name already exists" AS message');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@database_name AND TABLE_NAME='auto_service_documents' AND COLUMN_NAME='customer_mobile') = 0,
'ALTER TABLE auto_service_documents ADD COLUMN customer_mobile VARCHAR(50) NULL AFTER customer_name',
'SELECT "auto_service_documents.customer_mobile already exists" AS message');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@database_name AND TABLE_NAME='auto_service_documents' AND COLUMN_NAME='customer_upload_ip') = 0,
'ALTER TABLE auto_service_documents ADD COLUMN customer_upload_ip VARCHAR(64) NULL AFTER customer_mobile',
'SELECT "auto_service_documents.customer_upload_ip already exists" AS message');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@database_name AND TABLE_NAME='auto_service_documents' AND COLUMN_NAME='document_status') = 0,
'ALTER TABLE auto_service_documents ADD COLUMN document_status VARCHAR(50) NOT NULL DEFAULT "active" AFTER customer_upload_ip, ADD INDEX idx_as_doc_status (document_status)',
'SELECT "auto_service_documents.document_status already exists" AS message');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO auto_service_settings (business_id, `key`, `value`, created_at, updated_at)
SELECT NULL, 'allow_customer_document_upload', '1', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@database_name AND TABLE_NAME='auto_service_settings')
AND NOT EXISTS (SELECT 1 FROM auto_service_settings WHERE `key`='allow_customer_document_upload');

INSERT INTO auto_service_settings (business_id, `key`, `value`, created_at, updated_at)
SELECT NULL, 'notify_service_advisor_on_customer_upload', '1', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@database_name AND TABLE_NAME='auto_service_settings')
AND NOT EXISTS (SELECT 1 FROM auto_service_settings WHERE `key`='notify_service_advisor_on_customer_upload');

INSERT INTO auto_service_settings (business_id, `key`, `value`, created_at, updated_at)
SELECT NULL, 'allow_customer_photo_upload', '1', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@database_name AND TABLE_NAME='auto_service_settings')
AND NOT EXISTS (SELECT 1 FROM auto_service_settings WHERE `key`='allow_customer_photo_upload');

/* Verification */
SELECT 'Stage 028 customer portal documents upgrade completed' AS status;


-- ============================================================
-- 30: SQL/30_AUTOSERVICE_STAGE029_CUSTOMER_PORTAL_ALERTS_COMMUNICATION_LOG.sql
-- ============================================================

-- Auto Service Stage 029 - Customer Portal Alerts & Communication Log
-- Raw SQL for tenant databases only. Run per tenant DB after Stage 028.

CREATE TABLE IF NOT EXISTS `auto_service_customer_portal_alerts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `location_id` INT UNSIGNED NULL,
  `job_id` BIGINT UNSIGNED NULL,
  `vehicle_id` BIGINT UNSIGNED NULL,
  `contact_id` BIGINT UNSIGNED NULL,
  `event_type` VARCHAR(100) NULL,
  `title` VARCHAR(191) NOT NULL,
  `message` TEXT NULL,
  `priority` VARCHAR(30) NOT NULL DEFAULT 'normal',
  `status` VARCHAR(30) NOT NULL DEFAULT 'new',
  `visible_to_customer` TINYINT(1) NOT NULL DEFAULT 1,
  `acknowledged_at` DATETIME NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `as_customer_portal_alerts_business_idx` (`business_id`),
  KEY `as_customer_portal_alerts_location_idx` (`location_id`),
  KEY `as_customer_portal_alerts_job_idx` (`job_id`),
  KEY `as_customer_portal_alerts_vehicle_idx` (`vehicle_id`),
  KEY `as_customer_portal_alerts_contact_idx` (`contact_id`),
  KEY `as_customer_portal_alerts_status_idx` (`status`),
  KEY `as_customer_portal_alerts_event_idx` (`event_type`),
  KEY `as_customer_portal_alerts_created_idx` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `auto_service_notification_logs`
  ADD COLUMN IF NOT EXISTS `customer_visible` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`,
  ADD COLUMN IF NOT EXISTS `portal_reference` VARCHAR(191) NULL AFTER `customer_visible`;

ALTER TABLE `auto_service_jobs`
  ADD COLUMN IF NOT EXISTS `customer_status_message` TEXT NULL AFTER `customer_visible_note`,
  ADD COLUMN IF NOT EXISTS `customer_last_notified_at` DATETIME NULL AFTER `customer_status_message`;

INSERT INTO `auto_service_settings` (`business_id`, `key`, `value`, `created_at`, `updated_at`)
SELECT NULL, 'enable_customer_portal_alerts', '1', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `auto_service_settings` WHERE `key` = 'enable_customer_portal_alerts' AND `business_id` IS NULL);

INSERT INTO `auto_service_settings` (`business_id`, `key`, `value`, `created_at`, `updated_at`)
SELECT NULL, 'enable_customer_communication_log', '1', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `auto_service_settings` WHERE `key` = 'enable_customer_communication_log' AND `business_id` IS NULL);


-- ============================================================
-- 31: SQL/31_AUTOSERVICE_STAGE030_CUSTOMER_BILL_PAYMENT_EXPORTS.sql
-- ============================================================

/*
 AutoService Stage 030 - Customer Bill, Payment History and Export Tools
 Tenant database SQL only. Safe to run on each tenant database. No database name is hardcoded.
*/

-- Customer portal settings for bill/payment/export visibility
INSERT INTO auto_service_settings (`key`, `value`, `created_at`, `updated_at`)
SELECT 'allow_customer_service_summary_print', '1', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM auto_service_settings WHERE `key` = 'allow_customer_service_summary_print');

INSERT INTO auto_service_settings (`key`, `value`, `created_at`, `updated_at`)
SELECT 'allow_customer_parts_history_export', '1', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM auto_service_settings WHERE `key` = 'allow_customer_parts_history_export');

INSERT INTO auto_service_settings (`key`, `value`, `created_at`, `updated_at`)
SELECT 'allow_customer_payment_history_view', '1', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM auto_service_settings WHERE `key` = 'allow_customer_payment_history_view');

-- Optional permission keys for staff-side visibility and support. Insert only if permissions table exists in tenant DB.
INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'autoservice.customer_portal.exports', 'web', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
AND NOT EXISTS (SELECT 1 FROM permissions WHERE `name` = 'autoservice.customer_portal.exports');

INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'autoservice.customer_portal.payment_history', 'web', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
AND NOT EXISTS (SELECT 1 FROM permissions WHERE `name` = 'autoservice.customer_portal.payment_history');

-- Performance indexes for customer portal bill/history lookups. Add manually if your MySQL version does not support IF NOT EXISTS for indexes.
ALTER TABLE auto_service_payments ADD INDEX IF NOT EXISTS idx_as_payments_invoice_date (invoice_id, payment_date);
ALTER TABLE auto_service_invoice_lines ADD INDEX IF NOT EXISTS idx_as_invoice_lines_type_product (line_type, product_id);
ALTER TABLE auto_service_job_lines ADD INDEX IF NOT EXISTS idx_as_job_lines_type_product (line_type, product_id);
ALTER TABLE auto_service_part_movements ADD INDEX IF NOT EXISTS idx_as_part_movements_job_date (job_id, movement_date);


-- ============================================================
-- 32: SQL/32_AUTOSERVICE_STAGE031_MAINTENANCE_PLANNER_VEHICLE_HEALTH.sql
-- ============================================================

-- AutoService Stage 031 - Maintenance Planner and Vehicle Health
-- Apply to each tenant database. No database name is hard-coded.

CREATE TABLE IF NOT EXISTS `auto_service_maintenance_plans` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `contact_id` BIGINT UNSIGNED NULL,
  `vehicle_id` BIGINT UNSIGNED NOT NULL,
  `job_id` BIGINT UNSIGNED NULL,
  `plan_no` VARCHAR(60) NOT NULL,
  `plan_type` VARCHAR(60) NOT NULL DEFAULT 'periodic_service',
  `current_meter` DECIMAL(15,3) NULL,
  `next_service_date` DATE NOT NULL,
  `next_service_meter` DECIMAL(15,3) NULL,
  `interval_days` INT NULL,
  `interval_meter` DECIMAL(15,3) NULL,
  `service_note` TEXT NULL,
  `recommended_parts` TEXT NULL,
  `customer_visible` TINYINT(1) NOT NULL DEFAULT 1,
  `status` VARCHAR(30) NOT NULL DEFAULT 'active',
  `completion_note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `completed_by` BIGINT UNSIGNED NULL,
  `completed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `as_mp_plan_no_unique` (`plan_no`),
  KEY `as_mp_business_status_date_idx` (`business_id`, `status`, `next_service_date`),
  KEY `as_mp_vehicle_idx` (`vehicle_id`),
  KEY `as_mp_contact_idx` (`contact_id`),
  KEY `as_mp_job_idx` (`job_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
('autoservice.maintenance_planner.view', 'web', NOW(), NOW()),
('autoservice.maintenance_planner.manage', 'web', NOW(), NOW()),
('autoservice.vehicle_health.view', 'web', NOW(), NOW());


-- ============================================================
-- 33: SQL/33_AUTOSERVICE_STAGE032_WORKSHOP_MANAGEMENT_KPI.sql
-- ============================================================

-- AutoService Stage 032 - Workshop Management KPI / Technician Performance / Bay Utilisation / Repeat Repair Tracking
-- Run this on every tenant database. Do not hard-code tenant database names.

CREATE TABLE IF NOT EXISTS `auto_service_repeat_repairs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned NULL,
  `location_id` int unsigned NULL,
  `job_id` bigint unsigned NOT NULL,
  `vehicle_id` bigint unsigned NULL,
  `contact_id` bigint unsigned NULL,
  `reason` varchar(255) NOT NULL,
  `corrective_action` text NULL,
  `severity` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `status` enum('open','reviewed','resolved','closed') NOT NULL DEFAULT 'open',
  `created_by` bigint unsigned NULL,
  `reviewed_by` bigint unsigned NULL,
  `reviewed_at` timestamp NULL,
  `closed_by` bigint unsigned NULL,
  `closed_at` timestamp NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  KEY `as_repeat_business_idx` (`business_id`),
  KEY `as_repeat_location_idx` (`location_id`),
  KEY `as_repeat_job_idx` (`job_id`),
  KEY `as_repeat_vehicle_idx` (`vehicle_id`),
  KEY `as_repeat_contact_idx` (`contact_id`),
  KEY `as_repeat_status_idx` (`status`),
  KEY `as_repeat_created_idx` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'autoservice.management_kpi.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'autoservice.management_kpi.view');

INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'autoservice.management_kpi.manage', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'autoservice.management_kpi.manage');

INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'autoservice.repeat_repairs.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'autoservice.repeat_repairs.view');

INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'autoservice.repeat_repairs.manage', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'autoservice.repeat_repairs.manage');


-- ============================================================
-- 34: SQL/34_AUTOSERVICE_STAGE033_INVENTORY_CONTROL_REORDER_PROFITABILITY.sql
-- ============================================================

-- AutoService Stage 033 - Inventory Control, Reorder Alerts and Parts Profitability
-- Run this SQL on each tenant database. It is idempotent as far as MySQL allows.

CREATE TABLE IF NOT EXISTS `auto_service_parts_stock` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `product_id` BIGINT UNSIGNED NULL,
  `part_name` VARCHAR(255) NOT NULL,
  `sku` VARCHAR(100) NULL,
  `qty_available` DECIMAL(22,3) NOT NULL DEFAULT 0.000,
  `qty_reserved` DECIMAL(22,3) NOT NULL DEFAULT 0.000,
  `reorder_level` DECIMAL(22,3) NOT NULL DEFAULT 0.000,
  `last_purchase_price` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `selling_price` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `preferred_supplier_id` BIGINT UNSIGNED NULL,
  `preferred_supplier_name` VARCHAR(255) NULL,
  `notes` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `as_parts_stock_business_location_idx` (`business_id`,`location_id`),
  KEY `as_parts_stock_product_idx` (`product_id`),
  KEY `as_parts_stock_sku_idx` (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `auto_service_reorder_requests` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `part_stock_id` BIGINT UNSIGNED NULL,
  `part_name` VARCHAR(255) NOT NULL,
  `sku` VARCHAR(100) NULL,
  `current_qty` DECIMAL(22,3) NOT NULL DEFAULT 0.000,
  `reorder_level` DECIMAL(22,3) NOT NULL DEFAULT 0.000,
  `required_qty` DECIMAL(22,3) NOT NULL DEFAULT 0.000,
  `preferred_supplier_id` BIGINT UNSIGNED NULL,
  `preferred_supplier_name` VARCHAR(255) NULL,
  `priority` VARCHAR(30) NOT NULL DEFAULT 'normal',
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `approved_by` BIGINT UNSIGNED NULL,
  `approved_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `as_reorder_business_location_idx` (`business_id`,`location_id`),
  KEY `as_reorder_status_idx` (`status`),
  KEY `as_reorder_part_stock_idx` (`part_stock_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add costing columns used by the profitability report. Ignore duplicate-column errors if already added.
ALTER TABLE `auto_service_job_lines` ADD COLUMN `cost_amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000 AFTER `unit_price`;
ALTER TABLE `auto_service_job_lines` ADD COLUMN `supplier_id` BIGINT UNSIGNED NULL AFTER `product_id`;
ALTER TABLE `auto_service_job_lines` ADD COLUMN `supplier_name` VARCHAR(255) NULL AFTER `supplier_id`;

-- Optional permission seed. Adjust table name if your system stores permissions differently.
INSERT IGNORE INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
('autoservice.inventory_control.view', 'web', NOW(), NOW()),
('autoservice.inventory_control.manage', 'web', NOW(), NOW()),
('autoservice.parts_profitability.view', 'web', NOW(), NOW()),
('autoservice.reorder_requests.manage', 'web', NOW(), NOW());


-- ============================================================
-- 35: SQL/35_AUTOSERVICE_STAGE034_WORKSHOP_COMMAND_CENTRE.sql
-- ============================================================

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


-- ============================================================
-- 36: Database/Sql/36_AUTOSERVICE_STAGE035_ADVANCED_VEHICLE_HISTORY.sql
-- ============================================================

-- AutoService Stage 035: Advanced Vehicle History
-- Run on each tenant database. No database name is specified intentionally.

ALTER TABLE auto_service_vehicles
    ADD COLUMN IF NOT EXISTS fuel_type VARCHAR(100) NULL AFTER year,
    ADD COLUMN IF NOT EXISTS transmission VARCHAR(100) NULL AFTER fuel_type,
    ADD COLUMN IF NOT EXISTS vehicle_colour VARCHAR(100) NULL AFTER transmission,
    ADD COLUMN IF NOT EXISTS ownership_status VARCHAR(100) NULL AFTER contact_id,
    ADD COLUMN IF NOT EXISTS purchase_date DATE NULL AFTER ownership_status,
    ADD COLUMN IF NOT EXISTS lifetime_service_cost DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER next_service_odometer;

ALTER TABLE auto_service_part_movements
    ADD COLUMN IF NOT EXISTS tax_amount DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER discount_amount,
    ADD COLUMN IF NOT EXISTS warranty_days INT NULL AFTER tax_amount,
    ADD COLUMN IF NOT EXISTS supplier_id BIGINT UNSIGNED NULL AFTER product_id;

CREATE INDEX IF NOT EXISTS auto_service_part_movements_supplier_id_index ON auto_service_part_movements (supplier_id);

CREATE TABLE IF NOT EXISTS auto_service_vehicle_history_snapshots (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    location_id BIGINT UNSIGNED NULL,
    vehicle_id BIGINT UNSIGNED NOT NULL,
    job_id BIGINT UNSIGNED NULL,
    snapshot_date DATE NULL,
    odometer INT UNSIGNED NULL,
    job_total DECIMAL(22,4) NOT NULL DEFAULT 0,
    parts_total DECIMAL(22,4) NOT NULL DEFAULT 0,
    labour_total DECIMAL(22,4) NOT NULL DEFAULT 0,
    health_status VARCHAR(100) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY as_vehicle_history_vehicle_job_unique (vehicle_id, job_id),
    KEY as_vehicle_history_business_idx (business_id),
    KEY as_vehicle_history_location_idx (location_id),
    KEY as_vehicle_history_vehicle_idx (vehicle_id),
    KEY as_vehicle_history_job_idx (job_id),
    KEY as_vehicle_history_date_idx (snapshot_date),
    KEY as_vehicle_history_status_idx (health_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO auto_service_vehicle_history_snapshots
    (business_id, location_id, vehicle_id, job_id, snapshot_date, odometer, job_total, health_status, created_at, updated_at)
SELECT business_id, location_id, vehicle_id, id, job_date, odometer, total_amount, status, NOW(), NOW()
FROM auto_service_jobs
WHERE vehicle_id IS NOT NULL;

INSERT IGNORE INTO permissions (name, guard_name, created_at, updated_at) VALUES
('autoservice.advanced_vehicle_history.view', 'web', NOW(), NOW()),
('autoservice.advanced_vehicle_history.export', 'web', NOW(), NOW());


-- AutoService Stage 036 - Customer Experience Portal Enhancements
-- Tenant-safe raw SQL. Execute against each tenant database.

CREATE TABLE IF NOT EXISTS `auto_service_customer_callback_requests` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `location_id` INT UNSIGNED NULL,
  `job_id` BIGINT UNSIGNED NULL,
  `vehicle_id` BIGINT UNSIGNED NULL,
  `contact_id` BIGINT UNSIGNED NULL,
  `customer_name` VARCHAR(191) NULL,
  `customer_mobile` VARCHAR(50) NULL,
  `preferred_time` VARCHAR(100) NULL,
  `reason` VARCHAR(100) NULL DEFAULT 'service_update',
  `message` TEXT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'requested',
  `assigned_to` BIGINT UNSIGNED NULL,
  `requested_at` DATETIME NULL,
  `responded_at` DATETIME NULL,
  `response_note` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `as_callback_business_status_idx` (`business_id`, `status`),
  KEY `as_callback_vehicle_idx` (`vehicle_id`),
  KEY `as_callback_job_idx` (`job_id`),
  KEY `as_callback_requested_idx` (`requested_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `auto_service_jobs`
  ADD COLUMN IF NOT EXISTS `customer_live_progress_enabled` TINYINT(1) NOT NULL DEFAULT 1 AFTER `customer_visible_note`,
  ADD COLUMN IF NOT EXISTS `customer_progress_percent` DECIMAL(5,2) NULL AFTER `customer_live_progress_enabled`,
  ADD COLUMN IF NOT EXISTS `customer_estimated_ready_at` DATETIME NULL AFTER `customer_progress_percent`,
  ADD COLUMN IF NOT EXISTS `customer_download_job_card_enabled` TINYINT(1) NOT NULL DEFAULT 1 AFTER `customer_estimated_ready_at`,
  ADD COLUMN IF NOT EXISTS `customer_download_inspection_enabled` TINYINT(1) NOT NULL DEFAULT 1 AFTER `customer_download_job_card_enabled`,
  ADD COLUMN IF NOT EXISTS `customer_download_warranty_enabled` TINYINT(1) NOT NULL DEFAULT 1 AFTER `customer_download_inspection_enabled`;

ALTER TABLE `auto_service_settings`
  ADD COLUMN IF NOT EXISTS `allow_customer_callback_request` TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `allow_customer_job_card_download` TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `allow_customer_inspection_download` TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `allow_customer_warranty_download` TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `allow_customer_live_progress_refresh` TINYINT(1) NOT NULL DEFAULT 1;

INSERT IGNORE INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
('autoservice.customer_experience.view', 'web', NOW(), NOW()),
('autoservice.customer_experience.manage', 'web', NOW(), NOW());


-- AutoService Stage 037 - Workshop Planning
-- Tenant database SQL. Safe to run per tenant database.

CREATE TABLE IF NOT EXISTS autoservice_workshop_plans (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    business_location_id BIGINT UNSIGNED NULL,
    job_id BIGINT UNSIGNED NOT NULL,
    planned_start_date DATE NOT NULL,
    planned_completion_date DATE NULL,
    priority VARCHAR(50) NULL,
    planning_notes TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY autoservice_workshop_plans_business_job_unique (business_id, job_id),
    KEY autoservice_workshop_plans_business_location_idx (business_id, business_location_id),
    KEY autoservice_workshop_plans_dates_idx (planned_start_date, planned_completion_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS autoservice_technician_schedules (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    business_location_id BIGINT UNSIGNED NULL,
    job_id BIGINT UNSIGNED NOT NULL,
    technician_id BIGINT UNSIGNED NOT NULL,
    planned_date DATE NOT NULL,
    start_time VARCHAR(20) NULL,
    end_time VARCHAR(20) NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'planned',
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    KEY autoservice_tech_schedules_business_date_idx (business_id, business_location_id, planned_date),
    KEY autoservice_tech_schedules_technician_idx (technician_id, planned_date),
    KEY autoservice_tech_schedules_job_idx (job_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS autoservice_bay_schedules (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    business_location_id BIGINT UNSIGNED NULL,
    job_id BIGINT UNSIGNED NOT NULL,
    bay_id BIGINT UNSIGNED NOT NULL,
    planned_date DATE NOT NULL,
    start_time VARCHAR(20) NULL,
    end_time VARCHAR(20) NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'reserved',
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    KEY autoservice_bay_schedules_business_date_idx (business_id, business_location_id, planned_date),
    KEY autoservice_bay_schedules_bay_idx (bay_id, planned_date),
    KEY autoservice_bay_schedules_job_idx (job_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS autoservice_parts_reservations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    business_location_id BIGINT UNSIGNED NULL,
    job_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NULL,
    part_name VARCHAR(191) NOT NULL,
    quantity DECIMAL(22,6) NOT NULL DEFAULT 0,
    required_date DATE NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'reserved',
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    KEY autoservice_parts_reservations_business_date_idx (business_id, business_location_id, required_date),
    KEY autoservice_parts_reservations_job_idx (job_id),
    KEY autoservice_parts_reservations_product_idx (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO permissions (name, guard_name, created_at, updated_at) VALUES
('autoservice.workshop_planning.view', 'web', NOW(), NOW()),
('autoservice.workshop_planning.manage', 'web', NOW(), NOW());


-- AutoService Stage 038 - Business Intelligence & Profitability
-- Run on each tenant database. Global/tenant-safe SQL; no database name is hardcoded.

CREATE TABLE IF NOT EXISTS auto_service_business_intelligence_snapshots (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id INT UNSIGNED NULL,
    location_id INT UNSIGNED NULL,
    snapshot_date DATE NOT NULL,
    total_jobs INT NOT NULL DEFAULT 0,
    total_invoices INT NOT NULL DEFAULT 0,
    gross_revenue DECIMAL(22,4) NOT NULL DEFAULT 0,
    parts_revenue DECIMAL(22,4) NOT NULL DEFAULT 0,
    labour_revenue DECIMAL(22,4) NOT NULL DEFAULT 0,
    discount_amount DECIMAL(22,4) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(22,4) NOT NULL DEFAULT 0,
    estimated_cost DECIMAL(22,4) NOT NULL DEFAULT 0,
    estimated_profit DECIMAL(22,4) NOT NULL DEFAULT 0,
    warranty_cost DECIMAL(22,4) NOT NULL DEFAULT 0,
    repeat_customers INT NOT NULL DEFAULT 0,
    retention_percent DECIMAL(8,2) NOT NULL DEFAULT 0,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_as_bi_snapshot_business_date (business_id, snapshot_date),
    INDEX idx_as_bi_snapshot_location_date (location_id, snapshot_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS auto_service_business_intelligence_exports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id INT UNSIGNED NULL,
    location_id INT UNSIGNED NULL,
    export_type VARCHAR(80) NOT NULL DEFAULT 'job_profitability',
    date_from DATE NULL,
    date_to DATE NULL,
    filters_json JSON NULL,
    exported_by BIGINT UNSIGNED NULL,
    exported_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_as_bi_export_business_date (business_id, exported_at),
    INDEX idx_as_bi_export_type (export_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'autoservice.business_intelligence.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'autoservice.business_intelligence.view');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'autoservice.business_intelligence.export', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'autoservice.business_intelligence.export');


-- AutoService Stage 039 - Dealer Enterprise, Fleet, AMC, Corporate Pricing, Driver Management
-- Run this SQL on each tenant database that uses the Auto Service module.
-- All changes are additive and business/location-safe.

CREATE TABLE IF NOT EXISTS auto_service_fleet_customers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    location_id BIGINT UNSIGNED NULL,
    contact_id BIGINT UNSIGNED NULL,
    fleet_name VARCHAR(191) NOT NULL,
    contract_no VARCHAR(100) NULL,
    credit_limit DECIMAL(22,4) NOT NULL DEFAULT 0,
    billing_cycle VARCHAR(50) NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'active',
    note TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_as_fleet_business (business_id),
    INDEX idx_as_fleet_location (location_id),
    INDEX idx_as_fleet_contact (contact_id),
    INDEX idx_as_fleet_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS auto_service_fleet_contracts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    location_id BIGINT UNSIGNED NULL,
    fleet_customer_id BIGINT UNSIGNED NOT NULL,
    contract_no VARCHAR(100) NOT NULL,
    contract_type VARCHAR(100) NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    vehicle_limit INT NULL,
    monthly_value DECIMAL(22,4) NOT NULL DEFAULT 0,
    status VARCHAR(50) NOT NULL DEFAULT 'draft',
    note TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_as_fleet_contract_business (business_id),
    INDEX idx_as_fleet_contract_location (location_id),
    INDEX idx_as_fleet_contract_customer (fleet_customer_id),
    INDEX idx_as_fleet_contract_status (status),
    INDEX idx_as_fleet_contract_dates (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS auto_service_fleet_drivers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    location_id BIGINT UNSIGNED NULL,
    fleet_customer_id BIGINT UNSIGNED NOT NULL,
    assigned_vehicle_id BIGINT UNSIGNED NULL,
    driver_name VARCHAR(191) NOT NULL,
    mobile VARCHAR(50) NULL,
    nic_no VARCHAR(100) NULL,
    license_no VARCHAR(100) NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'active',
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_as_fleet_driver_business (business_id),
    INDEX idx_as_fleet_driver_location (location_id),
    INDEX idx_as_fleet_driver_customer (fleet_customer_id),
    INDEX idx_as_fleet_driver_vehicle (assigned_vehicle_id),
    INDEX idx_as_fleet_driver_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS auto_service_corporate_pricing (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    location_id BIGINT UNSIGNED NULL,
    fleet_customer_id BIGINT UNSIGNED NULL,
    contact_id BIGINT UNSIGNED NULL,
    item_type VARCHAR(50) NOT NULL DEFAULT 'part',
    item_id BIGINT UNSIGNED NULL,
    item_code VARCHAR(100) NULL,
    item_name VARCHAR(191) NOT NULL,
    normal_price DECIMAL(22,4) NOT NULL DEFAULT 0,
    special_price DECIMAL(22,4) NOT NULL DEFAULT 0,
    discount_type VARCHAR(20) NULL,
    discount_value DECIMAL(22,4) NOT NULL DEFAULT 0,
    effective_from DATE NULL,
    effective_to DATE NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'active',
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_as_corp_price_business (business_id),
    INDEX idx_as_corp_price_location (location_id),
    INDEX idx_as_corp_price_fleet (fleet_customer_id),
    INDEX idx_as_corp_price_contact (contact_id),
    INDEX idx_as_corp_price_item (item_type, item_id),
    INDEX idx_as_corp_price_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS auto_service_dealer_trade_ins (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    location_id BIGINT UNSIGNED NULL,
    contact_id BIGINT UNSIGNED NULL,
    vehicle_id BIGINT UNSIGNED NULL,
    registration_no VARCHAR(100) NULL,
    make VARCHAR(100) NULL,
    model VARCHAR(100) NULL,
    year VARCHAR(20) NULL,
    odometer DECIMAL(22,4) NOT NULL DEFAULT 0,
    appraisal_value DECIMAL(22,4) NOT NULL DEFAULT 0,
    agreed_value DECIMAL(22,4) NOT NULL DEFAULT 0,
    status VARCHAR(50) NOT NULL DEFAULT 'draft',
    note TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_as_tradein_business (business_id),
    INDEX idx_as_tradein_location (location_id),
    INDEX idx_as_tradein_contact (contact_id),
    INDEX idx_as_tradein_vehicle (vehicle_id),
    INDEX idx_as_tradein_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELIMITER $$
DROP PROCEDURE IF EXISTS autoservice_stage039_add_column$$
CREATE PROCEDURE autoservice_stage039_add_column(IN p_table VARCHAR(191), IN p_column VARCHAR(191), IN p_sql TEXT)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND COLUMN_NAME = p_column
    ) THEN
        SET @ddl = p_sql;
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DROP PROCEDURE IF EXISTS autoservice_stage039_add_index$$
CREATE PROCEDURE autoservice_stage039_add_index(IN p_table VARCHAR(191), IN p_index VARCHAR(191), IN p_sql TEXT)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND INDEX_NAME = p_index
    ) THEN
        SET @ddl = p_sql;
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

CALL autoservice_stage039_add_column('auto_service_jobs', 'fleet_customer_id', 'ALTER TABLE auto_service_jobs ADD COLUMN fleet_customer_id BIGINT UNSIGNED NULL AFTER contact_id');
CALL autoservice_stage039_add_column('auto_service_jobs', 'fleet_contract_id', 'ALTER TABLE auto_service_jobs ADD COLUMN fleet_contract_id BIGINT UNSIGNED NULL AFTER fleet_customer_id');
CALL autoservice_stage039_add_column('auto_service_jobs', 'driver_id', 'ALTER TABLE auto_service_jobs ADD COLUMN driver_id BIGINT UNSIGNED NULL AFTER fleet_contract_id');
CALL autoservice_stage039_add_column('auto_service_jobs', 'corporate_pricing_applied', 'ALTER TABLE auto_service_jobs ADD COLUMN corporate_pricing_applied TINYINT(1) NOT NULL DEFAULT 0 AFTER driver_id');

CALL autoservice_stage039_add_index('auto_service_jobs', 'idx_as_jobs_fleet_customer', 'CREATE INDEX idx_as_jobs_fleet_customer ON auto_service_jobs (fleet_customer_id)');
CALL autoservice_stage039_add_index('auto_service_jobs', 'idx_as_jobs_fleet_contract', 'CREATE INDEX idx_as_jobs_fleet_contract ON auto_service_jobs (fleet_contract_id)');
CALL autoservice_stage039_add_index('auto_service_jobs', 'idx_as_jobs_driver', 'CREATE INDEX idx_as_jobs_driver ON auto_service_jobs (driver_id)');

DROP PROCEDURE IF EXISTS autoservice_stage039_add_column;
DROP PROCEDURE IF EXISTS autoservice_stage039_add_index;

-- Permission keys for permission seeders/importers. If your permission table is named differently,
-- add the following permission keys through Super Admin permission management:
-- autoservice.dealer_enterprise.view
-- autoservice.dealer_enterprise.manage
-- autoservice.fleet_customer.view
-- autoservice.fleet_customer.manage
-- autoservice.fleet_contract.view
-- autoservice.fleet_contract.manage
-- autoservice.corporate_pricing.view
-- autoservice.corporate_pricing.manage
