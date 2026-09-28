-- Communication Hub COMPLETE TENANT TABLES - 26 Sep 2026
-- Generated from the supplied CommunicationHub module source.
-- Import into EACH tenant database that uses Communication Hub.
-- Contains module-owned tables and their indexes. No DROP TABLE statements.
-- Safe for a fresh database because CREATE TABLE IF NOT EXISTS is used.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

-- [01] communication_deployment_signoffs (source: 20_OPERATIONS_SUPPORT_AND_FINAL_SIGNOFF.sql)
CREATE TABLE IF NOT EXISTS communication_deployment_signoffs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    business_location_id BIGINT UNSIGNED NULL,
    stage_code VARCHAR(50) NOT NULL DEFAULT 'STAGE_019',
    checklist_key VARCHAR(100) NOT NULL,
    checklist_label VARCHAR(255) NOT NULL,
    status ENUM('pending','passed','failed','not_applicable') NOT NULL DEFAULT 'pending',
    remarks TEXT NULL,
    checked_by BIGINT UNSIGNED NULL,
    checked_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ch_signoff_business (business_id, business_location_id),
    INDEX idx_ch_signoff_stage (stage_code, checklist_key),
    INDEX idx_ch_signoff_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [02] communication_hub_advanced_schedules (source: 18_ENTERPRISE_EXCELLENCE_FINAL_STAGE.sql)
