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
