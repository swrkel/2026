-- Communication Hub Stage 008 - Communication Automation Engine
-- Run this SQL in EACH tenant database. No database name is hard-coded.

CREATE TABLE IF NOT EXISTS `communication_hub_automation_rules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `rule_name` VARCHAR(191) NOT NULL,
  `event_code` VARCHAR(191) NOT NULL,
  `source_module` VARCHAR(100) NULL,
  `channels` VARCHAR(191) NOT NULL COMMENT 'Comma separated: sms,email,whatsapp,push,in_app',
  `subject` VARCHAR(191) NULL,
  `message_body` TEXT NOT NULL,
  `conditions_json` JSON NULL,
  `priority` VARCHAR(30) NOT NULL DEFAULT 'normal',
  `delay_minutes` INT NOT NULL DEFAULT 0,
  `status` VARCHAR(30) NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ch_auto_rules_business_idx` (`business_id`),
  KEY `ch_auto_rules_location_idx` (`business_location_id`),
  KEY `ch_auto_rules_event_idx` (`event_code`),
  KEY `ch_auto_rules_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `communication_hub_automation_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `event_code` VARCHAR(191) NOT NULL,
  `event_name` VARCHAR(191) NULL,
  `source_module` VARCHAR(100) NULL,
  `source_record_id` VARCHAR(100) NULL,
  `recipient_name` VARCHAR(191) NULL,
  `recipient_mobile` VARCHAR(50) NULL,
  `recipient_email` VARCHAR(191) NULL,
  `recipient_whatsapp` VARCHAR(50) NULL,
  `payload_json` JSON NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `created_messages` INT NOT NULL DEFAULT 0,
  `processed_at` TIMESTAMP NULL DEFAULT NULL,
  `error_message` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ch_auto_events_business_idx` (`business_id`),
  KEY `ch_auto_events_location_idx` (`business_location_id`),
  KEY `ch_auto_events_code_idx` (`event_code`),
  KEY `ch_auto_events_status_idx` (`status`),
  KEY `ch_auto_events_source_idx` (`source_module`, `source_record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Message queue compatibility columns. MySQL 8 supports IF NOT EXISTS.
ALTER TABLE `communication_hub_messages`
  ADD COLUMN IF NOT EXISTS `source` VARCHAR(50) NULL AFTER `priority`,
  ADD COLUMN IF NOT EXISTS `source_module` VARCHAR(100) NULL AFTER `source`,
  ADD COLUMN IF NOT EXISTS `source_record_id` VARCHAR(100) NULL AFTER `source_module`,
  ADD COLUMN IF NOT EXISTS `automation_rule_id` BIGINT UNSIGNED NULL AFTER `source_record_id`,
  ADD COLUMN IF NOT EXISTS `automation_event_id` BIGINT UNSIGNED NULL AFTER `automation_rule_id`,
  ADD COLUMN IF NOT EXISTS `business_location_id` BIGINT UNSIGNED NULL AFTER `business_id`;

CREATE INDEX IF NOT EXISTS `ch_messages_automation_event_idx` ON `communication_hub_messages` (`automation_event_id`);
CREATE INDEX IF NOT EXISTS `ch_messages_automation_rule_idx` ON `communication_hub_messages` (`automation_rule_id`);

INSERT INTO `communication_hub_automation_rules` (`business_id`, `business_location_id`, `rule_name`, `event_code`, `source_module`, `channels`, `subject`, `message_body`, `priority`, `delay_minutes`, `status`, `created_at`, `updated_at`)
SELECT NULL, NULL, 'Customer Created Welcome Message', 'customer.created', 'Customers', 'sms,email,whatsapp', 'Welcome {{recipient_name}}', 'Dear {{recipient_name}}, welcome to our business. Your profile has been created successfully.', 'normal', 0, 'inactive', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `communication_hub_automation_rules` WHERE `event_code` = 'customer.created' AND `rule_name` = 'Customer Created Welcome Message');

INSERT INTO `communication_hub_automation_rules` (`business_id`, `business_location_id`, `rule_name`, `event_code`, `source_module`, `channels`, `subject`, `message_body`, `priority`, `delay_minutes`, `status`, `created_at`, `updated_at`)
SELECT NULL, NULL, 'Invoice Created Notification', 'sales.invoice.created', 'Sales', 'sms,email,whatsapp,in_app', 'Invoice {{invoice_no}}', 'Dear {{recipient_name}}, invoice {{invoice_no}} amount {{amount}} has been created.', 'normal', 0, 'inactive', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `communication_hub_automation_rules` WHERE `event_code` = 'sales.invoice.created' AND `rule_name` = 'Invoice Created Notification');
