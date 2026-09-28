-- ATN-008 TABLES
CREATE TABLE IF NOT EXISTS `atn_operational_tasks` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`task_no` VARCHAR(40) NOT NULL,`task_type` VARCHAR(50) NOT NULL,`reference_type` VARCHAR(80) NULL,`reference_id` BIGINT UNSIGNED NULL,
`title` VARCHAR(190) NOT NULL,`description` TEXT NULL,`priority` VARCHAR(20) NOT NULL DEFAULT 'normal',`due_at` DATETIME NULL,
`assigned_to` BIGINT UNSIGNED NULL,`status` VARCHAR(30) NOT NULL DEFAULT 'open',`completed_at` DATETIME NULL,
`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,
UNIQUE KEY `atn_operational_task_uq` (`business_id`,`task_type`,`reference_type`,`reference_id`),KEY `atn_operational_due_idx` (`business_id`,`due_at`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `atn_notification_rules` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`event_code` VARCHAR(80) NOT NULL,`channel` VARCHAR(30) NOT NULL,`recipient_type` VARCHAR(30) NOT NULL,`recipient_value` VARCHAR(190) NULL,
`template_subject` VARCHAR(190) NULL,`template_body` TEXT NOT NULL,`lead_minutes` INT NOT NULL DEFAULT 0,`is_active` TINYINT(1) NOT NULL DEFAULT 1,
`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,
KEY `atn_notification_rule_idx` (`business_id`,`event_code`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `atn_notification_logs` (
`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`business_id` BIGINT UNSIGNED NOT NULL,`business_location_id` BIGINT UNSIGNED NULL,`store_id` BIGINT UNSIGNED NULL,
`event_code` VARCHAR(80) NOT NULL,`reference_type` VARCHAR(80) NULL,`reference_id` BIGINT UNSIGNED NULL,`channel` VARCHAR(30) NOT NULL,
`recipient` VARCHAR(190) NOT NULL,`subject` VARCHAR(190) NULL,`message` TEXT NOT NULL,`status` VARCHAR(30) NOT NULL DEFAULT 'queued',
`provider_reference` VARCHAR(190) NULL,`sent_at` DATETIME NULL,`error_message` TEXT NULL,
`created_by` BIGINT UNSIGNED NULL,`updated_by` BIGINT UNSIGNED NULL,`created_at` TIMESTAMP NULL,`updated_at` TIMESTAMP NULL,
KEY `atn_notification_log_idx` (`business_id`,`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
