-- DISNEW 022 - Operational Polish
-- Apply this to each tenant database where Distribution New is enabled.

CREATE TABLE IF NOT EXISTS `disnew_profitability_runs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `from_date` DATE NOT NULL,
  `to_date` DATE NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'draft',
  `gross_sales` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `returns_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `delivery_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `vehicle_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `commission_cost` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `net_profit` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`), KEY `idx_disnew_profitability_runs_business` (`business_id`), KEY `idx_disnew_profitability_runs_dates` (`from_date`,`to_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `disnew_profitability_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `run_id` BIGINT UNSIGNED NOT NULL,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `scope_type` VARCHAR(40) NOT NULL,
  `scope_id` BIGINT UNSIGNED NULL,
  `scope_name` VARCHAR(255) NULL,
  `sales_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `cost_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `return_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `collection_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `profit_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `profit_percent` DECIMAL(12,4) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`), KEY `idx_disnew_profitability_lines_run` (`run_id`), KEY `idx_disnew_profitability_lines_scope` (`scope_type`,`scope_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `disnew_collection_controls` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `sales_rep_id` BIGINT UNSIGNED NULL,
  `collection_date` DATE NOT NULL,
  `expected_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `collected_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `short_excess_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `status` VARCHAR(30) NOT NULL DEFAULT 'open',
  `remarks` TEXT NULL,
  `approved_by` BIGINT UNSIGNED NULL,
  `approved_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`), KEY `idx_disnew_collection_controls_business_date` (`business_id`,`collection_date`), KEY `idx_disnew_collection_controls_rep` (`sales_rep_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `disnew_reconciliation_exceptions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `exception_type` VARCHAR(60) NOT NULL,
  `reference_type` VARCHAR(80) NULL,
  `reference_id` BIGINT UNSIGNED NULL,
  `expected_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `actual_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `variance_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `variance_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `severity` VARCHAR(20) NOT NULL DEFAULT 'normal',
  `status` VARCHAR(30) NOT NULL DEFAULT 'open',
  `resolution_note` TEXT NULL,
  `resolved_by` BIGINT UNSIGNED NULL,
  `resolved_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`), KEY `idx_disnew_recon_exception_business` (`business_id`,`status`), KEY `idx_disnew_recon_exception_ref` (`reference_type`,`reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `disnew_deployment_verifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `check_group` VARCHAR(80) NOT NULL,
  `check_key` VARCHAR(120) NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `message` TEXT NULL,
  `payload` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`), KEY `idx_disnew_deployment_checks` (`check_group`,`check_key`), KEY `idx_disnew_deployment_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
('distribution_new.operational_polish.view','web',NOW(),NOW()),
('distribution_new.profitability.view','web',NOW(),NOW()),
('distribution_new.collection_controls.view','web',NOW(),NOW()),
('distribution_new.reconciliation_exceptions.view','web',NOW(),NOW()),
('distribution_new.deployment_verification.view','web',NOW(),NOW());
