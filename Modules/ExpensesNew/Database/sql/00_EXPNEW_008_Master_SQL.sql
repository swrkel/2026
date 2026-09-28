-- EXPNEW_008 Tenant Create Tables
-- Run on each tenant database only.

CREATE TABLE IF NOT EXISTS `expnew_cost_transactions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NULL,
  `code` VARCHAR(100) NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expnew_cost_transactions_business_idx` (`business_id`),
  KEY `expnew_cost_transactions_location_idx` (`business_location_id`),
  KEY `expnew_cost_transactions_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_cost_pools` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NULL,
  `code` VARCHAR(100) NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expnew_cost_pools_business_idx` (`business_id`),
  KEY `expnew_cost_pools_location_idx` (`business_location_id`),
  KEY `expnew_cost_pools_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_cost_drivers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NULL,
  `code` VARCHAR(100) NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expnew_cost_drivers_business_idx` (`business_id`),
  KEY `expnew_cost_drivers_location_idx` (`business_location_id`),
  KEY `expnew_cost_drivers_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_cost_allocation_rules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NULL,
  `code` VARCHAR(100) NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expnew_cost_allocation_rules_business_idx` (`business_id`),
  KEY `expnew_cost_allocation_rules_location_idx` (`business_location_id`),
  KEY `expnew_cost_allocation_rules_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_cost_allocation_runs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NULL,
  `code` VARCHAR(100) NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expnew_cost_allocation_runs_business_idx` (`business_id`),
  KEY `expnew_cost_allocation_runs_location_idx` (`business_location_id`),
  KEY `expnew_cost_allocation_runs_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_cost_allocation_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NULL,
  `code` VARCHAR(100) NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expnew_cost_allocation_lines_business_idx` (`business_id`),
  KEY `expnew_cost_allocation_lines_location_idx` (`business_location_id`),
  KEY `expnew_cost_allocation_lines_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_shared_expenses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NULL,
  `code` VARCHAR(100) NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expnew_shared_expenses_business_idx` (`business_id`),
  KEY `expnew_shared_expenses_location_idx` (`business_location_id`),
  KEY `expnew_shared_expenses_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_overhead_recovery_rules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NULL,
  `code` VARCHAR(100) NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expnew_overhead_recovery_rules_business_idx` (`business_id`),
  KEY `expnew_overhead_recovery_rules_location_idx` (`business_location_id`),
  KEY `expnew_overhead_recovery_rules_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_activitys` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NULL,
  `code` VARCHAR(100) NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expnew_activitys_business_idx` (`business_id`),
  KEY `expnew_activitys_location_idx` (`business_location_id`),
  KEY `expnew_activitys_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_activity_costs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NULL,
  `code` VARCHAR(100) NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expnew_activity_costs_business_idx` (`business_id`),
  KEY `expnew_activity_costs_location_idx` (`business_location_id`),
  KEY `expnew_activity_costs_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_profitability_snapshots` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NULL,
  `code` VARCHAR(100) NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expnew_profitability_snapshots_business_idx` (`business_id`),
  KEY `expnew_profitability_snapshots_location_idx` (`business_location_id`),
  KEY `expnew_profitability_snapshots_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_kpi_metrics` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NULL,
  `code` VARCHAR(100) NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expnew_kpi_metrics_business_idx` (`business_id`),
  KEY `expnew_kpi_metrics_location_idx` (`business_location_id`),
  KEY `expnew_kpi_metrics_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expnew_kpi_values` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(191) NULL,
  `code` VARCHAR(100) NULL,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expnew_kpi_values_business_idx` (`business_id`),
  KEY `expnew_kpi_values_location_idx` (`business_location_id`),
  KEY `expnew_kpi_values_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- EXPNEW_008 Default Data - idempotent style
INSERT INTO `expnew_kpi_metrics` (`business_id`,`name`,`code`,`status`,`created_at`,`updated_at`)
SELECT NULL,'Expense Ratio','EXPENSE_RATIO','active',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `expnew_kpi_metrics` WHERE `code`='EXPENSE_RATIO' AND `business_id` IS NULL);

INSERT INTO `expnew_kpi_metrics` (`business_id`,`name`,`code`,`status`,`created_at`,`updated_at`)
SELECT NULL,'Cost Per Employee','COST_PER_EMPLOYEE','active',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `expnew_kpi_metrics` WHERE `code`='COST_PER_EMPLOYEE' AND `business_id` IS NULL);
