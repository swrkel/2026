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
