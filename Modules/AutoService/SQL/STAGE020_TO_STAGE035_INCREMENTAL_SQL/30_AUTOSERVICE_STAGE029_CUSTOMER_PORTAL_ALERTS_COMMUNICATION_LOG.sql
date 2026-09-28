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
