

-- ============================================================
-- 00_RUN_THIS_IN_EACH_TENANT_DATABASE.sql
-- ============================================================
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


-- ============================================================
-- 01_COMMUNICATION_HUB_PERMISSIONS.sql
-- ============================================================
-- Optional CommunicationHub permissions seed.
-- Run only if your tenant DB has a `permissions` table with `name`, `guard_name`, timestamps.
INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT permission_name, 'web', NOW(), NOW()
FROM (
  SELECT 'communicationhub.dashboard.view' AS permission_name UNION ALL
  SELECT 'communicationhub.providers.view' UNION ALL
  SELECT 'communicationhub.providers.create' UNION ALL
  SELECT 'communicationhub.providers.edit' UNION ALL
  SELECT 'communicationhub.providers.delete' UNION ALL
  SELECT 'communicationhub.templates.view' UNION ALL
  SELECT 'communicationhub.templates.create' UNION ALL
  SELECT 'communicationhub.templates.edit' UNION ALL
  SELECT 'communicationhub.templates.delete' UNION ALL
  SELECT 'communicationhub.queue.view' UNION ALL
  SELECT 'communicationhub.queue.process' UNION ALL
  SELECT 'communicationhub.queue.retry' UNION ALL
  SELECT 'communicationhub.otp.view' UNION ALL
  SELECT 'communicationhub.otp.generate' UNION ALL
  SELECT 'communicationhub.otp.verify' UNION ALL
  SELECT 'communicationhub.reports.view' UNION ALL
  SELECT 'communicationhub.settings.view' UNION ALL
  SELECT 'communicationhub.settings.edit' UNION ALL
  SELECT 'communicationhub.audit.view' UNION ALL
  SELECT 'communicationhub.production.view' UNION ALL
  SELECT 'communicationhub.marketplace.view' UNION ALL
  SELECT 'communicationhub.marketplace.install' UNION ALL
  SELECT 'communicationhub.marketplace.enable' UNION ALL
  SELECT 'communicationhub.marketplace.disable' UNION ALL
  SELECT 'communicationhub.marketplace.sandbox' UNION ALL
  SELECT 'communicationhub.certification.view' UNION ALL
  SELECT 'communicationhub.commercial.dashboard.view' UNION ALL
  SELECT 'communicationhub.commercial.sms_packages.view' UNION ALL
  SELECT 'communicationhub.commercial.sms_packages.create' UNION ALL
  SELECT 'communicationhub.commercial.clients.view' UNION ALL
  SELECT 'communicationhub.commercial.clients.create' UNION ALL
  SELECT 'communicationhub.commercial.wallets.view' UNION ALL
  SELECT 'communicationhub.commercial.refills.view' UNION ALL
  SELECT 'communicationhub.commercial.refills.create' UNION ALL
  SELECT 'communicationhub.commercial.transactions.view' UNION ALL
  SELECT 'communicationhub.commercial.send_sms' UNION ALL
  SELECT 'communicationhub.commercial.bulk_sms' UNION ALL
  SELECT 'communicationhub.commercial.api_tokens.view' UNION ALL
  SELECT 'communicationhub.commercial.api_tokens.create' UNION ALL
  SELECT 'communicationhub.commercial.delivery_reports.view' UNION ALL
  SELECT 'communicationhub.commercial.profit_reports.view'
) AS p
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = p.permission_name AND `guard_name` = 'web');


-- ============================================================
-- 02_COMMERCIAL_SMS_PLATFORM_TABLES.sql
-- ============================================================
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


-- ============================================================
-- 02_DEFAULT_DATA.sql
-- ============================================================
-- CommunicationHub default seed data placeholder.
-- Keep tenant-specific default providers, packages, sender IDs, and templates here.
-- Safe to leave empty until final gateway/provider details are confirmed.


-- ============================================================
-- 03_COMMUNICATION_HUB_INDEXES.sql
-- ============================================================
-- Communication Hub optional performance indexes
-- Run in each tenant database. Safe to skip if indexes already exist.
-- If your MySQL version reports duplicate key name, skip that index.

ALTER TABLE `communication_hub_messages` ADD INDEX `idx_commhub_messages_business_status` (`business_id`, `status`);
ALTER TABLE `communication_hub_messages` ADD INDEX `idx_commhub_messages_created` (`created_at`);
ALTER TABLE `communication_hub_wallet_transactions` ADD INDEX `idx_commhub_wallet_tx_client` (`client_id`);
ALTER TABLE `communication_hub_wallet_transactions` ADD INDEX `idx_commhub_wallet_tx_created` (`created_at`);
ALTER TABLE `communication_hub_wallets` ADD INDEX `idx_commhub_wallets_client` (`client_id`);


-- ============================================================
-- 03_INDEXES.sql
-- ============================================================
-- Optional CommunicationHub performance indexes.
-- Run after the main table creation SQL if report/query performance needs improvement.

CREATE INDEX IF NOT EXISTS idx_ch_messages_business_status ON communication_hub_messages (business_id, status);
CREATE INDEX IF NOT EXISTS idx_ch_messages_business_channel ON communication_hub_messages (business_id, channel);
CREATE INDEX IF NOT EXISTS idx_ch_messages_created_at ON communication_hub_messages (created_at);
CREATE INDEX IF NOT EXISTS idx_ch_delivery_events_message ON communication_hub_delivery_events (message_id);
CREATE INDEX IF NOT EXISTS idx_ch_templates_business_channel ON communication_hub_templates (business_id, channel);


-- ============================================================
-- 07_WHATSAPP_BUSINESS_PLATFORM_UPGRADE.sql
-- ============================================================
-- Communication Hub Stage 004: WhatsApp Business Platform
-- Run this inside EACH tenant database. No database name is specified because this ERP uses multiple tenant databases.

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