CREATE TABLE IF NOT EXISTS communication_hub_advanced_schedules (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NULL,
  business_location_id BIGINT UNSIGNED NULL,
  name VARCHAR(191) NOT NULL,
  channel VARCHAR(50) NOT NULL,
  frequency VARCHAR(50) NOT NULL DEFAULT 'once',
  start_at TIMESTAMP NULL,
  end_at TIMESTAMP NULL,
  timezone VARCHAR(60) NULL DEFAULT 'Asia/Colombo',
  payload JSON NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_run_at TIMESTAMP NULL,
  next_run_at TIMESTAMP NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY ch_advanced_schedules_business_active_idx (business_id, is_active),
  KEY ch_advanced_schedules_channel_idx (channel),
  KEY ch_advanced_schedules_next_run_idx (next_run_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [03] communication_hub_api_clients (source: 00_RUN_THIS_IN_EACH_TENANT_DATABASE.sql)
CREATE TABLE IF NOT EXISTS `communication_hub_api_clients` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `module_name` VARCHAR(255) NOT NULL,
  `client_name` VARCHAR(255) NOT NULL,
  `name` VARCHAR(191) NULL,
  `token` VARCHAR(191) NULL,
  `secret` VARCHAR(191) NULL,
  `client_id` BIGINT UNSIGNED NULL,
  `token_hash` VARCHAR(64) NOT NULL,
  `allowed_channels` JSON NULL,
  `allowed_ips` JSON NULL,
  `permissions` JSON NULL,
  `rate_limits` JSON NULL,
  `rate_limit_per_minute` INT UNSIGNED NOT NULL DEFAULT 60,
  `daily_limit` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_used_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ch_api_token` (`token_hash`),
  KEY `idx_ch_api_business` (`business_id`),
  KEY `idx_ch_api_module` (`module_name`),
  KEY `idx_ch_api_client_id` (`client_id`),
  KEY `idx_ch_api_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [04] communication_hub_api_gateway_tokens (source: 13_ANALYTICS_API_GATEWAY_FINAL_AUDIT.sql)
CREATE TABLE IF NOT EXISTS `communication_hub_api_gateway_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `business_location_id` INT UNSIGNED NULL,
  `client_name` VARCHAR(191) NOT NULL,
  `token_hash` VARCHAR(191) NOT NULL,
  `allowed_channels` TEXT NULL,
  `allowed_events` TEXT NULL,
  `daily_limit` INT UNSIGNED NOT NULL DEFAULT 0,
  `monthly_limit` INT UNSIGNED NOT NULL DEFAULT 0,
  `used_today` INT UNSIGNED NOT NULL DEFAULT 0,
  `used_month` INT UNSIGNED NOT NULL DEFAULT 0,
  `last_used_at` TIMESTAMP NULL DEFAULT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'active',
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ch_api_gt_business_idx` (`business_id`),
  KEY `ch_api_gt_location_idx` (`business_location_id`),
  KEY `ch_api_gt_status_idx` (`status`),
  UNIQUE KEY `ch_api_gt_token_unique` (`token_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [05] communication_hub_api_request_logs (source: 00_RUN_THIS_IN_EACH_TENANT_DATABASE.sql)
CREATE TABLE IF NOT EXISTS `communication_hub_api_request_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `api_client_id` BIGINT UNSIGNED NULL,
  `business_id` BIGINT UNSIGNED NULL,
  `client_id` BIGINT UNSIGNED NULL,
  `module_name` VARCHAR(255) NULL,
  `endpoint` VARCHAR(255) NOT NULL,
  `method` VARCHAR(20) NULL,
  `request_method` VARCHAR(20) NULL,
  `ip_address` VARCHAR(255) NULL,
  `request_payload` JSON NULL,
  `response_payload` JSON NULL,
  `status_code` SMALLINT UNSIGNED NULL,
  `response_code` SMALLINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `channel` VARCHAR(50) NULL,
  `event_code` VARCHAR(191) NULL,
  `response_status` VARCHAR(50) NULL,
  `response_time_ms` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ch_api_log_client` (`api_client_id`),
  KEY `idx_ch_api_log_business` (`business_id`),
  KEY `idx_ch_api_log_client_id` (`client_id`),
  KEY `idx_ch_api_log_module` (`module_name`),
  KEY `idx_ch_api_log_endpoint` (`endpoint`),
  KEY `idx_ch_api_log_status` (`status_code`),
  KEY `ch_api_log_business_created_idx` (`business_id`,`created_at`),
  KEY `ch_api_log_channel_idx` (`channel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [06] communication_hub_audit_logs (source: 00_RUN_THIS_IN_EACH_TENANT_DATABASE.sql)
CREATE TABLE IF NOT EXISTS `communication_hub_audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `action` VARCHAR(120) NOT NULL,
  `payload` JSON NULL,
  `meta` JSON NULL,
  `ip_address` VARCHAR(64) NULL,
  `user_agent` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ch_audit_business` (`business_id`),
  KEY `idx_ch_audit_user` (`user_id`),
  KEY `idx_ch_audit_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [07] communication_hub_automation_events (source: 11_AUTOMATION_ENGINE_UPGRADE.sql)
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

-- [08] communication_hub_automation_rules (source: 11_AUTOMATION_ENGINE_UPGRADE.sql)
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

-- [09] communication_hub_campaigns (source: 00_RUN_THIS_IN_EACH_TENANT_DATABASE.sql)
CREATE TABLE IF NOT EXISTS `communication_hub_campaigns` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `channel` VARCHAR(30) NOT NULL DEFAULT 'sms',
  `template_id` BIGINT UNSIGNED NULL,
  `audience_type` VARCHAR(50) NOT NULL DEFAULT 'manual',
  `audience_filters` JSON NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'draft',
  `scheduled_at` TIMESTAMP NULL,
  `started_at` TIMESTAMP NULL,
  `completed_at` TIMESTAMP NULL,
  `total_recipients` INT UNSIGNED NOT NULL DEFAULT 0,
  `sent_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `failed_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `estimated_cost` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
  `actual_cost` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
  `description` TEXT NULL,
  `metadata` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ch_campaign_channel` (`channel`),
  KEY `idx_ch_campaign_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [10] communication_hub_chat_conversations (source: 10_LIVE_CHAT_INTERNAL_MESSAGING_UPGRADE.sql)
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

-- [11] communication_hub_chat_messages (source: 10_LIVE_CHAT_INTERNAL_MESSAGING_UPGRADE.sql)
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

-- [12] communication_hub_chat_templates (source: 10_LIVE_CHAT_INTERNAL_MESSAGING_UPGRADE.sql)
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

-- [13] communication_hub_clients (source: 00_RUN_THIS_IN_EACH_TENANT_DATABASE.sql)
CREATE TABLE IF NOT EXISTS `communication_hub_clients` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `parent_client_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NOT NULL,
  `business_name` VARCHAR(191) NULL,
  `mobile` VARCHAR(30) NOT NULL,
  `email` VARCHAR(191) NULL,
  `client_type` VARCHAR(50) NOT NULL DEFAULT 'business',
  `status` VARCHAR(30) NOT NULL DEFAULT 'active',
  `api_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `notes` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ch_client_business` (`business_id`),
  KEY `idx_ch_client_parent` (`parent_client_id`),
  KEY `idx_ch_client_mobile` (`mobile`),
  KEY `idx_ch_client_email` (`email`),
  KEY `idx_ch_client_type` (`client_type`),
  KEY `idx_ch_client_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [14] communication_hub_cost_entries (source: 18_ENTERPRISE_EXCELLENCE_FINAL_STAGE.sql)
CREATE TABLE IF NOT EXISTS communication_hub_cost_entries (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NULL,
  business_location_id BIGINT UNSIGNED NULL,
  channel VARCHAR(50) NOT NULL,
  provider_name VARCHAR(191) NULL,
  cost_amount DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  currency VARCHAR(10) NULL DEFAULT 'LKR',
  reference_no VARCHAR(191) NULL,
  cost_date DATE NOT NULL,
  note TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY ch_cost_entries_business_date_idx (business_id, cost_date),
  KEY ch_cost_entries_channel_idx (channel),
  KEY ch_cost_entries_location_idx (business_location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [15] communication_hub_delivery_events (source: 00_RUN_THIS_IN_EACH_TENANT_DATABASE.sql)
CREATE TABLE IF NOT EXISTS `communication_hub_delivery_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `message_id` BIGINT UNSIGNED NOT NULL,
  `status` VARCHAR(30) NOT NULL,
  `provider_id` BIGINT UNSIGNED NULL,
  `provider_reference` VARCHAR(255) NULL,
  `response_code` VARCHAR(255) NULL,
  `response_message` TEXT NULL,
  `cost` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `meta` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ch_del_message` (`message_id`),
  KEY `idx_ch_del_status` (`status`),
  KEY `idx_ch_del_provider` (`provider_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [16] communication_hub_deployment_checks (source: 19_PRODUCTION_QA_AND_ROLLOUT_VALIDATION.sql)
CREATE TABLE IF NOT EXISTS communication_hub_deployment_checks (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    location_id BIGINT UNSIGNED NULL,
    check_key VARCHAR(191) NOT NULL,
    check_label VARCHAR(255) NOT NULL,
    check_status ENUM('pending','pass','warning','fail') NOT NULL DEFAULT 'pending',
    checked_at TIMESTAMP NULL,
    checked_by BIGINT UNSIGNED NULL,
    details JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX ch_deploy_business_idx (business_id),
    INDEX ch_deploy_location_idx (location_id),
    INDEX ch_deploy_status_idx (check_status),
    INDEX ch_deploy_key_idx (check_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [17] communication_hub_email_campaigns (source: 06_EMAIL_BUSINESS_PLATFORM_UPGRADE.sql)
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

-- [18] communication_hub_email_events (source: 06_EMAIL_BUSINESS_PLATFORM_UPGRADE.sql)
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

-- [19] communication_hub_event_registry (source: 18_ENTERPRISE_EXCELLENCE_FINAL_STAGE.sql)
CREATE TABLE IF NOT EXISTS communication_hub_event_registry (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NULL,
  event_key VARCHAR(191) NOT NULL,
  event_name VARCHAR(191) NOT NULL,
  source_module VARCHAR(100) NOT NULL,
  channels JSON NULL,
  template_id BIGINT UNSIGNED NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY ch_event_registry_business_key_unique (business_id, event_key),
  KEY ch_event_registry_module_idx (source_module),
  KEY ch_event_registry_active_idx (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [20] communication_hub_final_audit_checks (source: 13_ANALYTICS_API_GATEWAY_FINAL_AUDIT.sql)
CREATE TABLE IF NOT EXISTS `communication_hub_final_audit_checks` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `check_key` VARCHAR(191) NOT NULL,
  `check_name` VARCHAR(191) NOT NULL,
  `category` VARCHAR(100) NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `severity` VARCHAR(30) NOT NULL DEFAULT 'normal',
  `result_message` TEXT NULL,
  `checked_by` INT UNSIGNED NULL,
  `checked_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ch_final_audit_business_idx` (`business_id`),
  KEY `ch_final_audit_category_idx` (`category`),
  KEY `ch_final_audit_status_idx` (`status`),
  UNIQUE KEY `ch_final_audit_key_unique` (`business_id`,`check_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [21] communication_hub_global_notifications (source: 18_ENTERPRISE_EXCELLENCE_FINAL_STAGE.sql)
CREATE TABLE IF NOT EXISTS communication_hub_global_notifications (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NULL,
  business_location_id BIGINT UNSIGNED NULL,
  target_user_id BIGINT UNSIGNED NULL,
  source_module VARCHAR(100) NULL,
  type VARCHAR(50) NULL DEFAULT 'info',
  title VARCHAR(191) NOT NULL,
  message TEXT NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'unread',
  read_at TIMESTAMP NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY ch_global_notifications_business_status_idx (business_id, status),
  KEY ch_global_notifications_user_status_idx (target_user_id, status),
  KEY ch_global_notifications_module_idx (source_module)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [22] communication_hub_in_app_notifications (source: 09_IN_APP_NOTIFICATION_CENTRE_UPGRADE.sql)
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

-- [23] communication_hub_in_app_templates (source: 09_IN_APP_NOTIFICATION_CENTRE_UPGRADE.sql)
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

-- [24] communication_hub_internal_messages (source: 10_LIVE_CHAT_INTERNAL_MESSAGING_UPGRADE.sql)
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

-- [25] communication_hub_marketplace_packages (source: 00_RUN_THIS_IN_EACH_TENANT_DATABASE.sql)
CREATE TABLE IF NOT EXISTS `communication_hub_marketplace_packages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `package_code` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `channel` VARCHAR(50) NOT NULL,
  `provider_key` VARCHAR(100) NULL,
  `version` VARCHAR(50) NOT NULL DEFAULT '1.0.0',
  `available_version` VARCHAR(50) NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'available',
  `is_installed` TINYINT(1) NOT NULL DEFAULT 0,
  `is_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `compatibility_status` VARCHAR(50) NOT NULL DEFAULT 'compatible',
  `license_status` VARCHAR(50) NOT NULL DEFAULT 'not_required',
  `cost_per_message` DECIMAL(12,4) NULL,
  `failover_priority` INT UNSIGNED NOT NULL DEFAULT 100,
  `capabilities` JSON NULL,
  `configuration_schema` JSON NULL,
  `sandbox_results` JSON NULL,
  `last_error` TEXT NULL,
  `installed_at` TIMESTAMP NULL,
  `enabled_at` TIMESTAMP NULL,
  `disabled_at` TIMESTAMP NULL,
  `last_tested_at` TIMESTAMP NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ch_market_package_code` (`package_code`),
  KEY `idx_ch_market_channel` (`channel`),
  KEY `idx_ch_market_provider` (`provider_key`),
  KEY `idx_ch_market_installed` (`is_installed`),
  KEY `idx_ch_market_enabled` (`is_enabled`),
  KEY `idx_ch_market_status` (`status`,`compatibility_status`),
  KEY `idx_ch_market_channel_enabled` (`channel`,`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [26] communication_hub_menu_health_logs (source: 15_POST_HANDOVER_MENU_PERMISSION_STABILIZATION.sql)
CREATE TABLE IF NOT EXISTS communication_hub_menu_health_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id INT UNSIGNED NULL,
    route_name VARCHAR(191) NOT NULL,
    menu_title VARCHAR(191) NULL,
    permission_name VARCHAR(191) NULL,
    status ENUM('available','missing_route','missing_permission','hidden','error') NOT NULL DEFAULT 'available',
    message TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ch_menu_health_business_idx (business_id),
    KEY ch_menu_health_route_idx (route_name),
    KEY ch_menu_health_status_idx (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [27] communication_hub_messages (source: 00_RUN_THIS_IN_EACH_TENANT_DATABASE.sql)
CREATE TABLE IF NOT EXISTS `communication_hub_messages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `module` VARCHAR(100) NULL,
  `source_module` VARCHAR(255) NULL,
  `source_reference` VARCHAR(255) NULL,
  `channel` VARCHAR(50) NOT NULL,
  `recipient` VARCHAR(255) NOT NULL,
  `subject` VARCHAR(255) NULL,
  `body` LONGTEXT NULL,
  `message` LONGTEXT NULL,
  `payload` JSON NULL,
  `priority` VARCHAR(20) NOT NULL DEFAULT 'normal',
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `attempts` INT UNSIGNED NOT NULL DEFAULT 0,
  `retries` INT UNSIGNED NOT NULL DEFAULT 0,
  `cost` DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
  `estimated_cost` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `actual_cost` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'LKR',
  `wallet_charge_status` VARCHAR(30) NULL,
  `wallet_transaction_reference` VARCHAR(255) NULL,
  `wallet_response` JSON NULL,
  `client_id` BIGINT UNSIGNED NULL,
  `gateway_cost` DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
  `selling_price` DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
  `profit_amount` DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
  `provider_id` BIGINT UNSIGNED NULL,
  `provider_reference` VARCHAR(255) NULL,
  `response_code` VARCHAR(255) NULL,
  `response_message` TEXT NULL,
  `response` JSON NULL,
  `scheduled_at` TIMESTAMP NULL,
  `attempted_at` TIMESTAMP NULL,
  `sent_at` TIMESTAMP NULL,
  `delivered_at` TIMESTAMP NULL,
  `campaign_id` BIGINT UNSIGNED NULL,
  `source` VARCHAR(50) NULL,
  `source_record_id` VARCHAR(100) NULL,
  `automation_rule_id` BIGINT UNSIGNED NULL,
  `automation_event_id` BIGINT UNSIGNED NULL,
  `workflow_rule_id` BIGINT UNSIGNED NULL,
  `workflow_event_log_id` BIGINT UNSIGNED NULL,
  `api_request_id` BIGINT UNSIGNED NULL,
  `delivery_latency_ms` INT UNSIGNED NULL,
  `opened_at` TIMESTAMP NULL,
  `clicked_at` TIMESTAMP NULL,
  `read_at` TIMESTAMP NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ch_msg_business` (`business_id`),
  KEY `idx_ch_msg_location` (`business_location_id`),
  KEY `idx_ch_msg_module` (`module`),
  KEY `idx_ch_msg_source_module` (`source_module`),
  KEY `idx_ch_msg_source_reference` (`source_reference`),
  KEY `idx_ch_msg_channel` (`channel`),
  KEY `idx_ch_msg_recipient` (`recipient`),
  KEY `idx_ch_msg_priority` (`priority`),
  KEY `idx_ch_msg_status` (`status`),
  KEY `idx_ch_msg_wallet_status` (`wallet_charge_status`),
  KEY `idx_ch_msg_wallet_ref` (`wallet_transaction_reference`),
  KEY `idx_ch_msg_client` (`client_id`),
  KEY `idx_ch_msg_provider` (`provider_id`),
  KEY `idx_ch_msg_scheduled` (`scheduled_at`),
  KEY `idx_ch_msg_sent` (`sent_at`),
  KEY `idx_ch_msg_created_by` (`created_by`),
  KEY `ch_messages_email_business_status_idx` (`business_id`, `channel`, `status`),
  KEY `ch_messages_email_campaign_idx` (`campaign_id`),
  KEY `ch_messages_automation_event_idx` (`automation_event_id`),
  KEY `ch_messages_automation_rule_idx` (`automation_rule_id`),
  KEY `ch_msg_api_req_idx` (`api_request_id`),
  KEY `ch_msg_channel_status_idx` (`channel`,`status`),
  KEY `ch_msg_business_created_idx` (`business_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [28] communication_hub_mobile_devices (source: 18_ENTERPRISE_EXCELLENCE_FINAL_STAGE.sql)
CREATE TABLE IF NOT EXISTS communication_hub_mobile_devices (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NULL,
  business_location_id BIGINT UNSIGNED NULL,
  user_id BIGINT UNSIGNED NULL,
  platform VARCHAR(30) NULL,
  device_name VARCHAR(191) NULL,
  device_token TEXT NULL,
  app_version VARCHAR(50) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_seen_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY ch_mobile_devices_business_user_idx (business_id, user_id),
  KEY ch_mobile_devices_platform_idx (platform),
  KEY ch_mobile_devices_active_idx (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [29] communication_hub_notification_preferences (source: 09_IN_APP_NOTIFICATION_CENTRE_UPGRADE.sql)
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

-- [30] communication_hub_otps (source: 00_RUN_THIS_IN_EACH_TENANT_DATABASE.sql)
CREATE TABLE IF NOT EXISTS `communication_hub_otps` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `module` VARCHAR(100) NULL,
  `recipient` VARCHAR(255) NULL,
  `identifier` VARCHAR(255) NULL,
  `channel` VARCHAR(50) NULL,
  `purpose` VARCHAR(100) NULL DEFAULT 'login',
  `otp_hash` VARCHAR(255) NOT NULL,
  `plain_otp_preview` VARCHAR(255) NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `attempts` INT UNSIGNED NOT NULL DEFAULT 0,
  `expires_at` TIMESTAMP NULL,
  `verified_at` TIMESTAMP NULL,
  `meta` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ch_otp_business` (`business_id`),
  KEY `idx_ch_otp_module` (`module`),
  KEY `idx_ch_otp_recipient` (`recipient`),
  KEY `idx_ch_otp_identifier` (`identifier`),
  KEY `idx_ch_otp_status` (`status`),
  KEY `idx_ch_otp_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [31] communication_hub_provider_health (source: 18_ENTERPRISE_EXCELLENCE_FINAL_STAGE.sql)
CREATE TABLE IF NOT EXISTS communication_hub_provider_health (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NULL,
  provider_id BIGINT UNSIGNED NULL,
  provider_name VARCHAR(191) NOT NULL,
  channel VARCHAR(50) NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'unknown',
  response_time_ms INT NULL,
  message TEXT NULL,
  checked_at TIMESTAMP NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY ch_provider_health_business_channel_idx (business_id, channel),
  KEY ch_provider_health_status_idx (status),
  KEY ch_provider_health_checked_idx (checked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [32] communication_hub_providers (source: 00_RUN_THIS_IN_EACH_TENANT_DATABASE.sql)
CREATE TABLE IF NOT EXISTS `communication_hub_providers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(255) NOT NULL,
  `channel` VARCHAR(50) NOT NULL,
  `country_code` VARCHAR(10) NULL,
  `gateway_code` VARCHAR(100) NULL,
  `driver` VARCHAR(100) NULL,
  `credentials` JSON NULL,
  `provider_config` JSON NULL,
  `meta` JSON NULL,
  `priority` INT UNSIGNED NOT NULL DEFAULT 1,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `health_status` VARCHAR(30) NOT NULL DEFAULT 'unknown',
  `cost_per_message` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `daily_limit` INT UNSIGNED NULL,
  `monthly_limit` INT UNSIGNED NULL,
  `last_used_at` TIMESTAMP NULL,
  `last_success_at` TIMESTAMP NULL,
  `last_failure_at` TIMESTAMP NULL,
  `last_health_check_at` TIMESTAMP NULL,
  `last_response_code` VARCHAR(100) NULL,
  `last_response_message` TEXT NULL,
  `average_response_ms` INT UNSIGNED NOT NULL DEFAULT 0,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ch_provider_business` (`business_id`),
  KEY `idx_ch_provider_channel` (`channel`),
  KEY `idx_ch_provider_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [33] communication_hub_public_api_clients (source: 18_ENTERPRISE_EXCELLENCE_FINAL_STAGE.sql)
CREATE TABLE IF NOT EXISTS communication_hub_public_api_clients (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  business_id BIGINT UNSIGNED NULL,
  client_name VARCHAR(191) NOT NULL,
  api_key VARCHAR(191) NOT NULL,
  allowed_channels JSON NULL,
  rate_limit_per_minute INT NOT NULL DEFAULT 60,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_used_at TIMESTAMP NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY ch_public_api_clients_api_key_unique (api_key),
  KEY ch_public_api_clients_business_active_idx (business_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [34] communication_hub_push_devices (source: 08_PUSH_NOTIFICATION_PLATFORM_UPGRADE.sql)
CREATE TABLE IF NOT EXISTS communication_hub_push_devices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    business_location_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    contact_id BIGINT UNSIGNED NULL,
    customer_id BIGINT UNSIGNED NULL,
    device_name VARCHAR(191) NULL,
    platform VARCHAR(50) NOT NULL DEFAULT 'web',
    browser VARCHAR(100) NULL,
    device_group VARCHAR(100) NULL,
    device_token TEXT NOT NULL,
    endpoint TEXT NULL,
    public_key TEXT NULL,
    auth_token TEXT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'active',
    last_seen_at TIMESTAMP NULL DEFAULT NULL,
    disabled_at TIMESTAMP NULL DEFAULT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX ch_push_devices_business_idx (business_id, business_location_id),
    INDEX ch_push_devices_user_idx (user_id),
    INDEX ch_push_devices_status_idx (status),
    INDEX ch_push_devices_group_idx (device_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [35] communication_hub_push_subscriptions (source: 08_PUSH_NOTIFICATION_PLATFORM_UPGRADE.sql)
CREATE TABLE IF NOT EXISTS communication_hub_push_subscriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    business_location_id BIGINT UNSIGNED NULL,
    device_id BIGINT UNSIGNED NULL,
    subscription_type VARCHAR(100) NOT NULL DEFAULT 'general',
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX ch_push_subscriptions_business_idx (business_id, business_location_id),
    INDEX ch_push_subscriptions_device_idx (device_id),
    INDEX ch_push_subscriptions_type_idx (subscription_type, is_enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [36] communication_hub_push_templates (source: 08_PUSH_NOTIFICATION_PLATFORM_UPGRADE.sql)
CREATE TABLE IF NOT EXISTS communication_hub_push_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    business_location_id BIGINT UNSIGNED NULL,
    template_name VARCHAR(191) NOT NULL,
    category VARCHAR(50) NOT NULL DEFAULT 'general',
    title VARCHAR(191) NOT NULL,
    body TEXT NOT NULL,
    icon_url VARCHAR(1000) NULL,
    image_url VARCHAR(1000) NULL,
    action_url VARCHAR(1000) NULL,
    variables TEXT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'active',
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX ch_push_templates_business_idx (business_id, business_location_id),
    INDEX ch_push_templates_status_idx (status),
    INDEX ch_push_templates_category_idx (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [37] communication_hub_report_snapshots (source: 13_ANALYTICS_API_GATEWAY_FINAL_AUDIT.sql)
CREATE TABLE IF NOT EXISTS `communication_hub_report_snapshots` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `business_location_id` INT UNSIGNED NULL,
  `report_key` VARCHAR(191) NOT NULL,
  `report_name` VARCHAR(191) NOT NULL,
  `period_from` DATE NULL,
  `period_to` DATE NULL,
  `filters_json` LONGTEXT NULL,
  `summary_json` LONGTEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ch_report_snap_business_idx` (`business_id`),
  KEY `ch_report_snap_location_idx` (`business_location_id`),
  KEY `ch_report_snap_key_idx` (`report_key`),
  KEY `ch_report_snap_period_idx` (`period_from`,`period_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [38] communication_hub_sender_ids (source: 00_RUN_THIS_IN_EACH_TENANT_DATABASE.sql)
CREATE TABLE IF NOT EXISTS `communication_hub_sender_ids` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `client_id` BIGINT UNSIGNED NULL,
  `provider_id` BIGINT UNSIGNED NULL,
  `sender_id` VARCHAR(100) NOT NULL,
  `channel` VARCHAR(50) NOT NULL DEFAULT 'sms',
  `status` VARCHAR(30) NOT NULL DEFAULT 'active',
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ch_sender_business` (`business_id`),
  KEY `idx_ch_sender_client` (`client_id`),
  KEY `idx_ch_sender_provider` (`provider_id`),
  KEY `idx_ch_sender_id` (`sender_id`),
  KEY `idx_ch_sender_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [39] communication_hub_settings (source: 00_RUN_THIS_IN_EACH_TENANT_DATABASE.sql)
CREATE TABLE IF NOT EXISTS `communication_hub_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `group` VARCHAR(255) NULL,
  `key` VARCHAR(255) NOT NULL,
  `value` LONGTEXT NULL,
  `meta` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ch_setting_business` (`business_id`),
  KEY `idx_ch_setting_group` (`group`),
  KEY `idx_ch_setting_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [40] communication_hub_sms_packages (source: 00_RUN_THIS_IN_EACH_TENANT_DATABASE.sql)
CREATE TABLE IF NOT EXISTS `communication_hub_sms_packages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `code` VARCHAR(100) NULL,
  `name` VARCHAR(191) NOT NULL,
  `credits` INT UNSIGNED NOT NULL DEFAULT 0,
  `cost_price` DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
  `selling_price` DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
  `profit_amount` DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
  `validity_days` INT UNSIGNED NOT NULL DEFAULT 0,
  `description` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ch_sms_pkg_business` (`business_id`),
  KEY `idx_ch_sms_pkg_code` (`code`),
  KEY `idx_ch_sms_pkg_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [41] communication_hub_templates (source: 00_RUN_THIS_IN_EACH_TENANT_DATABASE.sql)
CREATE TABLE IF NOT EXISTS `communication_hub_templates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `code` VARCHAR(100) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `category` VARCHAR(100) NULL,
  `channel` VARCHAR(50) NULL,
  `channels` JSON NULL,
  `subject` VARCHAR(255) NULL,
  `body` LONGTEXT NULL,
  `content` LONGTEXT NULL,
  `placeholders` JSON NULL,
  `meta` JSON NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ch_tpl_business` (`business_id`),
  KEY `idx_ch_tpl_code` (`code`),
  KEY `idx_ch_tpl_category` (`category`),
  KEY `idx_ch_tpl_channel` (`channel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [42] communication_hub_wallet_transactions (source: 00_RUN_THIS_IN_EACH_TENANT_DATABASE.sql)
CREATE TABLE IF NOT EXISTS `communication_hub_wallet_transactions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `client_id` BIGINT UNSIGNED NULL,
  `wallet_id` BIGINT UNSIGNED NULL,
  `package_id` BIGINT UNSIGNED NULL,
  `type` VARCHAR(50) NOT NULL,
  `credits` INT NOT NULL DEFAULT 0,
  `opening_balance` INT NOT NULL DEFAULT 0,
  `closing_balance` INT NOT NULL DEFAULT 0,
  `amount` DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
  `profit_amount` DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
  `reference` VARCHAR(191) NULL,
  `note` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ch_wtxn_business` (`business_id`),
  KEY `idx_ch_wtxn_client` (`client_id`),
  KEY `idx_ch_wtxn_wallet` (`wallet_id`),
  KEY `idx_ch_wtxn_package` (`package_id`),
  KEY `idx_ch_wtxn_type` (`type`),
  KEY `idx_ch_wtxn_reference` (`reference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [43] communication_hub_wallets (source: 00_RUN_THIS_IN_EACH_TENANT_DATABASE.sql)
CREATE TABLE IF NOT EXISTS `communication_hub_wallets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `client_id` BIGINT UNSIGNED NOT NULL,
  `available_credits` INT NOT NULL DEFAULT 0,
  `reserved_credits` INT NOT NULL DEFAULT 0,
  `total_purchased` INT NOT NULL DEFAULT 0,
  `total_used` INT NOT NULL DEFAULT 0,
  `low_balance_alert_at` INT NULL,
  `expires_at` TIMESTAMP NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ch_wallet_client` (`client_id`),
  KEY `idx_ch_wallet_business` (`business_id`),
  KEY `idx_ch_wallet_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [44] communication_hub_whatsapp_campaigns (source: 07_WHATSAPP_BUSINESS_PLATFORM_UPGRADE.sql)
CREATE TABLE IF NOT EXISTS communication_hub_whatsapp_campaigns (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    business_location_id BIGINT UNSIGNED NULL,
    profile_id BIGINT UNSIGNED NULL,
    template_id BIGINT UNSIGNED NULL,
    campaign_name VARCHAR(191) NOT NULL,
    audience_source VARCHAR(100) NULL,
    recipient_count INT NOT NULL DEFAULT 0,
    queued_count INT NOT NULL DEFAULT 0,
    sent_count INT NOT NULL DEFAULT 0,
    failed_count INT NOT NULL DEFAULT 0,
    read_count INT NOT NULL DEFAULT 0,
    status VARCHAR(30) NOT NULL DEFAULT 'draft',
    scheduled_at TIMESTAMP NULL DEFAULT NULL,
    started_at TIMESTAMP NULL DEFAULT NULL,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX ch_wa_campaigns_business_idx (business_id, business_location_id),
    INDEX ch_wa_campaigns_status_idx (status),
    INDEX ch_wa_campaigns_schedule_idx (scheduled_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [45] communication_hub_whatsapp_profiles (source: 07_WHATSAPP_BUSINESS_PLATFORM_UPGRADE.sql)
CREATE TABLE IF NOT EXISTS communication_hub_whatsapp_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    business_location_id BIGINT UNSIGNED NULL,
    profile_name VARCHAR(191) NOT NULL,
    provider_name VARCHAR(100) NOT NULL DEFAULT 'meta_cloud_api',
    phone_number VARCHAR(30) NOT NULL,
    business_account_id VARCHAR(191) NULL,
    phone_number_id VARCHAR(191) NULL,
    api_base_url VARCHAR(500) NULL,
    access_token TEXT NULL,
    webhook_verify_token VARCHAR(191) NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    status VARCHAR(30) NOT NULL DEFAULT 'active',
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX ch_wa_profiles_business_idx (business_id, business_location_id),
    INDEX ch_wa_profiles_status_idx (status),
    INDEX ch_wa_profiles_default_idx (business_id, is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [46] communication_hub_whatsapp_templates (source: 07_WHATSAPP_BUSINESS_PLATFORM_UPGRADE.sql)
CREATE TABLE IF NOT EXISTS communication_hub_whatsapp_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    business_location_id BIGINT UNSIGNED NULL,
    provider_template_id VARCHAR(191) NULL,
    template_name VARCHAR(191) NOT NULL,
    category VARCHAR(50) NOT NULL DEFAULT 'utility',
    language_code VARCHAR(20) NOT NULL DEFAULT 'en',
    header_type VARCHAR(30) NULL,
    header_text VARCHAR(500) NULL,
    body TEXT NOT NULL,
    footer_text VARCHAR(500) NULL,
    variables TEXT NULL,
    buttons_json LONGTEXT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'draft',
    approved_at TIMESTAMP NULL DEFAULT NULL,
    rejected_reason TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX ch_wa_templates_business_idx (business_id, business_location_id),
    INDEX ch_wa_templates_status_idx (status),
    INDEX ch_wa_templates_name_idx (template_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [47] communication_hub_workflow_event_logs (source: 12_WORKFLOW_EVENT_ENGINE_UPGRADE.sql)
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

-- [48] communication_hub_workflow_events (source: 12_WORKFLOW_EVENT_ENGINE_UPGRADE.sql)
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

-- [49] communication_hub_workflow_rules (source: 12_WORKFLOW_EVENT_ENGINE_UPGRADE.sql)
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

-- [50] communication_rollout_notes (source: 20_OPERATIONS_SUPPORT_AND_FINAL_SIGNOFF.sql)
CREATE TABLE IF NOT EXISTS communication_rollout_notes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    note_type ENUM('deployment','testing','issue','resolution','handover') NOT NULL DEFAULT 'deployment',
    title VARCHAR(255) NOT NULL,
    note LONGTEXT NULL,
    severity ENUM('low','medium','high','critical') NOT NULL DEFAULT 'low',
    status ENUM('open','in_progress','closed') NOT NULL DEFAULT 'open',
    created_by BIGINT UNSIGNED NULL,
    closed_by BIGINT UNSIGNED NULL,
    closed_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ch_rollout_business (business_id),
    INDEX idx_ch_rollout_status (status, severity),
    INDEX idx_ch_rollout_type (note_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


SET FOREIGN_KEY_CHECKS=1;

-- End of Communication Hub complete table schema.
