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
