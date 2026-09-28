-- MASTER AutoService SQL up to Stage 041
-- This file is intended as the consolidated master for tenant rollout.
-- Run in each tenant database after backing up the tenant DB.

SOURCE STAGE020_TO_STAGE035_INCREMENTAL_SQL/21_AUTOSERVICE_STAGE020_COMPLETION_READINESS.sql;
SOURCE STAGE020_TO_STAGE035_INCREMENTAL_SQL/22_AUTOSERVICE_STAGE021_JOB_ESTIMATE_WORKFLOW_COMPLETION.sql;
SOURCE STAGE020_TO_STAGE035_INCREMENTAL_SQL/23_AUTOSERVICE_STAGE022_PARTS_LABOUR_CONTROL.sql;
SOURCE STAGE020_TO_STAGE035_INCREMENTAL_SQL/24_AUTOSERVICE_STAGE023_SERVICE_FLOW_QC_DELIVERY_CONTROL.sql;
SOURCE STAGE020_TO_STAGE035_INCREMENTAL_SQL/25_AUTOSERVICE_STAGE024_BILLING_PAYMENT_DELIVERY_HANDOVER.sql;
SOURCE STAGE020_TO_STAGE035_INCREMENTAL_SQL/26_AUTOSERVICE_STAGE025_CUSTOMER_CARE_WARRANTY_FEEDBACK.sql;
SOURCE STAGE020_TO_STAGE035_INCREMENTAL_SQL/27_AUTOSERVICE_STAGE026_CUSTOMER_SERVICE_PORTAL_HISTORY.sql;
SOURCE STAGE020_TO_STAGE035_INCREMENTAL_SQL/28_AUTOSERVICE_STAGE027_CUSTOMER_SELF_SERVICE_ACTIONS.sql;
SOURCE STAGE020_TO_STAGE035_INCREMENTAL_SQL/29_AUTOSERVICE_STAGE028_CUSTOMER_DOCUMENTS_APPROVALS_ALERTS.sql;
SOURCE STAGE020_TO_STAGE035_INCREMENTAL_SQL/30_AUTOSERVICE_STAGE029_CUSTOMER_PORTAL_ALERTS_COMMUNICATION_LOG.sql;
SOURCE STAGE020_TO_STAGE035_INCREMENTAL_SQL/31_AUTOSERVICE_STAGE030_CUSTOMER_BILL_PAYMENT_EXPORTS.sql;
SOURCE STAGE020_TO_STAGE035_INCREMENTAL_SQL/32_AUTOSERVICE_STAGE031_MAINTENANCE_PLANNER_VEHICLE_HEALTH.sql;
SOURCE STAGE020_TO_STAGE035_INCREMENTAL_SQL/33_AUTOSERVICE_STAGE032_WORKSHOP_MANAGEMENT_KPI.sql;
SOURCE STAGE020_TO_STAGE035_INCREMENTAL_SQL/34_AUTOSERVICE_STAGE033_INVENTORY_CONTROL_REORDER_PROFITABILITY.sql;
SOURCE STAGE020_TO_STAGE035_INCREMENTAL_SQL/35_AUTOSERVICE_STAGE034_WORKSHOP_COMMAND_CENTRE.sql;
SOURCE STAGE020_TO_STAGE035_INCREMENTAL_SQL/36_AUTOSERVICE_STAGE035_ADVANCED_VEHICLE_HISTORY.sql;
SOURCE 37_AUTOSERVICE_STAGE036_CUSTOMER_EXPERIENCE_PORTAL.sql;
SOURCE 38_AUTOSERVICE_STAGE037_WORKSHOP_PLANNING.sql;
SOURCE 39_AUTOSERVICE_STAGE038_BUSINESS_INTELLIGENCE_PROFITABILITY.sql;
SOURCE 40_AUTOSERVICE_STAGE039_DEALER_ENTERPRISE_FLEET_AMC.sql;
SOURCE 41_AUTOSERVICE_STAGE040_FINAL_ENTERPRISE_AUDIT.sql;
SOURCE 42_AUTOSERVICE_STAGE041_STABILIZATION_ISSUE_CAPTURE.sql;


-- =========================================================
-- Stage 042
-- =========================================================

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
-- Auto Service Stage 043 - Enterprise Integration
-- Run this in every tenant database that uses Auto Service.

