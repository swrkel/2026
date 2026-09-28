-- Communication Hub - Stage 003 Email Business Platform Upgrade
-- Run this in EACH TENANT database. Do not hardcode database names.

CREATE TABLE IF NOT EXISTS `communication_hub_email_campaigns` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NOT NULL,
  `subject` VARCHAR(191) NULL,
  `body` LONGTEXT NULL,
  `from_name` VARCHAR(191) NULL,
  `from_email` VARCHAR(191) NULL,
  `reply_to` VARCHAR(191) NULL,
  `total_recipients` INT UNSIGNED NOT NULL DEFAULT 0,
  `sent_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `failed_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `open_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `click_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` VARCHAR(50) NOT NULL DEFAULT 'draft',
  `scheduled_at` TIMESTAMP NULL DEFAULT NULL,
  `started_at` TIMESTAMP NULL DEFAULT NULL,
  `completed_at` TIMESTAMP NULL DEFAULT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ch_email_campaigns_business_status_idx` (`business_id`, `status`),
  KEY `ch_email_campaigns_schedule_idx` (`scheduled_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `communication_hub_email_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `message_id` BIGINT UNSIGNED NULL,
  `campaign_id` BIGINT UNSIGNED NULL,
  `recipient` VARCHAR(191) NULL,
  `event_type` VARCHAR(50) NOT NULL,
  `provider` VARCHAR(100) NULL,
  `provider_event_id` VARCHAR(191) NULL,
  `payload` LONGTEXT NULL,
  `event_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ch_email_events_business_type_idx` (`business_id`, `event_type`),
  KEY `ch_email_events_message_idx` (`message_id`),
  KEY `ch_email_events_campaign_idx` (`campaign_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `communication_hub_messages`
  ADD COLUMN IF NOT EXISTS `subject` VARCHAR(191) NULL AFTER `recipient`,
  ADD COLUMN IF NOT EXISTS `campaign_id` BIGINT UNSIGNED NULL AFTER `client_id`,
  ADD COLUMN IF NOT EXISTS `business_location_id` BIGINT UNSIGNED NULL AFTER `business_id`,
  ADD COLUMN IF NOT EXISTS `opened_at` TIMESTAMP NULL DEFAULT NULL AFTER `sent_at`,
  ADD COLUMN IF NOT EXISTS `clicked_at` TIMESTAMP NULL DEFAULT NULL AFTER `opened_at`;

CREATE INDEX IF NOT EXISTS `ch_messages_email_business_status_idx` ON `communication_hub_messages` (`business_id`, `channel`, `status`);
CREATE INDEX IF NOT EXISTS `ch_messages_email_campaign_idx` ON `communication_hub_messages` (`campaign_id`);

INSERT IGNORE INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
('communicationhub.email.view','web',NOW(),NOW()),
('communicationhub.email.send','web',NOW(),NOW()),
('communicationhub.email.bulk','web',NOW(),NOW()),
('communicationhub.email.queue','web',NOW(),NOW()),
('communicationhub.email.retry','web',NOW(),NOW());
