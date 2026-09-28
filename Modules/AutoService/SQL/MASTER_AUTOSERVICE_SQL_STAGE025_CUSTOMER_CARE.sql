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