DELIMITER $$
DROP PROCEDURE IF EXISTS ch_add_column_if_missing $$
CREATE PROCEDURE ch_add_column_if_missing(IN p_table VARCHAR(128), IN p_column VARCHAR(128), IN p_definition TEXT)
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = p_table)
       AND NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = p_table AND column_name = p_column) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_column, '` ', p_definition);
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    END IF;
END $$

DROP PROCEDURE IF EXISTS ch_add_index_if_missing $$
CREATE PROCEDURE ch_add_index_if_missing(IN p_table VARCHAR(128), IN p_index VARCHAR(128), IN p_definition TEXT)
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = p_table)
       AND NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = p_table AND index_name = p_index) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD INDEX `', p_index, '` ', p_definition);
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    END IF;
END $$
DELIMITER ;

CALL ch_add_column_if_missing('communication_hub_messages', 'business_location_id', 'BIGINT UNSIGNED NULL AFTER `business_id`');
CALL ch_add_column_if_missing('communication_hub_messages', 'profile_id', 'BIGINT UNSIGNED NULL AFTER `client_id`');
CALL ch_add_column_if_missing('communication_hub_messages', 'campaign_id', 'BIGINT UNSIGNED NULL AFTER `profile_id`');
CALL ch_add_column_if_missing('communication_hub_messages', 'message_type', 'VARCHAR(30) NULL AFTER `channel`');
CALL ch_add_column_if_missing('communication_hub_messages', 'media_url', 'VARCHAR(1000) NULL AFTER `message`');
CALL ch_add_column_if_missing('communication_hub_messages', 'caption', 'VARCHAR(1000) NULL AFTER `media_url`');
CALL ch_add_column_if_missing('communication_hub_messages', 'provider_message_id', 'VARCHAR(191) NULL AFTER `response_message`');
CALL ch_add_column_if_missing('communication_hub_messages', 'delivered_at', 'TIMESTAMP NULL DEFAULT NULL AFTER `sent_at`');
CALL ch_add_column_if_missing('communication_hub_messages', 'read_at', 'TIMESTAMP NULL DEFAULT NULL AFTER `delivered_at`');

CALL ch_add_index_if_missing('communication_hub_messages', 'ch_messages_wa_business_idx', '(`business_id`, `business_location_id`, `channel`, `status`)');
CALL ch_add_index_if_missing('communication_hub_messages', 'ch_messages_wa_profile_idx', '(`profile_id`, `channel`, `status`)');
CALL ch_add_index_if_missing('communication_hub_messages', 'ch_messages_wa_campaign_idx', '(`campaign_id`, `channel`, `status`)');

DROP PROCEDURE IF EXISTS ch_add_column_if_missing;
DROP PROCEDURE IF EXISTS ch_add_index_if_missing;

-- Optional draft default profile creation when a tenant has a businesses table.
DELIMITER $$
DROP PROCEDURE IF EXISTS ch_seed_default_whatsapp_profiles $$
CREATE PROCEDURE ch_seed_default_whatsapp_profiles()
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'businesses') THEN
        INSERT INTO communication_hub_whatsapp_profiles
            (business_id, business_location_id, profile_name, provider_name, phone_number, is_default, status, created_at, updated_at)
        SELECT b.id, NULL, CONCAT('Default WhatsApp - ', COALESCE(b.name, b.id)), 'meta_cloud_api', '', 1, 'draft', NOW(), NOW()
        FROM businesses b
        WHERE NOT EXISTS (
            SELECT 1 FROM communication_hub_whatsapp_profiles p WHERE p.business_id = b.id
        )
        LIMIT 500;
    END IF;
END $$
DELIMITER ;
CALL ch_seed_default_whatsapp_profiles();
DROP PROCEDURE IF EXISTS ch_seed_default_whatsapp_profiles;


-- ============================================================
-- 08_PUSH_NOTIFICATION_PLATFORM_UPGRADE.sql
-- ============================================================
-- Communication Hub Stage 005: Push Notifications Platform
-- Run this inside EACH tenant database. No database name is specified because this ERP uses multiple tenant databases.

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

DELIMITER $$
DROP PROCEDURE IF EXISTS ch_add_column_if_missing $$
CREATE PROCEDURE ch_add_column_if_missing(IN p_table VARCHAR(128), IN p_column VARCHAR(128), IN p_definition TEXT)
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = p_table)
       AND NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = p_table AND column_name = p_column) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_column, '` ', p_definition);
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    END IF;
END $$

DROP PROCEDURE IF EXISTS ch_add_index_if_missing $$
CREATE PROCEDURE ch_add_index_if_missing(IN p_table VARCHAR(128), IN p_index VARCHAR(128), IN p_definition TEXT)
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = p_table)
       AND NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = p_table AND index_name = p_index) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD INDEX `', p_index, '` ', p_definition);
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    END IF;
END $$
DELIMITER ;

CALL ch_add_column_if_missing('communication_hub_messages', 'business_location_id', 'BIGINT UNSIGNED NULL AFTER `business_id`');
CALL ch_add_column_if_missing('communication_hub_messages', 'message_type', 'VARCHAR(30) NULL AFTER `channel`');
CALL ch_add_column_if_missing('communication_hub_messages', 'subject', 'VARCHAR(191) NULL AFTER `recipient`');
CALL ch_add_column_if_missing('communication_hub_messages', 'title', 'VARCHAR(191) NULL AFTER `subject`');
CALL ch_add_column_if_missing('communication_hub_messages', 'action_url', 'VARCHAR(1000) NULL AFTER `caption`');
CALL ch_add_column_if_missing('communication_hub_messages', 'icon_url', 'VARCHAR(1000) NULL AFTER `action_url`');
CALL ch_add_column_if_missing('communication_hub_messages', 'image_url', 'VARCHAR(1000) NULL AFTER `icon_url`');
CALL ch_add_column_if_missing('communication_hub_messages', 'opened_at', 'TIMESTAMP NULL DEFAULT NULL AFTER `read_at`');

