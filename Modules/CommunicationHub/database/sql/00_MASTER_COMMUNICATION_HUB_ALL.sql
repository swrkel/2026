

-- ============================================================
-- 10_LIVE_CHAT_INTERNAL_MESSAGING_UPGRADE.sql
-- ============================================================
-- Communication Hub Stage 007: Live Chat and Internal Messaging Platform
-- Tenant DB SQL only. Run on each tenant database. No database name is specified.

CREATE TABLE IF NOT EXISTS `communication_hub_chat_conversations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `contact_type` VARCHAR(50) NULL,
  `contact_id` BIGINT UNSIGNED NULL,
  `contact_name` VARCHAR(191) NOT NULL,
  `contact_mobile` VARCHAR(50) NULL,
  `contact_email` VARCHAR(191) NULL,
  `subject` VARCHAR(191) NOT NULL,
  `priority` VARCHAR(30) NOT NULL DEFAULT 'normal',
  `source_channel` VARCHAR(50) NOT NULL DEFAULT 'manual',
  `status` VARCHAR(30) NOT NULL DEFAULT 'open',
  `assigned_to` BIGINT UNSIGNED NULL,
  `last_message_at` TIMESTAMP NULL,
  `closed_at` TIMESTAMP NULL,
  `closed_by` BIGINT UNSIGNED NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `ch_chat_conv_business_idx` (`business_id`, `business_location_id`),
  KEY `ch_chat_conv_status_idx` (`status`, `priority`),
  KEY `ch_chat_conv_contact_idx` (`contact_type`, `contact_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `communication_hub_chat_messages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `conversation_id` BIGINT UNSIGNED NOT NULL,
  `sender_type` VARCHAR(30) NOT NULL DEFAULT 'agent',
  `sender_user_id` BIGINT UNSIGNED NULL,
  `message_type` VARCHAR(30) NOT NULL DEFAULT 'text',
  `message_body` TEXT NOT NULL,
  `attachment_url` VARCHAR(1000) NULL,
  `direction` VARCHAR(30) NOT NULL DEFAULT 'outbound',
  `status` VARCHAR(30) NOT NULL DEFAULT 'sent',
  `read_at` TIMESTAMP NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `ch_chat_msg_business_idx` (`business_id`, `business_location_id`),
  KEY `ch_chat_msg_conversation_idx` (`conversation_id`),
  KEY `ch_chat_msg_status_idx` (`status`, `direction`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `communication_hub_internal_messages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `sender_user_id` BIGINT UNSIGNED NULL,
  `recipient_user_id` BIGINT UNSIGNED NULL,
  `recipient_role` VARCHAR(100) NULL,
  `recipient_group` VARCHAR(100) NULL,
  `subject` VARCHAR(191) NOT NULL,
  `message_body` TEXT NOT NULL,
  `priority` VARCHAR(30) NOT NULL DEFAULT 'normal',
  `action_url` VARCHAR(1000) NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'unread',
  `read_at` TIMESTAMP NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `ch_internal_business_idx` (`business_id`, `business_location_id`),
  KEY `ch_internal_recipient_idx` (`recipient_user_id`, `recipient_role`, `recipient_group`),
  KEY `ch_internal_status_idx` (`status`, `priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `communication_hub_chat_templates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `template_name` VARCHAR(191) NOT NULL,
  `template_type` VARCHAR(50) NOT NULL DEFAULT 'chat',
  `category` VARCHAR(50) NULL,
  `subject` VARCHAR(191) NULL,
  `body` TEXT NOT NULL,
  `variables` VARCHAR(1000) NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `ch_chat_tpl_business_idx` (`business_id`, `business_location_id`),
  KEY `ch_chat_tpl_type_idx` (`template_type`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =========================================================
-- Stage 008 - Communication Automation Engine
-- =========================================================
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
-- Communication Hub Stage 009 - Workflow Rules & Event Engine
-- Run this script inside EACH tenant database. Do not include a database name.

CREATE TABLE IF NOT EXISTS `communication_hub_workflow_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `business_location_id` INT UNSIGNED NULL,
  `event_code` VARCHAR(191) NOT NULL,
  `event_name` VARCHAR(191) NOT NULL,
  `source_module` VARCHAR(100) NULL,
  `description` TEXT NULL,
  `payload_schema` TEXT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'active',
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ch_wf_events_business_idx` (`business_id`),
  KEY `ch_wf_events_location_idx` (`business_location_id`),
  KEY `ch_wf_events_code_idx` (`event_code`),
  KEY `ch_wf_events_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `communication_hub_workflow_rules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `business_location_id` INT UNSIGNED NULL,
  `rule_name` VARCHAR(191) NOT NULL,
  `event_code` VARCHAR(191) NOT NULL,
  `source_module` VARCHAR(100) NULL,
  `condition_json` TEXT NULL,
  `channels` VARCHAR(191) NOT NULL,
  `recipient_source` VARCHAR(100) NULL DEFAULT 'payload',
  `recipient_field` VARCHAR(100) NULL,
  `template_key` VARCHAR(191) NULL,
  `message_body` TEXT NOT NULL,
  `priority` VARCHAR(30) NOT NULL DEFAULT 'normal',
  `delay_minutes` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` VARCHAR(30) NOT NULL DEFAULT 'active',
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ch_wf_rules_business_idx` (`business_id`),
  KEY `ch_wf_rules_location_idx` (`business_location_id`),
  KEY `ch_wf_rules_event_idx` (`event_code`),
  KEY `ch_wf_rules_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `communication_hub_workflow_event_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `business_location_id` INT UNSIGNED NULL,
  `event_code` VARCHAR(191) NOT NULL,
  `event_name` VARCHAR(191) NULL,
  `source_module` VARCHAR(100) NULL,
  `source_record_id` VARCHAR(100) NULL,
  `recipient_name` VARCHAR(191) NULL,
  `recipient_mobile` VARCHAR(50) NULL,
  `recipient_email` VARCHAR(191) NULL,
  `recipient_whatsapp` VARCHAR(50) NULL,
  `payload_json` LONGTEXT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `created_messages` INT UNSIGNED NOT NULL DEFAULT 0,
  `processed_at` TIMESTAMP NULL DEFAULT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ch_wf_logs_business_idx` (`business_id`),
  KEY `ch_wf_logs_location_idx` (`business_location_id`),
  KEY `ch_wf_logs_event_idx` (`event_code`),
  KEY `ch_wf_logs_status_idx` (`status`),
  KEY `ch_wf_logs_source_idx` (`source_module`, `source_record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `communication_hub_messages`
  ADD COLUMN IF NOT EXISTS `workflow_rule_id` BIGINT UNSIGNED NULL AFTER `automation_event_id`,
  ADD COLUMN IF NOT EXISTS `workflow_event_log_id` BIGINT UNSIGNED NULL AFTER `workflow_rule_id`;

INSERT INTO `communication_hub_workflow_events` (`event_code`, `event_name`, `source_module`, `description`, `status`, `created_at`, `updated_at`)
SELECT 'customer_created', 'Customer Created', 'Customers', 'Triggered when a customer is created.', 'active', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `communication_hub_workflow_events` WHERE `event_code` = 'customer_created' AND `business_id` IS NULL);

INSERT INTO `communication_hub_workflow_events` (`event_code`, `event_name`, `source_module`, `description`, `status`, `created_at`, `updated_at`)
SELECT 'sales_invoice_paid', 'Sales Invoice Paid', 'Sales', 'Triggered when a sales invoice is paid.', 'active', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `communication_hub_workflow_events` WHERE `event_code` = 'sales_invoice_paid' AND `business_id` IS NULL);

INSERT INTO `communication_hub_workflow_events` (`event_code`, `event_name`, `source_module`, `description`, `status`, `created_at`, `updated_at`)
SELECT 'pd_settlement_finalized', 'PD Settlement Finalized', 'PetroPD', 'Triggered when a PD settlement is finalized.', 'active', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `communication_hub_workflow_events` WHERE `event_code` = 'pd_settlement_finalized' AND `business_id` IS NULL);

INSERT INTO `communication_hub_workflow_events` (`event_code`, `event_name`, `source_module`, `description`, `status`, `created_at`, `updated_at`)
SELECT 'approval_required', 'Approval Required', 'ERP', 'Triggered when any module creates an approval request.', 'active', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `communication_hub_workflow_events` WHERE `event_code` = 'approval_required' AND `business_id` IS NULL);
