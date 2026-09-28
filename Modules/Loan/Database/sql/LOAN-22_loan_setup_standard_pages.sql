-- LOAN-22 - Loan Setup standard pages/tabs
-- Run in each tenant database where Loan Module is enabled.

CREATE TABLE IF NOT EXISTS `loan_settings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NULL,
  `created_by` int unsigned NULL,
  `updated_by` int unsigned NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loan_settings_business_key_unique` (`business_id`, `setting_key`),
  KEY `loan_settings_business_id_index` (`business_id`),
  KEY `loan_settings_setting_key_index` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `loan_officers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned NOT NULL,
  `user_id` int unsigned NULL,
  `name` varchar(191) NOT NULL,
  `email` varchar(191) NULL,
  `mobile` varchar(30) NULL,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `notes` text NULL,
  `created_by` int unsigned NULL,
  `updated_by` int unsigned NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `loan_officers_business_id_index` (`business_id`),
  KEY `loan_officers_user_id_index` (`user_id`),
  KEY `loan_officers_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `loan_purposes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` text NULL,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `created_by` int unsigned NULL,
  `updated_by` int unsigned NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `loan_purposes_business_id_index` (`business_id`),
  KEY `loan_purposes_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `loan_collateral_types` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` text NULL,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `created_by` int unsigned NULL,
  `updated_by` int unsigned NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `loan_collateral_types_business_id_index` (`business_id`),
  KEY `loan_collateral_types_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `loan_statuses` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` text NULL,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int unsigned NULL,
  `updated_by` int unsigned NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `loan_statuses_business_id_index` (`business_id`),
  KEY `loan_statuses_status_index` (`status`),
  KEY `loan_statuses_active_index` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `loan_charges` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int unsigned NOT NULL,
  `name` varchar(191) NOT NULL,
  `amount` decimal(22,4) NOT NULL DEFAULT 0.0000,
  `charge_type` varchar(100) NULL,
  `description` text NULL,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `created_by` int unsigned NULL,
  `updated_by` int unsigned NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `loan_charges_business_id_index` (`business_id`),
  KEY `loan_charges_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional ALTERs for sites where old tables already exist but are missing new fields.
ALTER TABLE loan_purposes ADD COLUMN IF NOT EXISTS business_id INT UNSIGNED NULL AFTER id;
ALTER TABLE loan_purposes ADD COLUMN IF NOT EXISTS description TEXT NULL;
ALTER TABLE loan_purposes ADD COLUMN IF NOT EXISTS status VARCHAR(30) NOT NULL DEFAULT 'active';
ALTER TABLE loan_purposes ADD COLUMN IF NOT EXISTS created_by INT UNSIGNED NULL;
ALTER TABLE loan_purposes ADD COLUMN IF NOT EXISTS updated_by INT UNSIGNED NULL;
ALTER TABLE loan_purposes ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NULL DEFAULT NULL;
ALTER TABLE loan_purposes ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT NULL;

ALTER TABLE loan_collateral_types ADD COLUMN IF NOT EXISTS business_id INT UNSIGNED NULL AFTER id;
ALTER TABLE loan_collateral_types ADD COLUMN IF NOT EXISTS description TEXT NULL;
ALTER TABLE loan_collateral_types ADD COLUMN IF NOT EXISTS status VARCHAR(30) NOT NULL DEFAULT 'active';
ALTER TABLE loan_collateral_types ADD COLUMN IF NOT EXISTS created_by INT UNSIGNED NULL;
ALTER TABLE loan_collateral_types ADD COLUMN IF NOT EXISTS updated_by INT UNSIGNED NULL;
ALTER TABLE loan_collateral_types ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NULL DEFAULT NULL;
ALTER TABLE loan_collateral_types ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT NULL;

ALTER TABLE loan_statuses ADD COLUMN IF NOT EXISTS business_id INT UNSIGNED NULL AFTER id;
ALTER TABLE loan_statuses ADD COLUMN IF NOT EXISTS description TEXT NULL;
ALTER TABLE loan_statuses ADD COLUMN IF NOT EXISTS status VARCHAR(30) NOT NULL DEFAULT 'active';
ALTER TABLE loan_statuses ADD COLUMN IF NOT EXISTS active TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE loan_statuses ADD COLUMN IF NOT EXISTS created_by INT UNSIGNED NULL;
ALTER TABLE loan_statuses ADD COLUMN IF NOT EXISTS updated_by INT UNSIGNED NULL;
ALTER TABLE loan_statuses ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NULL DEFAULT NULL;
ALTER TABLE loan_statuses ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT NULL;

ALTER TABLE loan_charges ADD COLUMN IF NOT EXISTS business_id INT UNSIGNED NULL AFTER id;
ALTER TABLE loan_charges ADD COLUMN IF NOT EXISTS amount DECIMAL(22,4) NOT NULL DEFAULT 0.0000;
ALTER TABLE loan_charges ADD COLUMN IF NOT EXISTS charge_type VARCHAR(100) NULL;
ALTER TABLE loan_charges ADD COLUMN IF NOT EXISTS description TEXT NULL;
ALTER TABLE loan_charges ADD COLUMN IF NOT EXISTS status VARCHAR(30) NOT NULL DEFAULT 'active';
ALTER TABLE loan_charges ADD COLUMN IF NOT EXISTS created_by INT UNSIGNED NULL;
ALTER TABLE loan_charges ADD COLUMN IF NOT EXISTS updated_by INT UNSIGNED NULL;
ALTER TABLE loan_charges ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NULL DEFAULT NULL;
ALTER TABLE loan_charges ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT NULL;
