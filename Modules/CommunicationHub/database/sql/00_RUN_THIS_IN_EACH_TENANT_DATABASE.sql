-- CommunicationHub v1.0 Production Tenant Schema
-- Run once in EACH tenant database. Safe for fresh tenants and existing partial CommunicationHub tenants.
-- MySQL 5.7+/8.0 compatible.

SET FOREIGN_KEY_CHECKS=0;

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
  KEY `idx_ch_msg_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ch_api_log_client` (`api_client_id`),
  KEY `idx_ch_api_log_business` (`business_id`),
  KEY `idx_ch_api_log_client_id` (`client_id`),
  KEY `idx_ch_api_log_module` (`module_name`),
  KEY `idx_ch_api_log_endpoint` (`endpoint`),
  KEY `idx_ch_api_log_status` (`status_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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



-- Compatibility note: deployment SQL intentionally uses plain CREATE TABLE / INSERT statements only for phpMyAdmin compatibility.
-- For a fresh tenant database, the CREATE TABLE statements below already include the required columns.

-- Seed internal API test client only if missing.
INSERT INTO `communication_hub_api_clients` (`module_name`, `client_name`, `token_hash`, `allowed_channels`, `permissions`, `is_active`, `created_at`, `updated_at`)
SELECT 'internal_test', 'Internal Test Client', SHA2('communicationhub-test-token', 256), JSON_ARRAY('sms','email','whatsapp','push'), JSON_ARRAY('send','otp','status','estimate'), 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `communication_hub_api_clients` WHERE `module_name` = 'internal_test');



-- Commercial/API compatibility note: fresh tenant databases receive all required columns directly in CREATE TABLE statements.
-- Fresh tenant databases receive all required columns in CREATE TABLE statements.

SET FOREIGN_KEY_CHECKS=1;
-- Communication Hub commercial SMS selling platform tables
-- Run after 00_RUN_THIS_IN_EACH_TENANT_DATABASE.sql if your previous CommunicationHub SQL was already installed.
-- Safe for existing tenants: all tables use CREATE TABLE IF NOT EXISTS.

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

-- Optional indexes are provided separately in 03_COMMUNICATION_HUB_INDEXES.sql.
