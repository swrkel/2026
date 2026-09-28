-- Auto Service Stage 042 - Deployment Diagnostics & Server Rollout Hardening
-- Run this on every tenant database. No database name is specified for multi-tenant rollout safety.

CREATE TABLE IF NOT EXISTS `auto_service_deployment_diagnostic_runs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `run_by` BIGINT UNSIGNED NULL,
  `tenant_connection` VARCHAR(100) NULL,
  `tenant_database` VARCHAR(190) NULL,
  `missing_routes` INT UNSIGNED NOT NULL DEFAULT 0,
  `missing_tables` INT UNSIGNED NOT NULL DEFAULT 0,
  `open_issues` INT UNSIGNED NOT NULL DEFAULT 0,
  `payload_json` LONGTEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `as_diag_business_location_idx` (`business_id`, `location_id`),
  KEY `as_diag_created_idx` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'autoservice.deployment_diagnostics.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'autoservice.deployment_diagnostics.view');

CREATE TABLE IF NOT EXISTS `auto_service_rollout_checklist` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `check_key` VARCHAR(190) NOT NULL,
  `check_title` VARCHAR(255) NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
  `notes` TEXT NULL,
  `checked_by` BIGINT UNSIGNED NULL,
  `checked_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `as_rollout_unique_check` (`business_id`, `location_id`, `check_key`),
  KEY `as_rollout_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `auto_service_rollout_checklist` (`business_id`, `location_id`, `check_key`, `check_title`, `status`, `created_at`, `updated_at`) VALUES
(NULL, NULL, 'module_sidebar_visible', 'Auto Service module visible in sidebar', 'pending', NOW(), NOW()),
(NULL, NULL, 'routes_loaded', 'All Stage 020-042 routes loaded', 'pending', NOW(), NOW()),
(NULL, NULL, 'tenant_tables_created', 'All tenant tables created', 'pending', NOW(), NOW()),
(NULL, NULL, 'permissions_assigned', 'Auto Service permissions assigned to roles', 'pending', NOW(), NOW()),
(NULL, NULL, 'business_scope_verified', 'Business-wise data isolation verified', 'pending', NOW(), NOW()),
(NULL, NULL, 'location_scope_verified', 'Business location filters verified', 'pending', NOW(), NOW()),
(NULL, NULL, 'customer_portal_verified', 'Customer portal status/bill/history verified', 'pending', NOW(), NOW()),
(NULL, NULL, 'billing_flow_verified', 'Job to invoice to payment to delivery verified', 'pending', NOW(), NOW()),
(NULL, NULL, 'reports_exports_verified', 'Reports and CSV exports verified', 'pending', NOW(), NOW());