CALL ch_add_column_if_missing('communication_hub_delivery_events', 'event_type', 'VARCHAR(50) NULL AFTER `event`');
CALL ch_add_column_if_missing('communication_hub_delivery_events', 'business_id', 'BIGINT UNSIGNED NULL AFTER `id`');
CALL ch_add_column_if_missing('communication_hub_delivery_events', 'business_location_id', 'BIGINT UNSIGNED NULL AFTER `business_id`');

CALL ch_add_index_if_missing('communication_hub_messages', 'ch_messages_push_business_idx', '(`business_id`, `business_location_id`, `channel`, `status`)');
CALL ch_add_index_if_missing('communication_hub_messages', 'ch_messages_push_schedule_idx', '(`channel`, `status`, `scheduled_at`)');
CALL ch_add_index_if_missing('communication_hub_delivery_events', 'ch_delivery_events_push_idx', '(`business_id`, `event_type`)');

DROP PROCEDURE IF EXISTS ch_add_column_if_missing;
DROP PROCEDURE IF EXISTS ch_add_index_if_missing;

-- Optional default templates for each tenant. Business-specific templates can be added from the UI.
INSERT INTO communication_hub_push_templates (business_id, business_location_id, template_name, category, title, body, action_url, variables, status, created_at, updated_at)
SELECT NULL, NULL, 'Invoice Posted Alert', 'finance', 'Invoice Posted', 'Invoice {invoice_no} has been posted for {customer_name}.', NULL, '{invoice_no},{customer_name}', 'active', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM communication_hub_push_templates WHERE template_name = 'Invoice Posted Alert');

INSERT INTO communication_hub_push_templates (business_id, business_location_id, template_name, category, title, body, action_url, variables, status, created_at, updated_at)
SELECT NULL, NULL, 'Approval Required Alert', 'approval', 'Approval Required', '{module_name} requires your approval.', NULL, '{module_name}', 'active', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM communication_hub_push_templates WHERE template_name = 'Approval Required Alert');


-- ============================================================
-- 09_IN_APP_NOTIFICATION_CENTRE_UPGRADE.sql
-- ============================================================
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


-- ============================================================
-- 12_WORKFLOW_EVENT_ENGINE_UPGRADE.sql
-- ============================================================
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


-- ============================================================
-- 13_ANALYTICS_API_GATEWAY_FINAL_AUDIT.sql
-- ============================================================
-- Communication Hub Final Milestone - Analytics, Reports, API Gateway and Audit Centre
-- Run inside EACH tenant database. Do NOT include a database name.

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

ALTER TABLE `communication_hub_messages`
  ADD COLUMN IF NOT EXISTS `api_request_id` BIGINT UNSIGNED NULL AFTER `client_id`,
  ADD COLUMN IF NOT EXISTS `delivery_latency_ms` INT UNSIGNED NULL AFTER `sent_at`,
  ADD COLUMN IF NOT EXISTS `opened_at` TIMESTAMP NULL DEFAULT NULL AFTER `delivered_at`,
  ADD COLUMN IF NOT EXISTS `read_at` TIMESTAMP NULL DEFAULT NULL AFTER `opened_at`;

ALTER TABLE `communication_hub_api_request_logs`
  ADD COLUMN IF NOT EXISTS `business_id` INT UNSIGNED NULL AFTER `id`,
  ADD COLUMN IF NOT EXISTS `business_location_id` INT UNSIGNED NULL AFTER `business_id`,
  ADD COLUMN IF NOT EXISTS `channel` VARCHAR(50) NULL AFTER `business_location_id`,
  ADD COLUMN IF NOT EXISTS `event_code` VARCHAR(191) NULL AFTER `channel`,
  ADD COLUMN IF NOT EXISTS `response_time_ms` INT UNSIGNED NULL AFTER `response_status`;

CREATE INDEX IF NOT EXISTS `ch_msg_api_req_idx` ON `communication_hub_messages` (`api_request_id`);
CREATE INDEX IF NOT EXISTS `ch_msg_channel_status_idx` ON `communication_hub_messages` (`channel`,`status`);
CREATE INDEX IF NOT EXISTS `ch_msg_business_created_idx` ON `communication_hub_messages` (`business_id`,`created_at`);
CREATE INDEX IF NOT EXISTS `ch_api_log_business_created_idx` ON `communication_hub_api_request_logs` (`business_id`,`created_at`);
CREATE INDEX IF NOT EXISTS `ch_api_log_channel_idx` ON `communication_hub_api_request_logs` (`channel`);

INSERT IGNORE INTO `communication_hub_final_audit_checks`
(`business_id`, `check_key`, `check_name`, `category`, `status`, `severity`, `created_at`, `updated_at`) VALUES
(NULL, 'tenant_scope', 'Tenant database scope verified', 'Architecture', 'pending', 'critical', NOW(), NOW()),
(NULL, 'business_scope', 'Business and location scope verified', 'Architecture', 'pending', 'critical', NOW(), NOW()),
(NULL, 'permissions', 'Communication Hub permissions verified', 'Security', 'pending', 'critical', NOW(), NOW()),
(NULL, 'queue_indexes', 'Queue and report indexes verified', 'Performance', 'pending', 'normal', NOW(), NOW()),
(NULL, 'provider_failover', 'Provider failover path verified', 'Reliability', 'pending', 'normal', NOW(), NOW()),
(NULL, 'ui_standard', 'ERP UI standard verified', 'UI', 'pending', 'normal', NOW(), NOW());