CREATE TABLE IF NOT EXISTS auto_service_integration_bridge_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    location_id BIGINT UNSIGNED NULL,
    bridge_type VARCHAR(80) NOT NULL DEFAULT 'system_bridge',
    source_module VARCHAR(80) NOT NULL DEFAULT 'AutoService',
    target_module VARCHAR(80) NOT NULL,
    reference_type VARCHAR(80) NULL,
    reference_id BIGINT UNSIGNED NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'pending',
    message TEXT NULL,
    payload LONGTEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_as_bridge_business_location (business_id, location_id),
    INDEX idx_as_bridge_target_status (target_module, status),
    INDEX idx_as_bridge_reference (reference_type, reference_id),
    INDEX idx_as_bridge_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS auto_service_external_posting_map (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    location_id BIGINT UNSIGNED NULL,
    source_type VARCHAR(80) NOT NULL,
    source_id BIGINT UNSIGNED NOT NULL,
    target_module VARCHAR(80) NOT NULL,
    target_type VARCHAR(80) NULL,
    target_id BIGINT UNSIGNED NULL,
    posting_status VARCHAR(40) NOT NULL DEFAULT 'pending',
    posted_at TIMESTAMP NULL DEFAULT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uq_as_external_posting (source_type, source_id, target_module, target_type),
    INDEX idx_as_external_posting_scope (business_id, location_id),
    INDEX idx_as_external_posting_status (posting_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'autoservice.enterprise_integration.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'autoservice.enterprise_integration.view');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'autoservice.enterprise_integration.manage', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'autoservice.enterprise_integration.manage');
/*
Auto Service Stage 044 - UI Standardization & Performance Hardening
Run on every tenant database after Stage 043.
All statements are written to be safe for repeat execution where MySQL supports IF NOT EXISTS.
*/

CREATE TABLE IF NOT EXISTS auto_service_ui_audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id INT UNSIGNED NULL,
    location_id INT UNSIGNED NULL,
    audit_area VARCHAR(100) NOT NULL,
    audit_status VARCHAR(50) NOT NULL DEFAULT 'checked',
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    INDEX idx_as_ui_audit_business_location (business_id, location_id),
    INDEX idx_as_ui_audit_area_status (audit_area, audit_status),
    INDEX idx_as_ui_audit_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS auto_service_page_performance_checks (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id INT UNSIGNED NULL,
    location_id INT UNSIGNED NULL,
    page_key VARCHAR(150) NOT NULL,
    route_name VARCHAR(150) NULL,
    expected_permission VARCHAR(150) NULL,
    expected_table VARCHAR(150) NULL,
    check_status VARCHAR(50) NOT NULL DEFAULT 'pending',
    remarks TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_as_page_perf_page_route (page_key, route_name),
    INDEX idx_as_page_perf_business_location (business_id, location_id),
    INDEX idx_as_page_perf_status (check_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO auto_service_page_performance_checks
(page_key, route_name, expected_permission, expected_table, check_status, remarks, created_at, updated_at)
VALUES
('ui_standardization', 'autoservice.ui_standardization.index', 'autoservice.ui_standardization.view', 'auto_service_ui_audit_logs', 'ready', 'Stage 044 UI and performance audit page.', NOW(), NOW()),
('dashboard', 'autoservice.dashboard', 'autoservice.dashboard.view', 'auto_service_jobs', 'ready', 'Main Auto Service dashboard route validation.', NOW(), NOW()),
('command_centre', 'autoservice.command_centre.index', 'autoservice.command_centre.view', 'auto_service_jobs', 'ready', 'Workshop command centre route validation.', NOW(), NOW()),
('customer_portal', 'autoservice.customer_portal.lookup', 'autoservice.customer_portal.view', 'auto_service_jobs', 'ready', 'Customer portal route validation.', NOW(), NOW()),
('advanced_vehicle_history', 'autoservice.advanced_vehicle_history.index', 'autoservice.advanced_vehicle_history.view', 'auto_service_job_parts', 'ready', 'Advanced vehicle history route validation.', NOW(), NOW()),
('enterprise_integration', 'autoservice.enterprise_integration.index', 'autoservice.enterprise_integration.view', 'auto_service_integration_bridge_logs', 'ready', 'ERP integration route validation.', NOW(), NOW())
ON DUPLICATE KEY UPDATE
expected_permission = VALUES(expected_permission),
expected_table = VALUES(expected_table),
check_status = VALUES(check_status),
remarks = VALUES(remarks),
updated_at = NOW();

/* Permission inserts guarded for tenants where a standard permissions table exists. */
DROP PROCEDURE IF EXISTS autoservice_stage044_add_permission;
DELIMITER $$
CREATE PROCEDURE autoservice_stage044_add_permission(IN p_name VARCHAR(150))
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions') THEN
        SET @sql = CONCAT("INSERT INTO permissions (name, guard_name, created_at, updated_at) SELECT '", p_name, "', 'web', NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = '", p_name, "')");
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;
CALL autoservice_stage044_add_permission('autoservice.ui_standardization.view');
CALL autoservice_stage044_add_permission('autoservice.ui_standardization.manage');
DROP PROCEDURE IF EXISTS autoservice_stage044_add_permission;

/* Recommended performance indexes, guarded to avoid duplicate-index failures. */
DROP PROCEDURE IF EXISTS autoservice_stage044_add_index;
DELIMITER $$
CREATE PROCEDURE autoservice_stage044_add_index(IN p_table VARCHAR(150), IN p_index VARCHAR(150), IN p_columns TEXT)
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = p_table)
       AND NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = p_table AND index_name = p_index) THEN
        SET @sql = CONCAT('ALTER TABLE ', p_table, ' ADD INDEX ', p_index, ' (', p_columns, ')');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;
CALL autoservice_stage044_add_index('auto_service_jobs', 'idx_as_jobs_business_location_status', 'business_id, location_id, status');
CALL autoservice_stage044_add_index('auto_service_job_parts', 'idx_as_parts_business_location_job_product', 'business_id, location_id, job_id, product_id');
CALL autoservice_stage044_add_index('auto_service_invoices', 'idx_as_invoices_business_location_status', 'business_id, location_id, status');
CALL autoservice_stage044_add_index('auto_service_appointments', 'idx_as_appt_business_location_date_status', 'business_id, location_id, appointment_date, status');
DROP PROCEDURE IF EXISTS autoservice_stage044_add_index;

INSERT INTO auto_service_ui_audit_logs
(business_id, location_id, audit_area, audit_status, notes, created_at, updated_at)
VALUES
(NULL, NULL, 'stage_044_sql', 'installed', 'Stage 044 UI standardization and performance SQL installed.', NOW(), NOW());
