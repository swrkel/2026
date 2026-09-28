-- Communication Hub Stage 006 - In-App Notification Centre
-- Run this in EACH tenant database. Do not prefix a database name.

CREATE TABLE IF NOT EXISTS `communication_hub_in_app_notifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `business_location_id` INT UNSIGNED NULL,
  `recipient_user_id` BIGINT UNSIGNED NULL,
  `recipient_role` VARCHAR(100) NULL,
  `recipient_group` VARCHAR(100) NULL,
  `title` VARCHAR(191) NOT NULL,
  `body` TEXT NULL,
  `message` TEXT NULL,
  `notification_type` VARCHAR(50) NOT NULL DEFAULT 'info',
  `priority` VARCHAR(30) NOT NULL DEFAULT 'normal',
  `action_url` VARCHAR(1000) NULL,
  `icon` VARCHAR(100) NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'unread',
  `read_at` TIMESTAMP NULL,
  `archived_at` TIMESTAMP NULL,
  `expires_at` TIMESTAMP NULL,
  `payload` JSON NULL,
  `source_module` VARCHAR(100) NULL,
  `source_event` VARCHAR(100) NULL,
  `source_record_id` BIGINT UNSIGNED NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `ch_inapp_business_status_idx` (`business_id`, `status`),
  INDEX `ch_inapp_location_status_idx` (`business_location_id`, `status`),
  INDEX `ch_inapp_user_status_idx` (`recipient_user_id`, `status`),
  INDEX `ch_inapp_role_status_idx` (`recipient_role`, `status`),
  INDEX `ch_inapp_type_priority_idx` (`notification_type`, `priority`),
  INDEX `ch_inapp_created_idx` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `communication_hub_in_app_templates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `business_location_id` INT UNSIGNED NULL,
  `template_name` VARCHAR(191) NOT NULL,
  `category` VARCHAR(50) NOT NULL DEFAULT 'general',
  `title` VARCHAR(191) NOT NULL,
  `body` TEXT NOT NULL,
  `notification_type` VARCHAR(50) NOT NULL DEFAULT 'info',
  `priority` VARCHAR(30) NOT NULL DEFAULT 'normal',
  `action_url` VARCHAR(1000) NULL,
  `variables` TEXT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `ch_inapp_tpl_business_idx` (`business_id`, `status`),
  INDEX `ch_inapp_tpl_category_idx` (`category`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `communication_hub_notification_preferences` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `business_location_id` INT UNSIGNED NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `role_name` VARCHAR(100) NULL,
  `channel` VARCHAR(50) NOT NULL DEFAULT 'in_app',
  `event_key` VARCHAR(191) NOT NULL,
  `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `allow_sms` TINYINT(1) NOT NULL DEFAULT 0,
  `allow_email` TINYINT(1) NOT NULL DEFAULT 0,
  `allow_whatsapp` TINYINT(1) NOT NULL DEFAULT 0,
  `allow_push` TINYINT(1) NOT NULL DEFAULT 0,
  `allow_in_app` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ch_notify_pref_unique` (`business_id`, `user_id`, `role_name`, `event_key`),
  INDEX `ch_notify_pref_event_idx` (`event_key`, `is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `communication_hub_in_app_templates`
(`business_id`, `business_location_id`, `template_name`, `category`, `title`, `body`, `notification_type`, `priority`, `action_url`, `variables`, `status`, `created_at`, `updated_at`)
SELECT NULL, NULL, 'Approval Required', 'approval', 'Approval required: {module}', 'A new {module} record requires your approval. Reference: {reference_no}.', 'approval', 'high', NULL, '{module},{reference_no}', 'active', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `communication_hub_in_app_templates` WHERE `template_name` = 'Approval Required' AND `business_id` IS NULL);

INSERT INTO `communication_hub_in_app_templates`
(`business_id`, `business_location_id`, `template_name`, `category`, `title`, `body`, `notification_type`, `priority`, `action_url`, `variables`, `status`, `created_at`, `updated_at`)
SELECT NULL, NULL, 'System Alert', 'system', 'System alert', '{message}', 'system', 'normal', NULL, '{message}', 'active', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `communication_hub_in_app_templates` WHERE `template_name` = 'System Alert' AND `business_id` IS NULL);