-- ============================================================
-- 99_ROLLBACK.sql
-- ============================================================
-- CommunicationHub rollback SQL.
-- WARNING: This removes CommunicationHub tenant data. Use only after a full backup.

DROP TABLE IF EXISTS communication_hub_api_request_logs;
DROP TABLE IF EXISTS communication_hub_api_clients;
DROP TABLE IF EXISTS communication_hub_marketplace_packages;
DROP TABLE IF EXISTS communication_hub_campaigns;
DROP TABLE IF EXISTS communication_hub_audit_logs;
DROP TABLE IF EXISTS communication_hub_otps;
DROP TABLE IF EXISTS communication_hub_delivery_events;
DROP TABLE IF EXISTS communication_hub_messages;
DROP TABLE IF EXISTS communication_hub_templates;
DROP TABLE IF EXISTS communication_hub_providers;
DROP TABLE IF EXISTS communication_hub_settings;


-- ============================================================
-- MASTER_ALL_COMMUNICATION_HUB_SQL_STAGE_005.sql
-- ============================================================
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
-- Optional CommunicationHub permissions seed.
-- Run only if your tenant DB has a `permissions` table with `name`, `guard_name`, timestamps.
INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT permission_name, 'web', NOW(), NOW()
FROM (
  SELECT 'communicationhub.dashboard.view' AS permission_name UNION ALL
  SELECT 'communicationhub.providers.view' UNION ALL
  SELECT 'communicationhub.providers.create' UNION ALL
  SELECT 'communicationhub.providers.edit' UNION ALL
  SELECT 'communicationhub.providers.delete' UNION ALL
  SELECT 'communicationhub.templates.view' UNION ALL
  SELECT 'communicationhub.templates.create' UNION ALL
  SELECT 'communicationhub.templates.edit' UNION ALL
  SELECT 'communicationhub.templates.delete' UNION ALL
  SELECT 'communicationhub.queue.view' UNION ALL
  SELECT 'communicationhub.queue.process' UNION ALL
  SELECT 'communicationhub.queue.retry' UNION ALL
  SELECT 'communicationhub.otp.view' UNION ALL
  SELECT 'communicationhub.otp.generate' UNION ALL
  SELECT 'communicationhub.otp.verify' UNION ALL
  SELECT 'communicationhub.reports.view' UNION ALL
  SELECT 'communicationhub.settings.view' UNION ALL
  SELECT 'communicationhub.settings.edit' UNION ALL
  SELECT 'communicationhub.audit.view' UNION ALL
  SELECT 'communicationhub.production.view' UNION ALL
  SELECT 'communicationhub.marketplace.view' UNION ALL
  SELECT 'communicationhub.marketplace.install' UNION ALL
  SELECT 'communicationhub.marketplace.enable' UNION ALL
  SELECT 'communicationhub.marketplace.disable' UNION ALL
  SELECT 'communicationhub.marketplace.sandbox' UNION ALL
  SELECT 'communicationhub.certification.view' UNION ALL
  SELECT 'communicationhub.commercial.dashboard.view' UNION ALL
  SELECT 'communicationhub.commercial.sms_packages.view' UNION ALL
  SELECT 'communicationhub.commercial.sms_packages.create' UNION ALL
  SELECT 'communicationhub.commercial.clients.view' UNION ALL
  SELECT 'communicationhub.commercial.clients.create' UNION ALL
  SELECT 'communicationhub.commercial.wallets.view' UNION ALL
  SELECT 'communicationhub.commercial.refills.view' UNION ALL
  SELECT 'communicationhub.commercial.refills.create' UNION ALL
  SELECT 'communicationhub.commercial.transactions.view' UNION ALL
  SELECT 'communicationhub.commercial.send_sms' UNION ALL
  SELECT 'communicationhub.commercial.bulk_sms' UNION ALL
  SELECT 'communicationhub.commercial.api_tokens.view' UNION ALL
  SELECT 'communicationhub.commercial.api_tokens.create' UNION ALL
  SELECT 'communicationhub.commercial.delivery_reports.view' UNION ALL
  SELECT 'communicationhub.commercial.profit_reports.view'
) AS p
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = p.permission_name AND `guard_name` = 'web');
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
-- CommunicationHub default seed data placeholder.
-- Keep tenant-specific default providers, packages, sender IDs, and templates here.
-- Safe to leave empty until final gateway/provider details are confirmed.
-- Communication Hub optional performance indexes
-- Run in each tenant database. Safe to skip if indexes already exist.
-- If your MySQL version reports duplicate key name, skip that index.

ALTER TABLE `communication_hub_messages` ADD INDEX `idx_commhub_messages_business_status` (`business_id`, `status`);
ALTER TABLE `communication_hub_messages` ADD INDEX `idx_commhub_messages_created` (`created_at`);
ALTER TABLE `communication_hub_wallet_transactions` ADD INDEX `idx_commhub_wallet_tx_client` (`client_id`);
ALTER TABLE `communication_hub_wallet_transactions` ADD INDEX `idx_commhub_wallet_tx_created` (`created_at`);
ALTER TABLE `communication_hub_wallets` ADD INDEX `idx_commhub_wallets_client` (`client_id`);
-- Optional CommunicationHub performance indexes.
-- Run after the main table creation SQL if report/query performance needs improvement.

CREATE INDEX IF NOT EXISTS idx_ch_messages_business_status ON communication_hub_messages (business_id, status);
CREATE INDEX IF NOT EXISTS idx_ch_messages_business_channel ON communication_hub_messages (business_id, channel);
CREATE INDEX IF NOT EXISTS idx_ch_messages_created_at ON communication_hub_messages (created_at);
CREATE INDEX IF NOT EXISTS idx_ch_delivery_events_message ON communication_hub_delivery_events (message_id);
CREATE INDEX IF NOT EXISTS idx_ch_templates_business_channel ON communication_hub_templates (business_id, channel);
-- Communication Hub Stage 004: WhatsApp Business Platform
-- Run this inside EACH tenant database. No database name is specified because this ERP uses multiple tenant databases.

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



