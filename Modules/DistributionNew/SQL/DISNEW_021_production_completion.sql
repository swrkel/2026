-- DISNEW_021 Production Completion & Operational Enhancements
-- Run inside each tenant database. No database name is specified.

CREATE TABLE IF NOT EXISTS `disnew_workflow_validation_runs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `run_no` VARCHAR(191) NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
  `checked_by` INT UNSIGNED NULL,
  `checked_at` TIMESTAMP NULL,
  `summary` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `disnew_workflow_validation_runs_run_no_unique` (`run_no`),
  KEY `disnew_wvr_business_id_index` (`business_id`), KEY `disnew_wvr_location_id_index` (`location_id`), KEY `disnew_wvr_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `disnew_workflow_validation_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `validation_run_id` BIGINT UNSIGNED NOT NULL,
  `area` VARCHAR(100) NOT NULL,
  `check_code` VARCHAR(150) NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
  `message` TEXT NULL,
  `payload` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`), KEY `disnew_wvi_run_id_index` (`validation_run_id`), KEY `disnew_wvi_area_index` (`area`), KEY `disnew_wvi_code_index` (`check_code`), KEY `disnew_wvi_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `disnew_stock_reconciliation_runs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `reconciliation_date` DATE NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'draft',
  `warehouse_variance_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `vehicle_variance_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`), KEY `disnew_srr_business_id_index` (`business_id`), KEY `disnew_srr_location_id_index` (`location_id`), KEY `disnew_srr_date_index` (`reconciliation_date`), KEY `disnew_srr_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `disnew_stock_reconciliation_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reconciliation_run_id` BIGINT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `warehouse_id` BIGINT UNSIGNED NULL,
  `vehicle_id` BIGINT UNSIGNED NULL,
  `system_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `physical_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `variance_qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `remarks` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`), KEY `disnew_srl_run_id_index` (`reconciliation_run_id`), KEY `disnew_srl_product_id_index` (`product_id`), KEY `disnew_srl_warehouse_id_index` (`warehouse_id`), KEY `disnew_srl_vehicle_id_index` (`vehicle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `disnew_visit_plans` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `sales_rep_id` INT UNSIGNED NOT NULL,
  `route_id` BIGINT UNSIGNED NULL,
  `visit_date` DATE NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'planned',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`), KEY `disnew_vp_business_id_index` (`business_id`), KEY `disnew_vp_location_id_index` (`location_id`), KEY `disnew_vp_sales_rep_id_index` (`sales_rep_id`), KEY `disnew_vp_route_id_index` (`route_id`), KEY `disnew_vp_visit_date_index` (`visit_date`), KEY `disnew_vp_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `disnew_visit_plan_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `visit_plan_id` BIGINT UNSIGNED NOT NULL,
  `customer_id` INT UNSIGNED NOT NULL,
  `sequence_no` INT UNSIGNED NOT NULL DEFAULT 0,
  `visit_status` VARCHAR(50) NOT NULL DEFAULT 'pending',
  `planned_time` TIME NULL,
  `visited_time` TIME NULL,
  `gps_lat` DECIMAL(12,8) NULL,
  `gps_lng` DECIMAL(12,8) NULL,
  `remarks` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`), KEY `disnew_vpl_plan_id_index` (`visit_plan_id`), KEY `disnew_vpl_customer_id_index` (`customer_id`), KEY `disnew_vpl_status_index` (`visit_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `disnew_management_dashboard_widgets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `dashboard_role` VARCHAR(80) NOT NULL,
  `widget_code` VARCHAR(120) NOT NULL,
  `title` VARCHAR(191) NOT NULL,
  `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `settings` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `disnew_dash_widget_unique` (`business_id`,`dashboard_role`,`widget_code`), KEY `disnew_mdw_role_index` (`dashboard_role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `disnew_performance_cache` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `cache_key` VARCHAR(191) NOT NULL,
  `cache_payload` LONGTEXT NULL,
  `expires_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `disnew_perf_cache_unique` (`business_id`,`cache_key`), KEY `disnew_pc_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional permission keys for permission seeder/import screen
INSERT IGNORE INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
('distribution_new.workflow_validation.view','web',NOW(),NOW()),
('distribution_new.stock_reconciliation.view','web',NOW(),NOW()),
('distribution_new.stock_reconciliation.create','web',NOW(),NOW()),
('distribution_new.visit_plan.view','web',NOW(),NOW()),
('distribution_new.visit_plan.create','web',NOW(),NOW()),
('distribution_new.management_dashboard.view','web',NOW(),NOW()),
('distribution_new.performance_cache.manage','web',NOW(),NOW());
