

-- ============================================================
-- DISNEW_012 UI STABILIZATION
-- ============================================================
-- DISNEW_012_UI_STABILIZATION.sql
-- Purpose: make Distribution New visible/testable from UI after Stage 1-11.
-- Run in every tenant database. This SQL is designed to be safe to re-run where possible.

CREATE TABLE IF NOT EXISTS `disnew_module_ui_status` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `module_key` VARCHAR(80) NOT NULL DEFAULT 'distribution_new',
  `is_visible` TINYINT(1) NOT NULL DEFAULT 1,
  `is_installed` TINYINT(1) NOT NULL DEFAULT 1,
  `last_checked_at` TIMESTAMP NULL DEFAULT NULL,
  `remarks` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `disnew_ui_business_module_unique` (`business_id`, `module_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `disnew_menu_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NULL,
  `parent_key` VARCHAR(80) NULL,
  `menu_key` VARCHAR(120) NOT NULL,
  `title` VARCHAR(160) NOT NULL,
  `route_name` VARCHAR(190) NOT NULL,
  `permission_name` VARCHAR(190) NULL,
  `icon` VARCHAR(80) NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `disnew_menu_key_unique` (`business_id`, `menu_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `disnew_module_ui_status` (`business_id`, `module_key`, `is_visible`, `is_installed`, `last_checked_at`, `remarks`, `created_at`, `updated_at`)
VALUES (NULL, 'distribution_new', 1, 1, NOW(), 'DISNEW_012 UI stabilization installed', NOW(), NOW())
ON DUPLICATE KEY UPDATE `is_visible` = VALUES(`is_visible`), `is_installed` = VALUES(`is_installed`), `last_checked_at` = NOW(), `updated_at` = NOW();

INSERT INTO `disnew_menu_items` (`business_id`, `parent_key`, `menu_key`, `title`, `route_name`, `permission_name`, `icon`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(NULL, NULL, 'distributionnew.dashboard', 'Dashboard', 'distributionnew.dashboard', 'distributionnew.dashboard', 'fa fa-dashboard', 10, 1, NOW(), NOW()),
(NULL, NULL, 'distributionnew.sales_orders', 'Sales Orders', 'distributionnew.sales-orders.index', 'distributionnew.sales_orders.view', 'fa fa-file-text-o', 20, 1, NOW(), NOW()),
(NULL, NULL, 'distributionnew.sales_invoices', 'Sales Invoices', 'distributionnew.sales-invoices.index', 'distributionnew.sales_invoices.view', 'fa fa-list-alt', 30, 1, NOW(), NOW()),
(NULL, NULL, 'distributionnew.loading_plans', 'Loading Plans', 'distributionnew.loading-plans.index', 'distributionnew.loading.view', 'fa fa-upload', 40, 1, NOW(), NOW()),
(NULL, NULL, 'distributionnew.loading', 'Loading', 'distributionnew.loading.index', 'distributionnew.loading.view', 'fa fa-truck', 50, 1, NOW(), NOW()),
(NULL, NULL, 'distributionnew.unloading', 'Unloading', 'distributionnew.unloading.index', 'distributionnew.unloading.view', 'fa fa-download', 60, 1, NOW(), NOW()),
(NULL, NULL, 'distributionnew.vehicles', 'Vehicles', 'distributionnew.vehicles.index', 'distributionnew.vehicles.view', 'fa fa-truck', 70, 1, NOW(), NOW()),
(NULL, NULL, 'distributionnew.vehicle_stock', 'Vehicle Stock', 'distributionnew.vehicle-stock.index', 'distributionnew.stock.view', 'fa fa-cubes', 80, 1, NOW(), NOW()),
(NULL, NULL, 'distributionnew.territories', 'Territories', 'distributionnew.territories.index', 'distributionnew.routes.view', 'fa fa-map', 90, 1, NOW(), NOW()),
(NULL, NULL, 'distributionnew.routes', 'Routes', 'distributionnew.routes.index', 'distributionnew.routes.view', 'fa fa-road', 100, 1, NOW(), NOW()),
(NULL, NULL, 'distributionnew.sales_reps', 'Sales Reps', 'distributionnew.sales-reps.index', 'distributionnew.sales_reps.view', 'fa fa-user', 110, 1, NOW(), NOW()),
(NULL, NULL, 'distributionnew.deliveries', 'Deliveries', 'distributionnew.deliveries.index', 'distributionnew.deliveries.view', 'fa fa-check-square-o', 120, 1, NOW(), NOW()),
(NULL, NULL, 'distributionnew.collections', 'Collections', 'distributionnew.collections.index', 'distributionnew.collections.view', 'fa fa-money', 130, 1, NOW(), NOW()),
(NULL, NULL, 'distributionnew.settlements', 'Settlements', 'distributionnew.settlements.index', 'distributionnew.settlements.view', 'fa fa-balance-scale', 140, 1, NOW(), NOW()),
(NULL, NULL, 'distributionnew.returns', 'Returns', 'distributionnew.returns.index', 'distributionnew.returns.view', 'fa fa-undo', 150, 1, NOW(), NOW()),
(NULL, NULL, 'distributionnew.credit_notes', 'Credit Notes', 'distributionnew.credit-notes.index', 'distributionnew.credit_notes.view', 'fa fa-credit-card', 160, 1, NOW(), NOW()),
(NULL, NULL, 'distributionnew.reports', 'Reports', 'distributionnew.reports.index', 'distributionnew.reports.view', 'fa fa-bar-chart', 170, 1, NOW(), NOW()),
(NULL, NULL, 'distributionnew.settings', 'Settings', 'distributionnew.settings.index', 'distributionnew.settings.view', 'fa fa-cog', 180, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE `title`=VALUES(`title`), `route_name`=VALUES(`route_name`), `permission_name`=VALUES(`permission_name`), `icon`=VALUES(`icon`), `sort_order`=VALUES(`sort_order`), `is_active`=1, `updated_at`=NOW();

-- Permissions are optional here because different ERP builds use different permission schemas.
-- If your application has Spatie permission table, this is safe:
INSERT IGNORE INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
('distributionnew.dashboard', 'web', NOW(), NOW()),
('distributionnew.sales_orders.view', 'web', NOW(), NOW()),
('distributionnew.sales_orders.create', 'web', NOW(), NOW()),
('distributionnew.sales_orders.update', 'web', NOW(), NOW()),
('distributionnew.sales_invoices.view', 'web', NOW(), NOW()),
('distributionnew.sales_invoices.create', 'web', NOW(), NOW()),
('distributionnew.vehicles.view', 'web', NOW(), NOW()),
('distributionnew.vehicles.create', 'web', NOW(), NOW()),
('distributionnew.vehicles.update', 'web', NOW(), NOW()),
('distributionnew.loading.view', 'web', NOW(), NOW()),
('distributionnew.loading.create', 'web', NOW(), NOW()),
('distributionnew.unloading.view', 'web', NOW(), NOW()),
('distributionnew.unloading.create', 'web', NOW(), NOW()),
('distributionnew.stock.view', 'web', NOW(), NOW()),
('distributionnew.routes.view', 'web', NOW(), NOW()),
('distributionnew.routes.create', 'web', NOW(), NOW()),
('distributionnew.sales_reps.view', 'web', NOW(), NOW()),
('distributionnew.sales_reps.create', 'web', NOW(), NOW()),
('distributionnew.deliveries.view', 'web', NOW(), NOW()),
('distributionnew.collections.view', 'web', NOW(), NOW()),
('distributionnew.collections.create', 'web', NOW(), NOW()),
('distributionnew.settlements.view', 'web', NOW(), NOW()),
('distributionnew.settlements.create', 'web', NOW(), NOW()),
('distributionnew.returns.view', 'web', NOW(), NOW()),
('distributionnew.credit_notes.view', 'web', NOW(), NOW()),
('distributionnew.reports.view', 'web', NOW(), NOW()),
('distributionnew.settings.view', 'web', NOW(), NOW());