DELIMITER $$
DROP PROCEDURE IF EXISTS ch_add_column_if_missing $$
CREATE PROCEDURE ch_add_column_if_missing(IN p_table VARCHAR(128), IN p_column VARCHAR(128), IN p_definition TEXT)
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = p_table)
       AND NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = p_table AND column_name = p_column) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_column, '` ', p_definition);
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    END IF;
END $$

DROP PROCEDURE IF EXISTS ch_add_index_if_missing $$
CREATE PROCEDURE ch_add_index_if_missing(IN p_table VARCHAR(128), IN p_index VARCHAR(128), IN p_definition TEXT)
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = p_table)
       AND NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = p_table AND index_name = p_index) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD INDEX `', p_index, '` ', p_definition);
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    END IF;
END $$
DELIMITER ;

CALL ch_add_column_if_missing('communication_hub_messages', 'business_location_id', 'BIGINT UNSIGNED NULL AFTER `business_id`');
CALL ch_add_column_if_missing('communication_hub_messages', 'profile_id', 'BIGINT UNSIGNED NULL AFTER `client_id`');
CALL ch_add_column_if_missing('communication_hub_messages', 'campaign_id', 'BIGINT UNSIGNED NULL AFTER `profile_id`');
CALL ch_add_column_if_missing('communication_hub_messages', 'message_type', 'VARCHAR(30) NULL AFTER `channel`');
CALL ch_add_column_if_missing('communication_hub_messages', 'media_url', 'VARCHAR(1000) NULL AFTER `message`');
CALL ch_add_column_if_missing('communication_hub_messages', 'caption', 'VARCHAR(1000) NULL AFTER `media_url`');
CALL ch_add_column_if_missing('communication_hub_messages', 'provider_message_id', 'VARCHAR(191) NULL AFTER `response_message`');
CALL ch_add_column_if_missing('communication_hub_messages', 'delivered_at', 'TIMESTAMP NULL DEFAULT NULL AFTER `sent_at`');
CALL ch_add_column_if_missing('communication_hub_messages', 'read_at', 'TIMESTAMP NULL DEFAULT NULL AFTER `delivered_at`');

CALL ch_add_index_if_missing('communication_hub_messages', 'ch_messages_wa_business_idx', '(`business_id`, `business_location_id`, `channel`, `status`)');
CALL ch_add_index_if_missing('communication_hub_messages', 'ch_messages_wa_profile_idx', '(`profile_id`, `channel`, `status`)');
CALL ch_add_index_if_missing('communication_hub_messages', 'ch_messages_wa_campaign_idx', '(`campaign_id`, `channel`, `status`)');

DROP PROCEDURE IF EXISTS ch_add_column_if_missing;
DROP PROCEDURE IF EXISTS ch_add_index_if_missing;

-- Optional draft default profile creation when a tenant has a businesses table.
DELIMITER $$
DROP PROCEDURE IF EXISTS ch_seed_default_whatsapp_profiles $$
CREATE PROCEDURE ch_seed_default_whatsapp_profiles()
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'businesses') THEN
        INSERT INTO communication_hub_whatsapp_profiles
            (business_id, business_location_id, profile_name, provider_name, phone_number, is_default, status, created_at, updated_at)
        SELECT b.id, NULL, CONCAT('Default WhatsApp - ', COALESCE(b.name, b.id)), 'meta_cloud_api', '', 1, 'draft', NOW(), NOW()
        FROM businesses b
        WHERE NOT EXISTS (
            SELECT 1 FROM communication_hub_whatsapp_profiles p WHERE p.business_id = b.id
        )
        LIMIT 500;
    END IF;
END $$
DELIMITER ;
CALL ch_seed_default_whatsapp_profiles();
DROP PROCEDURE IF EXISTS ch_seed_default_whatsapp_profiles;
-- Communication Hub Stage 005: Push Notifications Platform
-- Run this inside EACH tenant database. No database name is specified because this ERP uses multiple tenant databases.

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

DELIMITER $$
DROP PROCEDURE IF EXISTS ch_add_column_if_missing $$
CREATE PROCEDURE ch_add_column_if_missing(IN p_table VARCHAR(128), IN p_column VARCHAR(128), IN p_definition TEXT)
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = p_table)
       AND NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = p_table AND column_name = p_column) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_column, '` ', p_definition);
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    END IF;
END $$

DROP PROCEDURE IF EXISTS ch_add_index_if_missing $$
CREATE PROCEDURE ch_add_index_if_missing(IN p_table VARCHAR(128), IN p_index VARCHAR(128), IN p_definition TEXT)
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = p_table)
       AND NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = p_table AND index_name = p_index) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD INDEX `', p_index, '` ', p_definition);
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    END IF;
END $$
DELIMITER ;

CALL ch_add_column_if_missing('communication_hub_messages', 'business_location_id', 'BIGINT UNSIGNED NULL AFTER `business_id`');
CALL ch_add_column_if_missing('communication_hub_messages', 'message_type', 'VARCHAR(30) NULL AFTER `channel`');
CALL ch_add_column_if_missing('communication_hub_messages', 'subject', 'VARCHAR(191) NULL AFTER `recipient`');
CALL ch_add_column_if_missing('communication_hub_messages', 'title', 'VARCHAR(191) NULL AFTER `subject`');
CALL ch_add_column_if_missing('communication_hub_messages', 'action_url', 'VARCHAR(1000) NULL AFTER `caption`');
CALL ch_add_column_if_missing('communication_hub_messages', 'icon_url', 'VARCHAR(1000) NULL AFTER `action_url`');
CALL ch_add_column_if_missing('communication_hub_messages', 'image_url', 'VARCHAR(1000) NULL AFTER `icon_url`');
CALL ch_add_column_if_missing('communication_hub_messages', 'opened_at', 'TIMESTAMP NULL DEFAULT NULL AFTER `read_at`');

