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
