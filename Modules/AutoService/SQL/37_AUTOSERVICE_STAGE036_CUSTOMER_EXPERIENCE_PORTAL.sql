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