CALL ch_add_column_if_missing('communication_hub_delivery_events', 'event_type', 'VARCHAR(50) NULL AFTER `event`');
CALL ch_add_column_if_missing('communication_hub_delivery_events', 'business_id', 'BIGINT UNSIGNED NULL AFTER `id`');
CALL ch_add_column_if_missing('communication_hub_delivery_events', 'business_location_id', 'BIGINT UNSIGNED NULL AFTER `business_id`');

CALL ch_add_index_if_missing('communication_hub_messages', 'ch_messages_push_business_idx', '(`business_id`, `business_location_id`, `channel`, `status`)');
CALL ch_add_index_if_missing('communication_hub_messages', 'ch_messages_push_schedule_idx', '(`channel`, `status`, `scheduled_at`)');
CALL ch_add_index_if_missing('communication_hub_delivery_events', 'ch_delivery_events_push_idx', '(`business_id`, `event_type`)');

DROP PROCEDURE IF EXISTS ch_add_column_if_missing;
DROP PROCEDURE IF EXISTS ch_add_index_if_missing;

-- Optional default templates for each tenant. Business-specific templates can be added from the UI.
INSERT INTO communication_hub_push_templates (business_id, business_location_id, template_name, category, title, body, action_url, variables, status, created_at, updated_at)
SELECT NULL, NULL, 'Invoice Posted Alert', 'finance', 'Invoice Posted', 'Invoice {invoice_no} has been posted for {customer_name}.', NULL, '{invoice_no},{customer_name}', 'active', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM communication_hub_push_templates WHERE template_name = 'Invoice Posted Alert');

INSERT INTO communication_hub_push_templates (business_id, business_location_id, template_name, category, title, body, action_url, variables, status, created_at, updated_at)
SELECT NULL, NULL, 'Approval Required Alert', 'approval', 'Approval Required', '{module_name} requires your approval.', NULL, '{module_name}', 'active', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM communication_hub_push_templates WHERE template_name = 'Approval Required Alert');
-- CommunicationHub rollback SQL.
-- WARNING: This removes CommunicationHub tenant data. Use only after a full backup.

DROP TABLE IF EXISTS communication_hub_api_request_logs;
DROP TABLE IF EXISTS communication_hub_api_clients;
DROP TABLE IF EXISTS communication_hub_marketplace_packages;
DROP TABLE IF EXISTS communication_hub_campaigns;
DROP TABLE IF EXISTS communication_hub_audit_logs;
DROP TABLE IF EXISTS communication_hub_otps;
DROP TABLE IF EXISTS communication_hub_delivery_events;
DROP TABLE IF EXISTS communication_hub_messages;
DROP TABLE IF EXISTS communication_hub_templates;
DROP TABLE IF EXISTS communication_hub_providers;
DROP TABLE IF EXISTS communication_hub_settings;


-- ============================================================
-- MASTER_ALL_COMMUNICATION_HUB_SQL_STAGE_006.sql
-- ============================================================
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
-- Optional CommunicationHub permissions seed.
-- Run only if your tenant DB has a `permissions` table with `name`, `guard_name`, timestamps.
INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT permission_name, 'web', NOW(), NOW()
FROM (
  SELECT 'communicationhub.dashboard.view' AS permission_name UNION ALL
  SELECT 'communicationhub.providers.view' UNION ALL
  SELECT 'communicationhub.providers.create' UNION ALL
  SELECT 'communicationhub.providers.edit' UNION ALL
  SELECT 'communicationhub.providers.delete' UNION ALL
  SELECT 'communicationhub.templates.view' UNION ALL
  SELECT 'communicationhub.templates.create' UNION ALL
  SELECT 'communicationhub.templates.edit' UNION ALL
  SELECT 'communicationhub.templates.delete' UNION ALL
  SELECT 'communicationhub.queue.view' UNION ALL
  SELECT 'communicationhub.queue.process' UNION ALL
  SELECT 'communicationhub.queue.retry' UNION ALL
  SELECT 'communicationhub.otp.view' UNION ALL
  SELECT 'communicationhub.otp.generate' UNION ALL
  SELECT 'communicationhub.otp.verify' UNION ALL
  SELECT 'communicationhub.reports.view' UNION ALL
  SELECT 'communicationhub.settings.view' UNION ALL
  SELECT 'communicationhub.settings.edit' UNION ALL
  SELECT 'communicationhub.audit.view' UNION ALL
  SELECT 'communicationhub.production.view' UNION ALL
  SELECT 'communicationhub.marketplace.view' UNION ALL
  SELECT 'communicationhub.marketplace.install' UNION ALL
  SELECT 'communicationhub.marketplace.enable' UNION ALL
  SELECT 'communicationhub.marketplace.disable' UNION ALL
  SELECT 'communicationhub.marketplace.sandbox' UNION ALL
  SELECT 'communicationhub.certification.view' UNION ALL
  SELECT 'communicationhub.commercial.dashboard.view' UNION ALL
  SELECT 'communicationhub.commercial.sms_packages.view' UNION ALL
  SELECT 'communicationhub.commercial.sms_packages.create' UNION ALL
  SELECT 'communicationhub.commercial.clients.view' UNION ALL
  SELECT 'communicationhub.commercial.clients.create' UNION ALL
  SELECT 'communicationhub.commercial.wallets.view' UNION ALL
  SELECT 'communicationhub.commercial.refills.view' UNION ALL
  SELECT 'communicationhub.commercial.refills.create' UNION ALL
  SELECT 'communicationhub.commercial.transactions.view' UNION ALL
  SELECT 'communicationhub.commercial.send_sms' UNION ALL
  SELECT 'communicationhub.commercial.bulk_sms' UNION ALL
  SELECT 'communicationhub.commercial.api_tokens.view' UNION ALL
  SELECT 'communicationhub.commercial.api_tokens.create' UNION ALL
  SELECT 'communicationhub.commercial.delivery_reports.view' UNION ALL
  SELECT 'communicationhub.commercial.profit_reports.view'
) AS p
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = p.permission_name AND `guard_name` = 'web');
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
-- CommunicationHub default seed data placeholder.
-- Keep tenant-specific default providers, packages, sender IDs, and templates here.
-- Safe to leave empty until final gateway/provider details are confirmed.
-- Communication Hub optional performance indexes
-- Run in each tenant database. Safe to skip if indexes already exist.
-- If your MySQL version reports duplicate key name, skip that index.

