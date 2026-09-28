-- MASTER COMMUNICATION HUB SQL - STAGE 013 COMPLETE
-- Run inside each tenant database. No database name is hard-coded.


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
-- 04_SMS_OTP_BUSINESS_SAFE_UPGRADE.sql
-- ============================================================
-- CommunicationHub(6) - 04_SMS_OTP_BUSINESS_SAFE_UPGRADE.sql
-- Purpose: SMS-first commercial workflow hardening + OTP preparation.
-- Run in EACH tenant database. Safe for existing tenants.

SET @db := DATABASE();

-- Messages: add missing operational columns only when absent.
SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_messages' AND COLUMN_NAME='business_location_id')=0,
'ALTER TABLE communication_hub_messages ADD COLUMN business_location_id BIGINT UNSIGNED NULL AFTER business_id', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_messages' AND COLUMN_NAME='sender_id')=0,
'ALTER TABLE communication_hub_messages ADD COLUMN sender_id VARCHAR(100) NULL AFTER provider_id', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_messages' AND COLUMN_NAME='gateway_cost')=0,
'ALTER TABLE communication_hub_messages ADD COLUMN gateway_cost DECIMAL(18,4) NOT NULL DEFAULT 0.0000 AFTER client_id', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_messages' AND COLUMN_NAME='selling_price')=0,
'ALTER TABLE communication_hub_messages ADD COLUMN selling_price DECIMAL(18,4) NOT NULL DEFAULT 0.0000 AFTER gateway_cost', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_messages' AND COLUMN_NAME='profit_amount')=0,
'ALTER TABLE communication_hub_messages ADD COLUMN profit_amount DECIMAL(18,4) NOT NULL DEFAULT 0.0000 AFTER selling_price', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_messages' AND COLUMN_NAME='wallet_charge_status')=0,
'ALTER TABLE communication_hub_messages ADD COLUMN wallet_charge_status VARCHAR(30) NULL AFTER currency', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- OTP: keep all OTP records tenant/business-aware and compatible with older schemas.
SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_otps' AND COLUMN_NAME='business_id')=0,
'ALTER TABLE communication_hub_otps ADD COLUMN business_id BIGINT UNSIGNED NULL AFTER id', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_otps' AND COLUMN_NAME='module')=0,
'ALTER TABLE communication_hub_otps ADD COLUMN module VARCHAR(100) NULL AFTER business_id', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_otps' AND COLUMN_NAME='recipient')=0,
'ALTER TABLE communication_hub_otps ADD COLUMN recipient VARCHAR(255) NULL AFTER module', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_otps' AND COLUMN_NAME='identifier')=0,
'ALTER TABLE communication_hub_otps ADD COLUMN identifier VARCHAR(255) NULL AFTER recipient', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_otps' AND COLUMN_NAME='channel')=0,
'ALTER TABLE communication_hub_otps ADD COLUMN channel VARCHAR(50) NULL AFTER identifier', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_otps' AND COLUMN_NAME='verified_at')=0,
'ALTER TABLE communication_hub_otps ADD COLUMN verified_at TIMESTAMP NULL AFTER expires_at', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Helpful indexes, added only when absent.
SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_messages' AND INDEX_NAME='idx_ch_msg_business_status')=0,
'ALTER TABLE communication_hub_messages ADD INDEX idx_ch_msg_business_status (business_id, status)', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_messages' AND INDEX_NAME='idx_ch_msg_business_channel_status')=0,
'ALTER TABLE communication_hub_messages ADD INDEX idx_ch_msg_business_channel_status (business_id, channel, status)', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_otps' AND INDEX_NAME='idx_ch_otp_business_status')=0,
'ALTER TABLE communication_hub_otps ADD INDEX idx_ch_otp_business_status (business_id, status)', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_otps' AND INDEX_NAME='idx_ch_otp_business_identifier')=0,
'ALTER TABLE communication_hub_otps ADD INDEX idx_ch_otp_business_identifier (business_id, identifier)', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- ============================================================
-- 05_OTP_BUSINESS_PLATFORM_UPGRADE.sql
-- ============================================================
/*
Communication Hub - OTP Business Platform Upgrade
Run this in EACH TENANT database. No database name is hardcoded.
Safe for multi-tenant single-code / multiple-database deployments.
*/

SET @db := DATABASE();

SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_otps' AND COLUMN_NAME='business_id') = 0,
'ALTER TABLE communication_hub_otps ADD COLUMN business_id BIGINT UNSIGNED NULL AFTER id',
'SELECT "business_id already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_otps' AND COLUMN_NAME='business_location_id') = 0,
'ALTER TABLE communication_hub_otps ADD COLUMN business_location_id BIGINT UNSIGNED NULL AFTER business_id',
'SELECT "business_location_id already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_otps' AND COLUMN_NAME='created_by') = 0,
'ALTER TABLE communication_hub_otps ADD COLUMN created_by BIGINT UNSIGNED NULL AFTER business_location_id',
'SELECT "created_by already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_otps' AND COLUMN_NAME='identifier') = 0,
'ALTER TABLE communication_hub_otps ADD COLUMN identifier VARCHAR(191) NULL AFTER recipient',
'SELECT "identifier already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_otps' AND COLUMN_NAME='module') = 0,
'ALTER TABLE communication_hub_otps ADD COLUMN module VARCHAR(100) NULL AFTER channel',
'SELECT "module already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_otps' AND COLUMN_NAME='max_attempts') = 0,
'ALTER TABLE communication_hub_otps ADD COLUMN max_attempts INT UNSIGNED NOT NULL DEFAULT 3 AFTER attempts',
'SELECT "max_attempts already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_otps' AND COLUMN_NAME='message_id') = 0,
'ALTER TABLE communication_hub_otps ADD COLUMN message_id BIGINT UNSIGNED NULL AFTER otp_hash',
'SELECT "message_id already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_otps' AND COLUMN_NAME='sent_at') = 0,
'ALTER TABLE communication_hub_otps ADD COLUMN sent_at TIMESTAMP NULL AFTER verified_at',
'SELECT "sent_at already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_otps' AND COLUMN_NAME='plain_otp_preview') = 0,
'ALTER TABLE communication_hub_otps ADD COLUMN plain_otp_preview VARCHAR(20) NULL AFTER otp_hash',
'SELECT "plain_otp_preview already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_otps' AND INDEX_NAME='ch_otps_business_status_idx') = 0,
'ALTER TABLE communication_hub_otps ADD INDEX ch_otps_business_status_idx (business_id, business_location_id, status)',
'SELECT "ch_otps_business_status_idx already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='communication_hub_otps' AND INDEX_NAME='ch_otps_identifier_idx') = 0,
'ALTER TABLE communication_hub_otps ADD INDEX ch_otps_identifier_idx (business_id, identifier, purpose, status)',
'SELECT "ch_otps_identifier_idx already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE communication_hub_otps SET identifier = recipient WHERE identifier IS NULL AND recipient IS NOT NULL;
UPDATE communication_hub_otps SET max_attempts = 3 WHERE max_attempts IS NULL OR max_attempts = 0;


-- ============================================================
-- 06_EMAIL_BUSINESS_PLATFORM_UPGRADE.sql
-- ============================================================
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


-- ============================================================
-- 11_AUTOMATION_ENGINE_UPGRADE.sql
-- ============================================================
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
-- 14_DEPLOYMENT_VERIFICATION_AND_HEALTHCHECK.sql
-- ============================================================
-- Communication Hub Stage 013 - Deployment Verification & Health Check
-- Purpose: read-only checks to run inside each tenant database after uploading the module and running SQL 01-13.
-- This file does not change data.

SELECT 'communication_hub_tables' AS check_name, COUNT(*) AS table_count
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND (table_name LIKE 'communication\_hub\_%' OR table_name LIKE 'ch\_%');

SELECT 'core_tables_present' AS check_name, table_name
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND table_name IN (
    'communication_hub_messages',
    'communication_hub_queue',
    'communication_hub_templates',
    'communication_hub_providers',
    'communication_hub_otp_requests',
    'communication_hub_whatsapp_profiles',
    'communication_hub_push_devices',
    'communication_hub_in_app_notifications',
    'communication_hub_live_chat_conversations',
    'communication_hub_automation_rules',
    'communication_hub_workflow_rules',
    'communication_hub_api_tokens',
    'communication_hub_audit_logs'
  )
ORDER BY table_name;

SELECT 'business_scope_columns' AS check_name, table_name, column_name
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND table_name LIKE 'communication\_hub\_%'
  AND column_name IN ('business_id','location_id','business_location_id','created_by','tenant_id')
ORDER BY table_name, column_name;

SELECT 'missing_business_scope_warning' AS check_name, t.table_name
FROM information_schema.tables t
LEFT JOIN information_schema.columns c
  ON c.table_schema = t.table_schema
 AND c.table_name = t.table_name
 AND c.column_name = 'business_id'
WHERE t.table_schema = DATABASE()
  AND t.table_name LIKE 'communication\_hub\_%'
  AND c.column_name IS NULL
ORDER BY t.table_name;

SELECT 'communication_hub_indexes' AS check_name, table_name, index_name, GROUP_CONCAT(column_name ORDER BY seq_in_index) AS columns_in_index
FROM information_schema.statistics
WHERE table_schema = DATABASE()
  AND table_name LIKE 'communication\_hub\_%'
GROUP BY table_name, index_name
ORDER BY table_name, index_name;