ALTER TABLE `communication_hub_messages` ADD INDEX `idx_commhub_messages_business_status` (`business_id`, `status`);
ALTER TABLE `communication_hub_messages` ADD INDEX `idx_commhub_messages_created` (`created_at`);
ALTER TABLE `communication_hub_wallet_transactions` ADD INDEX `idx_commhub_wallet_tx_client` (`client_id`);
ALTER TABLE `communication_hub_wallet_transactions` ADD INDEX `idx_commhub_wallet_tx_created` (`created_at`);
ALTER TABLE `communication_hub_wallets` ADD INDEX `idx_commhub_wallets_client` (`client_id`);
-- Optional CommunicationHub performance indexes.
-- Run after the main table creation SQL if report/query performance needs improvement.

CREATE INDEX IF NOT EXISTS idx_ch_messages_business_status ON communication_hub_messages (business_id, status);
CREATE INDEX IF NOT EXISTS idx_ch_messages_business_channel ON communication_hub_messages (business_id, channel);
CREATE INDEX IF NOT EXISTS idx_ch_messages_created_at ON communication_hub_messages (created_at);
CREATE INDEX IF NOT EXISTS idx_ch_delivery_events_message ON communication_hub_delivery_events (message_id);
CREATE INDEX IF NOT EXISTS idx_ch_templates_business_channel ON communication_hub_templates (business_id, channel);
-- Communication Hub Stage 004: WhatsApp Business Platform
-- Run this inside EACH tenant database. No database name is specified because this ERP uses multiple tenant databases.

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



DELIMITER $$
DROP PROCEDURE IF EXISTS ch_add_column_if_missing $$
CREATE PROCEDURE ch_add_column_if_missing(IN p_table VARCHAR(128), IN p_column VARCHAR(128), IN p_definition TEXT)
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = p_table)
       AND NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = p_table AND column_name = p_column) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_column, '` ', p_definition);
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    END IF;
END $$

DROP PROCEDURE IF EXISTS ch_add_index_if_missing $$
CREATE PROCEDURE ch_add_index_if_missing(IN p_table VARCHAR(128), IN p_index VARCHAR(128), IN p_definition TEXT)
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = p_table)
       AND NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = p_table AND index_name = p_index) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD INDEX `', p_index, '` ', p_definition);
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    END IF;
END $$
DELIMITER ;

CALL ch_add_column_if_missing('communication_hub_messages', 'business_location_id', 'BIGINT UNSIGNED NULL AFTER `business_id`');
CALL ch_add_column_if_missing('communication_hub_messages', 'profile_id', 'BIGINT UNSIGNED NULL AFTER `client_id`');
CALL ch_add_column_if_missing('communication_hub_messages', 'campaign_id', 'BIGINT UNSIGNED NULL AFTER `profile_id`');
CALL ch_add_column_if_missing('communication_hub_messages', 'message_type', 'VARCHAR(30) NULL AFTER `channel`');
CALL ch_add_column_if_missing('communication_hub_messages', 'media_url', 'VARCHAR(1000) NULL AFTER `message`');
CALL ch_add_column_if_missing('communication_hub_messages', 'caption', 'VARCHAR(1000) NULL AFTER `media_url`');
CALL ch_add_column_if_missing('communication_hub_messages', 'provider_message_id', 'VARCHAR(191) NULL AFTER `response_message`');
CALL ch_add_column_if_missing('communication_hub_messages', 'delivered_at', 'TIMESTAMP NULL DEFAULT NULL AFTER `sent_at`');
CALL ch_add_column_if_missing('communication_hub_messages', 'read_at', 'TIMESTAMP NULL DEFAULT NULL AFTER `delivered_at`');

CALL ch_add_index_if_missing('communication_hub_messages', 'ch_messages_wa_business_idx', '(`business_id`, `business_location_id`, `channel`, `status`)');
CALL ch_add_index_if_missing('communication_hub_messages', 'ch_messages_wa_profile_idx', '(`profile_id`, `channel`, `status`)');
CALL ch_add_index_if_missing('communication_hub_messages', 'ch_messages_wa_campaign_idx', '(`campaign_id`, `channel`, `status`)');

DROP PROCEDURE IF EXISTS ch_add_column_if_missing;
DROP PROCEDURE IF EXISTS ch_add_index_if_missing;

-- Optional draft default profile creation when a tenant has a businesses table.
DELIMITER $$
DROP PROCEDURE IF EXISTS ch_seed_default_whatsapp_profiles $$
CREATE PROCEDURE ch_seed_default_whatsapp_profiles()
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'businesses') THEN
        INSERT INTO communication_hub_whatsapp_profiles
            (business_id, business_location_id, profile_name, provider_name, phone_number, is_default, status, created_at, updated_at)
        SELECT b.id, NULL, CONCAT('Default WhatsApp - ', COALESCE(b.name, b.id)), 'meta_cloud_api', '', 1, 'draft', NOW(), NOW()
        FROM businesses b
        WHERE NOT EXISTS (
            SELECT 1 FROM communication_hub_whatsapp_profiles p WHERE p.business_id = b.id
        )
        LIMIT 500;
    END IF;
END $$
DELIMITER ;
CALL ch_seed_default_whatsapp_profiles();
DROP PROCEDURE IF EXISTS ch_seed_default_whatsapp_profiles;
-- Communication Hub Stage 005: Push Notifications Platform
-- Run this inside EACH tenant database. No database name is specified because this ERP uses multiple tenant databases.

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

DELIMITER $$
DROP PROCEDURE IF EXISTS ch_add_column_if_missing $$
CREATE PROCEDURE ch_add_column_if_missing(IN p_table VARCHAR(128), IN p_column VARCHAR(128), IN p_definition TEXT)
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = p_table)
       AND NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = p_table AND column_name = p_column) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_column, '` ', p_definition);
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    END IF;
END $$

DROP PROCEDURE IF EXISTS ch_add_index_if_missing $$
CREATE PROCEDURE ch_add_index_if_missing(IN p_table VARCHAR(128), IN p_index VARCHAR(128), IN p_definition TEXT)
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = p_table)
       AND NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = p_table AND index_name = p_index) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD INDEX `', p_index, '` ', p_definition);
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    END IF;
END $$
DELIMITER ;

CALL ch_add_column_if_missing('communication_hub_messages', 'business_location_id', 'BIGINT UNSIGNED NULL AFTER `business_id`');
CALL ch_add_column_if_missing('communication_hub_messages', 'message_type', 'VARCHAR(30) NULL AFTER `channel`');
CALL ch_add_column_if_missing('communication_hub_messages', 'subject', 'VARCHAR(191) NULL AFTER `recipient`');
CALL ch_add_column_if_missing('communication_hub_messages', 'title', 'VARCHAR(191) NULL AFTER `subject`');
CALL ch_add_column_if_missing('communication_hub_messages', 'action_url', 'VARCHAR(1000) NULL AFTER `caption`');
CALL ch_add_column_if_missing('communication_hub_messages', 'icon_url', 'VARCHAR(1000) NULL AFTER `action_url`');
CALL ch_add_column_if_missing('communication_hub_messages', 'image_url', 'VARCHAR(1000) NULL AFTER `icon_url`');
CALL ch_add_column_if_missing('communication_hub_messages', 'opened_at', 'TIMESTAMP NULL DEFAULT NULL AFTER `read_at`');

CALL ch_add_column_if_missing('communication_hub_delivery_events', 'event_type', 'VARCHAR(50) NULL AFTER `event`');
CALL ch_add_column_if_missing('communication_hub_delivery_events', 'business_id', 'BIGINT UNSIGNED NULL AFTER `id`');
CALL ch_add_column_if_missing('communication_hub_delivery_events', 'business_location_id', 'BIGINT UNSIGNED NULL AFTER `business_id`');

CALL ch_add_index_if_missing('communication_hub_messages', 'ch_messages_push_business_idx', '(`business_id`, `business_location_id`, `channel`, `status`)');
CALL ch_add_index_if_missing('communication_hub_messages', 'ch_messages_push_schedule_idx', '(`channel`, `status`, `scheduled_at`)');
CALL ch_add_index_if_missing('communication_hub_delivery_events', 'ch_delivery_events_push_idx', '(`business_id`, `event_type`)');

DROP PROCEDURE IF EXISTS ch_add_column_if_missing;
DROP PROCEDURE IF EXISTS ch_add_index_if_missing;

-- Optional default templates for each tenant. Business-specific templates can be added from the UI.
INSERT INTO communication_hub_push_templates (business_id, business_location_id, template_name, category, title, body, action_url, variables, status, created_at, updated_at)
SELECT NULL, NULL, 'Invoice Posted Alert', 'finance', 'Invoice Posted', 'Invoice {invoice_no} has been posted for {customer_name}.', NULL, '{invoice_no},{customer_name}', 'active', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM communication_hub_push_templates WHERE template_name = 'Invoice Posted Alert');

INSERT INTO communication_hub_push_templates (business_id, business_location_id, template_name, category, title, body, action_url, variables, status, created_at, updated_at)
SELECT NULL, NULL, 'Approval Required Alert', 'approval', 'Approval Required', '{module_name} requires your approval.', NULL, '{module_name}', 'active', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM communication_hub_push_templates WHERE template_name = 'Approval Required Alert');
-- CommunicationHub rollback SQL.
-- WARNING: This removes CommunicationHub tenant data. Use only after a full backup.

DROP TABLE IF EXISTS communication_hub_api_request_logs;
DROP TABLE IF EXISTS communication_hub_api_clients;
DROP TABLE IF EXISTS communication_hub_marketplace_packages;
DROP TABLE IF EXISTS communication_hub_campaigns;
DROP TABLE IF EXISTS communication_hub_audit_logs;
DROP TABLE IF EXISTS communication_hub_otps;
DROP TABLE IF EXISTS communication_hub_delivery_events;
DROP TABLE IF EXISTS communication_hub_messages;
DROP TABLE IF EXISTS communication_hub_templates;
DROP TABLE IF EXISTS communication_hub_providers;
DROP TABLE IF EXISTS communication_hub_settings;

-- ========================================================
-- Stage 006 - In-App Notification Centre
-- ========================================================
SOURCE 09_IN_APP_NOTIFICATION_CENTRE_UPGRADE.sql;


-- ============================================================
-- MASTER_ALL_COMMUNICATION_HUB_SQL_STAGE_009.sql
-- ============================================================
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
